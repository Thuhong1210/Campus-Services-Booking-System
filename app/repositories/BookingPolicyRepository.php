<?php
declare(strict_types=1);

class BookingPolicyRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(array $filters = []): array
    {
        $sql = 'SELECT bp.*, rc.category_name
                FROM booking_policies bp
                JOIN resource_categories rc ON rc.id = bp.category_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= ' AND bp.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= ' AND bp.is_active = ?';
            $params[] = (int) $filters['is_active'];
        }

        $sql .= ' ORDER BY rc.category_name, bp.policy_name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT bp.*, rc.category_name
             FROM booking_policies bp
             JOIN resource_categories rc ON rc.id = bp.category_id
             WHERE bp.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByCategory(int $categoryId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM booking_policies
             WHERE category_id = ? AND is_active = 1
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO booking_policies
            (category_id, policy_name, max_duration_hours, weekly_quota, max_peak_slots_per_week,
             cancellation_deadline_hours, requires_approval, auto_approval_enabled, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['policy_name'],
            $data['max_duration_hours'] ?? 2.00,
            $data['weekly_quota'] ?? 5,
            $data['max_peak_slots_per_week'] ?? 2,
            $data['cancellation_deadline_hours'] ?? 24,
            (int) ($data['requires_approval'] ?? 0),
            (int) ($data['auto_approval_enabled'] ?? 0),
            (int) ($data['is_active'] ?? 1),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [
            'category_id', 'policy_name', 'max_duration_hours', 'weekly_quota',
            'max_peak_slots_per_week', 'cancellation_deadline_hours',
            'requires_approval', 'auto_approval_enabled', 'is_active',
        ];
        $sets = [];
        $params = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($sets)) {
            return;
        }

        $params[] = $id;
        $stmt = $this->db->prepare('UPDATE booking_policies SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM booking_policies WHERE id = ?');
        $stmt->execute([$id]);
    }
}
