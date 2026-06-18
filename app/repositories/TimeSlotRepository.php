<?php
declare(strict_types=1);

class TimeSlotRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(array $filters = []): array
    {
        $sql = 'SELECT ts.*, r.resource_name, r.resource_code
                FROM time_slots ts
                JOIN resources r ON r.id = ts.resource_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['resource_id'])) {
            $sql .= ' AND ts.resource_id = ?';
            $params[] = $filters['resource_id'];
        }
        if (isset($filters['is_active'])) {
            $sql .= ' AND ts.is_active = ?';
            $params[] = (int) $filters['is_active'];
        }
        if (isset($filters['is_peak'])) {
            $sql .= ' AND ts.is_peak = ?';
            $params[] = (int) $filters['is_peak'];
        }

        $sql .= ' ORDER BY ts.resource_id, ts.day_of_week, ts.start_time';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ts.*, r.resource_name FROM time_slots ts
             JOIN resources r ON r.id = ts.resource_id WHERE ts.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByResource(int $resourceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM time_slots WHERE resource_id = ? ORDER BY day_of_week, start_time'
        );
        $stmt->execute([$resourceId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO time_slots (resource_id, day_of_week, start_time, end_time, is_peak, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['resource_id'],
            $data['day_of_week'],
            $data['start_time'],
            $data['end_time'],
            (int) ($data['is_peak'] ?? 0),
            (int) ($data['is_active'] ?? 1),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = ['resource_id', 'day_of_week', 'start_time', 'end_time', 'is_peak', 'is_active'];
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
        $stmt = $this->db->prepare('UPDATE time_slots SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM time_slots WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function hasOverlap(int $resourceId, int $dayOfWeek, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM time_slots
                WHERE resource_id = ?
                AND day_of_week = ?
                AND is_active = 1
                AND start_time < ?
                AND end_time > ?';
        $params = [$resourceId, $dayOfWeek, $endTime, $startTime];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function isPeakSlot(int $resourceId, string $startDatetime, string $endDatetime): bool
    {
        $dayOfWeek = (int) date('w', strtotime($startDatetime));
        $startTime = date('H:i:s', strtotime($startDatetime));
        $endTime = date('H:i:s', strtotime($endDatetime));

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM time_slots
             WHERE resource_id = ?
             AND day_of_week = ?
             AND is_peak = 1
             AND is_active = 1
             AND start_time < ?
             AND end_time > ?'
        );
        $stmt->execute([$resourceId, $dayOfWeek, $endTime, $startTime]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
