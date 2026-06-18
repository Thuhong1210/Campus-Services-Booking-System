<?php

declare(strict_types=1);

class TimeSlotController extends Controller
{
    private TimeSlotRepository $timeSlotRepo;
    private ResourceRepository $resourceRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->timeSlotRepo = new TimeSlotRepository();
        $this->resourceRepo = new ResourceRepository();
        $this->auditLog = new AuditLogService();
    }

    public function index(): void
    {
        Middleware::admin();

        $filters = [
            'resource_id' => $this->get()['resource_id'] ?? '',
            'is_peak' => $this->get()['is_peak'] ?? '',
            'is_active' => $this->get()['is_active'] ?? '',
        ];

        if ($filters['is_peak'] !== '') {
            $filters['is_peak'] = (int) $filters['is_peak'];
        }
        if ($filters['is_active'] !== '') {
            $filters['is_active'] = (int) $filters['is_active'];
        }

        $timeSlots = $this->timeSlotRepo->findAll($filters);

        $this->view('time_slots/index', [
            'title' => 'Time Slot Management',
            'timeSlots' => $timeSlots,
            'filters' => $filters,
            'resources' => $this->resourceRepo->findAll([], 100, 0),
        ]);
    }

    public function create(): void
    {
        Middleware::admin();

        $this->view('time_slots/create', [
            'title' => 'Add Time Slot',
            'resources' => $this->resourceRepo->findAll([], 100, 0),
            'preselectedResourceId' => (int) ($this->get()['resource_id'] ?? 0),
        ]);
    }

    public function store(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $data = $this->post();
        $errors = $this->validateTimeSlot($data);

        if (!empty($errors)) {
            Flash::error($errors[0]);
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=time-slots&action=create');
        }

        try {
            $id = $this->timeSlotRepo->create($this->normalizeTimeSlotData($data));
            $this->auditLog->log(
                'create_resource',
                'time_slots',
                $id,
                null,
                ['resource_id' => $data['resource_id']]
            );
            Flash::success('Time slot created successfully.');
            redirect('index.php?page=time-slots');
        } catch (Exception $e) {
            Flash::error('Failed to create time slot.');
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=time-slots&action=create');
        }
    }

    public function edit(): void
    {
        Middleware::admin();

        $id = $this->requireTimeSlotId();
        $timeSlot = $this->timeSlotRepo->findById($id);
        if (!$timeSlot) {
            Flash::error('Time slot not found.');
            redirect('index.php?page=time-slots');
        }

        $this->view('time_slots/edit', [
            'title' => 'Edit Time Slot',
            'timeSlot' => $timeSlot,
            'resources' => $this->resourceRepo->findAll([], 100, 0),
        ]);
    }

    public function update(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requireTimeSlotId();
        $timeSlot = $this->timeSlotRepo->findById($id);
        if (!$timeSlot) {
            Flash::error('Time slot not found.');
            redirect('index.php?page=time-slots');
        }

        $data = $this->post();
        $errors = $this->validateTimeSlot($data, $id);

        if (!empty($errors)) {
            Flash::error($errors[0]);
            redirect('index.php?page=time-slots&action=edit&id=' . $id);
        }

        try {
            $this->timeSlotRepo->update($id, $this->normalizeTimeSlotData($data));
            Flash::success('Time slot updated successfully.');
            redirect('index.php?page=time-slots');
        } catch (Exception $e) {
            Flash::error('Failed to update time slot.');
            redirect('index.php?page=time-slots&action=edit&id=' . $id);
        }
    }

    public function delete(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $id = $this->requireTimeSlotId();

        try {
            $this->timeSlotRepo->delete($id);
            Flash::success('Time slot deleted successfully.');
        } catch (Exception $e) {
            Flash::error('Failed to delete time slot.');
        }

        redirect('index.php?page=time-slots');
    }

    private function requireTimeSlotId(): int
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            Flash::error('Invalid time slot ID.');
            redirect('index.php?page=time-slots');
        }
        return $id;
    }

    private function validateTimeSlot(array $data, ?int $excludeId = null): array
    {
        $validator = new Validator($data);
        $validator
            ->required('resource_id', 'Resource')
            ->required('day_of_week', 'Day of week')
            ->required('start_time', 'Start time')
            ->required('end_time', 'End time');

        if ($validator->fails()) {
            return [$validator->firstError() ?? 'Validation failed.'];
        }

        if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
            return ['Start time must be earlier than end time.'];
        }

        $resourceId = (int) $data['resource_id'];
        $dayOfWeek = (int) $data['day_of_week'];

        if ($this->timeSlotRepo->hasOverlap(
            $resourceId,
            $dayOfWeek,
            $data['start_time'],
            $data['end_time'],
            $excludeId
        )) {
            return ['This time slot overlaps with an existing time slot.'];
        }

        return [];
    }

    private function normalizeTimeSlotData(array $data): array
    {
        return [
            'resource_id' => (int) $data['resource_id'],
            'day_of_week' => (int) $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_peak' => (int) ($data['is_peak'] ?? 0),
            'is_active' => (int) ($data['is_active'] ?? 1),
        ];
    }
}
