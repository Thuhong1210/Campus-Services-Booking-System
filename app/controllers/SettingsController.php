<?php

declare(strict_types=1);

class SettingsController extends Controller
{
    public function index(): void
    {
        Middleware::admin();

        $settings = [
            'app_name' => APP_NAME,
            'app_url' => APP_URL,
            'timezone' => date_default_timezone_get(),
            'session_name' => SESSION_NAME,
            'peak_hour_limit' => 2,
            'default_cancellation_deadline' => 24,
        ];

        $this->view('settings/index', [
            'title' => 'System Settings',
            'settings' => $settings,
        ]);
    }
}
