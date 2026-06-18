<?php

declare(strict_types=1);

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return APP_URL . ($path ? '/' . $path : '');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old_input'][$key] ?? $default;
}

function clear_old(): void
{
    unset($_SESSION['old_input']);
}

function status_badge(string $status, string $type = 'booking'): string
{
    $classes = [
        'booking' => [
            'pending' => 'bg-warning text-dark',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            'completed' => 'bg-primary',
            'expired' => 'bg-dark',
        ],
        'resource' => [
            'available' => 'bg-success',
            'unavailable' => 'bg-secondary',
            'maintenance' => 'bg-warning text-dark',
            'restricted' => 'bg-danger',
        ],
    ];
    $map = $classes[$type] ?? $classes['booking'];
    $class = $map[$status] ?? 'bg-secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

function format_datetime(?string $dt, string $format = 'd/m/Y H:i'): string
{
    if (!$dt) return '-';
    return date($format, strtotime($dt));
}

function day_name(int $day): string
{
    return ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$day] ?? '';
}

function route_url(string $page, string $action = 'index', array $params = []): string
{
    $query = ['page' => $page];
    if ($action !== 'index') {
        $query['action'] = $action;
    }
    foreach ($params as $k => $v) {
        if ($v !== '' && $v !== null) {
            $query[$k] = $v;
        }
    }
    return url('index.php?' . http_build_query($query));
}

function is_active_nav(string $page, ?string $action = null): bool
{
    $currentPage = $_GET['page'] ?? 'dashboard';
    $currentAction = $_GET['action'] ?? 'index';
    if ($currentPage !== $page) {
        return false;
    }
    if ($action === null) {
        return $currentAction === 'index';
    }
    return $currentAction === $action;
}

function paginate(int $total, int $page, int $perPage, string $baseUrl): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    return [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
        'base_url' => $baseUrl,
    ];
}
