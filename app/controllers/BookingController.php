<?php

declare(strict_types=1);

class BookingController extends Controller
{
    private BookingRepository $bookingRepo;
    private BookingService $bookingService;
    private ResourceRepository $resourceRepo;
    private ResourceCategoryRepository $categoryRepo;
    private CancellationService $cancellationService;
    private ApprovalRepository $approvalRepo;

    public function __construct()
    {
        $this->bookingRepo = new BookingRepository();
        $this->bookingService = new BookingService();
        $this->resourceRepo = new ResourceRepository();
        $this->categoryRepo = new ResourceCategoryRepository();
        $this->cancellationService = new CancellationService();
        $this->approvalRepo = new ApprovalRepository();
    }

    public function index(): void
    {
        Middleware::role(['Admin', 'Staff']);

        $filters = [
            'search' => trim((string) ($this->get()['search'] ?? '')),
            'status' => $this->get()['status'] ?? '',
            'resource_id' => $this->get()['resource_id'] ?? '',
            'date_from' => $this->get()['date_from'] ?? '',
            'date_to' => $this->get()['date_to'] ?? '',
        ];

        $page = max(1, (int) ($this->get()['p'] ?? 1));
        $perPage = 20;
        $total = $this->bookingRepo->count($filters);
        $pagination = paginate($total, $page, $perPage, 'index.php?page=bookings');

        $bookings = $this->bookingRepo->findAll($filters, $perPage, $pagination['offset']);

        $this->view('bookings/index', [
            'title' => 'Booking Management',
            'bookings' => $bookings,
            'filters' => $filters,
            'pagination' => $pagination,
            'resources' => $this->resourceRepo->findAll([], 100, 0),
        ]);
    }

    public function create(): void
    {
        Middleware::auth();

        $this->view('bookings/create', [
            'title' => 'Create Booking',
            'resources' => $this->resourceRepo->findAvailable([]),
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
            'preselectedResourceId' => (int) ($this->get()['resource_id'] ?? 0),
        ]);
    }

    public function store(): void
    {
        Middleware::auth();
        $this->verifyCsrf();

        $data = $this->post();
        $data['user_id'] = Auth::id();

        // Combine date and time fields if provided separately
        if (!empty($data['booking_date']) && !empty($data['start_time'])) {
            $data['start_datetime'] = $data['booking_date'] . ' ' . $data['start_time'] . ':00';
        }
        if (!empty($data['booking_date']) && !empty($data['end_time'])) {
            $data['end_datetime'] = $data['booking_date'] . ' ' . $data['end_time'] . ':00';
        }

        $validator = new Validator($data);
        $validator
            ->required('resource_id', 'Resource')
            ->required('start_datetime', 'Start date/time')
            ->required('end_datetime', 'End date/time')
            ->required('purpose', 'Purpose')
            ->datetimeOrder('start_datetime', 'end_datetime');

        if ($validator->fails()) {
            Flash::error($validator->firstError() ?? 'Validation failed.');
            $_SESSION['old_input'] = $data;
            redirect('index.php?page=bookings&action=create');
        }

        $result = $this->bookingService->createBooking($data);

        if ($result['success']) {
            Flash::success($result['message']);
            redirect('index.php?page=bookings/my');
        }

        Flash::error($result['message']);
        $_SESSION['old_input'] = $data;
        redirect('index.php?page=bookings&action=create');
    }

    public function edit(): void
    {
        Middleware::auth();

        $id = $this->requireBookingId();
        $booking = $this->bookingRepo->findById($id);

        if (!$booking) {
            Flash::error('Booking not found.');
            redirect('index.php?page=bookings/my');
        }

        if (!Auth::isAdmin() && (int) $booking['user_id'] !== Auth::id()) {
            Flash::error('You do not have permission to perform this action.');
            redirect('index.php?page=bookings/my');
        }

        if (!in_array($booking['status'], ['pending', 'approved'], true)) {
            Flash::error('Only pending or approved bookings can be edited.');
            redirect('index.php?page=bookings/my');
        }

        $this->view('bookings/edit', [
            'title' => 'Edit Booking',
            'booking' => $booking,
            'resources' => $this->resourceRepo->findAvailable([]),
        ]);
    }

    public function update(): void
    {
        Middleware::auth();
        $this->verifyCsrf();

        $id = $this->requireBookingId();
        $data = $this->post();

        if (!empty($data['booking_date']) && !empty($data['start_time'])) {
            $data['start_datetime'] = $data['booking_date'] . ' ' . $data['start_time'] . ':00';
        }
        if (!empty($data['booking_date']) && !empty($data['end_time'])) {
            $data['end_datetime'] = $data['booking_date'] . ' ' . $data['end_time'] . ':00';
        }

        $validator = new Validator($data);
        $validator
            ->required('resource_id', 'Resource')
            ->required('start_datetime', 'Start date/time')
            ->required('end_datetime', 'End date/time')
            ->required('purpose', 'Purpose')
            ->datetimeOrder('start_datetime', 'end_datetime');

        if ($validator->fails()) {
            Flash::error($validator->firstError() ?? 'Validation failed.');
            redirect('index.php?page=bookings&action=edit&id=' . $id);
        }

        $result = $this->bookingService->updateBooking($id, $data, (int) Auth::id(), Auth::isAdmin());

        if ($result['success']) {
            Flash::success($result['message']);
            redirect('index.php?page=bookings/my');
        }

        Flash::error($result['message']);
        redirect('index.php?page=bookings&action=edit&id=' . $id);
    }

    public function show(): void
    {
        Middleware::auth();

        $id = $this->requireBookingId();
        $booking = $this->bookingRepo->findById($id);

        if (!$booking) {
            Flash::error('Booking not found.');
            redirect('index.php?page=bookings/my');
        }

        if (!Auth::isAdmin() && (int) $booking['user_id'] !== Auth::id()) {
            Flash::error('You do not have permission to perform this action.');
            redirect('index.php?page=bookings/my');
        }

        $this->view('bookings/detail', [
            'title' => 'Booking ' . $booking['booking_reference'],
            'booking' => $booking,
            'approvals' => array_filter([(new ApprovalRepository())->findByBooking($id)]),
            'cancellation' => (new CancellationRepository())->findByBooking($id),
        ]);
    }

    public function checkConflict(): void
    {
        Middleware::auth();
        $resourceId = (int) ($this->get()['resource_id'] ?? 0);
        $date = $this->get()['booking_date'] ?? '';
        $startTime = $this->get()['start_time'] ?? '';
        $endTime = $this->get()['end_time'] ?? '';

        if (!$resourceId || !$date || !$startTime || !$endTime) {
            Response::jsonError('Missing parameters.');
        }

        $start = $date . ' ' . $startTime . ':00';
        $end = $date . ' ' . $endTime . ':00';
        $resource = $this->resourceRepo->findById($resourceId);

        if (!$resource) {
            Response::jsonError('Resource not found.');
        }
        if ($resource['status'] !== 'available') {
            Response::json(['success' => false, 'available' => false, 'message' => 'Resource is not available.']);
        }

        $conflicts = $this->bookingRepo->findConflicts($resourceId, $start, $end);
        if (!empty($conflicts)) {
            Response::json([
                'success' => false,
                'available' => false,
                'message' => 'This resource is already booked during the selected time period.',
            ]);
        }

        Response::json(['success' => true, 'available' => true, 'message' => 'Time slot is available.']);
    }

    public function myBookings(): void
    {
        Middleware::auth();

        $filters = [
            'status' => $this->get()['status'] ?? '',
            'search' => trim((string) ($this->get()['search'] ?? '')),
        ];

        $userId = (int) Auth::id();
        $page = max(1, (int) ($this->get()['p'] ?? 1));
        $perPage = 20;
        $countFilters = array_merge($filters, ['user_id' => $userId]);
        $total = $this->bookingRepo->count($countFilters);
        $pagination = paginate($total, $page, $perPage, 'index.php?page=bookings/my');

        $bookings = $this->bookingRepo->findByUser($userId, $filters, $perPage, $pagination['offset']);

        $this->view('bookings/my_bookings', [
            'title' => 'My Bookings',
            'bookings' => $bookings,
            'filters' => $filters,
            'pagination' => $pagination,
        ]);
    }

    public function mySchedule(): void
    {
        Middleware::auth();

        $filters = [
            'view' => $this->get()['view'] ?? 'week',
            'status' => $this->get()['status'] ?? '',
            'category_id' => $this->get()['category_id'] ?? '',
            'date' => $this->get()['date'] ?? date('Y-m-d'),
        ];

        $bookingFilters = array_filter([
            'status' => $filters['status'],
            'category_id' => $filters['category_id'],
        ]);

        $schedule = $this->bookingRepo->findByUser((int) Auth::id(), $bookingFilters, 100, 0);

        $this->view('bookings/my_schedule', [
            'title' => 'My Schedule',
            'schedule' => $schedule,
            'filters' => $filters,
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
        ]);
    }

    public function calendar(): void
    {
        Middleware::auth();

        $filters = [
            'resource_id' => $this->get()['resource_id'] ?? '',
            'category_id' => $this->get()['category_id'] ?? '',
            'date' => $this->get()['date'] ?? date('Y-m-d'),
        ];

        $eventFilters = array_filter([
            'resource_id' => $filters['resource_id'],
            'category_id' => $filters['category_id'],
        ]);

        $events = $this->bookingRepo->findAll($eventFilters, 200, 0);

        $this->view('bookings/calendar', [
            'title' => 'Resource Calendar',
            'events' => $events,
            'filters' => $filters,
            'resources' => $this->resourceRepo->findAll([], 100, 0),
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
        ]);
    }

    public function cancel(): void
    {
        Middleware::auth();
        $this->verifyCsrf();

        $id = $this->requireBookingId();
        $reason = trim((string) ($this->post()['reason'] ?? ''));

        if ($reason === '') {
            Flash::error('Cancellation reason is required.');
            redirect('index.php?page=bookings&action=show&id=' . $id);
        }

        $result = $this->cancellationService->cancel($id, (int) Auth::id(), $reason, Auth::isAdmin());

        if ($result['success']) {
            Flash::success($result['message']);
        } else {
            Flash::error($result['message']);
        }

        redirect(Auth::isAdmin() ? 'index.php?page=bookings' : 'index.php?page=bookings/my');
    }

    private function requireBookingId(): int
    {
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            Flash::error('Invalid booking ID.');
            redirect('index.php?page=bookings/my');
        }
        return $id;
    }
}
