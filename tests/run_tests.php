#!/usr/bin/env php
<?php
/**
 * Campus Services Booking System - Integration Test Suite
 * Run: php tests/run_tests.php
 */
require dirname(__DIR__) . '/app/config/config.php';

// Reset database state before running tests to ensure complete idempotency
try {
    $db = Database::getInstance()->getConnection();
    $baseDir = dirname(__DIR__);
    $files = [
        $baseDir . '/database/campus_services_booking.sql',
        $baseDir . '/database/seed_isvnu.sql',
        $baseDir . '/database/seed_isvnu_part2.sql',
        $baseDir . '/database/migrations_advanced.sql',
    ];
    foreach ($files as $file) {
        if (!file_exists($file)) {
            throw new Exception("SQL seed file not found: $file");
        }
        $sql = file_get_contents($file);
        // Remove SQL comments to avoid parsing issues
        $sql = preg_replace('/^[ \t]*--.*/m', '', $sql);
        // Split statements by semicolon + line break
        $statements = preg_split('/;[ \t]*[\r\n]+/', $sql);
        foreach ($statements as $query) {
            $query = trim($query);
            if ($query !== '') {
                $db->exec($query);
            }
        }
    }
} catch (Throwable $e) {
    echo "CRITICAL ERROR: Failed to reset and seed the test database: " . $e->getMessage() . "\n";
    exit(1);
}
// Refresh the setting cache after database reset to avoid using stale values
setting('maintenance_mode', null, true);


$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $result = $fn();
        if ($result === true) {
            echo "✓ PASS: $name\n";
            $passed++;
        } else {
            echo "✗ FAIL: $name — " . (is_string($result) ? $result : 'false') . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "✗ FAIL: $name — " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "=== Campus Services Booking System Tests ===\n\n";

// 1. Login works
test('Correct login works', function () {
    $svc = new AuthService();
    $r = $svc->login('admin@is.vnu.edu.vn', 'admin123');
    return $r['success'] === true;
});

// 2. Wrong password
test('Incorrect password shows error', function () {
    $svc = new AuthService();
    $r = $svc->login('admin@is.vnu.edu.vn', 'wrongpass');
    return $r['success'] === false;
});

// 3. Admin create resource
test('Admin can create a new resource', function () {
    $repo = new ResourceRepository();
    $code = 'TEST-' . time();
    $id = $repo->create([
        'category_id' => 1, 'resource_code' => $code, 'resource_name' => 'Test Room',
        'location' => 'Test Bldg', 'capacity' => 5, 'description' => 'Test', 'status' => 'available',
    ]);
    return $id > 0;
});

// 4. Admin create time slot
test('Admin can create a time slot', function () {
    $repo = new TimeSlotRepository();
    $id = $repo->create([
        'resource_id' => 1, 'day_of_week' => 2, 'start_time' => '14:00:00',
        'end_time' => '16:00:00', 'is_peak' => 0, 'is_active' => 1,
    ]);
    return $id > 0;
});

// Dynamic offsets avoid collisions between repeated test runs
$baseOffset = 100 + (int) (time() % 200);
$far = fn(int $extra) => date('Y-m-d', strtotime('+' . ($baseOffset + $extra) . ' days'));

// 5. Student valid booking
test('Student can create a valid booking', function () use ($far) {
    $bs = new BookingService();
    $date = $far(60);
    $r = $bs->createBooking([
        'user_id' => 9, 'resource_id' => 2,
        'start_datetime' => "$date 10:00:00", 'end_datetime' => "$date 12:00:00",
        'purpose' => 'Test booking',
    ]);
    return $r['success'] === true;
});

// 6. Conflict blocked
test('Student blocked on overlapping booking', function () use ($baseOffset) {
    $bs = new BookingService();
    $mon = date('Y-m-d', strtotime('monday', strtotime('+' . ($baseOffset + 5) . ' days')));
    $r1 = $bs->createBooking([
        'user_id' => 9, 'resource_id' => 1,
        'start_datetime' => "$mon 09:30:00", 'end_datetime' => "$mon 11:30:00",
        'purpose' => 'Conflict setup',
    ]);
    if (!$r1['success']) {
        return 'Setup failed: ' . ($r1['message'] ?? '');
    }
    $r2 = $bs->createBooking([
        'user_id' => 9, 'resource_id' => 1,
        'start_datetime' => "$mon 09:30:00", 'end_datetime' => "$mon 11:30:00",
        'purpose' => 'Conflict test',
    ]);
    return $r2['success'] === false && str_contains($r2['message'], 'already booked');
});

// 7. Maintenance resource blocked
test('Student blocked booking maintenance resource', function () {
    $bs = new BookingService();
    $date = date('Y-m-d', strtotime('+3 days'));
    $r = $bs->createBooking([
        'user_id' => 9, 'resource_id' => 7, // LAB-D101 is maintenance in seed_isvnu.sql
        'start_datetime' => "$date 09:00:00", 'end_datetime' => "$date 11:00:00",
        'purpose' => 'Lab test',
    ]);
    return $r['success'] === false && str_contains($r['message'], 'maintenance');
});

// 8. Peak hour limit
test('Student blocked after 2 peak-hour bookings per week', function () use ($baseOffset) {
    $bs = new BookingService();
    $userId = 9;
    $base = strtotime('+' . ($baseOffset + 300) . ' days');
    $mon = date('Y-m-d', strtotime('monday', $base));
    $tue = date('Y-m-d', strtotime('tuesday', strtotime($mon)));
    $wed = date('Y-m-d', strtotime('wednesday', strtotime($mon)));
    $r1 = $bs->createBooking(['user_id'=>$userId,'resource_id'=>1,'start_datetime'=>"$mon 07:30:00",'end_datetime'=>"$mon 09:30:00",'purpose'=>'Peak 1']);
    $r2 = $bs->createBooking(['user_id'=>$userId,'resource_id'=>1,'start_datetime'=>"$tue 07:30:00",'end_datetime'=>"$tue 09:30:00",'purpose'=>'Peak 2']);
    if (!$r1['success'] || !$r2['success']) return 'Setup failed: ' . ($r1['message']??'') . ' / ' . ($r2['message']??'');
    $r3 = $bs->createBooking(['user_id'=>$userId,'resource_id'=>1,'start_datetime'=>"$mon 13:00:00",'end_datetime'=>"$mon 15:00:00",'purpose'=>'Peak 3']);
    return $r3['success'] === false && str_contains(strtolower($r3['message']), 'peak');
});

// 9. Lab booking pending
test('Laboratory booking becomes pending', function () use ($baseOffset) {
    $bs = new BookingService();
    $mon = date('Y-m-d', strtotime('monday', strtotime('+' . ($baseOffset + 75) . ' days')));
    $r = $bs->createBooking([
        'user_id' => 9, 'resource_id' => 5,
        'start_datetime' => "$mon 07:30:00", 'end_datetime' => "$mon 10:30:00",
        'purpose' => 'Lab session',
    ]);
    return $r['success'] === true && ($r['booking']['status'] ?? '') === 'pending';
});

// 10. Lecturer approve
test('Lecturer can approve a booking', function () {
    $repo = new BookingRepository();
    $pending = $repo->findAll(['status' => 'pending'], 1, 0);
    if (empty($pending)) return 'No pending bookings';
    $id = (int) $pending[0]['id'];
    $svc = new ApprovalService();
    $r = $svc->approve($id, 3, 'Approved for test');
    return $r['success'] === true;
});

// 11. Reject test
test('Lecturer can reject a booking', function () use ($baseOffset) {
    $bs = new BookingService();
    $base = strtotime('+' . ($baseOffset + 80) . ' days');
    $wed = date('Y-m-d', strtotime('wednesday', strtotime('monday', $base)));
    $created = $bs->createBooking([
        'user_id' => 9, 'resource_id' => 18,
        'start_datetime' => "$wed 14:00:00", 'end_datetime' => "$wed 16:00:00",
        'purpose' => 'Media studio test',
    ]);
    if (!$created['success']) return 'Could not create pending booking: ' . ($created['message']??'');
    $id = (int) $created['booking']['id'];
    $svc = new ApprovalService();
    $r = $svc->reject($id, 3, 'Rejected for test');
    return $r['success'] === true;
});

// 12. Cancel test
test('Student can cancel booking with reason', function () use ($far) {
    $bs = new BookingService();
    $date = $far(65);
    $created = $bs->createBooking([
        'user_id' => 9, 'resource_id' => 2,
        'start_datetime' => "$date 14:00:00", 'end_datetime' => "$date 16:00:00",
        'purpose' => 'Cancel test',
    ]);
    if (!$created['success']) return 'Could not create booking: ' . ($created['message']??'');
    $id = (int) $created['booking']['id'];
    $cs = new CancellationService();
    $r = $cs->cancel($id, 9, 'Schedule changed', false);
    return $r['success'] === true;
});

// 13. Admin view reports
test('Admin can view usage reports', function () {
    $svc = new ReportService();
    $data = $svc->getDashboardChartData();
    $reports = $svc->getAll([], 5, 0);
    return is_array($data) && is_array($reports);
});

// 14. Admin view audit logs
test('Admin can view audit logs', function () {
    $repo = new AuditLogRepository();
    $logs = $repo->findAll([], 5, 0);
    return count($logs) > 0;
});

// 15. Unauthorized - student cannot access admin-only (simulated via middleware role check)
test('Unauthorized role check works', function () {
    Auth::login(['id'=>2,'full_name'=>'Student'], ['Student']);
    $blocked = !Auth::hasAnyRole(['Admin']);
    Auth::logout();
    return $blocked === true;
});

// 16. SettingRepository CRUD
test('SettingRepository can read and write settings', function () {
    $repo = new SettingRepository();
    $orig = $repo->getValue('system_name');
    $repo->update('system_name', 'Test System Unique Name');
    $newVal = $repo->getValue('system_name');
    $repo->update('system_name', $orig);
    return $newVal === 'Test System Unique Name';
});

// 17. Student blocked from booking during maintenance mode
test('Student booking blocked when maintenance mode is active', function () {
    $repo = new SettingRepository();
    $bs = new BookingService();
    
    $repo->update('maintenance_mode', '1');
    setting('maintenance_mode', null, true); // force reload cache
    
    $date = date('Y-m-d', strtotime('+40 days'));
    $r = $bs->createBooking([
        'user_id' => 9, // Student
        'resource_id' => 2,
        'start_datetime' => "$date 10:00:00",
        'end_datetime' => "$date 12:00:00",
        'purpose' => 'Maintenance test',
    ]);
    
    $repo->update('maintenance_mode', '0');
    setting('maintenance_mode', null, true); // force reload cache
    
    return $r['success'] === false && str_contains($r['message'], 'maintenance');
});

// 18. Admin allowed to book during maintenance mode
test('Admin booking allowed when maintenance mode is active', function () {
    $repo = new SettingRepository();
    $bs = new BookingService();
    
    $repo->update('maintenance_mode', '1');
    setting('maintenance_mode', null, true); // force reload cache
    
    $date = date('Y-m-d', strtotime('+42 days'));
    $r = $bs->createBooking([
        'user_id' => 1, // Admin (Nguyen Thi Huong Giang)
        'resource_id' => 2,
        'start_datetime' => "$date 10:00:00",
        'end_datetime' => "$date 12:00:00",
        'purpose' => 'Admin booking under maintenance',
    ]);
    
    $repo->update('maintenance_mode', '0');
    setting('maintenance_mode', null, true); // force reload cache
    
    if ($r['success']) {
        $db = Database::getInstance()->getConnection();
        $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([(int) $r['booking']['id']]);
    }
    
    return $r['success'] === true;
});

// 19. Conflict recommendations are populated during booking overlap
test('Conflict recommendations are populated during booking overlap', function () use ($baseOffset) {
    $bs = new BookingService();
    $mon = date('Y-m-d', strtotime('monday', strtotime('+' . ($baseOffset + 15) . ' days')));
    
    $r1 = $bs->createBooking([
        'user_id' => 9, 'resource_id' => 1,
        'start_datetime' => "$mon 07:30:00", 'end_datetime' => "$mon 09:30:00",
        'purpose' => 'Setup conflict recs',
    ]);
    if (!$r1['success']) {
        return 'Setup failed: ' . ($r1['message'] ?? '');
    }

    $r2 = $bs->createBooking([
        'user_id' => 9, 'resource_id' => 1,
        'start_datetime' => "$mon 07:30:00", 'end_datetime' => "$mon 09:30:00",
        'purpose' => 'Trigger conflict recs',
    ]);

    $hasRecs = isset($r2['recommendations']) &&
               !empty($r2['recommendations']['alternative_slots']) &&
               is_array($r2['recommendations']['alternative_resources']);
               
    return $r2['success'] === false && $hasRecs;
});

// 20. QR Code Check-in and Check-out workflow
test('QR Code Check-in and Check-out workflow', function () {
    $bs = new BookingService();
    $repo = new BookingRepository();
    $db = Database::getInstance()->getConnection();
    
    $qrToken = bin2hex(random_bytes(16));
    $ref = $repo->generateReference();
    $id = $repo->create([
        'booking_reference' => $ref,
        'user_id' => 9,
        'resource_id' => 1,
        'start_datetime' => date('Y-m-d H:i:s', time() - 300), // started 5 mins ago
        'end_datetime' => date('Y-m-d H:i:s', time() + 3600), // ends in 1 hour
        'purpose' => 'Check-in test',
        'status' => 'approved',
        'requires_approval' => 1,
        'qr_token' => $qrToken,
    ]);

    $resIn = $bs->checkIn($qrToken, 9, false);
    if (!$resIn['success']) {
        return 'Check-in failed: ' . ($resIn['message'] ?? '');
    }

    $bookingObj = $repo->findById($id);
    if ((int)$bookingObj['checked_in'] !== 1) {
        return 'checked_in flag not set';
    }

    $resOut = $bs->checkOut($qrToken, 9, false);
    if (!$resOut['success']) {
        return 'Check-out failed: ' . ($resOut['message'] ?? '');
    }

    $bookingObj = $repo->findById($id);
    
    $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
    
    return $bookingObj['status'] === 'completed';
});

// 21. Auto-release no-shows cancels booking and marks is_no_show
test('Auto-release no-shows cancels booking and marks is_no_show', function () {
    $bs = new BookingService();
    $repo = new BookingRepository();
    $db = Database::getInstance()->getConnection();
    
    $ref = $repo->generateReference();
    $id = $repo->create([
        'booking_reference' => $ref,
        'user_id' => 9,
        'resource_id' => 1,
        'start_datetime' => date('Y-m-d H:i:s', time() - 1200), // 20 mins ago
        'end_datetime' => date('Y-m-d H:i:s', time() + 3600),
        'purpose' => 'Auto-release no-show test',
        'status' => 'approved',
        'requires_approval' => 1,
        'qr_token' => bin2hex(random_bytes(16)),
    ]);

    $releasedCount = $bs->autoReleaseNoShows();
    if ($releasedCount < 1) {
        return 'Auto-release did not release any bookings';
    }

    $bookingObj = $repo->findById($id);
    
    $db->prepare('DELETE FROM cancellations WHERE booking_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
    
    return $bookingObj['status'] === 'cancelled' && (int)$bookingObj['is_no_show'] === 1;
});

// 22. Waitlist system flow
test('Waitlist system flow (join, notify on cancel, confirm)', function () {
    $bs = new BookingService();
    $cs = new CancellationService();
    $ws = new WaitlistService();
    $db = Database::getInstance()->getConnection();

    $date = date('Y-m-d', strtotime('+70 days'));
    $start = "$date 13:00:00";
    $end = "$date 15:00:00";

    // 1. Create original booking for Student 9
    $r1 = $bs->createBooking([
        'user_id' => 9,
        'resource_id' => 1,
        'start_datetime' => $start,
        'end_datetime' => $end,
        'purpose' => 'Waitlist original booking',
    ]);
    if (!$r1['success']) {
        return 'Failed to create original booking: ' . ($r1['message'] ?? '');
    }
    $bookingId1 = (int) $r1['booking']['id'];

    // 2. Student 10 joins the waitlist for the same slot
    $r2 = $ws->joinWaitlist(10, 1, $start, $end);
    if (!$r2['success']) {
        return 'Failed to join waitlist: ' . ($r2['message'] ?? '');
    }
    $waitlistId = (int) $r2['waitlist_id'];

    // 3. Student 9 cancels the booking
    $r3 = $cs->cancel($bookingId1, 9, 'Not needed anymore', false);
    if (!$r3['success']) {
        return 'Failed to cancel original booking: ' . ($r3['message'] ?? '');
    }

    // 4. Verify waitlist entry was notified
    $entry = (new WaitlistRepository())->findById($waitlistId);
    if ($entry['status'] !== 'notified') {
        return 'Waitlist entry status was not updated to notified, got: ' . $entry['status'];
    }

    // 5. Student 10 confirms booking from waitlist
    $r4 = $ws->confirmFromWaitlist($waitlistId, 10);
    if (!$r4['success']) {
        return 'Failed to confirm booking from waitlist: ' . ($r4['message'] ?? '');
    }
    $bookingId2 = (int) $r4['booking']['id'];

    // Verify waitlist status is now confirmed
    $entry2 = (new WaitlistRepository())->findById($waitlistId);
    if ($entry2['status'] !== 'confirmed') {
        return 'Waitlist entry status was not updated to confirmed, got: ' . $entry2['status'];
    }

    // Cleanup
    $db->prepare('DELETE FROM cancellations WHERE booking_id IN (?, ?)')->execute([$bookingId1, $bookingId2]);
    $db->prepare('DELETE FROM bookings WHERE id IN (?, ?)')->execute([$bookingId1, $bookingId2]);
    $db->prepare('DELETE FROM waitlist WHERE id = ?')->execute([$waitlistId]);
    $db->prepare('DELETE FROM notifications WHERE user_id IN (9, 10)')->execute();

    return true;
});

// 23. API checkAvailability and calendar events
test('API checkAvailability and calendar events', function() {
    Auth::login(['id'=>9,'full_name'=>'Student'], ['Student']);
    $api = new ApiController(9, 'Student');
    
    $_GET['resource_id'] = 1;
    $_GET['start_datetime'] = date('Y-m-d H:i:s', time() + 7200);
    $_GET['end_datetime'] = date('Y-m-d H:i:s', time() + 10800);
    $res = $api->checkAvailability();
    
    if (!isset($res['success']) || !isset($res['available'])) {
        Auth::logout();
        return 'checkAvailability returned invalid response';
    }
    
    $_GET['start'] = date('Y-m-d');
    $_GET['end'] = date('Y-m-d');
    $cal = $api->getCalendarEvents();
    
    Auth::logout();
    return isset($cal['success']) && is_array($cal['events']);
});

// 24. Maintenance impact detection and lifecycle
test('Maintenance impact detection and lifecycle', function () {
    $bs = new BookingService();
    $ms = new MaintenanceService();
    $mRepo = new MaintenanceRepository();
    $rRepo = new ResourceRepository();
    $db = Database::getInstance()->getConnection();

    $date = date('Y-m-d', strtotime('+80 days'));
    $start = "$date 09:00:00";
    $end = "$date 12:00:00";

    // 1. Create a booking that will be impacted
    $r1 = $bs->createBooking([
        'user_id' => 9,
        'resource_id' => 1,
        'start_datetime' => "$date 10:00:00",
        'end_datetime' => "$date 11:00:00",
        'purpose' => 'Impacted booking test',
    ]);
    if (!$r1['success']) {
        return 'Failed to create booking for impact: ' . ($r1['message'] ?? '');
    }
    $bookingId = (int) $r1['booking']['id'];

    // 2. Schedule maintenance during that window
    $mId = $mRepo->create([
        'resource_id' => 1,
        'maintenance_start' => $start,
        'maintenance_end' => $end,
        'reason' => 'Oscilloscope calibration',
        'status' => 'scheduled',
        'created_by' => 1,
    ]);
    if (!$mId) {
        return 'Failed to create maintenance schedule';
    }

    // 3. Detect impacted bookings
    $impacted = $ms->detectImpactedBookings(1, $start, $end);
    $found = false;
    foreach ($impacted as $b) {
        if ((int)$b['id'] === $bookingId) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        return 'Impacted booking not detected';
    }

    // 4. Notify impacted users
    $notifyRes = $ms->notifyImpactedUsers($mId);
    if (!$notifyRes['success'] || $notifyRes['notified_count'] < 1) {
        return 'Notification failed: ' . ($notifyRes['message'] ?? '');
    }

    // 5. Activate maintenance (should notify again and lock resource)
    $actRes = $ms->activateMaintenance($mId, 1);
    if (!$actRes['success']) {
        return 'Activation failed: ' . ($actRes['message'] ?? '');
    }
    $resource = $rRepo->findById(1);
    if ($resource['status'] !== 'maintenance') {
        return 'Resource status did not change to maintenance';
    }

    // 6. Complete maintenance (should restore resource status)
    $compRes = $ms->completeMaintenance($mId, 1);
    if (!$compRes['success']) {
        return 'Completion failed: ' . ($compRes['message'] ?? '');
    }
    $resource = $rRepo->findById(1);
    if ($resource['status'] !== 'available') {
        return 'Resource status did not restore to available';
    }

    // Cleanup
    $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([$bookingId]);
    $db->prepare('DELETE FROM maintenance_schedules WHERE id = ?')->execute([$mId]);
    $db->prepare('DELETE FROM notifications WHERE user_id = 9')->execute();

    return true;
});

// 25. BookingPolicyRepository CRUD
test('BookingPolicyRepository read and write', function() {
    $repo = new BookingPolicyRepository();
    
    // Create a temporary policy
    $id = $repo->create([
        'category_id' => 1,
        'policy_name' => 'Temp Test Policy',
        'max_duration_hours' => 3.5,
        'weekly_quota' => 8,
        'max_peak_slots_per_week' => 3,
        'cancellation_deadline_hours' => 12,
        'requires_approval' => 1,
        'auto_approval_enabled' => 0,
        'is_active' => 1,
    ]);
    
    if ($id <= 0) {
        return 'Failed to create test policy';
    }
    
    $policy = $repo->findById($id);
    if (!$policy || $policy['policy_name'] !== 'Temp Test Policy' || (float)$policy['max_duration_hours'] !== 3.5) {
        $repo->delete($id);
        return 'Policy fields retrieved do not match';
    }
    
    // Update the policy
    $repo->update($id, [
        'category_id' => 1,
        'policy_name' => 'Updated Temp Policy',
        'max_duration_hours' => 4.0,
        'weekly_quota' => 8,
        'max_peak_slots_per_week' => 3,
        'cancellation_deadline_hours' => 12,
        'requires_approval' => 1,
        'auto_approval_enabled' => 0,
        'is_active' => 1,
    ]);
    
    $updated = $repo->findById($id);
    if (!$updated || $updated['policy_name'] !== 'Updated Temp Policy' || (float)$updated['max_duration_hours'] !== 4.0) {
        $repo->delete($id);
        return 'Updated policy fields do not match';
    }
    
    // Clean up
    $repo->delete($id);
    return true;
});

// 26. Feedback and Rating system flow
test('Feedback and Rating system flow', function () {
    $fs = new FeedbackService();
    $bRepo = new BookingRepository();
    $db = Database::getInstance()->getConnection();

    // 1. Create a completed booking
    $ref = $bRepo->generateReference();
    $bId = $bRepo->create([
        'booking_reference' => $ref,
        'user_id' => 9,
        'resource_id' => 1,
        'start_datetime' => date('Y-m-d H:i:s', time() - 7200),
        'end_datetime' => date('Y-m-d H:i:s', time() - 3600),
        'purpose' => 'Feedback test booking',
        'status' => 'completed',
    ]);

    if ($bId <= 0) {
        return 'Failed to create completed booking';
    }

    // 2. Submit feedback
    $res = $fs->submitFeedback($bId, 9, [
        'rating' => 5,
        'cleanliness_rating' => 4,
        'equipment_rating' => 5,
        'comment' => 'Very clean and the screen worked perfectly!',
    ]);

    if (!$res['success']) {
        $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([$bId]);
        return 'Failed to submit feedback: ' . ($res['message'] ?? '');
    }
    $fbId = (int) $res['feedback_id'];

    // Verify feedback fields
    $fb = $fs->getBookingFeedback($bId);
    if (!$fb || (int)$fb['rating'] !== 5 || $fb['comment'] !== 'Very clean and the screen worked perfectly!') {
        $db->prepare('DELETE FROM booking_feedback WHERE id = ?')->execute([$fbId]);
        $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([$bId]);
        return 'Feedback fields do not match';
    }

    // 3. Attempt duplicate feedback (should fail)
    $resDup = $fs->submitFeedback($bId, 9, [
        'rating' => 3,
    ]);
    if ($resDup['success']) {
        $db->prepare('DELETE FROM booking_feedback WHERE id = ?')->execute([$fbId]);
        $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([$bId]);
        return 'Duplicate feedback was incorrectly allowed';
    }

    // Cleanup
    $db->prepare('DELETE FROM booking_feedback WHERE id = ?')->execute([$fbId]);
    $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([$bId]);

    return true;
});

// 27. Password reset flow
test('Password reset flow', function () {
    $as = new AuthService();
    $db = Database::getInstance()->getConnection();

    // 1. Create a password reset token for student user (id = 9)
    $resToken = $as->createPasswordResetToken('student@is.vnu.edu.vn');
    if (!$resToken['success']) {
        return 'Failed to create password reset token: ' . ($resToken['message'] ?? '');
    }

    // 2. Fetch the token from DB
    $stmt = $db->prepare('SELECT token FROM password_resets WHERE user_id = 9 AND used = 0 ORDER BY created_at DESC LIMIT 1');
    $stmt->execute();
    $token = $stmt->fetchColumn();

    if (!$token) {
        return 'Token was not found in the database';
    }

    // 3. Reset the password
    $resReset = $as->resetPassword($token, 'newstudent123');
    if (!$resReset['success']) {
        return 'Failed to reset password: ' . ($resReset['message'] ?? '');
    }

    // 4. Try logging in with new password
    $loginRes = $as->login('student@is.vnu.edu.vn', 'newstudent123');
    if (!$loginRes['success']) {
        return 'Failed to log in with newly reset password: ' . ($loginRes['message'] ?? '');
    }

    // 5. Restore the original password (student123)
    $resRestore = $as->resetPassword($token, 'student123'); // Wait, token is marked as used now!
    // We can update the password directly using UserRepository
    $uRepo = new UserRepository();
    $uRepo->update(9, ['password' => 'student123']);

    // Cleanup
    $db->prepare('DELETE FROM password_resets WHERE user_id = 9')->execute();

    return true;
});

// 28. CSV Users Import flow
test('CSV Users Import flow', function () {
    $is = new ImportService();
    $uRepo = new UserRepository();
    $db = Database::getInstance()->getConnection();

    $csvContent = "\xEF\xBB\xBFfull_name,username,email,password,student_code\n";
    $csvContent .= "Import Test User 1,importuser1,importuser1@is.vnu.edu.vn,password123,STUIMP001\n";
    $csvContent .= "Import Test User 2,importuser2,importuser2@is.vnu.edu.vn,password123,STUIMP002\n";

    $tmpFile = tempnam(sys_get_temp_dir(), 'csv_test');
    file_put_contents($tmpFile, $csvContent);

    $fileData = [
        'tmp_name' => $tmpFile,
    ];

    $preview = $is->previewUsers($fileData);
    if (!empty($preview['errors'])) {
        unlink($tmpFile);
        return 'Preview failed with errors: ' . json_encode($preview['errors']);
    }

    if (count($preview['valid']) !== 2) {
        unlink($tmpFile);
        return 'Preview did not detect exactly 2 valid users, got: ' . count($preview['valid']);
    }

    $importRes = $is->importUsers($preview['valid'], 1);
    if (!$importRes['success'] || (int)$importRes['imported'] !== 2) {
        unlink($tmpFile);
        return 'Import failed: ' . ($importRes['message'] ?? '');
    }

    $user1 = $uRepo->findByLogin('importuser1');
    $user2 = $uRepo->findByLogin('importuser2');
    
    $ok = ($user1 && $user2 && $user1['full_name'] === 'Import Test User 1' && $user2['full_name'] === 'Import Test User 2');

    if ($user1) {
        $db->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([(int) $user1['id']]);
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([(int) $user1['id']]);
    }
    if ($user2) {
        $db->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([(int) $user2['id']]);
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([(int) $user2['id']]);
    }
    $db->prepare('DELETE FROM audit_logs WHERE action = "import_users"')->execute();
    unlink($tmpFile);

    return $ok;
});

// 29. Equipment Addons validation and allocation
test('Booking resource with equipment addons validates stock and records allocations', function () use ($baseOffset) {
    $bs = new BookingService();
    $eqRepo = new EquipmentRepository();
    $bEqRepo = new BookingEquipmentRepository();
    $db = Database::getInstance()->getConnection();

    $mon = date('Y-m-d', strtotime('monday', strtotime('+' . ($baseOffset + 120) . ' days')));
    $start = "$mon 09:30:00";
    $end = "$mon 11:30:00";

    // Equipment 4 is 'Máy quay 4K' which has total stock 4.
    // Let's create booking 1 requesting 3 units.
    $r1 = $bs->createBooking([
        'user_id' => 9,
        'resource_id' => 1,
        'start_datetime' => $start,
        'end_datetime' => $end,
        'purpose' => 'Equipment booking test 1',
        'equipment' => [
            4 => 3
        ]
    ]);

    if (!$r1['success']) {
        return 'Failed to create booking with 3 Sony FX3: ' . ($r1['message'] ?? '');
    }
    $bookingId1 = (int) $r1['booking']['id'];

    // Verify booking equipment has the correct quantity
    $allocated = $bEqRepo->findByBooking($bookingId1);
    if (count($allocated) !== 1 || (int)$allocated[0]['equipment_id'] !== 4 || (int)$allocated[0]['quantity'] !== 3) {
        $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([$bookingId1]);
        return 'Booking equipment association not recorded correctly';
    }

    // Now try to create a parallel booking requesting 2 units of equipment 4.
    // Since only 1 unit is left, this should fail.
    $r2 = $bs->createBooking([
        'user_id' => 10,
        'resource_id' => 2,
        'start_datetime' => $start,
        'end_datetime' => $end,
        'purpose' => 'Equipment booking test 2 (should fail)',
        'equipment' => [
            4 => 2
        ]
    ]);

    if ($r2['success']) {
        $bookingId2 = (int) $r2['booking']['id'];
        $db->prepare('DELETE FROM bookings WHERE id IN (?, ?)')->execute([$bookingId1, $bookingId2]);
        return 'Should not allow booking more equipment than available stock';
    }

    // Try to book with 1 unit of equipment 4, which is the remaining available stock.
    // This should succeed.
    $r3 = $bs->createBooking([
        'user_id' => 10,
        'resource_id' => 2,
        'start_datetime' => $start,
        'end_datetime' => $end,
        'purpose' => 'Equipment booking test 3 (should succeed)',
        'equipment' => [
            4 => 1
        ]
    ]);

    if (!$r3['success']) {
        $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([$bookingId1]);
        return 'Failed to book remaining equipment stock: ' . ($r3['message'] ?? '');
    }
    $bookingId3 = (int) $r3['booking']['id'];

    // Cleanup
    $db->prepare('DELETE FROM booking_equipment WHERE booking_id IN (?, ?)')->execute([$bookingId1, $bookingId3]);
    $db->prepare('DELETE FROM bookings WHERE id IN (?, ?)')->execute([$bookingId1, $bookingId3]);

    return true;
});

echo "\n=== Results: $passed passed, $failed failed ===\n";
exit($failed > 0 ? 1 : 0);
