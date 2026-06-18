<?php

declare(strict_types=1);

class EquipmentController extends Controller
{
    private EquipmentRepository $equipmentRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->equipmentRepo = new EquipmentRepository();
        $this->auditLog = new AuditLogService();
    }

    public function index(): void
    {
        Middleware::admin();
        $this->view('equipment/index', [
            'title' => 'Equipment Management',
            'equipment' => $this->equipmentRepo->findAll(),
        ]);
    }

    public function create(): void
    {
        Middleware::admin();
        $this->view('equipment/create', ['title' => 'Add Equipment']);
    }

    public function store(): void
    {
        Middleware::admin();
        $this->verifyCsrf();
        $data = $this->post();
        $validator = new Validator($data);
        $validator->required('equipment_name', 'Equipment name');
        if ($validator->fails()) {
            Flash::error($validator->firstError() ?? 'Validation failed.');
            redirect('index.php?page=equipment&action=create');
        }
        try {
            $id = $this->equipmentRepo->create($data);
            $this->auditLog->log('create_resource', 'equipment', $id, null, $data);
            Flash::success('Equipment added successfully.');
        } catch (Exception $e) {
            Flash::error('Failed to add equipment.');
        }
        redirect('index.php?page=equipment');
    }

    public function edit(): void
    {
        Middleware::admin();
        $id = (int) ($_GET['id'] ?? 0);
        $item = $this->equipmentRepo->findById($id);
        if (!$item) {
            Flash::error('Equipment not found.');
            redirect('index.php?page=equipment');
        }
        $this->view('equipment/edit', ['title' => 'Edit Equipment', 'item' => $item]);
    }

    public function update(): void
    {
        Middleware::admin();
        $this->verifyCsrf();
        $id = (int) ($_GET['id'] ?? 0);
        $data = $this->post();
        try {
            $this->equipmentRepo->update($id, $data);
            Flash::success('Equipment updated.');
        } catch (Exception $e) {
            Flash::error('Update failed.');
        }
        redirect('index.php?page=equipment');
    }

    public function delete(): void
    {
        Middleware::admin();
        $this->verifyCsrf();
        $id = (int) ($_GET['id'] ?? 0);
        try {
            $this->equipmentRepo->delete($id);
            Flash::success('Equipment deleted.');
        } catch (RuntimeException $e) {
            Flash::error('Cannot delete equipment assigned to resources.');
        }
        redirect('index.php?page=equipment');
    }
}
