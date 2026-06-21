<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';

// Language switcher interceptor
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'vi' ? 'vi' : 'en';
    $_SESSION['lang'] = $lang;

    // Clean URI of the lang parameter and redirect
    $uri = $_SERVER['REQUEST_URI'];
    $uri = (string) preg_replace('/[?&]lang=[^&]*/', '', $uri);
    if ($uri === '' || $uri === '/' || str_ends_with($uri, '?') || str_ends_with($uri, '&')) {
        $uri = rtrim($uri, '?&') ?: 'index.php';
    }
    header('Location: ' . $uri);
    exit;
}

$page = trim((string) ($_GET['page'] ?? 'dashboard'));
$action = trim((string) ($_GET['action'] ?? 'index'));
$method = $_SERVER['REQUEST_METHOD'];

// Support legacy slash URLs: page=bookings/my -> page=bookings, action=my
if (str_contains($page, '/')) {
    $parts = explode('/', $page, 2);
    $page = $parts[0];
    if ($action === 'index' && !empty($parts[1])) {
        $action = $parts[1];
    }
}

$postActionMap = [
    'store' => 'store', 'update' => 'update', 'delete' => 'delete', 'deactivate' => 'deactivate',
    'cancel' => 'cancel', 'approve' => 'approve', 'reject' => 'reject',
    'mark-read' => 'markRead', 'mark-all-read' => 'markAllRead',
    'generate' => 'generate', 'export-csv' => 'exportCsv',
    'export-excel' => 'exportExcel', 'export-pdf' => 'exportPdf',
    'export-schedule' => 'exportSchedule',
    'check-conflict' => 'checkConflict',
    'upload-avatar' => 'uploadAvatar',
    'export-ics' => 'exportIcs',
    'check-in' => 'checkIn',
    'confirm' => 'confirm',
    'submit' => 'submit',
    'preview-users' => 'previewUsers',
    'confirm-users' => 'confirmUsers',
    'preview-resources' => 'previewResources',
    'confirm-resources' => 'confirmResources',
    'send-reset' => 'sendReset',
    'reset' => 'reset',
    'simulate' => 'simulate',
    'cancel-recurring' => 'cancelRecurring',
    'activate' => 'activate',
    'complete' => 'complete',
    'notify-impacted' => 'notifyImpacted',
    'impact-report' => 'impactReport',
];

$routes = [
    'dashboard' => ['DashboardController', ['index' => [Middleware::class, 'auth']]],
    'forgot-password' => ['AuthController', ['index' => [Middleware::class, 'guest'], 'default' => 'forgotPassword']],
    'change-password' => ['AuthController', ['index' => [Middleware::class, 'auth'], 'default' => 'changePassword']],
    'users' => ['UserController', ['index' => [Middleware::class, 'admin'], 'create' => [Middleware::class, 'admin'], 'edit' => [Middleware::class, 'admin'], 'show' => [Middleware::class, 'admin'], 'store' => [Middleware::class, 'admin'], 'update' => [Middleware::class, 'admin'], 'deactivate' => [Middleware::class, 'admin'], 'delete' => [Middleware::class, 'admin']]],
    'resource-categories' => ['ResourceCategoryController', ['index' => [Middleware::class, 'admin'], 'create' => [Middleware::class, 'admin'], 'edit' => [Middleware::class, 'admin'], 'store' => [Middleware::class, 'admin'], 'update' => [Middleware::class, 'admin'], 'delete' => [Middleware::class, 'admin']]],
    'resources' => ['ResourceController', ['index' => [Middleware::class, 'admin'], 'create' => [Middleware::class, 'admin'], 'edit' => [Middleware::class, 'admin'], 'show' => [Middleware::class, 'auth'], 'browse' => [Middleware::class, 'auth'], 'store' => [Middleware::class, 'admin'], 'update' => [Middleware::class, 'admin'], 'delete' => [Middleware::class, 'admin']]],
    'equipment' => ['EquipmentController', ['index' => [Middleware::class, 'admin'], 'create' => [Middleware::class, 'admin'], 'edit' => [Middleware::class, 'admin'], 'store' => [Middleware::class, 'admin'], 'update' => [Middleware::class, 'admin'], 'delete' => [Middleware::class, 'admin']]],
    'time-slots' => ['TimeSlotController', ['index' => [Middleware::class, 'admin'], 'create' => [Middleware::class, 'admin'], 'edit' => [Middleware::class, 'admin'], 'store' => [Middleware::class, 'admin'], 'update' => [Middleware::class, 'admin'], 'delete' => [Middleware::class, 'admin']]],
    'booking-policies' => ['BookingPolicyController', ['index' => [Middleware::class, 'admin'], 'create' => [Middleware::class, 'admin'], 'edit' => [Middleware::class, 'admin'], 'store' => [Middleware::class, 'admin'], 'update' => [Middleware::class, 'admin'], 'delete' => [Middleware::class, 'admin'], 'simulate' => [Middleware::class, 'admin']]],
    'bookings' => ['BookingController', ['index' => [Middleware::class, 'staff'], 'create' => [Middleware::class, 'auth'], 'edit' => [Middleware::class, 'auth'], 'show' => [Middleware::class, 'auth'], 'myBookings' => [Middleware::class, 'auth'], 'mySchedule' => [Middleware::class, 'auth'], 'calendar' => [Middleware::class, 'auth'], 'store' => [Middleware::class, 'auth'], 'update' => [Middleware::class, 'auth'], 'cancel' => [Middleware::class, 'auth'], 'checkConflict' => [Middleware::class, 'auth'], 'exportSchedule' => [Middleware::class, 'auth'], 'exportIcs' => [Middleware::class, 'auth'], 'checkIn' => [Middleware::class, 'auth']]],
    'approvals' => ['ApprovalController', ['index' => [Middleware::class, 'approver'], 'show' => [Middleware::class, 'approver'], 'history' => [Middleware::class, 'approver'], 'approve' => [Middleware::class, 'approver'], 'reject' => [Middleware::class, 'approver']]],
    'cancellations' => ['CancellationController', ['index' => [Middleware::class, 'admin']]],
    'maintenance' => ['MaintenanceController', ['index' => [Middleware::class, 'admin'], 'create' => [Middleware::class, 'admin'], 'store' => [Middleware::class, 'admin'], 'edit' => [Middleware::class, 'admin'], 'update' => [Middleware::class, 'admin'], 'delete' => [Middleware::class, 'admin'], 'impactReport' => [Middleware::class, 'admin'], 'activate' => [Middleware::class, 'admin'], 'complete' => [Middleware::class, 'admin'], 'notifyImpacted' => [Middleware::class, 'admin']]],
    'notifications' => ['NotificationController', ['index' => [Middleware::class, 'auth'], 'markRead' => [Middleware::class, 'auth'], 'markAllRead' => [Middleware::class, 'auth']]],
    'reports' => ['ReportController', ['index' => [Middleware::class, 'admin'], 'generate' => [Middleware::class, 'admin'], 'exportCsv' => [Middleware::class, 'admin'], 'exportExcel' => [Middleware::class, 'admin'], 'exportPdf' => [Middleware::class, 'admin']]],
    'audit-logs' => ['AuditLogController', ['index' => [Middleware::class, 'admin']]],
    'profile' => ['ProfileController', ['index' => [Middleware::class, 'auth'], 'update' => [Middleware::class, 'auth'], 'upload-avatar' => [Middleware::class, 'auth']]],
    'settings' => ['SettingsController', ['index' => [Middleware::class, 'admin'], 'update' => [Middleware::class, 'admin']]],
    'waitlist' => ['WaitlistController', ['index' => [Middleware::class, 'auth'], 'store' => [Middleware::class, 'auth'], 'confirm' => [Middleware::class, 'auth'], 'cancel' => [Middleware::class, 'auth']]],
    'feedback' => ['FeedbackController', ['create' => [Middleware::class, 'auth'], 'store' => [Middleware::class, 'auth'], 'resource' => [Middleware::class, 'admin']]],
    'import' => ['ImportController', ['index' => [Middleware::class, 'admin'], 'previewUsers' => [Middleware::class, 'admin'], 'confirmUsers' => [Middleware::class, 'admin'], 'previewResources' => [Middleware::class, 'admin'], 'confirmResources' => [Middleware::class, 'admin']]],
    'password-reset' => ['PasswordResetController', ['index' => [Middleware::class, 'guest'], 'sendReset' => [Middleware::class, 'guest'], 'reset' => [Middleware::class, 'guest'], 'update' => [Middleware::class, 'guest']]],
];

if (!isset($routes[$page])) {
    Flash::error('Page not found.');
    redirect('index.php?page=dashboard');
}

[$controllerClass, $actions] = $routes[$page];

if ($method === 'POST') {
    $action = $postActionMap[$action] ?? $action;
}

$actionMap = [
    'my' => 'myBookings',
    'my-bookings' => 'myBookings',
    'my_bookings' => 'myBookings',
    'schedule' => 'mySchedule',
    'my-schedule' => 'mySchedule',
    'my_schedule' => 'mySchedule',
    'check-conflict' => 'checkConflict',
    'check_conflict' => 'checkConflict',
    'mark-read' => 'markRead',
    'mark_read' => 'markRead',
    'mark-all-read' => 'markAllRead',
    'mark_all_read' => 'markAllRead',
    'export-csv' => 'exportCsv',
    'export_csv' => 'exportCsv',
    'export-excel' => 'exportExcel',
    'export_excel' => 'exportExcel',
    'export-pdf' => 'exportPdf',
    'export_pdf' => 'exportPdf',
    'export-schedule' => 'exportSchedule',
    'export_schedule' => 'exportSchedule',
    'upload-avatar' => 'uploadAvatar',
    'upload_avatar' => 'uploadAvatar',
    'export-ics' => 'exportIcs',
    'export_ics' => 'exportIcs',
    'check-in' => 'checkIn',
    'check_in' => 'checkIn',
    'preview-users' => 'previewUsers',
    'preview_users' => 'previewUsers',
    'confirm-users' => 'confirmUsers',
    'confirm_users' => 'confirmUsers',
    'preview-resources' => 'previewResources',
    'preview_resources' => 'previewResources',
    'confirm-resources' => 'confirmResources',
    'confirm_resources' => 'confirmResources',
    'send-reset' => 'sendReset',
    'send_reset' => 'sendReset',
    'cancel-recurring' => 'cancelRecurring',
    'cancel_recurring' => 'cancelRecurring',
];

if (isset($actionMap[$action])) {
    $action = $actionMap[$action];
}

$methodName = $action;
if ($methodName === 'index' && isset($actions['default'])) {
    $methodName = $actions['default'];
}

if (!isset($actions[$methodName]) && !isset($actions['index'])) {
    Flash::error('Action not found.');
    redirect('index.php?page=dashboard');
}

$middleware = $actions[$methodName] ?? $actions['index'] ?? null;
if ($middleware) {
    call_user_func($middleware);
}

if (!class_exists($controllerClass)) {
    throw new RuntimeException("Controller not found: $controllerClass");
}

$controller = new $controllerClass();
if (!method_exists($controller, $methodName)) {
    Flash::error('Action not found.');
    redirect('index.php?page=dashboard');
}

$controller->$methodName();
