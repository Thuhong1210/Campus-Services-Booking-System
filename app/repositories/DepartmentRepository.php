<?php
declare(strict_types=1);

class DepartmentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM departments ORDER BY department_name ASC');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM departments WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO departments (department_name, description) VALUES (?, ?)'
        );
        $stmt->execute([
            $data['department_name'],
            $data['description'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE departments SET department_name = ?, description = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['department_name'],
            $data['description'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM departments WHERE id = ?');
        $stmt->execute([$id]);
    }
}
