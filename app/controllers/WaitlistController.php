<?php
declare(strict_types=1);

class WaitlistController extends Controller
{
    private WaitlistService $waitlistService;

    public function __construct()
    {
        $this->waitlistService = new WaitlistService();
    }

    /** GET – My Waitlist */
    public function index(): void
    {
        Middleware::auth();
        $user = Auth::user();
        $userId = (int) $user['id'];

        $entries = $this->waitlistService->getUserWaitlist($userId);

        $this->view('waitlist/index', [
            'title' => 'My Waitlist',
            'entries' => $entries
        ]);
    }

    /** POST – Join waitlist */
    public function store(): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            Flash::error('Invalid request.');
            redirect('index.php?page=bookings&action=create');
        }

        $user = Auth::user();
        $userId = (int) $user['id'];

        $resourceId = (int) ($_POST['resource_id'] ?? 0);
        $start      = trim($_POST['start_datetime'] ?? '');
        $end        = trim($_POST['end_datetime'] ?? '');

        if (!$resourceId || !$start || !$end) {
            Flash::error('Missing required fields.');
            redirect('index.php?page=bookings&action=create');
        }

        $result = $this->waitlistService->joinWaitlist($userId, $resourceId, $start, $end);

        if ($result['success']) {
            Flash::success($result['message']);
            redirect('index.php?page=waitlist&action=index');
        } else {
            Flash::error($result['message']);
            redirect('index.php?page=bookings&action=create');
        }
    }

    /** POST – Confirm from waitlist */
    public function confirm(): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            Flash::error('Invalid request.');
            redirect('index.php?page=waitlist&action=index');
        }

        $user        = Auth::user();
        $userId      = (int) $user['id'];
        $waitlistId  = (int) ($_POST['waitlist_id'] ?? 0);

        $result = $this->waitlistService->confirmFromWaitlist($waitlistId, $userId);

        if ($result['success']) {
            Flash::success('Booking confirmed from waitlist! ' . $result['message']);
            redirect('index.php?page=bookings&action=myBookings');
        } else {
            Flash::error($result['message']);
            redirect('index.php?page=waitlist&action=index');
        }
    }

    /** POST – Cancel waitlist entry */
    public function cancel(): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            Flash::error('Invalid request.');
            redirect('index.php?page=waitlist&action=index');
        }

        $user       = Auth::user();
        $userId     = (int) $user['id'];
        $role       = Auth::primaryRole();
        $waitlistId = (int) ($_POST['waitlist_id'] ?? 0);

        $result = $this->waitlistService->cancelWaitlist($waitlistId, $userId, $role === 'Admin');

        if ($result['success']) {
            Flash::success($result['message']);
        } else {
            Flash::error($result['message']);
        }
        redirect('index.php?page=waitlist&action=index');
    }
}
