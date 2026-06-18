<?php

declare(strict_types=1);

class ResourceCategoryController extends Controller
{
    private ResourceCategoryRepository $categoryRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->categoryRepo = new ResourceCategoryRepository();
        $this->auditLog = new AuditLogService();
    }

    public function index(): void
    {
        Middleware::admin();

        $filters = [
            'search' => trim((string) ($this->get()['search'] ?? '')),
            'status' => $this->get()['status'] ?? '',
        ];

        $categories = $this->categoryRepo->findAll($filters);

        $this->view('resource_categories/index', [
            'title' => 'Resource Categories',
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        Middleware::admin();

        $this->view('resource_categories/create', [
            'title' => 'Add Category',
        ]);
    }

    public function store(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $data = $this->post();
        $errors = $this->validateCategory($data);

        if (!empty($errors)) {
            Flash::error($errors[0]);
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=resource-categories&action=create');
        }

        try {
            $id = $this->categoryRepo->create($this->normalizeCategoryData($data));
            $this->auditLog->log(
                'create_resource',
                'resource_categories',
                $id,
                null,
                ['category_name' => $data['category_name']]
            );
            Flash::success('Category created successfully.');
            redirect('index.php?page=resource-categories');
        } catch (Exception $e) {
            Flash::error('Failed to create category. Category name may already exist.');
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=resource-categories&action=create');
        }
    }

    public function edit(): void
    {
        Middleware::admin();

        $id = $this->requireCategoryId();
        $category = $this->categoryRepo->findById($id);
        if (!$category) {
            Flash::error('Category not found.');
            redirect('index.php?page=resource-categories');
        }

        $this->view('resource_categories/edit', [
            'title' => 'Edit Category',
            'category' => $category,
        ]);
    }

    public function update(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requireCategoryId();
        $category = $this->categoryRepo->findById($id);
        if (!$category) {
            Flash::error('Category not found.');
            redirect('index.php?page=resource-categories');
        }

        $data = $this->post();
        $errors = $this->validateCategory($data);

        if (!empty($errors)) {
            Flash::error($errors[0]);
            redirect('index.php?page=resource-categories&action=edit&id=' . $id);
        }

        try {
            $this->categoryRepo->update($id, $this->normalizeCategoryData($data));
            $this->auditLog->log(
                'update_resource',
                'resource_categories',
                $id,
                ['category_name' => $category['category_name']],
                ['category_name' => $data['category_name']]
            );
            Flash::success('Category updated successfully.');
            redirect('index.php?page=resource-categories');
        } catch (Exception $e) {
            Flash::error('Failed to update category.');
            redirect('index.php?page=resource-categories&action=edit&id=' . $id);
        }
    }

    public function delete(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requireCategoryId();

        if ($this->categoryRepo->hasResources($id)) {
            Flash::error('Cannot delete this category because it is referenced by existing resources or bookings.');
            $this->auditLog->log('delete_attempt', 'resource_categories', $id, null, 'Has resources');
            redirect('index.php?page=resource-categories');
        }

        try {
            $this->categoryRepo->delete($id);
            Flash::success('Category deleted successfully.');
        } catch (Exception $e) {
            Flash::error('Failed to delete category.');
        }

        redirect('index.php?page=resource-categories');
    }

    private function requireCategoryId(): int
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            Flash::error('Invalid category ID.');
            redirect('index.php?page=resource-categories');
        }
        return $id;
    }

    private function validateCategory(array $data): array
    {
        $validator = new Validator($data);
        $validator->required('category_name', 'Category name');

        if ($validator->fails()) {
            return [$validator->firstError() ?? 'Validation failed.'];
        }

        return [];
    }

    private function normalizeCategoryData(array $data): array
    {
        return [
            'category_name' => trim((string) $data['category_name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'requires_approval' => (int) ($data['requires_approval'] ?? 0),
            'max_booking_hours_per_day' => (float) ($data['max_booking_hours_per_day'] ?? 4),
            'max_booking_hours_per_week' => (float) ($data['max_booking_hours_per_week'] ?? 10),
            'max_peak_slots_per_week' => (int) ($data['max_peak_slots_per_week'] ?? 2),
            'cancellation_deadline_hours' => (int) ($data['cancellation_deadline_hours'] ?? 24),
            'status' => $data['status'] ?? 'active',
        ];
    }
}
