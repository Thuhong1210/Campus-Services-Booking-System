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
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->bookingRepo = new BookingRepository();
        $this->bookingService = new BookingService();
        $this->resourceRepo = new ResourceRepository();
        $this->categoryRepo = new ResourceCategoryRepository();
        $this->cancellationService = new CancellationService();
        $this->approvalRepo = new ApprovalRepository();
        $this->userRepo = new UserRepository();
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

        $canSupervise = Auth::hasAnyRole(['Admin', 'Lecturer', 'Approver']);
        $students = $canSupervise
            ? $this->userRepo->all(['role' => 'Student', 'status' => 'active'], 200, 0)
            : [];

        $recommendations = $_SESSION['booking_recommendations'] ?? [];
        unset($_SESSION['booking_recommendations']);

        $this->view('bookings/create', [
            'title' => $canSupervise ? 'Create Supervised Booking' : 'Create Booking',
            'resources' => $this->resourceRepo->findAvailable([]),
            'categories' => $this->categoryRepo->findAll(['status' => 'active']),
            'preselectedResourceId' => (int) ($this->get()['resource_id'] ?? 0),
            'canSupervise' => $canSupervise,
            'students' => $students,
            'recommendations' => $recommendations,
        ]);
    }

    public function store(): void
    {
        Middleware::auth();
        $this->verifyCsrf();

        $data = $this->post();
        $data['user_id'] = (int) Auth::id();
        if (!empty($data['student_user_id']) && Auth::hasAnyRole(['Admin', 'Lecturer', 'Approver'])) {
            $student = $this->userRepo->findById((int) $data['student_user_id']);
            if ($student && $student['status'] === 'active') {
                $data['user_id'] = (int) $student['id'];
            }
        }

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
        if (!empty($result['recommendations'])) {
            $_SESSION['booking_recommendations'] = $result['recommendations'];
        }
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

        $approval = $this->approvalRepo->findByBooking($id);
        $approvals = $approval ? [$approval] : [];

        $this->view('bookings/detail', [
            'title' => 'Booking ' . $booking['booking_reference'],
            'booking' => $booking,
            'approvals' => $approvals,
            'cancellation' => (new CancellationRepository())->findByBooking($id),
            'canCancel' => in_array($booking['status'], ['pending', 'approved'], true)
                && (Auth::isAdmin() || (int) $booking['user_id'] === Auth::id()),
            'canEdit' => in_array($booking['status'], ['pending', 'approved'], true)
                && strtotime($booking['start_datetime']) >= time()
                && (Auth::isAdmin() || (int) $booking['user_id'] === Auth::id()),
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
        $result = $this->bookingService->checkAvailability(
            $resourceId,
            $start,
            $end,
            (int) Auth::id()
        );
        Response::json($result);
    }

    public function exportSchedule(): void
    {
        Middleware::auth();

        $filters = array_filter([
            'status' => $this->get()['status'] ?? '',
            'category_id' => $this->get()['category_id'] ?? '',
        ]);
        $schedule = $this->bookingRepo->findByUser((int) Auth::id(), $filters, 500, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="my_schedule_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        echo "Reference,Resource,Category,Start,End,Status,Purpose\n";
        foreach ($schedule as $b) {
            echo implode(',', [
                $b['booking_reference'],
                '"' . str_replace('"', '""', $b['resource_name']) . '"',
                '"' . str_replace('"', '""', $b['category_name'] ?? '') . '"',
                $b['start_datetime'],
                $b['end_datetime'],
                $b['status'],
                '"' . str_replace('"', '""', $b['purpose']) . '"',
            ]) . "\n";
        }
        exit;
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

    public function exportIcs(): void
    {
        Middleware::auth();
        $id = (int) ($this->get()['id'] ?? 0);
        if ($id <= 0) {
            Flash::error('Invalid booking ID.');
            redirect('index.php?page=bookings/my');
        }
        
        $booking = $this->bookingRepo->findById($id);
        if (!$booking) {
            Flash::error('Booking not found.');
            redirect('index.php?page=bookings/my');
        }

        $isAdminOrStaff = Auth::hasAnyRole(['Admin', 'Staff', 'Lecturer', 'Approver']);
        if ($booking['user_id'] !== Auth::id() && !$isAdminOrStaff) {
            Flash::error('Unauthorized access.');
            redirect('index.php?page=bookings/my');
        }

        $start = date('Ymd\THis', strtotime($booking['start_datetime']));
        $end = date('Ymd\THis', strtotime($booking['end_datetime']));
        $created = date('Ymd\THis', strtotime($booking['created_at']));
        
        $ref = $booking['booking_reference'];
        $summary = $booking['purpose'];
        $location = $booking['resource_name'] . ' (' . ($booking['resource_code'] ?? '') . ')';
        $desc = "Booking Reference: " . $ref . "\\nStatus: " . ucfirst($booking['status']) . "\\nNotes: " . ($booking['additional_notes'] ?? '');

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="booking_' . $ref . '.ics"');
        
        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//Campus Services Booking System//EN\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH\r\n";
        echo "BEGIN:VEVENT\r\n";
        echo "UID:" . $ref . "@campus-services-booking\r\n";
        echo "DTSTAMP:" . $created . "\r\n";
        echo "DTSTART:" . $start . "\r\n";
        echo "DTEND:" . $end . "\r\n";
        echo "SUMMARY:" . $summary . "\r\n";
        echo "LOCATION:" . $location . "\r\n";
        echo "DESCRIPTION:" . $desc . "\r\n";
        echo "END:VEVENT\r\n";
        echo "END:VCALENDAR\r\n";
        exit;
    }

    public function checkIn(): void
    {
        Middleware::auth();
        
        $token = trim((string) ($this->get()['token'] ?? $this->post()['token'] ?? ''));
        if ($token === '') {
            Flash::error('QR token is missing.');
            redirect('index.php?page=dashboard');
        }

        $booking = $this->bookingRepo->findByQrToken($token);
        if (!$booking) {
            Flash::error('Invalid QR token or booking not found.');
            redirect('index.php?page=dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            $action = trim((string) ($this->post()['check_action'] ?? ''));
            $actorId = (int) Auth::id();
            $isAdminOrStaff = Auth::hasAnyRole(['Admin', 'Staff', 'Lecturer', 'Approver']);

            if ($action === 'checkin') {
                $result = $this->bookingService->checkIn($token, $actorId, $isAdminOrStaff);
            } elseif ($action === 'checkout') {
                $result = $this->bookingService->checkOut($token, $actorId, $isAdminOrStaff);
            } else {
                $result = ['success' => false, 'message' => 'Invalid action.'];
            }

            if ($result['success']) {
                Flash::success($result['message']);
            } else {
                Flash::error($result['message']);
            }
            redirect('index.php?page=bookings&action=check-in&token=' . urlencode($token));
        }

        $this->view('bookings/check_in', [
            'title' => 'QR Code Check-in / Check-out',
            'booking' => $booking
        ]);
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
