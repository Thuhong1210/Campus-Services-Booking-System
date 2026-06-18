<?php
declare(strict_types=1);

class NotificationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (user_id, booking_id, title, message, type, is_read)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['booking_id'] ?? null,
            $data['title'],
            $data['message'],
            $data['type'],
            (int) ($data['is_read'] ?? 0),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findByUser(int $userId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT n.*, b.booking_reference
                FROM notifications n
                LEFT JOIN bookings b ON b.id = n.booking_id
                WHERE n.user_id = ?';
        $params = [$userId];

        if (isset($filters['is_read'])) {
            $sql .= ' AND n.is_read = ?';
            $params[] = (int) $filters['is_read'];
        }
        if (!empty($filters['type'])) {
            $sql .= ' AND n.type = ?';
            $params[] = $filters['type'];
        }

        $sql .= ' ORDER BY n.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function markRead(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllRead(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM notifications WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
