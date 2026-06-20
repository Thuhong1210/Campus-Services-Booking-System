<?php
declare(strict_types=1);

class ReportRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT ur.*, r.resource_name, r.resource_code, rc.category_name
                FROM usage_reports ur
                JOIN resources r ON r.id = ur.resource_id
                JOIN resource_categories rc ON rc.id = r.category_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['resource_id'])) {
            $sql .= ' AND ur.resource_id = ?';
            $params[] = $filters['resource_id'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= ' AND r.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['report_type'])) {
            $sql .= ' AND ur.report_type = ?';
            $params[] = $filters['report_type'];
        }
        if (!empty($filters['period_start'])) {
            $sql .= ' AND ur.period_start >= ?';
            $params[] = $filters['period_start'];
        }
        if (!empty($filters['period_end'])) {
            // Match reports whose period overlaps with the selected range
            // (period starts on or before the filter end-date)
            $sql .= ' AND ur.period_start <= ?';
            $params[] = $filters['period_end'];
        }

        $sql .= ' ORDER BY ur.generated_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function generateStats(int $resourceId, string $reportType, string $periodStart, string $periodEnd): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(*) AS total_bookings,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) AS total_approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) AS total_rejected,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) AS total_cancelled,
                COALESCE(SUM(
                    CASE WHEN status IN ("approved","completed")
                    THEN TIMESTAMPDIFF(MINUTE, start_datetime, end_datetime) / 60.0
                    ELSE 0 END
                ), 0) AS total_hours
             FROM bookings
             WHERE resource_id = ?
             AND start_datetime BETWEEN ? AND ?'
        );
        $stmt->execute([$resourceId, $periodStart . ' 00:00:00', $periodEnd . ' 23:59:59']);
        $stats = $stmt->fetch() ?: [];

        $peakStmt = $this->db->prepare(
            'SELECT COUNT(DISTINCT b.id) FROM bookings b
             JOIN time_slots ts ON ts.resource_id = b.resource_id
             WHERE b.resource_id = ?
             AND b.status IN ("approved", "completed")
             AND b.start_datetime BETWEEN ? AND ?
             AND ts.is_peak = 1
             AND ts.is_active = 1
             AND ts.day_of_week = DAYOFWEEK(b.start_datetime) - 1
             AND ts.start_time < TIME(b.end_datetime)
             AND ts.end_time > TIME(b.start_datetime)'
        );
        $peakStmt->execute([$resourceId, $periodStart . ' 00:00:00', $periodEnd . ' 23:59:59']);
        $stats['peak_hour_bookings'] = (int) $peakStmt->fetchColumn();

        $days = max(1, (int) ((strtotime($periodEnd) - strtotime($periodStart)) / 86400) + 1);
        $totalHours = (float) ($stats['total_hours'] ?? 0);
        $stats['utilization_rate'] = round(min(100, ($totalHours / ($days * 8)) * 100), 2);

        return $stats;
    }

    public function saveReport(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usage_reports
            (resource_id, report_type, period_start, period_end, total_bookings, total_approved,
             total_rejected, total_cancelled, total_hours, peak_hour_bookings, utilization_rate, generated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['resource_id'],
            $data['report_type'],
            $data['period_start'],
            $data['period_end'],
            $data['total_bookings'] ?? 0,
            $data['total_approved'] ?? 0,
            $data['total_rejected'] ?? 0,
            $data['total_cancelled'] ?? 0,
            $data['total_hours'] ?? 0,
            $data['peak_hour_bookings'] ?? 0,
            $data['utilization_rate'] ?? 0,
            $data['generated_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getDashboardChartData(): array
    {
        $bookingsByStatus = $this->db->query(
            'SELECT status, COUNT(*) AS count FROM bookings GROUP BY status ORDER BY count DESC'
        )->fetchAll();

        $bookingsByCategory = $this->db->query(
            'SELECT rc.category_name, COUNT(b.id) AS count
             FROM bookings b
             JOIN resources r ON r.id = b.resource_id
             JOIN resource_categories rc ON rc.id = r.category_id
             GROUP BY rc.id, rc.category_name
             ORDER BY count DESC'
        )->fetchAll();

        $monthlyTrend = $this->db->query(
            'SELECT DATE_FORMAT(start_datetime, "%Y-%m") AS month, COUNT(*) AS count
             FROM bookings
             WHERE start_datetime >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY month
             ORDER BY month ASC'
        )->fetchAll();

        $topResources = $this->db->query(
            'SELECT r.resource_name, COUNT(b.id) AS count
             FROM bookings b
             JOIN resources r ON r.id = b.resource_id
             WHERE b.status IN ("approved", "completed")
             GROUP BY r.id, r.resource_name
             ORDER BY count DESC
             LIMIT 5'
        )->fetchAll();

        $peakVsOffPeak = $this->db->query(
            'SELECT
    SUM(CASE WHEN peak_flag = 1 THEN 1 ELSE 0 END) AS peak,
    SUM(CASE WHEN peak_flag = 0 THEN 1 ELSE 0 END) AS off_peak
  FROM (
      SELECT b.id,
          MAX(CASE WHEN ts.is_peak = 1 THEN 1 ELSE 0 END) AS peak_flag
      FROM bookings b
      LEFT JOIN time_slots ts ON ts.resource_id = b.resource_id
          AND ts.day_of_week = DAYOFWEEK(b.start_datetime) - 1
          AND ts.is_active = 1
          AND ts.start_time < TIME(b.end_datetime)
          AND ts.end_time > TIME(b.start_datetime)
      WHERE b.status IN ("approved", "completed")
      AND b.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
      GROUP BY b.id
  ) AS sub'
        )->fetch();

        $bookingsByDepartment = $this->db->query(
            'SELECT COALESCE(d.department_name, "Other") AS department_name, COUNT(b.id) AS count
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             LEFT JOIN departments d ON d.id = u.department_id
             GROUP BY d.id, d.department_name
             ORDER BY count DESC'
        )->fetchAll();

        $bookingsByHour = $this->db->query(
            'SELECT HOUR(start_datetime) AS hour, COUNT(*) AS count
             FROM bookings
             GROUP BY hour
             ORDER BY hour ASC'
        )->fetchAll();

        $noShowStats = $this->db->query(
            'SELECT 
                 SUM(CASE WHEN is_no_show = 1 THEN 1 ELSE 0 END) AS no_shows,
                 SUM(CASE WHEN status IN ("approved", "completed") OR is_no_show = 1 THEN 1 ELSE 0 END) AS total_approved_ever,
                 SUM(CASE WHEN start_datetime >= DATE_FORMAT(CURDATE(), "%Y-%m-01") THEN 1 ELSE 0 END) AS bookings_this_month
             FROM bookings'
        )->fetch();

        return [
            'bookings_by_status' => $bookingsByStatus,
            'bookings_by_category' => $bookingsByCategory,
            'monthly_trend' => $monthlyTrend,
            'top_resources' => $topResources,
            'peak_vs_off_peak' => $peakVsOffPeak ?: ['peak' => 0, 'off_peak' => 0],
            'bookings_by_department' => $bookingsByDepartment,
            'bookings_by_hour' => $bookingsByHour,
            'no_show_stats' => $noShowStats ?: ['no_shows' => 0, 'total_approved_ever' => 0, 'bookings_this_month' => 0],
        ];
    }

    public function getUtilizationInsights(int $limit = 5): array
    {
        $overused = $this->db->query(
            'SELECT r.resource_name, r.resource_code, rc.category_name, COUNT(b.id) AS booking_count,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, b.start_datetime, b.end_datetime) / 60.0), 0) AS total_hours
             FROM bookings b
             JOIN resources r ON r.id = b.resource_id
             JOIN resource_categories rc ON rc.id = r.category_id
             WHERE b.status IN ("approved", "completed")
             AND b.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY r.id, r.resource_name, r.resource_code, rc.category_name
             ORDER BY booking_count DESC
             LIMIT ' . (int) $limit
        )->fetchAll();

        $underused = $this->db->query(
            'SELECT r.resource_name, r.resource_code, rc.category_name, COUNT(b.id) AS booking_count
             FROM resources r
             JOIN resource_categories rc ON rc.id = r.category_id
             LEFT JOIN bookings b ON b.resource_id = r.id
                AND b.status IN ("approved", "completed")
                AND b.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             WHERE r.status = "available"
             GROUP BY r.id, r.resource_name, r.resource_code, rc.category_name
             ORDER BY booking_count ASC, r.resource_name ASC
             LIMIT ' . (int) $limit
        )->fetchAll();

        return ['overused' => $overused, 'underused' => $underused];
    }
}
