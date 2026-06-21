<?php
declare(strict_types=1);

class CancellationService
{
    private CancellationRepository $cancellationRepo;
    private BookingRepository $bookingRepo;
    private ResourceRepository $resourceRepo;
    private PolicyService $policyService;
    private NotificationService $notificationService;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->cancellationRepo = new CancellationRepository();
        $this->bookingRepo = new BookingRepository();
        $this->resourceRepo = new ResourceRepository();
        $this->policyService = new PolicyService();
        $this->notificationService = new NotificationService();
        $this->auditLog = new AuditLogService();
    }

    public function cancel(int $bookingId, int $cancelledBy, string $reason, bool $isAdmin = false): array
    {
        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        if (!in_array($booking['status'], ['pending', 'approved'], true)) {
            return ['success' => false, 'message' => 'This booking cannot be cancelled.'];
        }

        if (!$isAdmin && (int) $booking['user_id'] !== $cancelledBy) {
            return ['success' => false, 'message' => 'You can only cancel your own bookings.'];
        }

        if (!$isAdmin) {
            $resource = $this->resourceRepo->findById((int) $booking['resource_id']);
            if ($resource) {
                $deadlineHours = $this->policyService->getCancellationDeadlineHours((int) $resource['category_id']);
                $hoursUntilStart = (strtotime($booking['start_datetime']) - time()) / 3600;
                if ($hoursUntilStart < $deadlineHours) {
                    return [
                        'success' => false,
                        'message' => sprintf(
                            'Cancellation must be made at least %d hours before the booking start time.',
                            $deadlineHours
                        ),
                    ];
                }
            }
        }

        $existing = $this->cancellationRepo->findByBooking($bookingId);
        if ($existing) {
            return ['success' => false, 'message' => 'Booking has already been cancelled.'];
        }

        $oldStatus = $booking['status'];
        $this->bookingRepo->update($bookingId, ['status' => 'cancelled']);

        $this->cancellationRepo->create([
            'booking_id' => $bookingId,
            'cancelled_by' => $cancelledBy,
            'reason' => $reason,
        ]);

        $resource = $this->resourceRepo->findById((int) $booking['resource_id']);
        $updatedBooking = $this->bookingRepo->findById($bookingId);

        $this->auditLog->log('cancel_booking', 'bookings', $bookingId, [
            'status' => $oldStatus,
        ], [
            'status' => 'cancelled',
            'reason' => $reason,
        ], $cancelledBy);

        if ($resource) {
            $this->notificationService->notifyBookingCancelled(
                (int) $booking['user_id'],
                $updatedBooking,
                $resource
            );
        }

        // Process waitlist: notify the first waiting user for this freed slot
        try {
            $waitlistService = new WaitlistService();
            $waitlistService->processWaitlistAfterCancellation($updatedBooking);
        } catch (Throwable $e) {
            // Non-fatal: waitlist processing failure should not block cancellation
        }

        return ['success' => true, 'message' => 'Booking cancelled successfully.', 'booking' => $updatedBooking];
    }

    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        return $this->cancellationRepo->findAll($filters, $limit, $offset);
    }

    public function getByBooking(int $bookingId): ?array
    {
        return $this->cancellationRepo->findByBooking($bookingId);
    }
}
