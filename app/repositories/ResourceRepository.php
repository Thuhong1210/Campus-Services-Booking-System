<?php
declare(strict_types=1);

class ResourceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT r.*, rc.category_name
                FROM resources r
                JOIN resource_categories rc ON rc.id = r.category_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= ' AND r.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (r.resource_name LIKE ? OR r.resource_code LIKE ? OR r.location LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= ' ORDER BY r.resource_name ASC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) FROM resources r WHERE 1=1';
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= ' AND r.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (r.resource_name LIKE ? OR r.resource_code LIKE ? OR r.location LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, rc.category_name, rc.requires_approval AS category_requires_approval
             FROM resources r
             JOIN resource_categories rc ON rc.id = r.category_id
             WHERE r.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO resources
            (category_id, resource_code, resource_name, location, capacity, description, image, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['resource_code'],
            $data['resource_name'],
            $data['location'],
            $data['capacity'] ?? 1,
            $data['description'] ?? null,
            $data['image'] ?? null,
            $data['status'] ?? 'available',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [
            'category_id', 'resource_code', 'resource_name', 'location',
            'capacity', 'description', 'image', 'status',
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
        $stmt = $this->db->prepare('UPDATE resources SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM resources WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function hasBookings(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM bookings WHERE resource_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function findAvailable(array $filters = []): array
    {
        $sql = 'SELECT r.*, rc.category_name
                FROM resources r
                JOIN resource_categories rc ON rc.id = r.category_id
                WHERE r.status = "available" AND rc.status = "active"';
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= ' AND r.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (r.resource_name LIKE ? OR r.resource_code LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        if (!empty($filters['location'])) {
            $sql .= ' AND r.location LIKE ?';
            $params[] = '%' . $filters['location'] . '%';
        }

        $sql .= ' ORDER BY r.resource_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getEquipment(int $resourceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, re.quantity AS assigned_quantity
             FROM resource_equipment re
             JOIN equipment e ON e.id = re.equipment_id
             WHERE re.resource_id = ?
             ORDER BY e.equipment_name ASC'
        );
        $stmt->execute([$resourceId]);
        return $stmt->fetchAll();
    }

    public function syncEquipment(int $resourceId, array $equipmentIds): void
    {
        $this->db->prepare('DELETE FROM resource_equipment WHERE resource_id = ?')->execute([$resourceId]);
        if (empty($equipmentIds)) {
            return;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO resource_equipment (resource_id, equipment_id, quantity) VALUES (?,?,1)'
        );
        foreach ($equipmentIds as $equipmentId) {
            $equipmentId = (int) $equipmentId;
            if ($equipmentId > 0) {
                $stmt->execute([$resourceId, $equipmentId]);
            }
        }
    }
}
