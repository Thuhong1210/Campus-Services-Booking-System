<?php

declare(strict_types=1);

class UserController extends Controller
{
    private UserRepository $userRepo;
    private RoleRepository $roleRepo;
    private DepartmentRepository $departmentRepo;
    private BookingRepository $bookingRepo;
    private ApprovalRepository $approvalRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->roleRepo = new RoleRepository();
        $this->departmentRepo = new DepartmentRepository();
        $this->bookingRepo = new BookingRepository();
        $this->approvalRepo = new ApprovalRepository();
        $this->auditLog = new AuditLogService();
    }

    public function index(): void
    {
        Middleware::admin();

        $filters = [
            'search' => trim((string) ($this->get()['search'] ?? '')),
            'status' => $this->get()['status'] ?? '',
            'department_id' => $this->get()['department_id'] ?? '',
            'role' => $this->get()['role'] ?? '',
        ];

        $page = max(1, (int) ($this->get()['page'] ?? 1));
        $perPage = 20;
        $total = $this->userRepo->count($filters);
        $pagination = paginate($total, $page, $perPage, 'index.php?page=users');

        $users = $this->userRepo->all($filters, $perPage, $pagination['offset']);

        $this->view('users/index', [
            'title' => 'User Management',
            'users' => $users,
            'filters' => $filters,
            'pagination' => $pagination,
            'departments' => $this->departmentRepo->findAll(),
            'roles' => $this->roleRepo->findAll(),
        ]);
    }

    public function create(): void
    {
        Middleware::admin();

        $this->view('users/create', [
            'title' => 'Add New User',
            'departments' => $this->departmentRepo->findAll(),
            'roles' => $this->roleRepo->findAll(),
        ]);
    }

    public function store(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $data = $this->post();
        $errors = $this->validateUserData($data);

        if (!empty($errors)) {
            Flash::error($errors[0]);
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=users&action=create');
        }

        $roleIds = array_map('intval', (array) ($data['role_ids'] ?? []));
        if (empty($roleIds) && !empty($data['role_id'])) {
            $roleIds = [(int) $data['role_id']];
        }
        if (empty($roleIds)) {
            Flash::error('At least one role is required.');
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=users&action=create');
        }

        try {
            $userId = $this->userRepo->create($data, $roleIds);
            $this->auditLog->log(
                'create_user',
                'users',
                $userId,
                null,
                ['email' => $data['email'], 'username' => $data['username']]
            );
            Flash::success('User created successfully.');
            redirect('index.php?page=users');
        } catch (Exception $e) {
            Flash::error('Failed to create user. Please try again.');
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=users&action=create');
        }
    }

    public function edit(): void
    {
        Middleware::admin();

        $id = $this->requireUserId();
        $user = $this->userRepo->findById($id);
        if (!$user) {
            Flash::error('User not found.');
            redirect('index.php?page=users');
        }

        $user['roles'] = $this->userRepo->getRoles($id);

        $this->view('users/edit', [
            'title' => 'Edit User',
            'user' => $user,
            'departments' => $this->departmentRepo->findAll(),
            'roles' => $this->roleRepo->findAll(),
        ]);
    }

    public function update(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requireUserId();
        $user = $this->userRepo->findById($id);
        if (!$user) {
            Flash::error('User not found.');
            redirect('index.php?page=users');
        }

        $data = $this->post();
        $errors = $this->validateUserData($data, $id, !empty($data['password']));

        if (!empty($errors)) {
            Flash::error($errors[0]);
            redirect('index.php?page=users&action=edit&id=' . $id);
        }

        $roleIds = array_map('intval', (array) ($data['role_ids'] ?? []));
        if (empty($roleIds) && !empty($data['role_id'])) {
            $roleIds = [(int) $data['role_id']];
        }
        if (empty($roleIds)) {
            Flash::error('At least one role is required.');
            redirect('index.php?page=users&action=edit&id=' . $id);
        }

        try {
            $this->userRepo->update($id, $data, $roleIds);
            $this->auditLog->log(
                'update_user',
                'users',
                $id,
                ['email' => $user['email']],
                ['email' => $data['email']]
            );
            Flash::success('User updated successfully.');
            redirect('index.php?page=users');
        } catch (Exception $e) {
            Flash::error('Failed to update user. Please try again.');
            redirect('index.php?page=users&action=edit&id=' . $id);
        }
    }

    public function show(): void
    {
        Middleware::admin();

        $id = $this->requireUserId();
        $user = $this->userRepo->findById($id);
        if (!$user) {
            Flash::error('User not found.');
            redirect('index.php?page=users');
        }

        $user['roles'] = $this->userRepo->getRoles($id);
        $recentBookings = $this->bookingRepo->findByUser($id, [], 10, 0);
        $approvalHistory = $this->approvalRepo->findHistory(['approver_id' => $id], 10, 0);

        $this->view('users/detail', [
            'title' => 'User Details',
            'user' => $user,
            'recentBookings' => $recentBookings,
            'approvalHistory' => $approvalHistory,
        ]);
    }

    public function deactivate(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requireUserId();
        if ($id === Auth::id()) {
            Flash::error('You cannot deactivate your own account.');
            redirect('index.php?page=users');
        }

        $user = $this->userRepo->findById($id);
        if (!$user) {
            Flash::error('User not found.');
            redirect('index.php?page=users');
        }

        $this->userRepo->deactivate($id);
        $this->auditLog->log(
            'deactivate_user',
            'users',
            $id,
            ['status' => $user['status']],
            ['status' => 'inactive']
        );
        Flash::success('User deactivated successfully.');
        redirect('index.php?page=users');
    }

    public function delete(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requireUserId();
        if ($id === Auth::id()) {
            Flash::error('You cannot delete your own account.');
            redirect('index.php?page=users');
        }

        if ($this->userRepo->hasBookings($id)) {
            Flash::error('Cannot delete this user because they have existing bookings.');
            $this->auditLog->log('delete_attempt', 'users', $id, null, 'Has existing bookings');
            redirect('index.php?page=users');
        }

        try {
            $this->userRepo->delete($id);
            $this->auditLog->log('delete_attempt', 'users', $id, null, 'Deleted');
            Flash::success('User deleted successfully.');
        } catch (Exception $e) {
            Flash::error('Failed to delete user.');
        }

        redirect('index.php?page=users');
    }

    private function requireUserId(): int
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            Flash::error('Invalid user ID.');
            redirect('index.php?page=users');
        }
        return $id;
    }

    private function validateUserData(array $data, ?int $excludeId = null, bool $requirePassword = true): array
    {
        $errors = [];
        $validator = new Validator($data);
        $validator
            ->required('full_name', 'Full name')
            ->required('email', 'Email')
            ->email('email')
            ->required('username', 'Username');

        if ($requirePassword) {
            $validator->required('password', 'Password')->min('password', 8, 'Password');
        } elseif (!empty($data['password'])) {
            $validator->min('password', 8, 'Password');
        }

        if ($validator->fails()) {
            return [$validator->firstError() ?? 'Validation failed.'];
        }

        if ($this->userRepo->exists('email', $data['email'], $excludeId)) {
            $errors[] = 'Email already exists.';
        }
        if ($this->userRepo->exists('username', $data['username'], $excludeId)) {
            $errors[] = 'Username already exists.';
        }
        if (!empty($data['student_code']) && $this->userRepo->exists('student_code', $data['student_code'], $excludeId)) {
            $errors[] = 'Student ID already exists.';
        }
        if (!empty($data['staff_code']) && $this->userRepo->exists('staff_code', $data['staff_code'], $excludeId)) {
            $errors[] = 'Staff ID already exists.';
        }

        return $errors;
    }
}
