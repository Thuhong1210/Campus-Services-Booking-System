<?php

declare(strict_types=1);

class ApprovalController extends Controller
{
    private ApprovalRepository $approvalRepo;
    private BookingRepository $bookingRepo;
    private ApprovalService $approvalService;

    public function __construct()
    {
        $this->approvalRepo = new ApprovalRepository();
        $this->bookingRepo = new BookingRepository();
        $this->approvalService = new ApprovalService();
    }

    public function index(): void
    {
        Middleware::approver();

        $page = max(1, (int) ($this->get()['p'] ?? 1));
        $perPage = 20;
        $total = $this->approvalRepo->countPending();
        $pagination = paginate($total, $page, $perPage, 'index.php?page=approvals');

        $pending = $this->approvalRepo->findPending($perPage, $pagination['offset']);

        $this->view('approvals/index', [
            'title' => 'Pending Approvals',
            'pending' => $pending,
            'pagination' => $pagination,
        ]);
    }

    public function show(): void
    {
        Middleware::approver();

        $bookingId = (int) ($_GET['id'] ?? 0);
        if ($bookingId <= 0) {
            Flash::error('Invalid booking ID.');
            redirect('index.php?page=approvals');
        }

        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) {
            Flash::error('Booking not found.');
            redirect('index.php?page=approvals');
        }

        if ($booking['status'] !== 'pending') {
            Flash::error('This booking is no longer pending approval.');
            redirect('index.php?page=approvals');
        }

        $approval = $this->approvalRepo->findByBooking($bookingId);

        $this->view('approvals/detail', [
            'title' => 'Approval Request',
            'booking' => $booking,
            'approval' => $approval,
        ]);
    }

    public function approve(): void
    {
        Middleware::approver();
        $this->verifyCsrf();

        $bookingId = (int) ($this->post()['booking_id'] ?? $_GET['id'] ?? 0);
        if ($bookingId <= 0) {
            Flash::error('Invalid booking ID.');
            redirect('index.php?page=approvals');
        }

        $comment = trim((string) ($this->post()['comment'] ?? ''));

        $result = $this->approvalService->approve($bookingId, (int) Auth::id(), $comment ?: null);

        if ($result['success']) {
            Flash::success($result['message'] ?? 'Booking approved successfully.');
        } else {
            Flash::error($result['message'] ?? 'Unable to approve booking.');
        }

        redirect('index.php?page=approvals');
    }

    public function reject(): void
    {
        Middleware::approver();
        $this->verifyCsrf();

        $bookingId = (int) ($this->post()['booking_id'] ?? $_GET['id'] ?? 0);
        if ($bookingId <= 0) {
            Flash::error('Invalid booking ID.');
            redirect('index.php?page=approvals');
        }

        $comment = trim((string) ($this->post()['comment'] ?? ''));

        if ($comment === '') {
            Flash::error('Rejection reason is required.');
            redirect('index.php?page=approvals&action=show&id=' . $bookingId);
        }

        $result = $this->approvalService->reject($bookingId, (int) Auth::id(), $comment);

        if ($result['success']) {
            Flash::success($result['message'] ?? 'Booking rejected.');
        } else {
            Flash::error($result['message'] ?? 'Unable to reject booking.');
        }

        redirect('index.php?page=approvals');
    }

    public function history(): void
    {
        Middleware::approver();

        $filters = [
            'decision' => $this->get()['decision'] ?? '',
            'approver_id' => $this->get()['approver_id'] ?? '',
            'date_from' => $this->get()['date_from'] ?? '',
            'date_to' => $this->get()['date_to'] ?? '',
        ];

        $page = max(1, (int) ($this->get()['p'] ?? 1));
        $perPage = 20;
        $allHistory = $this->approvalRepo->findHistory($filters, 1000, 0);
        $total = count($allHistory);
        $pagination = paginate($total, $page, $perPage, 'index.php?page=approvals/history');
        $history = array_slice($allHistory, $pagination['offset'], $perPage);

        $this->view('approvals/history', [
            'title' => 'Approval History',
            'history' => $history,
            'filters' => $filters,
            'pagination' => $pagination,
        ]);
    }
}
