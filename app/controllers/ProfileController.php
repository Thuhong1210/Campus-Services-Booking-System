<?php

declare(strict_types=1);

class ProfileController extends Controller
{
    private UserRepository $userRepo;
    private DepartmentRepository $departmentRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->departmentRepo = new DepartmentRepository();
        $this->auditLog = new AuditLogService();
    }

    public function index(): void
    {
        Middleware::auth();

        $userId = (int) Auth::id();
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            Flash::error('User profile not found.');
            redirect('index.php?page=dashboard');
        }

        $user['roles'] = $this->userRepo->getRoles($userId);

        $this->view('profile/index', [
            'title' => 'My Profile',
            'user' => $user,
            'departments' => $this->departmentRepo->findAll(),
        ]);
    }

    public function update(): void
    {
        Middleware::auth();
        $this->verifyCsrf();

        $userId = (int) Auth::id();
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            Flash::error('User profile not found.');
            redirect('index.php?page=dashboard');
        }

        $data = $this->post();
        $validator = new Validator($data);
        $validator
            ->required('full_name', 'Full name')
            ->required('email', 'Email')
            ->email('email');

        if ($validator->fails()) {
            Flash::error($validator->firstError() ?? 'Validation failed.');
            redirect('index.php?page=profile');
        }

        if ($this->userRepo->exists('email', $data['email'], $userId)) {
            Flash::error('Email already exists.');
            redirect('index.php?page=profile');
        }

        $updateData = [
            'full_name' => trim((string) $data['full_name']),
            'email' => trim((string) $data['email']),
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'department_id' => !empty($data['department_id']) ? (int) $data['department_id'] : null,
        ];

        try {
            $this->userRepo->update($userId, $updateData, $this->getCurrentRoleIds($userId));

            $_SESSION['user'] = array_merge($_SESSION['user'], [
                'full_name' => $updateData['full_name'],
                'email' => $updateData['email'],
                'phone' => $updateData['phone'],
            ]);

            $this->auditLog->log(
                'update_user',
                'users',
                $userId,
                ['email' => $user['email']],
                ['email' => $updateData['email']]
            );

            Flash::success('Profile updated successfully.');
        } catch (Exception $e) {
            Flash::error('Failed to update profile.');
        }

        redirect('index.php?page=profile');
    }

    private function getCurrentRoleIds(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT role_id FROM user_roles WHERE user_id = ?');
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
