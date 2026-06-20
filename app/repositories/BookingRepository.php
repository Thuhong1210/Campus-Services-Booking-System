<?php
declare(strict_types=1);

class BookingRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = $this->buildFilterSql($filters);
        $params = $this->buildFilterParams($filters);
        $sql .= ' ORDER BY b.start_datetime DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) FROM bookings b
                JOIN users u ON u.id = b.user_id
                JOIN resources r ON r.id = b.resource_id
                WHERE 1=1' . $this->filterClause($filters);
        $params = $this->buildFilterParams($filters);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT b.*, u.full_name AS user_name, u.email AS user_email,
                    r.resource_name, r.resource_code, r.category_id, rc.category_name
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             JOIN resources r ON r.id = b.resource_id
             JOIN resource_categories rc ON rc.id = r.category_id
             WHERE b.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO bookings
            (booking_reference, user_id, resource_id, start_datetime, end_datetime,
             purpose, additional_notes, status, requires_approval)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['booking_reference'],
            $data['user_id'],
            $data['resource_id'],
            $data['start_datetime'],
            $data['end_datetime'],
            $data['purpose'],
            $data['additional_notes'] ?? null,
            $data['status'] ?? 'pending',
            (int) ($data['requires_approval'] ?? 0),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [
            'resource_id', 'start_datetime', 'end_datetime', 'purpose',
            'additional_notes', 'status', 'requires_approval',
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
        $stmt = $this->db->prepare('UPDATE bookings SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function findConflicts(int $resourceId, string $startDatetime, string $endDatetime, ?int $excludeId = null): array
    {
        $sql = 'SELECT b.*, u.full_name AS user_name
                FROM bookings b
                JOIN users u ON u.id = b.user_id
                WHERE b.resource_id = ?
                AND b.status IN ("pending", "approved")
                AND b.start_datetime < ?
                AND b.end_datetime > ?';
        $params = [$resourceId, $endDatetime, $startDatetime];

        if ($excludeId !== null) {
            $sql .= ' AND b.id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countPeakBookingsThisWeek(int $userId, ?string $referenceDatetime = null, ?int $excludeBookingId = null): int
    {
        $ref = $referenceDatetime ? strtotime($referenceDatetime) : time();
        $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week', $ref));
        $weekEnd = date('Y-m-d 23:59:59', strtotime('sunday this week', $ref));

        $sql = 'SELECT COUNT(DISTINCT b.id) FROM bookings b
             JOIN time_slots ts ON ts.resource_id = b.resource_id
             WHERE b.user_id = ?
             AND b.status IN ("pending", "approved")
             AND b.start_datetime BETWEEN ? AND ?
             AND ts.is_peak = 1
             AND ts.is_active = 1
             AND ts.day_of_week = DAYOFWEEK(b.start_datetime) - 1
             AND ts.start_time < TIME(b.end_datetime)
             AND ts.end_time > TIME(b.start_datetime)';
        $params = [$userId, $weekStart, $weekEnd];
        if ($excludeBookingId !== null) {
            $sql .= ' AND b.id != ?';
            $params[] = $excludeBookingId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findByUser(int $userId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $filters['user_id'] = $userId;
        $sql = $this->buildFilterSql($filters);
        $params = $this->buildFilterParams($filters);
        $sql .= ' ORDER BY b.start_datetime DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findUpcoming(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.*, r.resource_name, r.resource_code, r.location
             FROM bookings b
             JOIN resources r ON r.id = b.resource_id
             WHERE b.user_id = ?
             AND b.start_datetime >= NOW()
             AND b.status IN ("pending", "approved")
             ORDER BY b.start_datetime ASC
             LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function getDashboardStats(?int $userId = null): array
    {
        if ($userId !== null) {
            $stmt = $this->db->prepare(
                'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) AS cancelled,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN start_datetime >= NOW() AND status IN ("pending","approved") THEN 1 ELSE 0 END) AS upcoming
                 FROM bookings WHERE user_id = ?'
            );
            $stmt->execute([$userId]);
            return $stmt->fetch() ?: [];
        }

        $stmt = $this->db->query(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) AS rejected,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN DATE(start_datetime) = CURDATE() THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN start_datetime >= NOW() AND status IN ("pending","approved") THEN 1 ELSE 0 END) AS upcoming
             FROM bookings'
        );
        return $stmt->fetch() ?: [];
    }

    public function countWeeklyBookings(int $userId, int $categoryId, ?int $excludeBookingId = null): int
    {
        $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $weekEnd = date('Y-m-d 23:59:59', strtotime('sunday this week'));

        $sql = 'SELECT COUNT(*) FROM bookings b
             JOIN resources r ON r.id = b.resource_id
             WHERE b.user_id = ?
             AND r.category_id = ?
             AND b.status IN ("pending", "approved")
             AND b.start_datetime BETWEEN ? AND ?';
        $params = [$userId, $categoryId, $weekStart, $weekEnd];
        if ($excludeBookingId !== null) {
            $sql .= ' AND b.id != ?';
            $params[] = $excludeBookingId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function sumDailyHours(int $userId, int $categoryId, string $date, ?int $excludeBookingId = null): float
    {
        $dayStart = $date . ' 00:00:00';
        $dayEnd = $date . ' 23:59:59';

        $sql = 'SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, b.start_datetime, b.end_datetime) / 60.0), 0)
             FROM bookings b
             JOIN resources r ON r.id = b.resource_id
             WHERE b.user_id = ?
             AND r.category_id = ?
             AND b.status IN ("pending", "approved")
             AND b.start_datetime BETWEEN ? AND ?';
        $params = [$userId, $categoryId, $dayStart, $dayEnd];
        if ($excludeBookingId !== null) {
            $sql .= ' AND b.id != ?';
            $params[] = $excludeBookingId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    public function sumWeeklyHours(int $userId, int $categoryId, ?int $excludeBookingId = null): float
    {
        $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $weekEnd = date('Y-m-d 23:59:59', strtotime('sunday this week'));

        $sql = 'SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, b.start_datetime, b.end_datetime) / 60.0), 0)
             FROM bookings b
             JOIN resources r ON r.id = b.resource_id
             WHERE b.user_id = ?
             AND r.category_id = ?
             AND b.status IN ("pending", "approved")
             AND b.start_datetime BETWEEN ? AND ?';
        $params = [$userId, $categoryId, $weekStart, $weekEnd];
        if ($excludeBookingId !== null) {
            $sql .= ' AND b.id != ?';
            $params[] = $excludeBookingId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    public function generateReference(): string
    {
        $prefix = 'BK' . date('Ymd');
        $stmt = $this->db->prepare(
            'SELECT booking_reference FROM bookings
             WHERE booking_reference LIKE ?
             ORDER BY booking_reference DESC LIMIT 1'
        );
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last) {
            $seq = (int) substr((string) $last, -3) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    private function buildFilterSql(array $filters): string
    {
        return 'SELECT b.*, u.full_name AS user_name, u.email AS user_email,
                       r.resource_name, r.resource_code, rc.category_name
                FROM bookings b
                JOIN users u ON u.id = b.user_id
                JOIN resources r ON r.id = b.resource_id
                JOIN resource_categories rc ON rc.id = r.category_id
                WHERE 1=1' . $this->filterClause($filters);
    }

    private function filterClause(array $filters): string
    {
        $sql = '';
        if (!empty($filters['user_id'])) {
            $sql .= ' AND b.user_id = ?';
        }
        if (!empty($filters['resource_id'])) {
            $sql .= ' AND b.resource_id = ?';
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND b.status = ?';
        }
        if (!empty($filters['category_id'])) {
            $sql .= ' AND r.category_id = ?';
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND b.start_datetime >= ?';
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND b.start_datetime <= ?';
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (b.booking_reference LIKE ? OR b.purpose LIKE ? OR u.full_name LIKE ? OR r.resource_name LIKE ?)';
        }
        return $sql;
    }

    private function buildFilterParams(array $filters, bool $withSearch = true): array
    {
        $params = [];
        if (!empty($filters['user_id'])) {
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['resource_id'])) {
            $params[] = $filters['resource_id'];
        }
        if (!empty($filters['status'])) {
            $params[] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['date_from'])) {
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $params[] = $filters['date_to'];
        }
        if ($withSearch && !empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        return $params;
    }

    public function getStudentChartData(int $userId): array
    {
        $stmtStatus = $this->db->prepare(
            'SELECT status, COUNT(*) AS count FROM bookings WHERE user_id = ? GROUP BY status ORDER BY count DESC'
        );
        $stmtStatus->execute([$userId]);
        $bookingsByStatus = $stmtStatus->fetchAll();

        $stmtCategory = $this->db->prepare(
            'SELECT rc.category_name, COUNT(b.id) AS count
             FROM bookings b
             JOIN resources r ON r.id = b.resource_id
             JOIN resource_categories rc ON rc.id = r.category_id
             WHERE b.user_id = ?
             GROUP BY rc.id, rc.category_name
             ORDER BY count DESC'
        );
        $stmtCategory->execute([$userId]);
        $bookingsByCategory = $stmtCategory->fetchAll();

        $stmtTrend = $this->db->prepare(
            'SELECT DATE_FORMAT(start_datetime, "%Y-%m") AS month, COUNT(*) AS count
             FROM bookings
             WHERE user_id = ?
             AND start_datetime >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY month
             ORDER BY month ASC'
        );
        $stmtTrend->execute([$userId]);
        $monthlyTrend = $stmtTrend->fetchAll();

        return [
            'bookings_by_status' => $bookingsByStatus,
            'bookings_by_category' => $bookingsByCategory,
            'monthly_trend' => $monthlyTrend,
        ];
    }

    public function getRecommendedResources(int $userId, int $limit = 3): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, rc.category_name, COUNT(b.id) AS booking_count
             FROM bookings b
             JOIN resources r ON r.id = b.resource_id
             JOIN resource_categories rc ON rc.id = r.category_id
             WHERE b.user_id = ? AND r.status = "available"
             GROUP BY r.id
             ORDER BY booking_count DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $recommended = $stmt->fetchAll();

        if (count($recommended) < $limit) {
            $needed = $limit - count($recommended);
            $excludeIds = empty($recommended) ? [0] : array_column($recommended, 'id');
            $inClause = implode(',', array_fill(0, count($excludeIds), '?'));

            $fallbackStmt = $this->db->prepare(
                "SELECT r.*, rc.category_name, COUNT(b.id) AS booking_count
                 FROM resources r
                 JOIN resource_categories rc ON rc.id = r.category_id
                 LEFT JOIN bookings b ON b.resource_id = r.id AND b.status IN ('approved', 'completed')
                 WHERE r.status = 'available' AND r.id NOT IN ($inClause)
                 GROUP BY r.id
                 ORDER BY booking_count DESC, r.id ASC
                 LIMIT ?"
            );

            foreach ($excludeIds as $idx => $id) {
                $fallbackStmt->bindValue($idx + 1, $id, PDO::PARAM_INT);
            }
            $fallbackStmt->bindValue(count($excludeIds) + 1, $needed, PDO::PARAM_INT);
            $fallbackStmt->execute();
            $fallback = $fallbackStmt->fetchAll();

            $recommended = array_merge($recommended, $fallback);
        }

        return $recommended;
    }
}
