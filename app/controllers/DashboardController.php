<?php

declare(strict_types=1);

class DashboardController extends Controller
{
    private BookingRepository $bookingRepo;
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private ApprovalRepository $approvalRepo;
    private NotificationRepository $notificationRepo;
    private AuditLogRepository $auditLogRepo;
    private ReportService $reportService;

    public function __construct()
    {
        $this->bookingRepo = new BookingRepository();
        $this->userRepo = new UserRepository();
        $this->resourceRepo = new ResourceRepository();
        $this->approvalRepo = new ApprovalRepository();
        $this->notificationRepo = new NotificationRepository();
        $this->auditLogRepo = new AuditLogRepository();
        $this->reportService = new ReportService();
    }

    public function index(): void
    {
        Middleware::auth();

        try {
            (new BookingService())->autoReleaseNoShows();
        } catch (Throwable $e) {
            // Ignore background errors
        }

        $role = Auth::primaryRole();
        $userId = (int) Auth::id();

        switch ($role) {
            case 'Admin':
                $this->adminDashboard();
                break;
            case 'Lecturer':
            case 'Approver':
                $this->lecturerDashboard($userId);
                break;
            case 'Staff':
                $this->staffDashboard($userId);
                break;
            default:
                $this->studentDashboard($userId);
                break;
        }
    }

    private function adminDashboard(): void
    {
        $stats = $this->bookingRepo->getDashboardStats();
        $stats['total_users']       = $this->userRepo->countAll();
        $stats['total_resources']   = $this->resourceRepo->count([]);
        $stats['pending_approvals'] = $this->approvalRepo->countPending();

        $rawCharts      = $this->reportService->getDashboardChartData();
        $recentActivity = $this->auditLogRepo->findAll([], 10, 0);
        $upcomingBookings = $this->bookingRepo->findAll(
            ['date_from' => date('Y-m-d H:i:s')],
            10,
            0
        );

        $peakVsOffPeak = $rawCharts['peak_vs_off_peak'] ?? ['peak' => 0, 'off_peak' => 0];

        // Build chart data for admin dashboard (5 charts)
        $chartData = [
            'category_labels'  => array_column($rawCharts['bookings_by_category'] ?? [], 'category_name'),
            'category_data'    => array_map('intval', array_column($rawCharts['bookings_by_category'] ?? [], 'count')),
            'peak_labels'      => array_column($rawCharts['monthly_trend'] ?? [], 'month'),
            'peak_data'        => array_map('intval', array_column($rawCharts['monthly_trend'] ?? [], 'count')),
            'resource_labels'  => array_column($rawCharts['top_resources'] ?? [], 'resource_name'),
            'resource_data'    => array_map('intval', array_column($rawCharts['top_resources'] ?? [], 'count')),
            'peak'             => (int) ($peakVsOffPeak['peak'] ?? 0),
            'off_peak'         => (int) ($peakVsOffPeak['off_peak'] ?? 0),
        ];

        $this->view('dashboard/admin', [
            'title'            => 'Admin Dashboard',
            'stats'            => $stats,
            'chartData'        => $chartData,
            'recentActivity'   => $recentActivity,
            'upcomingBookings' => $upcomingBookings,
        ]);
    }

    private function studentDashboard(int $userId): void
    {
        $stats = $this->bookingRepo->getDashboardStats($userId);
        $upcomingBookings = $this->bookingRepo->findUpcoming($userId, 5);
        $notifications = $this->notificationRepo->findByUser($userId, [], 5, 0);
        $unreadCount = $this->notificationRepo->countUnread($userId);

        // Fetch categories to calculate weekly quota usage
        $categoryRepo = new ResourceCategoryRepository();
        $categories = $categoryRepo->findAll(['status' => 'active']);
        
        $quotaUsage = [];
        foreach ($categories as $cat) {
            $catId = (int)$cat['id'];
            $weeklyHoursLimit = (float)$cat['max_booking_hours_per_week'];
            $weeklyHoursUsed = $this->bookingRepo->sumWeeklyHours($userId, $catId);
            $quotaUsage[] = [
                'category_name' => $cat['category_name'],
                'limit_hours' => $weeklyHoursLimit,
                'used_hours' => $weeklyHoursUsed,
                'percentage' => $weeklyHoursLimit > 0 ? min(100, (int)round(($weeklyHoursUsed / $weeklyHoursLimit) * 100)) : 0
            ];
        }

        // Calculate peak hour slots usage
        $peakLimit = 2; // Default limit
        $peakUsed = $this->bookingRepo->countPeakBookingsThisWeek($userId);
        $peakPercentage = min(100, (int)round(($peakUsed / $peakLimit) * 100));

        // Fetch recommended resources for quick booking
        $recommendedResources = $this->bookingRepo->getRecommendedResources($userId, 3);

        // Fetch student booking stats for visualization charts
        $chartData = $this->bookingRepo->getStudentChartData($userId);

        $this->view('dashboard/student', [
            'title' => 'Student Dashboard',
            'stats' => $stats,
            'upcomingBookings' => $upcomingBookings,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'quotaUsage' => $quotaUsage,
            'peakLimit' => $peakLimit,
            'peakUsed' => $peakUsed,
            'peakPercentage' => $peakPercentage,
            'recommendedResources' => $recommendedResources,
            'chartData' => $chartData,
        ]);
    }

    private function lecturerDashboard(int $userId): void
    {
        $stats = $this->bookingRepo->getDashboardStats($userId);
        $stats['pending_approvals'] = $this->approvalRepo->countPending();
        $pendingApprovals = $this->approvalRepo->findPending(5, 0);
        $recentHistory = $this->approvalRepo->findHistory(['approver_id' => $userId], 5, 0);
        $notifications = $this->notificationRepo->findByUser($userId, [], 5, 0);
        $unreadCount = $this->notificationRepo->countUnread($userId);
        $upcomingBookings = $this->bookingRepo->findUpcoming($userId, 5);

        $this->view('dashboard/lecturer', [
            'title' => 'Lecturer Dashboard',
            'stats' => $stats,
            'pendingApprovals' => $pendingApprovals,
            'recentHistory' => $recentHistory,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'upcomingBookings' => $upcomingBookings,
        ]);
    }

    private function staffDashboard(int $userId): void
    {
        $stats = $this->bookingRepo->getDashboardStats();
        $upcomingBookings = $this->bookingRepo->findAll(['date_from' => date('Y-m-d H:i:s')], 8, 0);
        $notifications = $this->notificationRepo->findByUser($userId, [], 5, 0);

        $this->view('dashboard/staff', [
            'title' => 'Staff Dashboard',
            'stats' => $stats,
            'upcomingBookings' => $upcomingBookings,
            'notifications' => $notifications,
        ]);
    }
}
