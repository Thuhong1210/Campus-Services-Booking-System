<?php
declare(strict_types=1);

class FeedbackRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO booking_feedback (booking_id, user_id, rating, cleanliness_rating, equipment_rating, comment)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['booking_id'],
            $data['user_id'],
            $data['rating'],
            $data['cleanliness_rating'] ?? null,
            $data['equipment_rating'] ?? null,
            $data['comment'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findByBooking(int $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT f.*, u.full_name AS user_name
             FROM booking_feedback f
             JOIN users u ON u.id = f.user_id
             WHERE f.booking_id = ? LIMIT 1'
        );
        $stmt->execute([$bookingId]);
        return $stmt->fetch() ?: null;
    }

    public function findByResource(int $resourceId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT f.*, u.full_name AS user_name, b.booking_reference, b.start_datetime
             FROM booking_feedback f
             JOIN bookings b ON b.id = f.booking_id
             JOIN users u ON u.id = f.user_id
             WHERE b.resource_id = ?
             ORDER BY f.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$resourceId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function getAverageRatings(int $resourceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
               COUNT(*) AS total_reviews,
               ROUND(AVG(f.rating), 1) AS avg_rating,
               ROUND(AVG(f.cleanliness_rating), 1) AS avg_cleanliness,
               ROUND(AVG(f.equipment_rating), 1) AS avg_equipment
             FROM booking_feedback f
             JOIN bookings b ON b.id = f.booking_id
             WHERE b.resource_id = ?'
        );
        $stmt->execute([$resourceId]);
        return $stmt->fetch() ?: [
            'total_reviews'  => 0,
            'avg_rating'     => 0,
            'avg_cleanliness'=> 0,
            'avg_equipment'  => 0,
        ];
    }

    public function getRatingDistribution(int $resourceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT f.rating, COUNT(*) AS count
             FROM booking_feedback f
             JOIN bookings b ON b.id = f.booking_id
             WHERE b.resource_id = ?
             GROUP BY f.rating
             ORDER BY f.rating DESC'
        );
        $stmt->execute([$resourceId]);
        return $stmt->fetchAll();
    }

    public function getAdminSummary(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.resource_name, r.resource_code, r.id AS resource_id,
                    COUNT(f.id) AS total_reviews,
                    ROUND(AVG(f.rating), 1) AS avg_rating
             FROM booking_feedback f
             JOIN bookings b ON b.id = f.booking_id
             JOIN resources r ON r.id = b.resource_id
             GROUP BY r.id, r.resource_name, r.resource_code
             ORDER BY avg_rating ASC, total_reviews DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
}
