<?php

declare(strict_types=1);

class BookingPolicyController extends Controller
{
    private BookingPolicyRepository $policyRepo;
    private ResourceCategoryRepository $categoryRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->policyRepo = new BookingPolicyRepository();
        $this->categoryRepo = new ResourceCategoryRepository();
        $this->auditLog = new AuditLogService();
    }

    public function index(): void
    {
        Middleware::admin();

        $filters = [
            'category_id' => $this->get()['category_id'] ?? '',
            'is_active' => $this->get()['is_active'] ?? '',
        ];

        if ($filters['is_active'] !== '') {
            $filters['is_active'] = (int) $filters['is_active'];
        }

        $policies = $this->policyRepo->findAll($filters);

        $this->view('booking_policies/index', [
            'title' => 'Booking Policies',
            'policies' => $policies,
            'filters' => $filters,
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
        ]);
    }

    public function create(): void
    {
        Middleware::admin();

        $this->view('booking_policies/create', [
            'title' => 'Add Booking Policy',
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
        ]);
    }

    public function store(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $data = $this->post();
        $errors = $this->validatePolicy($data);

        if (!empty($errors)) {
            Flash::error($errors[0]);
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=booking-policies&action=create');
        }

        try {
            $id = $this->policyRepo->create($this->normalizePolicyData($data));
            $this->auditLog->log(
                'update_policy',
                'booking_policies',
                $id,
                null,
                ['policy_name' => $data['policy_name']]
            );
            Flash::success('Booking policy created successfully.');
            redirect('index.php?page=booking-policies');
        } catch (Exception $e) {
            Flash::error('Failed to create booking policy.');
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=booking-policies&action=create');
        }
    }

    public function edit(): void
    {
        Middleware::admin();

        $id = $this->requirePolicyId();
        $policy = $this->policyRepo->findById($id);
        if (!$policy) {
            Flash::error('Booking policy not found.');
            redirect('index.php?page=booking-policies');
        }

        $this->view('booking_policies/edit', [
            'title' => 'Edit Booking Policy',
            'policy' => $policy,
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
        ]);
    }

    public function update(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requirePolicyId();
        $policy = $this->policyRepo->findById($id);
        if (!$policy) {
            Flash::error('Booking policy not found.');
            redirect('index.php?page=booking-policies');
        }

        $data = $this->post();
        $errors = $this->validatePolicy($data);

        if (!empty($errors)) {
            Flash::error($errors[0]);
            redirect('index.php?page=booking-policies&action=edit&id=' . $id);
        }

        try {
            $this->policyRepo->update($id, $this->normalizePolicyData($data));
            $this->auditLog->log(
                'update_policy',
                'booking_policies',
                $id,
                ['policy_name' => $policy['policy_name']],
                ['policy_name' => $data['policy_name']]
            );
            Flash::success('Booking policy updated successfully.');
            redirect('index.php?page=booking-policies');
        } catch (Exception $e) {
            Flash::error('Failed to update booking policy.');
            redirect('index.php?page=booking-policies&action=edit&id=' . $id);
        }
    }

    public function delete(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requirePolicyId();

        try {
            $this->policyRepo->delete($id);
            Flash::success('Booking policy deleted successfully.');
        } catch (Exception $e) {
            Flash::error('Failed to delete booking policy.');
        }

        redirect('index.php?page=booking-policies');
    }

    private function requirePolicyId(): int
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            Flash::error('Invalid policy ID.');
            redirect('index.php?page=booking-policies');
        }
        return $id;
    }

    private function validatePolicy(array $data): array
    {
        $validator = new Validator($data);
        $validator
            ->required('category_id', 'Category')
            ->required('policy_name', 'Policy name')
            ->required('max_duration_hours', 'Max duration')
            ->numeric('max_duration_hours');

        if ($validator->fails()) {
            return [$validator->firstError() ?? 'Validation failed.'];
        }

        return [];
    }

    private function normalizePolicyData(array $data): array
    {
        return [
            'category_id' => (int) $data['category_id'],
            'policy_name' => trim((string) $data['policy_name']),
            'max_duration_hours' => (float) ($data['max_duration_hours'] ?? 2),
            'weekly_quota' => (int) ($data['weekly_quota'] ?? 5),
            'max_peak_slots_per_week' => (int) ($data['max_peak_slots_per_week'] ?? 2),
            'cancellation_deadline_hours' => (int) ($data['cancellation_deadline_hours'] ?? 24),
            'requires_approval' => (int) ($data['requires_approval'] ?? 0),
            'auto_approval_enabled' => (int) ($data['auto_approval_enabled'] ?? 0),
            'is_active' => (int) ($data['is_active'] ?? 1),
        ];
    }
}
