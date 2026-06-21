<?php
declare(strict_types=1);

class ApiController
{
    private int $userId;
    private string $role;
    private BookingService $bookingService;
    private ResourceRepository $resourceRepo;
    private NotificationService $notificationService;
    private WaitlistService $waitlistService;

    public function __construct(int $userId, string $role)
    {
        $this->userId              = $userId;
        $this->role                = $role;
        $this->bookingService      = new BookingService();
        $this->resourceRepo        = new ResourceRepository();
        $this->notificationService = new NotificationService();
        $this->waitlistService     = new WaitlistService();
    }

    // ─────────────────────────────────────────────────────────────────
    // RESOURCES
    // ─────────────────────────────────────────────────────────────────

    public function getResources(): array
    {
        $filters = [];
        if (!empty($_GET['category_id'])) {
            $filters['category_id'] = (int) $_GET['category_id'];
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        $resources = $this->resourceRepo->findAll($filters, 50, 0);
        return ['success' => true, 'data' => $resources, 'count' => count($resources)];
    }

    public function checkAvailability(): array
    {
        $resourceId = (int) ($_GET['resource_id'] ?? 0);
        $start      = trim($_GET['start_datetime'] ?? '');
        $end        = trim($_GET['end_datetime'] ?? '');
        $excludeId  = !empty($_GET['exclude_booking_id']) ? (int) $_GET['exclude_booking_id'] : null;

        if (!$resourceId || !$start || !$end) {
            http_response_code(422);
            return ['success' => false, 'error' => 'Missing required parameters: resource_id, start_datetime, end_datetime'];
        }

        $result = $this->bookingService->checkAvailability($resourceId, $start, $end, $this->userId, $excludeId);

        // Also check for waitlist count
        $waitlistRepo = new WaitlistRepository();
        $waitlistCount = count($waitlistRepo->findPendingForSlot($resourceId, $start, $end));

        return array_merge($result, ['waitlist_count' => $waitlistCount]);
    }

    // ─────────────────────────────────────────────────────────────────
    // BOOKINGS
    // ─────────────────────────────────────────────────────────────────

    public function getBookings(): array
    {
        $filters = ['user_id' => $this->userId];
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        $bookings = $this->bookingService->getByUser($this->userId, $filters, 20, 0);
        return ['success' => true, 'data' => $bookings, 'count' => count($bookings)];
    }

    public function createBooking(): array
    {
        $body = $this->parseJsonBody();

        $result = $this->bookingService->createBooking([
            'user_id'          => $this->userId,
            'resource_id'      => (int) ($body['resource_id'] ?? 0),
            'start_datetime'   => $body['start_datetime'] ?? '',
            'end_datetime'     => $body['end_datetime'] ?? '',
            'purpose'          => $body['purpose'] ?? '',
            'additional_notes' => $body['additional_notes'] ?? null,
            'equipment'        => $body['equipment'] ?? [],
        ]);

        if (!$result['success']) {
            http_response_code(422);
        }
        return $result;
    }

    public function cancelBooking(): array
    {
        $body       = $this->parseJsonBody();
        $bookingId  = (int) ($body['booking_id'] ?? 0);
        $reason     = $body['reason'] ?? 'Cancelled via API';
        $isAdmin    = in_array($this->role, ['Admin', 'Staff'], true);

        if (!$bookingId) {
            http_response_code(422);
            return ['success' => false, 'error' => 'booking_id is required'];
        }

        $svc    = new CancellationService();
        $result = $svc->cancel($bookingId, $this->userId, $reason, $isAdmin);

        if (!$result['success']) {
            http_response_code(422);
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // WAITLIST
    // ─────────────────────────────────────────────────────────────────

    public function getWaitlist(): array
    {
        $entries = $this->waitlistService->getUserWaitlist($this->userId);
        return ['success' => true, 'data' => $entries, 'count' => count($entries)];
    }

    public function joinWaitlist(): array
    {
        $body       = $this->parseJsonBody();
        $resourceId = (int) ($body['resource_id'] ?? 0);
        $start      = $body['start_datetime'] ?? '';
        $end        = $body['end_datetime'] ?? '';

        if (!$resourceId || !$start || !$end) {
            http_response_code(422);
            return ['success' => false, 'error' => 'Missing required fields'];
        }

        $result = $this->waitlistService->joinWaitlist($this->userId, $resourceId, $start, $end);
        if (!$result['success']) {
            http_response_code(422);
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────────────────────────────────────

    public function getDashboardStats(): array
    {
        $userIdParam = in_array($this->role, ['Admin', 'Staff'], true) ? null : $this->userId;
        $stats = $this->bookingService->getDashboardStats($userIdParam);

        $resourceRepo = new ResourceRepository();
        $resourceStats = [
            'total'     => $resourceRepo->count([]),
            'available' => $resourceRepo->count(['status' => 'available']),
        ];

        return [
            'success'        => true,
            'bookings'       => $stats,
            'resources'      => $resourceStats,
            'generated_at'   => date('Y-m-d H:i:s'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // NOTIFICATIONS
    // ─────────────────────────────────────────────────────────────────

    public function getNotifications(): array
    {
        $limit  = min((int) ($_GET['limit'] ?? 10), 50);
        $unread = !empty($_GET['unread']);
        $filters = $unread ? ['is_read' => 0] : [];
        $notifications = $this->notificationService->getForUser($this->userId, $filters, $limit, 0);
        return ['success' => true, 'data' => $notifications, 'count' => count($notifications)];
    }

    public function markNotificationRead(): array
    {
        $body = $this->parseJsonBody();
        $notifId = (int) ($body['notification_id'] ?? 0);
        if (!$notifId) {
            http_response_code(422);
            return ['success' => false, 'error' => 'notification_id required'];
        }
        $this->notificationService->markAsRead($notifId, $this->userId);
        return ['success' => true, 'message' => 'Marked as read.'];
    }

    public function getUnreadCount(): array
    {
        $count = $this->notificationService->getUnreadCount($this->userId);
        return ['success' => true, 'unread_count' => $count];
    }

    // ─────────────────────────────────────────────────────────────────
    // CALENDAR
    // ─────────────────────────────────────────────────────────────────

    public function getCalendarEvents(): array
    {
        $resourceId = !empty($_GET['resource_id']) ? (int) $_GET['resource_id'] : null;
        $start      = $_GET['start'] ?? date('Y-m-01');
        $end        = $_GET['end']   ?? date('Y-m-t');

        $bookingRepo = new BookingRepository();
        $filters = [
            'date_from' => $start . ' 00:00:00',
            'date_to'   => $end   . ' 23:59:59',
        ];
        if ($resourceId) {
            $filters['resource_id'] = $resourceId;
        }
        if (!in_array($this->role, ['Admin', 'Staff'], true)) {
            $filters['user_id'] = $this->userId;
        }

        $bookings = $bookingRepo->findAll($filters, 200, 0);
        $events   = [];

        foreach ($bookings as $b) {
            $color = match ($b['status']) {
                'approved'  => '#198754',
                'pending'   => '#ffc107',
                'cancelled' => '#6c757d',
                'completed' => '#0d6efd',
                'rejected'  => '#dc3545',
                default     => '#6c757d',
            };
            $events[] = [
                'id'    => $b['id'],
                'title' => $b['resource_name'] . ' – ' . $b['user_name'],
                'start' => $b['start_datetime'],
                'end'   => $b['end_datetime'],
                'color' => $color,
                'extendedProps' => [
                    'status'    => $b['status'],
                    'reference' => $b['booking_reference'],
                    'purpose'   => $b['purpose'],
                ],
            ];
        }

        return ['success' => true, 'events' => $events];
    }

    // ─────────────────────────────────────────────────────────────────
    // EQUIPMENT
    // ─────────────────────────────────────────────────────────────────

    public function getAvailableEquipment(): array
    {
        $resourceId = (int) ($_GET['resource_id'] ?? 0);
        $start      = trim($_GET['start_datetime'] ?? '');
        $end        = trim($_GET['end_datetime'] ?? '');

        if (!$resourceId) {
            http_response_code(422);
            return ['success' => false, 'error' => 'resource_id required'];
        }

        $equipmentRepo = new EquipmentRepository();
        $equipment = $equipmentRepo->findByResource($resourceId);

        // If time range given, check availability per equipment
        if ($start && $end) {
            $db = Database::getInstance()->getConnection();
            foreach ($equipment as &$eq) {
                $stmt = $db->prepare(
                    'SELECT COALESCE(SUM(be.quantity), 0) AS booked
                     FROM booking_equipment be
                     JOIN bookings b ON b.id = be.booking_id
                     WHERE be.equipment_id = ?
                     AND b.status IN ("pending","approved")
                     AND b.start_datetime < ? AND b.end_datetime > ?'
                );
                $stmt->execute([$eq['equipment_id'], $end, $start]);
                $booked = (int) $stmt->fetchColumn();
                $eq['booked_qty']    = $booked;
                $eq['available_qty'] = max(0, (int) $eq['quantity'] - $booked);
            }
            unset($eq);
        }

        return ['success' => true, 'data' => $equipment];
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────

    private function parseJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!$raw) {
            return $_POST;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : $_POST;
    }
}
