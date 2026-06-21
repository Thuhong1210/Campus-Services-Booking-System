<?php
declare(strict_types=1);

class AuthService
{
    private UserRepository $userRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->userRepo  = new UserRepository();
        $this->auditLog  = new AuditLogService();
    }

    public function login(string $login, string $password, string $ip = ''): array
    {
        $maxAttempts   = (int) setting('max_login_attempts', 5);
        $lockoutMinutes = (int) setting('lockout_duration_minutes', 30);
        $db = Database::getInstance()->getConnection();

        // Check brute force from login_attempts table
        $windowStart = date('Y-m-d H:i:s', time() - $lockoutMinutes * 60);
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier = ? AND attempted_at > ?'
        );
        $stmt->execute([$login, $windowStart]);
        $recentAttempts = (int) $stmt->fetchColumn();

        if ($recentAttempts >= $maxAttempts) {
            return [
                'success' => false,
                'message' => "Too many failed login attempts. Please wait $lockoutMinutes minute(s) before trying again.",
            ];
        }

        $user = $this->userRepo->findByLogin($login);

        if (!$user) {
            $this->recordFailedAttempt($login, $ip);
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        // Check if account is locked
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $until = date('H:i', strtotime($user['locked_until']));
            return ['success' => false, 'message' => "Account locked until $until. Please try again later."];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Your account is not active.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($login, $ip);

            // Lock account after max attempts
            $stmt2 = $db->prepare(
                'SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at > ?'
            );
            $stmt2->execute([$login, $windowStart]);
            $total = (int) $stmt2->fetchColumn();

            if ($total >= $maxAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', time() + $lockoutMinutes * 60);
                $this->userRepo->update((int) $user['id'], ['locked_until' => $lockedUntil], null);
                return [
                    'success' => false,
                    'message' => "Too many failed attempts. Account locked for $lockoutMinutes minute(s).",
                ];
            }

            $remaining = $maxAttempts - $total;
            return ['success' => false, 'message' => "Invalid credentials. $remaining attempt(s) remaining."];
        }

        // Successful login: clear attempts and update last login
        $db->prepare('DELETE FROM login_attempts WHERE identifier = ?')->execute([$login]);
        $this->userRepo->update((int) $user['id'], [
            'locked_until'         => null,
            'failed_login_attempts'=> 0,
            'last_login_at'        => date('Y-m-d H:i:s'),
        ], null);

        unset($user['password_hash']);
        $roles = $this->userRepo->getRoles((int) $user['id']);
        Auth::login($user, $roles);

        // Refresh session to prevent session fixation
        session_regenerate_id(true);

        $this->auditLog->log('login', 'users', (int) $user['id'], null, [
            'email'    => $user['email'],
            'username' => $user['username'],
            'ip'       => $ip,
        ], (int) $user['id']);

        return ['success' => true, 'user' => $user, 'roles' => $roles];
    }

    private function recordFailedAttempt(string $identifier, string $ip): void
    {
        try {
            $db = Database::getInstance()->getConnection();
            $db->prepare('INSERT INTO login_attempts (identifier, ip_address) VALUES (?, ?)')
               ->execute([$identifier, $ip]);
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function logout(): void
    {
        $userId = Auth::id();
        if ($userId) {
            $this->auditLog->log('logout', 'users', $userId, null, null, $userId);
        }
        Auth::logout();
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->userRepo->findById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
        }

        $roles = $this->userRepo->getRoles($userId);
        $roleIds = [];
        $roleRepo = new RoleRepository();
        foreach ($roles as $roleName) {
            $role = $roleRepo->findByName($roleName);
            if ($role) {
                $roleIds[] = (int) $role['id'];
            }
        }

        $this->userRepo->update($userId, ['password' => $newPassword, 'must_change_password' => 0], $roleIds);

        $this->auditLog->log('change_password', 'users', $userId, null, [
            'changed_at' => date('Y-m-d H:i:s'),
        ], $userId);

        return ['success' => true, 'message' => 'Password changed successfully.'];
    }

    /**
     * Create a password reset token for a user (by email).
     */
    public function createPasswordResetToken(string $email): array
    {
        $user = $this->userRepo->findByEmail($email);
        if (!$user) {
            // Don't reveal whether user exists
            return ['success' => true, 'message' => 'If that email is registered, a reset link has been sent.'];
        }

        $db    = Database::getInstance()->getConnection();
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        // Invalidate old tokens
        $db->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0')
           ->execute([$user['id']]);

        $db->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)'
        )->execute([$user['id'], $token, $expires]);

        // Store as notification (in-system "email")
        $notifService = new NotificationService();
        $resetUrl = APP_URL . '/index.php?page=password-reset&action=reset&token=' . $token;
        $notifService->notify(
            (int) $user['id'],
            'Password Reset Request',
            "A password reset was requested. Use this link to reset your password (expires in 1 hour): $resetUrl",
            'system'
        );

        $this->auditLog->log('password_reset_request', 'users', (int) $user['id'], null, [
            'email' => $email,
        ]);

        return ['success' => true, 'message' => 'If that email is registered, a reset link has been sent.', 'token' => $token];
    }

    /**
     * Validate reset token and update password.
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
        }

        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            return ['success' => false, 'message' => 'Invalid or expired reset token.'];
        }

        $userId = (int) $reset['user_id'];
        $roleRepo = new RoleRepository();
        $roles = $this->userRepo->getRoles($userId);
        $roleIds = [];
        foreach ($roles as $roleName) {
            $role = $roleRepo->findByName($roleName);
            if ($role) $roleIds[] = (int) $role['id'];
        }

        $this->userRepo->update($userId, ['password' => $newPassword, 'must_change_password' => 0], $roleIds);
        $db->prepare('UPDATE password_resets SET used = 1 WHERE token = ?')->execute([$token]);

        $this->auditLog->log('password_reset', 'users', $userId, null, ['token_used' => substr($token, 0, 8) . '...']);

        return ['success' => true, 'message' => 'Password reset successfully. You can now log in.'];
    }
}
