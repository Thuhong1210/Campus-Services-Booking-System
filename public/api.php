<?php
declare(strict_types=1);

/**
 * REST API Entry Point
 * Returns JSON responses for AJAX-driven features.
 * Authentication uses the existing session.
 */
require_once dirname(__DIR__) . '/app/config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$endpoint = trim((string) ($_GET['endpoint'] ?? ''));
$method   = $_SERVER['REQUEST_METHOD'];

// Simple rate limiting via session
$rateLimitKey = 'api_calls_' . date('H:i');
$_SESSION[$rateLimitKey] = ($_SESSION[$rateLimitKey] ?? 0) + 1;
if ($_SESSION[$rateLimitKey] > 200) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please slow down.']);
    exit;
}

// Auth check helper (some endpoints are public)
$publicEndpoints = ['resources', 'resources/availability', 'resources/list'];
$isAuth = Auth::check();
if (!$isAuth && !in_array($endpoint, $publicEndpoints, true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$user   = Auth::user();
$userId = $user ? (int) $user['id'] : 0;
$role   = Auth::primaryRole();

try {
    $controller = new ApiController($userId, $role);
    $result = match (true) {
        // ── Resources ────────────────────────────────────────────────
        $endpoint === 'resources' && $method === 'GET'
            => $controller->getResources(),

        $endpoint === 'resources/list' && $method === 'GET'
            => $controller->getResources(),

        $endpoint === 'resources/availability' && $method === 'GET'
            => $controller->checkAvailability(),

        // ── Bookings ─────────────────────────────────────────────────
        $endpoint === 'bookings' && $method === 'GET'
            => $controller->getBookings(),

        $endpoint === 'bookings/create' && $method === 'POST'
            => $controller->createBooking(),

        $endpoint === 'bookings/cancel' && $method === 'POST'
            => $controller->cancelBooking(),

        // ── Waitlist ─────────────────────────────────────────────────
        $endpoint === 'waitlist' && $method === 'GET'
            => $controller->getWaitlist(),

        $endpoint === 'waitlist/join' && $method === 'POST'
            => $controller->joinWaitlist(),

        // ── Dashboard ────────────────────────────────────────────────
        $endpoint === 'dashboard/stats' && $method === 'GET'
            => $controller->getDashboardStats(),

        // ── Notifications ─────────────────────────────────────────────
        $endpoint === 'notifications' && $method === 'GET'
            => $controller->getNotifications(),

        $endpoint === 'notifications/mark-read' && $method === 'POST'
            => $controller->markNotificationRead(),

        $endpoint === 'notifications/unread-count' && $method === 'GET'
            => $controller->getUnreadCount(),

        // ── Calendar ─────────────────────────────────────────────────
        $endpoint === 'calendar/events' && $method === 'GET'
            => $controller->getCalendarEvents(),

        // ── Equipment ────────────────────────────────────────────────
        $endpoint === 'equipment/available' && $method === 'GET'
            => $controller->getAvailableEquipment(),

        default => (function () use ($endpoint) {
            http_response_code(404);
            return ['error' => "Unknown endpoint: $endpoint"];
        })(),
    };

    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Internal server error.',
        'details' => APP_DEBUG ? $e->getMessage() : null,
    ]);
}
