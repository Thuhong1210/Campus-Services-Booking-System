<?php
require dirname(__DIR__, 2) . '/app/config/config.php';

$service = new BookingCompletionService();
$count = $service->autoComplete();
echo "[" . date('Y-m-d H:i:s') . "] Auto-completed $count bookings.\n";