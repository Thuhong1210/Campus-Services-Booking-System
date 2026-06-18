<?php
declare(strict_types=1);

class ResourceCategoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(array $filters = []): array
    {
        $sql = 'SELECT * FROM resource_categories WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (category_name LIKE ? OR description LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= ' ORDER BY category_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM resource_categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO resource_categories
            (category_name, description, requires_approval, max_booking_hours_per_day,
             max_booking_hours_per_week, max_peak_slots_per_week, cancellation_deadline_hours, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_name'],
            $data['description'] ?? null,
            (int) ($data['requires_approval'] ?? 0),
            $data['max_booking_hours_per_day'] ?? 4.00,
            $data['max_booking_hours_per_week'] ?? 10.00,
            $data['max_peak_slots_per_week'] ?? 2,
            $data['cancellation_deadline_hours'] ?? 24,
            $data['status'] ?? 'active',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [
            'category_name', 'description', 'requires_approval',
            'max_booking_hours_per_day', 'max_booking_hours_per_week',
            'max_peak_slots_per_week', 'cancellation_deadline_hours', 'status',
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
        $stmt = $this->db->prepare('UPDATE resource_categories SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM resource_categories WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function hasResources(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM resources WHERE category_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
