<?php

declare(strict_types=1);

class MaintenanceController extends Controller
{
    private MaintenanceRepository $maintenanceRepo;
    private ResourceRepository $resourceRepo;
    private BookingRepository $bookingRepo;
    private AuditLogService $auditLog;
    private MaintenanceService $maintenanceService;

    public function __construct()
    {
        $this->maintenanceRepo    = new MaintenanceRepository();
        $this->resourceRepo       = new ResourceRepository();
        $this->bookingRepo        = new BookingRepository();
        $this->auditLog           = new AuditLogService();
        $this->maintenanceService = new MaintenanceService();
    }

    public function index(): void
    {
        Middleware::admin();
        $this->view('maintenance/index', [
            'title' => 'Maintenance Schedules',
            'schedules' => $this->maintenanceRepo->findAll(),
        ]);
    }

    public function create(): void
    {
        Middleware::admin();
        $this->view('maintenance/create', [
            'title' => 'Schedule Maintenance',
            'resources' => $this->resourceRepo->findAll([], 100, 0),
        ]);
    }

    public function store(): void
    {
        Middleware::admin();
        $this->verifyCsrf();
        $data = $this->post();
        $data['created_by'] = Auth::id();
        $validator = new Validator($data);
        $validator->required('resource_id')->required('maintenance_start')->required('maintenance_end')->required('reason');
        if ($validator->fails()) {
            Flash::error($validator->firstError() ?? 'Validation failed.');
            redirect('index.php?page=maintenance&action=create');
        }
        if (strtotime($data['maintenance_start']) >= strtotime($data['maintenance_end'])) {
            Flash::error('Maintenance end must be after start.');
            redirect('index.php?page=maintenance&action=create');
        }
        $id = $this->maintenanceRepo->create($data);
        $this->resourceRepo->update((int) $data['resource_id'], ['status' => 'maintenance']);
        $this->auditLog->log('update_resource', 'maintenance_schedules', $id, null, $data);

        $resource = $this->resourceRepo->findById((int) $data['resource_id']);
        $notificationService = new NotificationService();
        if ($resource) {
            $affected = $this->bookingRepo->findAll([
                'resource_id' => (string) $data['resource_id'],
                'date_from' => $data['maintenance_start'],
                'date_to' => $data['maintenance_end'],
            ], 100, 0);
            $notified = [];
            foreach ($affected as $booking) {
                if (in_array($booking['status'], ['pending', 'approved'], true) && !isset($notified[$booking['user_id']])) {
                    $notificationService->notifyMaintenance((int) $booking['user_id'], $resource, $data['reason']);
                    $notified[$booking['user_id']] = true;
                }
            }
        }

        Flash::success('Maintenance scheduled.');
        redirect('index.php?page=maintenance');
    }

    public function delete(): void
    {
        Middleware::admin();
        $this->verifyCsrf();
        $id = (int) ($_GET['id'] ?? 0);
        $schedule = $this->maintenanceRepo->findById($id);
        if ($schedule) {
            $this->maintenanceRepo->delete($id);
            $this->resourceRepo->update((int) $schedule['resource_id'], ['status' => 'available']);
            Flash::success('Maintenance schedule removed.');
        }
        redirect('index.php?page=maintenance');
    }

    public function edit(): void
    {
        Middleware::admin();
        $id = (int) ($_GET['id'] ?? 0);
        $schedule = $this->maintenanceRepo->findById($id);
        if (!$schedule) {
            Flash::error('Maintenance schedule not found.');
            redirect('index.php?page=maintenance');
        }
        $this->view('maintenance/edit', [
            'title' => 'Edit Maintenance',
            'schedule' => $schedule,
            'resources' => $this->resourceRepo->findAll([], 100, 0),
        ]);
    }

    public function update(): void
    {
        Middleware::admin();
        $this->verifyCsrf();
        $id = (int) ($_GET['id'] ?? 0);
        $schedule = $this->maintenanceRepo->findById($id);
        if (!$schedule) {
            Flash::error('Maintenance schedule not found.');
            redirect('index.php?page=maintenance');
        }
        $data = $this->post();
        $validator = new Validator($data);
        $validator->required('resource_id')->required('maintenance_start')->required('maintenance_end')->required('reason');
        if ($validator->fails()) {
            Flash::error($validator->firstError() ?? 'Validation failed.');
            redirect('index.php?page=maintenance&action=edit&id=' . $id);
        }
        if (strtotime($data['maintenance_start']) >= strtotime($data['maintenance_end'])) {
            Flash::error('Maintenance end must be after start.');
            redirect('index.php?page=maintenance&action=edit&id=' . $id);
        }
        $this->maintenanceRepo->update($id, $data);
        if (($data['status'] ?? '') === 'in_progress') {
            $this->resourceRepo->update((int) $data['resource_id'], ['status' => 'maintenance']);
        }
        $this->auditLog->log('update_resource', 'maintenance_schedules', $id, $schedule, $data);
        Flash::success('Maintenance schedule updated.');
        redirect('index.php?page=maintenance');
    }

    /** Show bookings impacted by this maintenance window */
    public function impactReport(): void
    {
        Middleware::admin();
        $id = (int) ($_GET['id'] ?? 0);
        $schedule = $this->maintenanceRepo->findById($id);
        if (!$schedule) {
            Flash::error('Maintenance schedule not found.');
            redirect('index.php?page=maintenance');
        }

        $impacted = $this->maintenanceService->detectImpactedBookings(
            (int) $schedule['resource_id'],
            $schedule['maintenance_start'],
            $schedule['maintenance_end']
        );

        $this->view('maintenance/impact', [
            'title'    => 'Maintenance Impact Report',
            'schedule' => $schedule,
            'impacted' => $impacted,
        ]);
    }

    /** Activate maintenance + notify impacted users */
    public function activate(): void
    {
        Middleware::admin();
        $this->verifyCsrf();
        $id     = (int) ($_POST['id'] ?? 0);
        $result = $this->maintenanceService->activateMaintenance($id, (int) Auth::id());
        if ($result['success']) {
            Flash::success($result['message']);
        } else {
            Flash::error($result['message']);
        }
        redirect('index.php?page=maintenance');
    }

    /** Complete maintenance + restore resource */
    public function complete(): void
    {
        Middleware::admin();
        $this->verifyCsrf();
        $id     = (int) ($_POST['id'] ?? 0);
        $result = $this->maintenanceService->completeMaintenance($id, (int) Auth::id());
        if ($result['success']) {
            Flash::success($result['message']);
        } else {
            Flash::error($result['message']);
        }
        redirect('index.php?page=maintenance');
    }

    /** Notify impacted users for a specific maintenance window */
    public function notifyImpacted(): void
    {
        Middleware::admin();
        $this->verifyCsrf();
        $id     = (int) ($_POST['id'] ?? 0);
        $result = $this->maintenanceService->notifyImpactedUsers($id);
        if ($result['success']) {
            Flash::success($result['message']);
        } else {
            Flash::error($result['message']);
        }
        redirect('index.php?page=maintenance&action=impactReport&id=' . $id);
    }
}
