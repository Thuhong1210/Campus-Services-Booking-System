<?php
declare(strict_types=1);

class ApprovalRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO approvals (booking_id, approver_id, decision, comment, decided_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['booking_id'],
            $data['approver_id'],
            $data['decision'],
            $data['comment'] ?? null,
            $data['decided_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findPending(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.*, u.full_name AS user_name, u.email AS user_email,
                    r.resource_name, r.resource_code, rc.category_name
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             JOIN resources r ON r.id = b.resource_id
             JOIN resource_categories rc ON rc.id = r.category_id
             WHERE b.status = "pending" AND b.requires_approval = 1
             ORDER BY b.created_at ASC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function countPending(): int
    {
        $stmt = $this->db->query(
            'SELECT COUNT(*) FROM bookings WHERE status = "pending" AND requires_approval = 1'
        );
        return (int) $stmt->fetchColumn();
    }

    public function findHistory(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT a.*, b.booking_reference, b.purpose, b.start_datetime, b.end_datetime,
                       u.full_name AS approver_name, bu.full_name AS requester_name,
                       r.resource_name
                FROM approvals a
                JOIN bookings b ON b.id = a.booking_id
                JOIN users u ON u.id = a.approver_id
                JOIN users bu ON bu.id = b.user_id
                JOIN resources r ON r.id = b.resource_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['approver_id'])) {
            $sql .= ' AND a.approver_id = ?';
            $params[] = $filters['approver_id'];
        }
        if (!empty($filters['decision'])) {
            $sql .= ' AND a.decision = ?';
            $params[] = $filters['decision'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND a.decided_at >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND a.decided_at <= ?';
            $params[] = $filters['date_to'];
        }

        $sql .= ' ORDER BY a.decided_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByBooking(int $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.full_name AS approver_name
             FROM approvals a
             JOIN users u ON u.id = a.approver_id
             WHERE a.booking_id = ?
             ORDER BY a.decided_at DESC LIMIT 1'
        );
        $stmt->execute([$bookingId]);
        return $stmt->fetch() ?: null;
    }
}
