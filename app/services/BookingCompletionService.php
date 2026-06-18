<?php
declare(strict_types=1);

class BookingCompletionService
{
    private BookingRepository $bookingRepo;

    public function __construct()
    {
        $this->bookingRepo = new BookingRepository();
    }

    public function autoComplete(): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'UPDATE bookings
             SET status = "completed"
             WHERE status = "approved"
             AND end_datetime < NOW()'
        );
        $stmt->execute();
        return $stmt->rowCount(); // trả về số booking vừa complete
    }
}