<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';

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
    'check-conflict' => 'checkConflict',
    'upload-avatar' => 'uploadAvatar',
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
    'booking-policies' => ['BookingPolicyController', ['index' => [Middleware::class, 'admin'], 'create' => [Middleware::class, 'admin'], 'edit' => [Middleware::class, 'admin'], 'store' => [Middleware::class, 'admin'], 'update' => [Middleware::class, 'admin'], 'delete' => [Middleware::class, 'admin']]],
    'bookings' => ['BookingController', ['index' => [Middleware::class, 'staff'], 'create' => [Middleware::class, 'auth'], 'edit' => [Middleware::class, 'auth'], 'show' => [Middleware::class, 'auth'], 'myBookings' => [Middleware::class, 'auth'], 'mySchedule' => [Middleware::class, 'auth'], 'calendar' => [Middleware::class, 'auth'], 'store' => [Middleware::class, 'auth'], 'update' => [Middleware::class, 'auth'], 'cancel' => [Middleware::class, 'auth'], 'checkConflict' => [Middleware::class, 'auth']]],
    'approvals' => ['ApprovalController', ['index' => [Middleware::class, 'approver'], 'show' => [Middleware::class, 'approver'], 'history' => [Middleware::class, 'approver'], 'approve' => [Middleware::class, 'approver'], 'reject' => [Middleware::class, 'approver']]],
    'cancellations' => ['CancellationController', ['index' => [Middleware::class, 'admin']]],
    'maintenance' => ['MaintenanceController', ['index' => [Middleware::class, 'admin'], 'create' => [Middleware::class, 'admin'], 'store' => [Middleware::class, 'admin'], 'edit' => [Middleware::class, 'admin'], 'update' => [Middleware::class, 'admin'], 'delete' => [Middleware::class, 'admin']]],
    'notifications' => ['NotificationController', ['index' => [Middleware::class, 'auth'], 'markRead' => [Middleware::class, 'auth'], 'markAllRead' => [Middleware::class, 'auth']]],
    'reports' => ['ReportController', ['index' => [Middleware::class, 'admin'], 'generate' => [Middleware::class, 'admin'], 'exportCsv' => [Middleware::class, 'admin']]],
    'audit-logs' => ['AuditLogController', ['index' => [Middleware::class, 'admin']]],
    'profile' => ['ProfileController', ['index' => [Middleware::class, 'auth'], 'update' => [Middleware::class, 'auth'], 'upload-avatar' => [Middleware::class, 'auth']]],
    'settings' => ['SettingsController', ['index' => [Middleware::class, 'admin']]],
];

if (!isset($routes[$page])) {
    Flash::error('Page not found.');
    redirect('index.php?page=dashboard');
}

[$controllerClass, $actions] = $routes[$page];

if ($method === 'POST') {
    $action = $postActionMap[$action] ?? $action;
}

$getActionMap = ['check-conflict' => 'checkConflict'];
if ($method === 'GET' && isset($getActionMap[$action])) {
    $action = $getActionMap[$action];
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
