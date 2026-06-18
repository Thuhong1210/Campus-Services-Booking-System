<?php
declare(strict_types=1);

class AuditLogRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['action'],
            $data['table_name'] ?? null,
            $data['record_id'] ?? null,
            $data['old_value'] ?? null,
            $data['new_value'] ?? null,
            $data['ip_address'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT al.*, u.full_name AS user_name, u.email AS user_email
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= ' AND al.user_id = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $sql .= ' AND al.action = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['table_name'])) {
            $sql .= ' AND al.table_name = ?';
            $params[] = $filters['table_name'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND al.created_at >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND al.created_at <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (al.action LIKE ? OR al.table_name LIKE ? OR u.full_name LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= ' ORDER BY al.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= ' AND al.user_id = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $sql .= ' AND al.action = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['table_name'])) {
            $sql .= ' AND al.table_name = ?';
            $params[] = $filters['table_name'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND al.created_at >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND al.created_at <= ?';
            $params[] = $filters['date_to'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
