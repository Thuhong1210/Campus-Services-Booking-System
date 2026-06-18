<?php

declare(strict_types=1);

class EquipmentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        return $this->db->query('SELECT * FROM equipment ORDER BY equipment_name')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM equipment WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO equipment (equipment_name, description, quantity, status) VALUES (?,?,?,?)'
        );
        $stmt->execute([
            $data['equipment_name'],
            $data['description'] ?? null,
            (int) ($data['quantity'] ?? 1),
            $data['status'] ?? 'available',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE equipment SET equipment_name=?, description=?, quantity=?, status=? WHERE id=?'
        );
        $stmt->execute([
            $data['equipment_name'],
            $data['description'] ?? null,
            (int) ($data['quantity'] ?? 1),
            $data['status'] ?? 'available',
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM resource_equipment WHERE equipment_id = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('Equipment is assigned to resources.');
        }
        $this->db->prepare('DELETE FROM equipment WHERE id = ?')->execute([$id]);
    }

    public function assignToResource(int $resourceId, int $equipmentId, int $quantity): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO resource_equipment (resource_id, equipment_id, quantity) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
        );
        $stmt->execute([$resourceId, $equipmentId, $quantity]);
    }
}
