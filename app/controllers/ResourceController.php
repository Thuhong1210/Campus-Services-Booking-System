<?php

declare(strict_types=1);

class ResourceController extends Controller
{
    private ResourceRepository $resourceRepo;
    private ResourceCategoryRepository $categoryRepo;
    private TimeSlotRepository $timeSlotRepo;
    private BookingRepository $bookingRepo;
    private BookingPolicyRepository $policyRepo;
    private AuditLogService $auditLog;

    private EquipmentRepository $equipmentRepo;

    public function __construct()
    {
        $this->resourceRepo = new ResourceRepository();
        $this->categoryRepo = new ResourceCategoryRepository();
        $this->timeSlotRepo = new TimeSlotRepository();
        $this->bookingRepo = new BookingRepository();
        $this->policyRepo = new BookingPolicyRepository();
        $this->auditLog = new AuditLogService();
        $this->equipmentRepo = new EquipmentRepository();
    }

    public function index(): void
    {
        Middleware::admin();

        $filters = [
            'search' => trim((string) ($this->get()['search'] ?? '')),
            'category_id' => $this->get()['category_id'] ?? '',
            'status' => $this->get()['status'] ?? '',
        ];

        $page = max(1, (int) ($this->get()['p'] ?? 1));
        $perPage = 20;
        $total = $this->resourceRepo->count($filters);
        $pagination = paginate($total, $page, $perPage, 'index.php?page=resources');

        $resources = $this->resourceRepo->findAll($filters, $perPage, $pagination['offset']);

        $this->view('resources/index', [
            'title' => 'Resource Management',
            'resources' => $resources,
            'filters' => $filters,
            'pagination' => $pagination,
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
        ]);
    }

    public function create(): void
    {
        Middleware::admin();

        $this->view('resources/create', [
            'title' => 'Add Resource',
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
            'equipmentList' => $this->equipmentRepo->findAll(),
        ]);
    }

    public function store(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $data = $this->post();
        $errors = $this->validateResource($data);

        if (!empty($errors)) {
            Flash::error($errors[0]);
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=resources&action=create');
        }

        try {
            $id = $this->resourceRepo->create($this->normalizeResourceData($data));
            $this->resourceRepo->syncEquipment($id, $data['equipment_ids'] ?? []);
            $this->auditLog->log(
                'create_resource',
                'resources',
                $id,
                null,
                ['resource_code' => $data['resource_code']]
            );
            Flash::success('Resource created successfully.');
            redirect('index.php?page=resources');
        } catch (Exception $e) {
            Flash::error('Failed to create resource. Resource code may already exist.');
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=resources&action=create');
        }
    }

    public function edit(): void
    {
        Middleware::admin();

        $id = $this->requireResourceId();
        $resource = $this->resourceRepo->findById($id);
        if (!$resource) {
            Flash::error('Resource not found.');
            redirect('index.php?page=resources');
        }

        $this->view('resources/edit', [
            'title' => 'Edit Resource',
            'resource' => $resource,
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
            'equipmentList' => $this->equipmentRepo->findAll(),
            'assignedEquipment' => array_column($this->resourceRepo->getEquipment($id), 'id'),
        ]);
    }

    public function update(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requireResourceId();
        $resource = $this->resourceRepo->findById($id);
        if (!$resource) {
            Flash::error('Resource not found.');
            redirect('index.php?page=resources');
        }

        $data = $this->post();
        $errors = $this->validateResource($data, $id);

        if (!empty($errors)) {
            Flash::error($errors[0]);
            redirect('index.php?page=resources&action=edit&id=' . $id);
        }

        try {
            $this->resourceRepo->update($id, $this->normalizeResourceData($data));
            $this->resourceRepo->syncEquipment($id, $data['equipment_ids'] ?? []);
            $this->auditLog->log(
                'update_resource',
                'resources',
                $id,
                ['resource_code' => $resource['resource_code']],
                ['resource_code' => $data['resource_code']]
            );
            Flash::success('Resource updated successfully.');
            redirect('index.php?page=resources');
        } catch (Exception $e) {
            Flash::error('Failed to update resource.');
            redirect('index.php?page=resources&action=edit&id=' . $id);
        }
    }

    public function show(): void
    {
        Middleware::auth();

        $id = $this->requireResourceId();
        $resource = $this->resourceRepo->findById($id);
        if (!$resource) {
            Flash::error('Resource not found.');
            redirect(Auth::isAdmin() ? 'index.php?page=resources' : 'index.php?page=resources&action=browse');
        }

        $equipment = $this->resourceRepo->getEquipment($id);
        $timeSlots = $this->timeSlotRepo->findByResource($id);
        $policy = $this->policyRepo->findByCategory((int) $resource['category_id']);
        $currentBookings = $this->bookingRepo->findAll(['resource_id' => (string) $id], 10, 0);

        $this->view('resources/detail', [
            'title' => $resource['resource_name'],
            'resource' => $resource,
            'equipment' => $equipment,
            'timeSlots' => $timeSlots,
            'policy' => $policy,
            'currentBookings' => $currentBookings,
        ]);
    }

    public function delete(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requireResourceId();

        if ($this->resourceRepo->hasBookings($id)) {
            Flash::error('Cannot delete this record because it is referenced by existing bookings.');
            $this->auditLog->log('delete_attempt', 'resources', $id, null, 'Has bookings');
            redirect('index.php?page=resources');
        }

        try {
            $this->resourceRepo->delete($id);
            Flash::success('Resource deleted successfully.');
        } catch (Exception $e) {
            Flash::error('Failed to delete resource.');
        }

        redirect('index.php?page=resources');
    }

    public function browse(): void
    {
        Middleware::auth();

        $filters = [
            'search' => trim((string) ($this->get()['search'] ?? '')),
            'category_id' => $this->get()['category_id'] ?? '',
            'location' => trim((string) ($this->get()['location'] ?? '')),
        ];

        $resources = $this->resourceRepo->findAvailable($filters);

        $this->view('resources/browse', [
            'title' => 'Browse Resources',
            'resources' => $resources,
            'filters' => $filters,
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
        ]);
    }

    private function requireResourceId(): int
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            Flash::error('Invalid resource ID.');
            redirect('index.php?page=resources');
        }
        return $id;
    }

    private function validateResource(array $data, ?int $excludeId = null): array
    {
        $validator = new Validator($data);
        $validator
            ->required('resource_name', 'Resource name')
            ->required('resource_code', 'Resource code')
            ->required('category_id', 'Category')
            ->required('location', 'Location');

        if ($validator->fails()) {
            return [$validator->firstError() ?? 'Validation failed.'];
        }

        $stmt = Database::getInstance()->getConnection()->prepare(
            'SELECT COUNT(*) FROM resources WHERE resource_code = ?' . ($excludeId ? ' AND id != ?' : '')
        );
        $params = [$data['resource_code']];
        if ($excludeId) {
            $params[] = $excludeId;
        }
        $stmt->execute($params);
        if ((int) $stmt->fetchColumn() > 0) {
            return ['Resource code already exists.'];
        }

        return [];
    }

    private function normalizeResourceData(array $data): array
    {
        return [
            'category_id' => (int) $data['category_id'],
            'resource_code' => trim((string) $data['resource_code']),
            'resource_name' => trim((string) $data['resource_name']),
            'location' => trim((string) $data['location']),
            'capacity' => (int) ($data['capacity'] ?? 1),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'image' => trim((string) ($data['image'] ?? '')) ?: null,
            'status' => $data['status'] ?? 'available',
        ];
    }
}
