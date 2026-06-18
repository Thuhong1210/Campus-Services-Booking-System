<?php
declare(strict_types=1);

class NotificationService
{
    private NotificationRepository $notificationRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->notificationRepo = new NotificationRepository();
        $this->userRepo = new UserRepository();
    }

    public function notify(int $userId, string $title, string $message, string $type, ?int $bookingId = null): int
    {
        return $this->notificationRepo->create([
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ]);
    }

    public function notifyBookingCreated(int $userId, array $booking, array $resource): int
    {
        $status = $booking['status'] === 'pending' ? 'submitted and is pending approval' : 'confirmed';
        return $this->notify(
            $userId,
            'Booking Created',
            sprintf(
                'Your booking %s for %s has been %s.',
                $booking['booking_reference'],
                $resource['resource_name'],
                $status
            ),
            $booking['status'] === 'pending' ? 'pending_approval' : 'booking_created',
            (int) $booking['id']
        );
    }

    public function notifyBookingApproved(int $userId, array $booking, array $resource): int
    {
        return $this->notify(
            $userId,
            'Booking Approved',
            sprintf(
                'Your booking %s for %s has been approved.',
                $booking['booking_reference'],
                $resource['resource_name']
            ),
            'booking_approved',
            (int) $booking['id']
        );
    }

    public function notifyBookingRejected(int $userId, array $booking, array $resource, ?string $comment = null): int
    {
        $message = sprintf(
            'Your booking %s for %s has been rejected.',
            $booking['booking_reference'],
            $resource['resource_name']
        );
        if ($comment) {
            $message .= ' Reason: ' . $comment;
        }
        return $this->notify($userId, 'Booking Rejected', $message, 'booking_rejected', (int) $booking['id']);
    }

    public function notifyBookingCancelled(int $userId, array $booking, array $resource): int
    {
        return $this->notify(
            $userId,
            'Booking Cancelled',
            sprintf(
                'Your booking %s for %s has been cancelled.',
                $booking['booking_reference'],
                $resource['resource_name']
            ),
            'booking_cancelled',
            (int) $booking['id']
        );
    }

    public function notifyApproversPending(array $booking, array $resource): void
    {
        $approvers = $this->userRepo->all(['role' => 'Approver'], 100, 0);
        $lecturers = $this->userRepo->all(['role' => 'Lecturer'], 100, 0);
        $recipients = array_merge($approvers, $lecturers);

        $seen = [];
        foreach ($recipients as $recipient) {
            if (isset($seen[$recipient['id']])) {
                continue;
            }
            $seen[$recipient['id']] = true;
            $this->notify(
                (int) $recipient['id'],
                'Approval Required',
                sprintf(
                    'New booking %s for %s requires your approval.',
                    $booking['booking_reference'],
                    $resource['resource_name']
                ),
                'pending_approval',
                (int) $booking['id']
            );
        }
    }

    public function notifyMaintenance(int $userId, array $resource, string $reason): int
    {
        return $this->notify(
            $userId,
            'Resource Maintenance',
            sprintf('%s is scheduled for maintenance. %s', $resource['resource_name'], $reason),
            'resource_maintenance'
        );
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepo->countUnread($userId);
    }

    public function getForUser(int $userId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        return $this->notificationRepo->findByUser($userId, $filters, $limit, $offset);
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        return $this->notificationRepo->markRead($notificationId, $userId);
    }

    public function markAllAsRead(int $userId): void
    {
        $this->notificationRepo->markAllRead($userId);
    }
}
