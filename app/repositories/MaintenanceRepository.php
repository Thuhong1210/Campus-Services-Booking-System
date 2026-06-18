<?php
declare(strict_types=1);

class MaintenanceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT ms.*, r.resource_name, r.resource_code, u.full_name AS created_by_name
                FROM maintenance_schedules ms
                JOIN resources r ON r.id = ms.resource_id
                JOIN users u ON u.id = ms.created_by
                WHERE 1=1';
        $params = [];

        if (!empty($filters['resource_id'])) {
            $sql .= ' AND ms.resource_id = ?';
            $params[] = $filters['resource_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND ms.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND ms.maintenance_start >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND ms.maintenance_end <= ?';
            $params[] = $filters['date_to'];
        }

        $sql .= ' ORDER BY ms.maintenance_start DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ms.*, r.resource_name, r.resource_code, u.full_name AS created_by_name
             FROM maintenance_schedules ms
             JOIN resources r ON r.id = ms.resource_id
             JOIN users u ON u.id = ms.created_by
             WHERE ms.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findActiveForResource(int $resourceId, string $startDatetime, string $endDatetime): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM maintenance_schedules
             WHERE resource_id = ?
             AND status IN ("scheduled", "in_progress")
             AND maintenance_start < ?
             AND maintenance_end > ?'
        );
        $stmt->execute([$resourceId, $endDatetime, $startDatetime]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO maintenance_schedules
            (resource_id, maintenance_start, maintenance_end, reason, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['resource_id'],
            $data['maintenance_start'],
            $data['maintenance_end'],
            $data['reason'],
            $data['status'] ?? 'scheduled',
            $data['created_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = ['resource_id', 'maintenance_start', 'maintenance_end', 'reason', 'status'];
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
        $stmt = $this->db->prepare('UPDATE maintenance_schedules SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM maintenance_schedules WHERE id = ?');
        $stmt->execute([$id]);
    }
}
