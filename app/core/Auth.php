<?php

declare(strict_types=1);

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user, array $roles): void
    {
        $_SESSION['user'] = $user;
        $_SESSION['roles'] = $roles;
    }

    public static function logout(): void
    {
        unset($_SESSION['user'], $_SESSION['roles']);
    }

    public static function roles(): array
    {
        return $_SESSION['roles'] ?? [];
    }

    public static function hasRole(string $role): bool
    {
        return in_array($role, self::roles(), true);
    }

    public static function hasAnyRole(array $roles): bool
    {
        return !empty(array_intersect($roles, self::roles()));
    }

    public static function isAdmin(): bool
    {
        return self::hasRole('Admin');
    }

    public static function primaryRole(): string
    {
        $priority = ['Admin', 'Lecturer', 'Approver', 'Staff', 'Student'];
        foreach ($priority as $role) {
            if (self::hasRole($role)) {
                return $role;
            }
        }
        return self::roles()[0] ?? 'Student';
    }
}
