<?php

declare(strict_types=1);

class ReportController extends Controller
{
    private ReportService $reportService;
    private ReportRepository $reportRepo;
    private ReportExportService $exportService;
    private ResourceCategoryRepository $categoryRepo;
    private ResourceRepository $resourceRepo;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->reportRepo = new ReportRepository();
        $this->exportService = new ReportExportService();
        $this->categoryRepo = new ResourceCategoryRepository();
        $this->resourceRepo = new ResourceRepository();
    }

    public function index(): void
    {
        Middleware::admin();

        $filters = $this->getReportFilters();
        $reportFilters = array_filter([
            'resource_id' => $filters['resource_id'] ?: null,
            'category_id' => $filters['category_id'] ?: null,
            'report_type' => $filters['report_type'] ?: null,
            'period_start' => $filters['date_from'] ?: null,
            'period_end' => $filters['date_to'] ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        $bookingStats = (new BookingRepository())->getDashboardStats();
        $rawCharts = $this->reportService->getDashboardChartData();
        $insights = $this->reportRepo->getUtilizationInsights(5);

        $statusMap = [];
        foreach ($rawCharts['bookings_by_status'] ?? [] as $row) {
            $statusMap[$row['status']] = (int) $row['count'];
        }
        $totalDecided = ($statusMap['approved'] ?? 0) + ($statusMap['rejected'] ?? 0);
        $approvalRate = $totalDecided > 0
            ? round((($statusMap['approved'] ?? 0) / $totalDecided) * 100, 1)
            : 0;
        $totalAll = max(1, (int) ($bookingStats['total'] ?? 1));
        $cancellationRate = round((($bookingStats['cancelled'] ?? 0) / $totalAll) * 100, 1);

        $noShowStats = $rawCharts['no_show_stats'] ?? ['no_shows' => 0, 'total_approved_ever' => 0, 'bookings_this_month' => 0];
        $totalApprovedEver = (int) ($noShowStats['total_approved_ever'] ?? 0);
        $noShowRate = $totalApprovedEver > 0
            ? round(((int)($noShowStats['no_shows'] ?? 0) / $totalApprovedEver) * 100, 1)
            : 0;
        $bookingsThisMonth = (int) ($noShowStats['bookings_this_month'] ?? 0);

        $this->view('reports/index', [
            'title' => 'Usage Reports',
            'summary' => array_merge($bookingStats, [
                'approval_rate' => $approvalRate,
                'cancellation_rate' => $cancellationRate,
                'avg_utilization' => $this->averageUtilization($reportFilters),
                'no_show_rate' => $noShowRate,
                'bookings_this_month' => $bookingsThisMonth,
            ]),
            'reports' => $this->reportService->getAll($reportFilters, 50, 0),
            'chartData' => $this->formatChartData($rawCharts),
            'insights' => $insights,
            'filters' => $filters,
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
            'resources' => $this->resourceRepo->findAll([], 100, 0),
        ]);
    }

    public function generate(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $filters = $this->getReportFilters();
        $resourceId = (int) ($this->post()['resource_id'] ?? $filters['resource_id'] ?? 0);

        if ($resourceId <= 0) {
            Flash::error('Please select a resource to generate a report.');
            redirect('index.php?page=reports&' . http_build_query(array_filter($filters)));
        }

        $result = $this->reportService->generate(
            $resourceId,
            $filters['report_type'],
            $filters['date_from'],
            $filters['date_to']
        );

        if ($result['success']) {
            Flash::success($result['message'] ?? 'Report generated successfully.');
        } else {
            Flash::error($result['message'] ?? 'Failed to generate report.');
        }
        redirect('index.php?page=reports&' . http_build_query(array_filter($filters)));
    }

    public function exportCsv(): void
    {
        Middleware::admin();
        $this->sendExport('csv');
    }

    public function exportExcel(): void
    {
        Middleware::admin();
        $this->sendExport('excel');
    }

    public function exportPdf(): void
    {
        Middleware::admin();
        $this->sendExport('pdf');
    }

    private function sendExport(string $format): void
    {
        $filters = $this->getReportFilters();
        $reportFilters = array_filter([
            'resource_id' => $filters['resource_id'] ?: null,
            'category_id' => $filters['category_id'] ?: null,
            'report_type' => $filters['report_type'] ?: null,
            'period_start' => $filters['date_from'] ?: null,
            'period_end' => $filters['date_to'] ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        $reports = $this->reportRepo->findAll($reportFilters, 1000, 0);
        $headers = ['Resource', 'Category', 'Report Type', 'Period Start', 'Period End', 'Bookings', 'Approved', 'Rejected', 'Cancelled', 'Hours', 'Peak Hours', 'Utilization %', 'Generated At'];
        $rows = [];
        foreach ($reports as $row) {
            $rows[] = [
                $row['resource_name'] ?? '',
                $row['category_name'] ?? '',
                $row['report_type'] ?? '',
                $row['period_start'] ?? '',
                $row['period_end'] ?? '',
                $row['total_bookings'] ?? 0,
                $row['total_approved'] ?? 0,
                $row['total_rejected'] ?? 0,
                $row['total_cancelled'] ?? 0,
                $row['total_hours'] ?? 0,
                $row['peak_hour_bookings'] ?? 0,
                ($row['utilization_rate'] ?? 0) . '%',
                $row['generated_at'] ?? '',
            ];
        }

        $date = date('Y-m-d');
        if ($format === 'csv') {
            $lines = [implode(',', $headers)];
            foreach ($rows as $row) {
                $lines[] = implode(',', array_map(fn ($v) => $this->csvEscape((string) $v), $row));
            }
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="usage_report_' . $date . '.csv"');
            echo "\xEF\xBB\xBF" . implode("\n", $lines);
            exit;
        }
        if ($format === 'excel') {
            $this->exportService->sendExcel($headers, $rows, 'usage_report_' . $date . '.xls');
        }
        $this->exportService->sendPdf('Campus Services Usage Report', $headers, $rows, 'usage_report_' . $date . '.pdf');
    }

    private function getReportFilters(): array
    {
        return [
            'date_from' => $this->get()['date_from'] ?? $this->post()['date_from'] ?? date('Y-m-01'),
            'date_to' => $this->get()['date_to'] ?? $this->post()['date_to'] ?? date('Y-m-d'),
            'category_id' => $this->get()['category_id'] ?? $this->post()['category_id'] ?? '',
            'resource_id' => $this->get()['resource_id'] ?? $this->post()['resource_id'] ?? '',
            'report_type' => $this->get()['report_type'] ?? $this->post()['report_type'] ?? 'monthly',
        ];
    }

    private function formatChartData(array $raw): array
    {
        $statusMap = [];
        foreach ($raw['bookings_by_status'] ?? [] as $row) {
            $statusMap[$row['status']] = (int) $row['count'];
        }

        $hourLabels = [];
        $hourData = [];
        $hourMap = [];
        foreach ($raw['bookings_by_hour'] ?? [] as $row) {
            $hourMap[(int)$row['hour']] = (int)$row['count'];
        }
        for ($h = 7; $h <= 21; $h++) { // School operating hours: 7 AM to 9 PM
            $hourLabels[] = sprintf('%02d:00', $h);
            $hourData[] = $hourMap[$h] ?? 0;
        }

        return [
            'resource_labels' => array_column($raw['top_resources'] ?? [], 'resource_name'),
            'resource_data' => array_column($raw['top_resources'] ?? [], 'count'),
            'category_labels' => array_column($raw['bookings_by_category'] ?? [], 'category_name'),
            'category_data' => array_column($raw['bookings_by_category'] ?? [], 'count'),
            'monthly_labels' => array_column($raw['monthly_trend'] ?? [], 'month'),
            'monthly_data' => array_column($raw['monthly_trend'] ?? [], 'count'),
            'approved' => (int) ($statusMap['approved'] ?? 0),
            'rejected' => (int) ($statusMap['rejected'] ?? 0),
            'cancelled' => (int) ($statusMap['cancelled'] ?? 0),
            'peak' => (int) ($raw['peak_vs_off_peak']['peak'] ?? 0),
            'off_peak' => (int) ($raw['peak_vs_off_peak']['off_peak'] ?? 0),
            'dept_labels' => array_column($raw['bookings_by_department'] ?? [], 'department_name'),
            'dept_data' => array_map('intval', array_column($raw['bookings_by_department'] ?? [], 'count')),
            'hour_labels' => $hourLabels,
            'hour_data' => $hourData,
        ];
    }

    private function averageUtilization(array $filters): float
    {
        $reports = $this->reportRepo->findAll($filters, 100, 0);
        if (empty($reports)) {
            return 0.0;
        }
        $sum = array_sum(array_column($reports, 'utilization_rate'));
        return round($sum / count($reports), 1);
    }

    private function csvEscape(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"')) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}
