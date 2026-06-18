<?php
declare(strict_types=1);

class AuthService
{
    private UserRepository $userRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->auditLog = new AuditLogService();
    }

    public function login(string $login, string $password): array
    {
        $user = $this->userRepo->findByLogin($login);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Your account is not active.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        unset($user['password_hash']);
        $roles = $this->userRepo->getRoles((int) $user['id']);
        Auth::login($user, $roles);

        $this->auditLog->log('login', 'users', (int) $user['id'], null, [
            'email' => $user['email'],
            'username' => $user['username'],
        ], (int) $user['id']);

        return ['success' => true, 'user' => $user, 'roles' => $roles];
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

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters.'];
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

        $this->userRepo->update($userId, ['password' => $newPassword], $roleIds);

        $this->auditLog->log('change_password', 'users', $userId, null, [
            'changed_at' => date('Y-m-d H:i:s'),
        ], $userId);

        return ['success' => true, 'message' => 'Password changed successfully.'];
    }
}
