<?php
declare(strict_types=1);

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT u.*, d.department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? OR username = ? OR student_code = ? LIMIT 1');
        $stmt->execute([$login, $login, $login]);
        return $stmt->fetch() ?: null;
    }

    public function getRoles(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT r.role_name FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function all(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT u.*, d.department_name, GROUP_CONCAT(r.role_name) as roles
                FROM users u
                LEFT JOIN departments d ON d.id = u.department_id
                LEFT JOIN user_roles ur ON ur.user_id = u.id
                LEFT JOIN roles r ON r.id = ur.role_id
                WHERE 1=1';
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s]);
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND u.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['department_id'])) {
            $sql .= ' AND u.department_id = ?';
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['role'])) {
            $sql .= ' AND r.role_name = ?';
            $params[] = $filters['role'];
        }
        $sql .= ' GROUP BY u.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(array $filters = []): int
    {
        $sql = 'SELECT COUNT(DISTINCT u.id) FROM users u
                LEFT JOIN user_roles ur ON ur.user_id = u.id
                LEFT JOIN roles r ON r.id = ur.role_id WHERE 1=1';
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s]);
        }
        if (!empty($filters['status'])) { $sql .= ' AND u.status = ?'; $params[] = $filters['status']; }
        if (!empty($filters['department_id'])) { $sql .= ' AND u.department_id = ?'; $params[] = $filters['department_id']; }
        if (!empty($filters['role'])) { $sql .= ' AND r.role_name = ?'; $params[] = $filters['role']; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function exists(string $field, string $value, ?int $excludeId = null): bool
    {
        $allowed = ['email', 'username', 'student_code', 'staff_code'];
        if (!in_array($field, $allowed, true)) return false;
        $sql = "SELECT COUNT(*) FROM users WHERE $field = ?";
        $params = [$value];
        if ($excludeId) { $sql .= ' AND id != ?'; $params[] = $excludeId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data, array $roleIds): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('INSERT INTO users (department_id, full_name, username, email, password_hash, phone, student_code, staff_code, status) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $data['department_id'] ?: null,
                $data['full_name'], $data['username'], $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['phone'] ?? null,
                $data['student_code'] ?: null,
                $data['staff_code'] ?: null,
                $data['status'] ?? 'active',
            ]);
            $userId = (int) $this->db->lastInsertId();
            $roleStmt = $this->db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)');
            foreach ($roleIds as $roleId) {
                $roleStmt->execute([$userId, $roleId]);
            }
            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data, array $roleIds): void
    {
        $this->db->beginTransaction();
        try {
            $fields = ['department_id', 'full_name', 'username', 'email', 'phone', 'student_code', 'staff_code', 'status'];
            $sets = [];
            $params = [];
            foreach ($fields as $f) {
                if (array_key_exists($f, $data)) {
                    $sets[] = "$f = ?";
                    $params[] = $data[$f] ?: null;
                }
            }
            if (!empty($data['password'])) {
                $sets[] = 'password_hash = ?';
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            if ($sets) {
                $params[] = $id;
                $this->db->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
            }
            $this->db->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$id]);
            $roleStmt = $this->db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)');
            foreach ($roleIds as $roleId) {
                $roleStmt->execute([$id, $roleId]);
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deactivate(int $id): void
    {
        $this->db->prepare('UPDATE users SET status = ? WHERE id = ?')->execute(['inactive', $id]);
    }

    public function hasBookings(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn();
    }
}