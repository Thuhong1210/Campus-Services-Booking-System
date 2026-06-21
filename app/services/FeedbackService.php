<?php
declare(strict_types=1);

class FeedbackService
{
    private FeedbackRepository $feedbackRepo;
    private BookingRepository $bookingRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->feedbackRepo = new FeedbackRepository();
        $this->bookingRepo  = new BookingRepository();
        $this->auditLog     = new AuditLogService();
    }

    public function submitFeedback(int $bookingId, int $userId, array $data): array
    {
        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        if ((int) $booking['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'You can only review your own bookings.'];
        }

        if ($booking['status'] !== 'completed') {
            return ['success' => false, 'message' => 'You can only review completed bookings.'];
        }

        $existing = $this->feedbackRepo->findByBooking($bookingId);
        if ($existing) {
            return ['success' => false, 'message' => 'You have already submitted feedback for this booking.'];
        }

        $rating = (int) ($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5.'];
        }

        $cleanlinessRating = isset($data['cleanliness_rating']) && $data['cleanliness_rating'] !== ''
            ? (int) $data['cleanliness_rating'] : null;
        $equipmentRating = isset($data['equipment_rating']) && $data['equipment_rating'] !== ''
            ? (int) $data['equipment_rating'] : null;

        $id = $this->feedbackRepo->create([
            'booking_id'         => $bookingId,
            'user_id'            => $userId,
            'rating'             => $rating,
            'cleanliness_rating' => $cleanlinessRating,
            'equipment_rating'   => $equipmentRating,
            'comment'            => trim($data['comment'] ?? ''),
        ]);

        $this->auditLog->log('submit_feedback', 'booking_feedback', $id, null, [
            'booking_id' => $bookingId,
            'rating'     => $rating,
        ]);

        return ['success' => true, 'message' => 'Thank you for your feedback!', 'feedback_id' => $id];
    }

    public function getBookingFeedback(int $bookingId): ?array
    {
        return $this->feedbackRepo->findByBooking($bookingId);
    }

    public function getResourceFeedback(int $resourceId, int $limit = 20, int $offset = 0): array
    {
        return [
            'reviews'      => $this->feedbackRepo->findByResource($resourceId, $limit, $offset),
            'averages'     => $this->feedbackRepo->getAverageRatings($resourceId),
            'distribution' => $this->feedbackRepo->getRatingDistribution($resourceId),
        ];
    }

    public function getAdminSummary(): array
    {
        return $this->feedbackRepo->getAdminSummary(50, 0);
    }
}
