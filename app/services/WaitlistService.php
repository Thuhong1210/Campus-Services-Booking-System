<?php
declare(strict_types=1);

class WaitlistService
{
    private WaitlistRepository $waitlistRepo;
    private BookingRepository $bookingRepo;
    private ResourceRepository $resourceRepo;
    private NotificationService $notificationService;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->waitlistRepo      = new WaitlistRepository();
        $this->bookingRepo       = new BookingRepository();
        $this->resourceRepo      = new ResourceRepository();
        $this->notificationService = new NotificationService();
        $this->auditLog          = new AuditLogService();
    }

    /**
     * Join the waitlist for a resource/time slot.
     */
    public function joinWaitlist(int $userId, int $resourceId, string $start, string $end): array
    {
        // Validate resource exists
        $resource = $this->resourceRepo->findById($resourceId);
        if (!$resource) {
            return ['success' => false, 'message' => 'Resource not found.'];
        }

        // Check if already on waitlist for this slot
        $existing = $this->waitlistRepo->findByUserAndSlot($userId, $resourceId, $start, $end);
        if ($existing) {
            return ['success' => false, 'message' => 'You are already on the waitlist for this time slot.'];
        }

        // Check datetime validity
        if (strtotime($start) >= strtotime($end)) {
            return ['success' => false, 'message' => 'Invalid time range.'];
        }
        if (strtotime($start) <= time()) {
            return ['success' => false, 'message' => 'Cannot join waitlist for a past time slot.'];
        }

        $id = $this->waitlistRepo->create([
            'user_id'       => $userId,
            'resource_id'   => $resourceId,
            'desired_start' => $start,
            'desired_end'   => $end,
            'status'        => 'waiting',
        ]);

        $this->auditLog->log('join_waitlist', 'waitlist', $id, null, [
            'resource_id' => $resourceId,
            'start'       => $start,
            'end'         => $end,
        ]);

        return [
            'success' => true,
            'message' => 'You have been added to the waitlist. We will notify you when the slot becomes available.',
            'waitlist_id' => $id,
        ];
    }

    /**
     * Called after a booking is cancelled – notify the first person on waitlist.
     */
    public function processWaitlistAfterCancellation(array $booking): void
    {
        $pending = $this->waitlistRepo->findPendingForSlot(
            (int) $booking['resource_id'],
            $booking['start_datetime'],
            $booking['end_datetime']
        );

        if (empty($pending)) {
            return;
        }

        $first = $pending[0];
        $confirmHours = (int) setting('waitlist_confirm_hours', 24);
        $expiresAt    = date('Y-m-d H:i:s', time() + $confirmHours * 3600);

        $this->waitlistRepo->updateStatus(
            (int) $first['id'],
            'notified',
            date('Y-m-d H:i:s'),
            $expiresAt
        );

        $resource = $this->resourceRepo->findById((int) $booking['resource_id']);
        $resourceName = $resource ? $resource['resource_name'] : 'the resource';
        $startFmt = date('d/m/Y H:i', strtotime($booking['start_datetime']));
        $endFmt   = date('H:i', strtotime($booking['end_datetime']));

        $this->notificationService->notify(
            (int) $first['user_id'],
            'Waitlist Slot Available!',
            sprintf(
                'Great news! The slot for %s on %s–%s is now available. You have %d hour(s) to confirm your booking.',
                $resourceName,
                $startFmt,
                $endFmt,
                $confirmHours
            ),
            'waitlist_available'
        );
    }

    /**
     * User confirms their waitlist booking slot.
     */
    public function confirmFromWaitlist(int $waitlistId, int $userId): array
    {
        $entry = $this->waitlistRepo->findById($waitlistId);
        if (!$entry) {
            return ['success' => false, 'message' => 'Waitlist entry not found.'];
        }

        if ((int) $entry['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        if ($entry['status'] !== 'notified') {
            return ['success' => false, 'message' => 'This waitlist slot is not ready for confirmation (status: ' . $entry['status'] . ').'];
        }

        if ($entry['expires_at'] && strtotime($entry['expires_at']) < time()) {
            $this->waitlistRepo->updateStatus($waitlistId, 'expired');
            return ['success' => false, 'message' => 'Your waitlist confirmation window has expired.'];
        }

        // Check slot still free
        $conflicts = $this->bookingRepo->findConflicts(
            (int) $entry['resource_id'],
            $entry['desired_start'],
            $entry['desired_end']
        );
        if (!empty($conflicts)) {
            $this->waitlistRepo->updateStatus($waitlistId, 'expired');
            return ['success' => false, 'message' => 'Unfortunately the slot was taken before you could confirm. Please try again.'];
        }

        // Create the booking via BookingService
        $bookingService = new BookingService();
        $result = $bookingService->createBooking([
            'user_id'          => $userId,
            'resource_id'      => (int) $entry['resource_id'],
            'start_datetime'   => $entry['desired_start'],
            'end_datetime'     => $entry['desired_end'],
            'purpose'          => 'Confirmed from waitlist',
            'additional_notes' => 'Auto-confirmed from waitlist entry #' . $waitlistId,
        ]);

        if ($result['success']) {
            $this->waitlistRepo->updateStatus($waitlistId, 'confirmed');
            $this->auditLog->log('confirm_waitlist', 'waitlist', $waitlistId, null, [
                'booking_id' => $result['booking']['id'] ?? null,
            ]);
        }

        return $result;
    }

    /**
     * Cancel a waitlist entry.
     */
    public function cancelWaitlist(int $waitlistId, int $userId, bool $isAdmin = false): array
    {
        $entry = $this->waitlistRepo->findById($waitlistId);
        if (!$entry) {
            return ['success' => false, 'message' => 'Waitlist entry not found.'];
        }

        if (!$isAdmin && (int) $entry['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        $this->waitlistRepo->updateStatus($waitlistId, 'cancelled');

        return ['success' => true, 'message' => 'Removed from waitlist successfully.'];
    }

    public function getUserWaitlist(int $userId): array
    {
        return $this->waitlistRepo->findByUser($userId);
    }

    public function expireOldEntries(): int
    {
        return $this->waitlistRepo->expireNotified();
    }

    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        return $this->waitlistRepo->findAll($filters, $limit, $offset);
    }
}
