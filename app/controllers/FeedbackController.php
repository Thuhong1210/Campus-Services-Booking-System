<?php
declare(strict_types=1);

class FeedbackController extends Controller
{
    private FeedbackService $feedbackService;

    public function __construct()
    {
        $this->feedbackService = new FeedbackService();
    }

    /** GET – Show feedback form for a completed booking */
    public function create(): void
    {
        Middleware::auth();
        $bookingId = (int) ($_GET['booking_id'] ?? 0);
        if (!$bookingId) {
            Flash::error('Invalid booking.');
            redirect('index.php?page=bookings/my');
        }

        $bookingRepo = new BookingRepository();
        $booking = $bookingRepo->findById($bookingId);

        if (!$booking || (int) $booking['user_id'] !== (int) Auth::id()) {
            Flash::error('Booking not found or access denied.');
            redirect('index.php?page=bookings/my');
        }

        if ($booking['status'] !== 'completed') {
            Flash::error('You can only rate completed bookings.');
            redirect('index.php?page=bookings/my');
        }

        $existing = $this->feedbackService->getBookingFeedback($bookingId);
        if ($existing) {
            Flash::error('You have already submitted feedback for this booking.');
            redirect('index.php?page=bookings&action=show&id=' . $bookingId);
        }

        $this->view('feedback/create', [
            'title'   => 'Rate Your Experience',
            'booking' => $booking,
        ]);
    }

    /** POST – Submit feedback */
    public function store(): void
    {
        Middleware::auth();
        $this->verifyCsrf();

        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $userId    = (int) Auth::id();

        $result = $this->feedbackService->submitFeedback($bookingId, $userId, $_POST);

        if ($result['success']) {
            Flash::success($result['message']);
            redirect('index.php?page=bookings&action=show&id=' . $bookingId);
        } else {
            Flash::error($result['message']);
            redirect('index.php?page=feedback&action=create&booking_id=' . $bookingId);
        }
    }

    /** Admin – View all feedback summary and per-resource reviews */
    public function resource(): void
    {
        Middleware::admin();
        $resourceId = (int) ($_GET['resource_id'] ?? 0);

        $summary = $this->feedbackService->getAdminSummary();
        $resourceFeedback = null;

        if ($resourceId > 0) {
            $resourceFeedback = $this->feedbackService->getResourceFeedback($resourceId, 20, 0);
            $resourceRepo = new ResourceRepository();
            $resource = $resourceRepo->findById($resourceId);
        }

        $this->view('feedback/resource', [
            'title'           => 'Feedback & Ratings',
            'summary'         => $summary,
            'resourceId'      => $resourceId,
            'resourceFeedback'=> $resourceFeedback,
            'resource'        => $resource ?? null,
        ]);
    }
}
