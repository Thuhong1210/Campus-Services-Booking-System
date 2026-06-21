<?php
declare(strict_types=1);

class BookingEquipmentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Link an equipment item to a booking.
     */
    public function create(int $bookingId, int $equipmentId, int $quantity): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO booking_equipment (booking_id, equipment_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
        );
        return $stmt->execute([$bookingId, $equipmentId, $quantity]);
    }

    /**
     * Find equipment associated with a booking.
     */
    public function findByBooking(int $bookingId): array
    {
        $stmt = $this->db->prepare(
            'SELECT be.*, e.equipment_name, e.description, e.status
             FROM booking_equipment be
             JOIN equipment e ON e.id = be.equipment_id
             WHERE be.booking_id = ?'
        );
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete all equipment allocations for a booking.
     */
    public function deleteForBooking(int $bookingId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM booking_equipment WHERE booking_id = ?');
        return $stmt->execute([$bookingId]);
    }

    /**
     * Check how many units of a given equipment are already allocated to active bookings in a time window.
     */
    public function getAllocatedQuantity(int $equipmentId, string $start, string $end, ?int $excludeBookingId = null): int
    {
        $sql = 'SELECT COALESCE(SUM(be.quantity), 0) AS total_allocated
                FROM booking_equipment be
                JOIN bookings b ON b.id = be.booking_id
                WHERE be.equipment_id = ?
                AND b.status IN ("pending", "approved")
                AND b.start_datetime < ?
                AND b.end_datetime > ?';
        $params = [$equipmentId, $end, $start];

        if ($excludeBookingId !== null) {
            $sql .= ' AND b.id != ?';
            $params[] = $excludeBookingId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
