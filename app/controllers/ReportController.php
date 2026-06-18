<?php

declare(strict_types=1);

class ReportController extends Controller
{
    private ReportService $reportService;
    private ReportRepository $reportRepo;
    private ResourceCategoryRepository $categoryRepo;
    private ResourceRepository $resourceRepo;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->reportRepo = new ReportRepository();
        $this->categoryRepo = new ResourceCategoryRepository();
        $this->resourceRepo = new ResourceRepository();
    }

    public function index(): void
    {
        Middleware::admin();

        $filters = $this->getReportFilters();
        $reportFilters = [
            'resource_id' => $filters['resource_id'] ?: null,
            'report_type' => $filters['report_type'] ?: null,
            'period_start' => $filters['date_from'] ?: null,
            'period_end' => $filters['date_to'] ?: null,
        ];
        $reportFilters = array_filter($reportFilters, fn ($v) => $v !== null && $v !== '');

        $reportData = [
            'charts' => $this->reportService->getDashboardChartData(),
            'reports' => $this->reportService->getAll($reportFilters, 50, 0),
            'summary' => (new BookingRepository())->getDashboardStats(),
        ];

        $this->view('reports/index', [
            'title' => 'Usage Reports',
            'summary' => $reportData['summary'] ?? [],
            'reports' => $reportData['reports'] ?? [],
            'chartData' => $reportData['charts'] ?? [],
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
            redirect('index.php?page=reports');
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

        $filters = $this->getReportFilters();
        $reportFilters = array_filter([
            'resource_id' => $filters['resource_id'] ?: null,
            'report_type' => $filters['report_type'] ?: null,
            'period_start' => $filters['date_from'] ?: null,
            'period_end' => $filters['date_to'] ?: null,
        ]);

        $reports = $this->reportRepo->findAll($reportFilters, 1000, 0);

        $lines = ['Resource,Report Type,Period Start,Period End,Total Bookings,Approved,Rejected,Cancelled,Total Hours,Peak Hour Bookings,Utilization Rate,Generated At'];
        foreach ($reports as $row) {
            $lines[] = implode(',', [
                $this->csvEscape($row['resource_name'] ?? ''),
                $this->csvEscape($row['report_type'] ?? ''),
                $row['period_start'] ?? '',
                $row['period_end'] ?? '',
                $row['total_bookings'] ?? 0,
                $row['total_approved'] ?? 0,
                $row['total_rejected'] ?? 0,
                $row['total_cancelled'] ?? 0,
                $row['total_hours'] ?? 0,
                $row['peak_hour_bookings'] ?? 0,
                $row['utilization_rate'] ?? 0,
                $row['generated_at'] ?? '',
            ]);
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="usage_report_' . date('Y-m-d') . '.csv"');
        echo implode("\n", $lines);
        exit;
    }

    private function getReportFilters(): array
    {
        return [
            'date_from' => $this->get()['date_from'] ?? date('Y-m-01'),
            'date_to' => $this->get()['date_to'] ?? date('Y-m-d'),
            'category_id' => $this->get()['category_id'] ?? '',
            'resource_id' => $this->get()['resource_id'] ?? '',
            'report_type' => $this->get()['report_type'] ?? 'monthly',
        ];
    }

    private function csvEscape(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"')) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}
