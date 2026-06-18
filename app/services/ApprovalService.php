<?php
declare(strict_types=1);

class ApprovalService
{
    private ApprovalRepository $approvalRepo;
    private BookingRepository $bookingRepo;
    private ResourceRepository $resourceRepo;
    private NotificationService $notificationService;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->approvalRepo = new ApprovalRepository();
        $this->bookingRepo = new BookingRepository();
        $this->resourceRepo = new ResourceRepository();
        $this->notificationService = new NotificationService();
        $this->auditLog = new AuditLogService();
    }

    public function approve(int $bookingId, int $approverId, ?string $comment = null): array
    {
        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        if ($booking['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Only pending bookings can be approved.'];
        }

        $conflicts = $this->bookingRepo->findConflicts(
            (int) $booking['resource_id'],
            $booking['start_datetime'],
            $booking['end_datetime'],
            $bookingId
        );
        if (!empty($conflicts)) {
            return ['success' => false, 'message' => 'Cannot approve: time slot conflicts with another booking.'];
        }

        $oldStatus = $booking['status'];
        $this->bookingRepo->update($bookingId, ['status' => 'approved']);

        $this->approvalRepo->create([
            'booking_id' => $bookingId,
            'approver_id' => $approverId,
            'decision' => 'approved',
            'comment' => $comment,
        ]);

        $resource = $this->resourceRepo->findById((int) $booking['resource_id']);
        $updatedBooking = $this->bookingRepo->findById($bookingId);

        $this->auditLog->log('approve_booking', 'bookings', $bookingId, [
            'status' => $oldStatus,
        ], [
            'status' => 'approved',
            'approver_id' => $approverId,
        ], $approverId);

        if ($resource) {
            $this->notificationService->notifyBookingApproved(
                (int) $booking['user_id'],
                $updatedBooking,
                $resource
            );
        }

        return ['success' => true, 'message' => 'Booking approved successfully.', 'booking' => $updatedBooking];
    }

    public function reject(int $bookingId, int $approverId, ?string $comment = null): array
    {
        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        if ($booking['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Only pending bookings can be rejected.'];
        }

        $oldStatus = $booking['status'];
        $this->bookingRepo->update($bookingId, ['status' => 'rejected']);

        $this->approvalRepo->create([
            'booking_id' => $bookingId,
            'approver_id' => $approverId,
            'decision' => 'rejected',
            'comment' => $comment,
        ]);

        $resource = $this->resourceRepo->findById((int) $booking['resource_id']);
        $updatedBooking = $this->bookingRepo->findById($bookingId);

        $this->auditLog->log('reject_booking', 'bookings', $bookingId, [
            'status' => $oldStatus,
        ], [
            'status' => 'rejected',
            'approver_id' => $approverId,
            'comment' => $comment,
        ], $approverId);

        if ($resource) {
            $this->notificationService->notifyBookingRejected(
                (int) $booking['user_id'],
                $updatedBooking,
                $resource,
                $comment
            );
        }

        return ['success' => true, 'message' => 'Booking rejected.', 'booking' => $updatedBooking];
    }

    public function getPending(int $limit = 20, int $offset = 0): array
    {
        return $this->approvalRepo->findPending($limit, $offset);
    }

    public function getHistory(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        return $this->approvalRepo->findHistory($filters, $limit, $offset);
    }

    public function countPending(): int
    {
        return $this->approvalRepo->countPending();
    }
}
