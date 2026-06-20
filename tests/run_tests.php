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

echo "\n=== Results: $passed passed, $failed failed ===\n";
exit($failed > 0 ? 1 : 0);
