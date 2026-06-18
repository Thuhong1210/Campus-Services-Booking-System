<?php
declare(strict_types=1);

class ReportService
{
    private ReportRepository $reportRepo;
    private ResourceRepository $resourceRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->reportRepo = new ReportRepository();
        $this->resourceRepo = new ResourceRepository();
        $this->auditLog = new AuditLogService();
    }

    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        return $this->reportRepo->findAll($filters, $limit, $offset);
    }

    public function generate(int $resourceId, string $reportType, string $periodStart, string $periodEnd): array
    {
        $resource = $this->resourceRepo->findById($resourceId);
        if (!$resource) {
            return ['success' => false, 'message' => 'Resource not found.'];
        }

        $stats = $this->reportRepo->generateStats($resourceId, $reportType, $periodStart, $periodEnd);

        $reportId = $this->reportRepo->saveReport([
            'resource_id' => $resourceId,
            'report_type' => $reportType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_bookings' => (int) ($stats['total_bookings'] ?? 0),
            'total_approved' => (int) ($stats['total_approved'] ?? 0),
            'total_rejected' => (int) ($stats['total_rejected'] ?? 0),
            'total_cancelled' => (int) ($stats['total_cancelled'] ?? 0),
            'total_hours' => (float) ($stats['total_hours'] ?? 0),
            'peak_hour_bookings' => (int) ($stats['peak_hour_bookings'] ?? 0),
            'utilization_rate' => (float) ($stats['utilization_rate'] ?? 0),
        ]);

        $this->auditLog->log('generate_report', 'usage_reports', $reportId, null, [
            'resource_id' => $resourceId,
            'report_type' => $reportType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        return [
            'success' => true,
            'message' => 'Report generated successfully.',
            'report_id' => $reportId,
            'stats' => $stats,
            'resource' => $resource,
        ];
    }

    public function getDashboardChartData(): array
    {
        return $this->reportRepo->getDashboardChartData();
    }

    public function generateWeekly(int $resourceId): array
    {
        $periodStart = date('Y-m-d', strtotime('monday this week'));
        $periodEnd = date('Y-m-d', strtotime('sunday this week'));
        return $this->generate($resourceId, 'weekly', $periodStart, $periodEnd);
    }

    public function generateMonthly(int $resourceId): array
    {
        $periodStart = date('Y-m-01');
        $periodEnd = date('Y-m-t');
        return $this->generate($resourceId, 'monthly', $periodStart, $periodEnd);
    }

    public function generateSemester(int $resourceId): array
    {
        $month = (int) date('n');
        if ($month >= 1 && $month <= 5) {
            $periodStart = date('Y') . '-01-01';
            $periodEnd = date('Y') . '-05-31';
        } elseif ($month >= 6 && $month <= 8) {
            $periodStart = date('Y') . '-06-01';
            $periodEnd = date('Y') . '-08-31';
        } else {
            $periodStart = date('Y') . '-09-01';
            $periodEnd = date('Y') . '-12-31';
        }
        return $this->generate($resourceId, 'semester', $periodStart, $periodEnd);
    }
}
