<?php
declare(strict_types=1);

class CancellationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO cancellations (booking_id, cancelled_by, reason, cancelled_at)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['booking_id'],
            $data['cancelled_by'],
            $data['reason'],
            $data['cancelled_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT c.*, b.booking_reference, b.start_datetime, b.end_datetime, b.purpose,
                       u.full_name AS cancelled_by_name, bu.full_name AS booking_user_name,
                       r.resource_name, r.resource_code
                FROM cancellations c
                JOIN bookings b ON b.id = c.booking_id
                JOIN users u ON u.id = c.cancelled_by
                JOIN users bu ON bu.id = b.user_id
                JOIN resources r ON r.id = b.resource_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['cancelled_by'])) {
            $sql .= ' AND c.cancelled_by = ?';
            $params[] = $filters['cancelled_by'];
        }
        if (!empty($filters['user_id'])) {
            $sql .= ' AND b.user_id = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['resource_id'])) {
            $sql .= ' AND b.resource_id = ?';
            $params[] = $filters['resource_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND c.cancelled_at >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND c.cancelled_at <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (b.booking_reference LIKE ? OR c.reason LIKE ? OR u.full_name LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= ' ORDER BY c.cancelled_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) FROM cancellations c
                JOIN bookings b ON b.id = c.booking_id
                JOIN users u ON u.id = c.cancelled_by
                WHERE 1=1';
        $params = [];

        if (!empty($filters['cancelled_by'])) {
            $sql .= ' AND c.cancelled_by = ?';
            $params[] = $filters['cancelled_by'];
        }
        if (!empty($filters['user_id'])) {
            $sql .= ' AND b.user_id = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['resource_id'])) {
            $sql .= ' AND b.resource_id = ?';
            $params[] = $filters['resource_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND c.cancelled_at >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND c.cancelled_at <= ?';
            $params[] = $filters['date_to'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findByBooking(int $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.full_name AS cancelled_by_name
             FROM cancellations c
             JOIN users u ON u.id = c.cancelled_by
             WHERE c.booking_id = ?'
        );
        $stmt->execute([$bookingId]);
        return $stmt->fetch() ?: null;
    }
}
