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
        $stats['total_users'] = $this->userRepo->countAll();
        $stats['total_resources'] = $this->resourceRepo->count([]);
        $stats['pending_approvals'] = $this->approvalRepo->countPending();

        $rawCharts = $this->reportService->getDashboardChartData();
        $recentActivity = $this->auditLogRepo->findAll([], 10, 0);
        $upcomingBookings = $this->bookingRepo->findAll(
            ['date_from' => date('Y-m-d H:i:s')],
            10,
            0
        );

        // Format raw chart data into the arrays that admin.php JavaScript expects
        $chartData = [
            'category_labels' => array_column($rawCharts['bookings_by_category'] ?? [], 'category_name'),
            'category_data'   => array_map('intval', array_column($rawCharts['bookings_by_category'] ?? [], 'count')),
            'peak_labels'     => array_column($rawCharts['monthly_trend'] ?? [], 'month'),
            'peak_data'       => array_map('intval', array_column($rawCharts['monthly_trend'] ?? [], 'count')),
        ];

        $this->view('dashboard/admin', [
            'title' => 'Admin Dashboard',
            'stats' => $stats,
            'chartData' => $chartData,
            'recentActivity' => $recentActivity,
            'upcomingBookings' => $upcomingBookings,
        ]);
    }

    private function studentDashboard(int $userId): void
    {
        $stats = $this->bookingRepo->getDashboardStats($userId);
        $upcomingBookings = $this->bookingRepo->findUpcoming($userId, 5);
        $notifications = $this->notificationRepo->findByUser($userId, [], 5, 0);
        $unreadCount = $this->notificationRepo->countUnread($userId);

        $this->view('dashboard/student', [
            'title' => 'Student Dashboard',
            'stats' => $stats,
            'upcomingBookings' => $upcomingBookings,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    private function lecturerDashboard(int $userId): void
    {
        $stats = $this->bookingRepo->getDashboardStats($userId);
        $stats['pending_approvals'] = $this->approvalRepo->countPending();
        $pendingApprovals = $this->approvalRepo->findPending(5, 0);
        $recentHistory = $this->approvalRepo->findHistory(['approver_id' => $userId], 5, 0);
        $notifications = $this->notificationRepo->findByUser($userId, [], 5, 0);

        $this->view('dashboard/lecturer', [
            'title' => 'Lecturer Dashboard',
            'stats' => $stats,
            'pendingApprovals' => $pendingApprovals,
            'recentHistory' => $recentHistory,
            'notifications' => $notifications,
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
