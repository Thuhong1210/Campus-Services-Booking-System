<?php

declare(strict_types=1);

class Middleware
{
    public static function auth(): void
    {
        if (!Auth::check()) {
            Flash::error('Please log in to continue.');
            redirect('login.php');
        }
    }

    public static function guest(): void
    {
        if (Auth::check()) {
            redirect('index.php?page=dashboard');
        }
    }

    public static function role(array $roles): void
    {
        self::auth();
        if (!Auth::hasAnyRole($roles)) {
            Flash::error('You do not have permission to perform this action.');
            redirect('index.php?page=dashboard');
        }
    }

    public static function admin(): void
    {
        self::role(['Admin']);
    }

    public static function staff(): void
    {
        self::role(['Admin', 'Staff']);
    }

    public static function approver(): void
    {
        self::role(['Admin', 'Lecturer', 'Approver']);
    }
}
