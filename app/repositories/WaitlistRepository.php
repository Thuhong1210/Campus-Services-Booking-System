<?php
declare(strict_types=1);

class WaitlistRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO waitlist (user_id, resource_id, desired_start, desired_end, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['resource_id'],
            $data['desired_start'],
            $data['desired_end'],
            $data['status'] ?? 'waiting',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT w.*, u.full_name AS user_name, u.email AS user_email,
                    r.resource_name, r.resource_code, r.location
             FROM waitlist w
             JOIN users u ON u.id = w.user_id
             JOIN resources r ON r.id = w.resource_id
             WHERE w.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByUser(int $userId, string $status = ''): array
    {
        $sql = 'SELECT w.*, r.resource_name, r.resource_code, r.location
                FROM waitlist w
                JOIN resources r ON r.id = w.resource_id
                WHERE w.user_id = ?';
        $params = [$userId];
        if ($status !== '') {
            $sql .= ' AND w.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY w.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findPendingForSlot(int $resourceId, string $start, string $end): array
    {
        $stmt = $this->db->prepare(
            'SELECT w.*, u.full_name AS user_name, u.email AS user_email
             FROM waitlist w
             JOIN users u ON u.id = w.user_id
             WHERE w.resource_id = ?
             AND w.desired_start = ?
             AND w.desired_end = ?
             AND w.status = ?
             ORDER BY w.created_at ASC'
        );
        $stmt->execute([$resourceId, $start, $end, 'waiting']);
        return $stmt->fetchAll();
    }

    public function findByUserAndSlot(int $userId, int $resourceId, string $start, string $end): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM waitlist
             WHERE user_id = ? AND resource_id = ? AND desired_start = ? AND desired_end = ?
             AND status IN ("waiting","notified")
             LIMIT 1'
        );
        $stmt->execute([$userId, $resourceId, $start, $end]);
        return $stmt->fetch() ?: null;
    }

    public function updateStatus(int $id, string $status, ?string $notifiedAt = null, ?string $expiresAt = null): void
    {
        $sets = ['status = ?'];
        $params = [$status];

        if ($notifiedAt !== null) {
            $sets[] = 'notified_at = ?';
            $params[] = $notifiedAt;
        }
        if ($expiresAt !== null) {
            $sets[] = 'expires_at = ?';
            $params[] = $expiresAt;
        }
        $params[] = $id;

        $stmt = $this->db->prepare(
            'UPDATE waitlist SET ' . implode(', ', $sets) . ' WHERE id = ?'
        );
        $stmt->execute($params);
    }

    public function expireNotified(): int
    {
        $stmt = $this->db->prepare(
            'UPDATE waitlist SET status = "expired"
             WHERE status = "notified" AND expires_at IS NOT NULL AND expires_at < NOW()'
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function countAll(int $userId = 0): int
    {
        if ($userId > 0) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM waitlist WHERE user_id = ? AND status IN ("waiting","notified")');
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->db->query('SELECT COUNT(*) FROM waitlist WHERE status IN ("waiting","notified")');
        }
        return (int) $stmt->fetchColumn();
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT w.*, u.full_name AS user_name, u.email AS user_email,
                       r.resource_name, r.resource_code
                FROM waitlist w
                JOIN users u ON u.id = w.user_id
                JOIN resources r ON r.id = w.resource_id
                WHERE 1=1';
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= ' AND w.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['resource_id'])) {
            $sql .= ' AND w.resource_id = ?';
            $params[] = $filters['resource_id'];
        }
        $sql .= ' ORDER BY w.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
