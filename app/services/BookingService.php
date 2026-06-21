<?php
declare(strict_types=1);

class BookingService
{
    private BookingRepository $bookingRepo;
    private ResourceRepository $resourceRepo;
    private MaintenanceRepository $maintenanceRepo;
    private TimeSlotRepository $timeSlotRepo;
    private UserRepository $userRepo;
    private PolicyService $policyService;
    private NotificationService $notificationService;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->bookingRepo = new BookingRepository();
        $this->resourceRepo = new ResourceRepository();
        $this->maintenanceRepo = new MaintenanceRepository();
        $this->timeSlotRepo = new TimeSlotRepository();
        $this->userRepo = new UserRepository();
        $this->policyService = new PolicyService();
        $this->notificationService = new NotificationService();
        $this->auditLog = new AuditLogService();
    }

    public function createBooking(array $data): array
    {
        $resource = $this->resourceRepo->findById((int) $data['resource_id']);
        if (!$resource) {
            return ['success' => false, 'message' => 'Resource not found.'];
        }

        if ($resource['status'] !== 'available') {
            $msg = match ($resource['status']) {
                'maintenance' => 'This resource is currently under maintenance and cannot be booked.',
                'restricted' => 'This resource is restricted and cannot be booked.',
                default => 'This resource is not available for booking.',
            };
            return ['success' => false, 'message' => $msg];
        }

        $userRoles = $this->userRepo->getRoles((int) $data['user_id']);

        // Maintenance Mode Check
        if (setting('maintenance_mode', '0') === '1' && !in_array('Admin', $userRoles, true)) {
            return ['success' => false, 'message' => __('The system is currently undergoing maintenance. Booking functions are temporarily locked.')];
        }

        $policyErrors = $this->policyService->validate($data, $resource, $userRoles);
        if (!empty($policyErrors)) {
            return ['success' => false, 'message' => implode(' ', $policyErrors)];
        }

        // Validate equipment availability
        $bookingEquipmentRepo = new BookingEquipmentRepository();
        $requestedEquipment = $data['equipment'] ?? [];
        if (!empty($requestedEquipment)) {
            $equipmentRepo = new EquipmentRepository();
            foreach ($requestedEquipment as $eqId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) continue;
                
                $eq = $equipmentRepo->findById((int) $eqId);
                if (!$eq || $eq['status'] !== 'available') {
                    return ['success' => false, 'message' => "Requested equipment #$eqId is not available."];
                }
                
                $allocated = $bookingEquipmentRepo->getAllocatedQuantity((int) $eqId, $data['start_datetime'], $data['end_datetime']);
                $available = (int) $eq['quantity'] - $allocated;
                if ($qty > $available) {
                    return ['success' => false, 'message' => sprintf('Not enough stock for "%s". Requested: %d, Available: %d.', $eq['equipment_name'], $qty, $available)];
                }
            }
        }

        $conflicts = $this->bookingRepo->findConflicts(
            (int) $data['resource_id'],
            $data['start_datetime'],
            $data['end_datetime']
        );
        if (!empty($conflicts)) {
            $recommendations = $this->findAlternativeRecommendations((int) $data['resource_id'], $data['start_datetime'], $data['end_datetime']);
            return [
                'success' => false,
                'message' => 'This resource is already booked during the selected time period.',
                'recommendations' => $recommendations
            ];
        }

        $maintenance = $this->maintenanceRepo->findActiveForResource(
            (int) $data['resource_id'],
            $data['start_datetime'],
            $data['end_datetime']
        );
        if (!empty($maintenance)) {
            return ['success' => false, 'message' => 'This resource is currently under maintenance and cannot be booked.'];
        }

        $isPeak = $this->timeSlotRepo->isPeakSlot(
            (int) $data['resource_id'],
            $data['start_datetime'],
            $data['end_datetime']
        );

        if ($isPeak && in_array('Student', $userRoles, true)) {
            $peakCount = $this->bookingRepo->countPeakBookingsThisWeek((int) $data['user_id'], $data['start_datetime']);
            $maxPeak = $this->policyService->getMaxPeakSlotsPerWeek((int) $resource['category_id']);
            if ($peakCount >= $maxPeak) {
                return [
                    'success' => false,
                    'message' => 'You have reached the maximum number of peak-hour bookings this week.',
                ];
            }
        }

        $requiresApproval = $this->policyService->requiresApproval($resource);
        $canAutoApprove = $this->policyService->canAutoApprove($resource);

        $status = 'approved';
        if ($requiresApproval && !$canAutoApprove) {
            if (!in_array('Lecturer', $userRoles, true) && !in_array('Admin', $userRoles, true)) {
                $status = 'pending';
            }
        }

        $reference = $this->bookingRepo->generateReference();
        $qrToken = bin2hex(random_bytes(16));

        $bookingId = $this->bookingRepo->create([
            'booking_reference' => $reference,
            'user_id' => (int) $data['user_id'],
            'resource_id' => (int) $data['resource_id'],
            'start_datetime' => $data['start_datetime'],
            'end_datetime' => $data['end_datetime'],
            'purpose' => $data['purpose'],
            'additional_notes' => $data['additional_notes'] ?? null,
            'status' => $status,
            'requires_approval' => $requiresApproval ? 1 : 0,
            'qr_token' => $qrToken,
        ]);

        // Insert equipment associations
        if (!empty($requestedEquipment)) {
            foreach ($requestedEquipment as $eqId => $qty) {
                $qty = (int) $qty;
                if ($qty > 0) {
                    $bookingEquipmentRepo->create($bookingId, (int) $eqId, $qty);
                }
            }
        }

        $booking = $this->bookingRepo->findById($bookingId);

        $this->auditLog->log('create_booking', 'bookings', $bookingId, null, [
            'reference' => $reference,
            'status' => $status,
            'resource_id' => (int) $data['resource_id'],
        ]);

        $this->notificationService->notifyBookingCreated((int) $data['user_id'], $booking, $resource);

        if ($status === 'pending') {
            $this->notificationService->notifyApproversPending($booking, $resource);
        }

        return [
            'success' => true,
            'message' => $status === 'pending'
                ? 'Your booking request has been submitted and is waiting for lecturer/admin approval.'
                : 'Your booking has been approved successfully.',
            'booking' => $booking,
        ];
    }

    public function getById(int $id): ?array
    {
        return $this->bookingRepo->findById($id);
    }

    public function getByUser(int $userId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        return $this->bookingRepo->findByUser($userId, $filters, $limit, $offset);
    }

    public function getUpcoming(int $userId, int $limit = 10): array
    {
        return $this->bookingRepo->findUpcoming($userId, $limit);
    }

    public function getDashboardStats(?int $userId = null): array
    {
        return $this->bookingRepo->getDashboardStats($userId);
    }

    public function updateBooking(int $bookingId, array $data, int $actorId, bool $isAdmin = false): array
    {
        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        if (!$isAdmin && (int) $booking['user_id'] !== $actorId) {
            return ['success' => false, 'message' => 'You do not have permission to perform this action.'];
        }

        if (!in_array($booking['status'], ['pending', 'approved'], true)) {
            return ['success' => false, 'message' => 'Only pending or approved bookings can be edited.'];
        }

        if (strtotime($booking['start_datetime']) < time()) {
            return ['success' => false, 'message' => 'Past bookings cannot be edited.'];
        }

        $resource = $this->resourceRepo->findById((int) $data['resource_id']);
        if (!$resource) {
            return ['success' => false, 'message' => 'Resource not found.'];
        }

        if ($resource['status'] !== 'available') {
            $msg = match ($resource['status']) {
                'maintenance' => 'This resource is currently under maintenance and cannot be booked.',
                'restricted' => 'This resource is restricted and cannot be booked.',
                default => 'This resource is not available for booking.',
            };
            return ['success' => false, 'message' => $msg];
        }

        $data['user_id'] = (int) $booking['user_id'];
        $data['exclude_booking_id'] = $bookingId;
        $userRoles = $this->userRepo->getRoles($data['user_id']);

        // Maintenance Mode Check
        if (setting('maintenance_mode', '0') === '1' && !$isAdmin && !in_array('Admin', $userRoles, true)) {
            return ['success' => false, 'message' => __('The system is currently undergoing maintenance. Booking functions are temporarily locked.')];
        }

        $policyErrors = $this->policyService->validate($data, $resource, $userRoles);
        if (!empty($policyErrors)) {
            return ['success' => false, 'message' => implode(' ', $policyErrors)];
        }

        $conflicts = $this->bookingRepo->findConflicts(
            (int) $data['resource_id'],
            $data['start_datetime'],
            $data['end_datetime'],
            $bookingId
        );
        if (!empty($conflicts)) {
            return ['success' => false, 'message' => 'This resource is already booked during the selected time period.'];
        }

        $maintenance = $this->maintenanceRepo->findActiveForResource(
            (int) $data['resource_id'],
            $data['start_datetime'],
            $data['end_datetime']
        );
        if (!empty($maintenance)) {
            return ['success' => false, 'message' => 'This resource is currently under maintenance and cannot be booked.'];
        }

        $isPeak = $this->timeSlotRepo->isPeakSlot(
            (int) $data['resource_id'],
            $data['start_datetime'],
            $data['end_datetime']
        );

        if ($isPeak && in_array('Student', $userRoles, true)) {
            $peakCount = $this->bookingRepo->countPeakBookingsThisWeek(
                $data['user_id'],
                $data['start_datetime'],
                $bookingId
            );
            $maxPeak = $this->policyService->getMaxPeakSlotsPerWeek((int) $resource['category_id']);
            if ($peakCount >= $maxPeak) {
                return [
                    'success' => false,
                    'message' => 'You have reached the maximum number of peak-hour bookings this week.',
                ];
            }
        }

        $oldValues = [
            'resource_id' => (int) $booking['resource_id'],
            'start_datetime' => $booking['start_datetime'],
            'end_datetime' => $booking['end_datetime'],
            'purpose' => $booking['purpose'],
        ];

        $this->bookingRepo->update($bookingId, [
            'resource_id' => (int) $data['resource_id'],
            'start_datetime' => $data['start_datetime'],
            'end_datetime' => $data['end_datetime'],
            'purpose' => $data['purpose'],
            'additional_notes' => $data['additional_notes'] ?? null,
        ]);

        $this->auditLog->log('update_booking', 'bookings', $bookingId, $oldValues, [
            'resource_id' => (int) $data['resource_id'],
            'start_datetime' => $data['start_datetime'],
            'end_datetime' => $data['end_datetime'],
            'purpose' => $data['purpose'],
        ]);

        $updated = $this->bookingRepo->findById($bookingId);

        return [
            'success' => true,
            'message' => 'Booking updated successfully.',
            'booking' => $updated,
        ];
    }

    public function checkAvailability(
        int $resourceId,
        string $startDatetime,
        string $endDatetime,
        int $userId,
        ?int $excludeBookingId = null
    ): array {
        $resource = $this->resourceRepo->findById($resourceId);
        if (!$resource) {
            return ['success' => false, 'available' => false, 'message' => 'Resource not found.'];
        }

        if ($resource['status'] !== 'available') {
            return ['success' => false, 'available' => false, 'message' => 'This resource is not available for booking.'];
        }

        $data = [
            'user_id' => $userId,
            'resource_id' => $resourceId,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'purpose' => 'Availability check',
            'exclude_booking_id' => $excludeBookingId,
        ];
        $userRoles = $this->userRepo->getRoles($userId);
        $policyErrors = $this->policyService->validate($data, $resource, $userRoles);
        if (!empty($policyErrors)) {
            return ['success' => false, 'available' => false, 'message' => $policyErrors[0]];
        }

        $conflicts = $this->bookingRepo->findConflicts($resourceId, $startDatetime, $endDatetime, $excludeBookingId);
        if (!empty($conflicts)) {
            return ['success' => false, 'available' => false, 'message' => 'This resource is already booked during the selected time period.'];
        }

        $maintenance = $this->maintenanceRepo->findActiveForResource($resourceId, $startDatetime, $endDatetime);
        if (!empty($maintenance)) {
            return ['success' => false, 'available' => false, 'message' => 'This resource is currently under maintenance and cannot be booked.'];
        }

        return ['success' => true, 'available' => true, 'message' => 'Time slot is available.'];
    }

    public function findAlternativeRecommendations(int $resourceId, string $startDatetime, string $endDatetime): array
    {
        $resource = $this->resourceRepo->findById($resourceId);
        if (!$resource) {
            return ['alternative_slots' => [], 'alternative_resources' => []];
        }

        $categoryId = (int) $resource['category_id'];
        $capacity = (int) $resource['capacity'];

        $alternativeSlots = [];
        $currentTry = strtotime($startDatetime);
        $duration = strtotime($endDatetime) - strtotime($startDatetime);
        
        for ($i = 1; $i <= 24; $i++) {
            $tryStart = $currentTry + ($i * 7200); // 2 hour steps
            $tryEnd = $tryStart + $duration;
            
            $startStr = date('Y-m-d H:i:s', $tryStart);
            $endStr = date('Y-m-d H:i:s', $tryEnd);
            
            $dayOfWeek = (int) date('w', $tryStart);
            $daySlots = $this->timeSlotRepo->findByResource($resourceId);
            $inActiveSlot = empty($daySlots);
            foreach ($daySlots as $ts) {
                if ((int)$ts['day_of_week'] === $dayOfWeek && (int)$ts['is_active'] === 1) {
                    $tsStart = strtotime(date('Y-m-d ', $tryStart) . $ts['start_time']);
                    $tsEnd = strtotime(date('Y-m-d ', $tryStart) . $ts['end_time']);
                    if ($tryStart >= $tsStart && $tryEnd <= $tsEnd) {
                        $inActiveSlot = true;
                        break;
                    }
                }
            }
            if (!$inActiveSlot) {
                continue;
            }

            $conflicts = $this->bookingRepo->findConflicts($resourceId, $startStr, $endStr);
            if (empty($conflicts)) {
                $alternativeSlots[] = [
                    'booking_date' => date('Y-m-d', $tryStart),
                    'start_time' => date('H:i', $tryStart),
                    'end_time' => date('H:i', $tryEnd)
                ];
                if (count($alternativeSlots) >= 3) {
                    break;
                }
            }
        }

        $alternativeResources = [];
        $sameCatResources = $this->resourceRepo->findAll(['category_id' => $categoryId], 100, 0);
        foreach ($sameCatResources as $otherRes) {
            if ((int) $otherRes['id'] === $resourceId) {
                continue;
            }
            if ($otherRes['status'] !== 'available') {
                continue;
            }
            if ((int) $otherRes['capacity'] < $capacity) {
                continue;
            }

            $conflicts = $this->bookingRepo->findConflicts((int) $otherRes['id'], $startDatetime, $endDatetime);
            if (empty($conflicts)) {
                $alternativeResources[] = [
                    'id' => $otherRes['id'],
                    'resource_name' => $otherRes['resource_name'],
                    'resource_code' => $otherRes['resource_code'],
                    'location' => $otherRes['location'] ?? ''
                ];
                if (count($alternativeResources) >= 3) {
                    break;
                }
            }
        }

        return [
            'alternative_slots' => $alternativeSlots,
            'alternative_resources' => $alternativeResources
        ];
    }

    public function checkIn(string $token, int $actorId, bool $isAdminOrStaff): array
    {
        $booking = $this->bookingRepo->findByQrToken($token);
        if (!$booking) {
            return ['success' => false, 'message' => 'Invalid QR token.'];
        }

        if ($booking['user_id'] !== $actorId && !$isAdminOrStaff) {
            return ['success' => false, 'message' => 'Unauthorized to check in this booking.'];
        }

        if ($booking['status'] !== 'approved') {
            return ['success' => false, 'message' => 'Only approved bookings can be checked in. Current status: ' . $booking['status']];
        }

        if ((int) $booking['checked_in'] === 1) {
            return ['success' => false, 'message' => 'This booking is already checked in.'];
        }

        $start = strtotime($booking['start_datetime']);
        $end = strtotime($booking['end_datetime']);
        $now = time();

        if ($now < $start - 900) { // 15 mins early max
            return ['success' => false, 'message' => 'Check-in is only allowed up to 15 minutes before the start time.'];
        }
        if ($now > $end) {
            return ['success' => false, 'message' => 'This booking period has already ended. Check-in is expired.'];
        }

        $this->bookingRepo->update((int) $booking['id'], [
            'checked_in' => 1,
            'check_in_time' => date('Y-m-d H:i:s')
        ]);

        $this->auditLog->log('check_in', 'bookings', (int) $booking['id'], null, [
            'reference' => $booking['booking_reference'],
            'checked_in' => 1
        ]);

        return ['success' => true, 'message' => 'Checked in successfully! Enjoy your resource.', 'booking' => $booking];
    }

    public function checkOut(string $token, int $actorId, bool $isAdminOrStaff): array
    {
        $booking = $this->bookingRepo->findByQrToken($token);
        if (!$booking) {
            return ['success' => false, 'message' => 'Invalid QR token.'];
        }

        if ($booking['user_id'] !== $actorId && !$isAdminOrStaff) {
            return ['success' => false, 'message' => 'Unauthorized to check out this booking.'];
        }

        if ((int) $booking['checked_in'] !== 1) {
            return ['success' => false, 'message' => 'This booking has not been checked in yet.'];
        }

        if ($booking['status'] === 'completed') {
            return ['success' => false, 'message' => 'This booking is already completed.'];
        }

        $this->bookingRepo->update((int) $booking['id'], [
            'status' => 'completed'
        ]);

        $this->auditLog->log('check_out', 'bookings', (int) $booking['id'], null, [
            'reference' => $booking['booking_reference'],
            'status' => 'completed'
        ]);

        return ['success' => true, 'message' => 'Checked out successfully. Thank you!', 'booking' => $booking];
    }

    public function autoReleaseNoShows(): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - 900); // 15 minutes ago
        $expired = $this->bookingRepo->findExpiredApproved($cutoff);
        
        $releasedCount = 0;
        foreach ($expired as $b) {
            $this->bookingRepo->update((int) $b['id'], [
                'status' => 'cancelled',
                'is_no_show' => 1
            ]);

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'INSERT INTO cancellations (booking_id, cancelled_by, reason, cancelled_at) 
                 VALUES (?, ?, ?, NOW())'
            );
            $stmt->execute([
                $b['id'],
                $b['user_id'],
                'Auto-Released: No-show for 15 minutes.'
            ]);

            $this->auditLog->log('auto_release_no_show', 'bookings', (int) $b['id'], null, [
                'reference' => $b['booking_reference'],
                'reason' => 'No-show'
            ]);

            try {
                $msg = "Lịch đặt {$b['booking_reference']} đã bị hệ thống tự động hủy do bạn không điểm danh nhận phòng đúng hạn (quá 15 phút).";
                $this->notificationService->notify((int) $b['user_id'], 'Tự động giải phóng phòng (No-show)', $msg, 'system', (int) $b['id']);
            } catch (Throwable $e) {
                // ignore
            }

            $releasedCount++;
        }
        return $releasedCount;
    }

    /**
     * Create a series of recurring bookings (weekly or monthly).
     * All occurrences are validated BEFORE any are created.
     */
    public function createRecurring(array $data, string $type, int $count): array
    {
        if (!in_array($type, ['weekly', 'monthly'], true)) {
            return ['success' => false, 'message' => 'Invalid recurrence type.'];
        }
        if ($count < 2 || $count > 12) {
            return ['success' => false, 'message' => 'Recurrence count must be between 2 and 12.'];
        }

        $startTs   = strtotime($data['start_datetime']);
        $endTs     = strtotime($data['end_datetime']);
        $duration  = $endTs - $startTs;
        $resourceId = (int) $data['resource_id'];
        $userId     = (int) $data['user_id'];

        // Build all occurrence datetimes
        $occurrences = [];
        for ($i = 0; $i < $count; $i++) {
            if ($type === 'weekly') {
                $offset = $i * 7 * 86400;
            } else { // monthly
                $offset = 0;
                $baseMonth = (int) date('n', $startTs) + $i;
                $baseYear  = (int) date('Y', $startTs) + (int) floor(($baseMonth - 1) / 12);
                $baseMonth = (($baseMonth - 1) % 12) + 1;
                $baseDay   = (int) date('j', $startTs);
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $baseMonth, $baseYear);
                $day = min($baseDay, $daysInMonth);
                $offsetDate = mktime(
                    (int) date('H', $startTs),
                    (int) date('i', $startTs),
                    0,
                    $baseMonth, $day, $baseYear
                );
                $occurrences[] = [
                    'start' => date('Y-m-d H:i:s', $offsetDate),
                    'end'   => date('Y-m-d H:i:s', $offsetDate + $duration),
                ];
                continue;
            }
            $occurrences[] = [
                'start' => date('Y-m-d H:i:s', $startTs + $offset),
                'end'   => date('Y-m-d H:i:s', $startTs + $offset + $duration),
            ];
        }

        // Check all occurrences for conflicts first
        $conflicts = [];
        foreach ($occurrences as $idx => $occ) {
            $conflictCheck = $this->bookingRepo->findConflicts($resourceId, $occ['start'], $occ['end']);
            $maintCheck    = $this->maintenanceRepo->findActiveForResource($resourceId, $occ['start'], $occ['end']);
            if (!empty($conflictCheck) || !empty($maintCheck)) {
                $conflicts[] = [
                    'occurrence' => $idx + 1,
                    'start'      => $occ['start'],
                    'end'        => $occ['end'],
                ];
            }
        }

        // Skip conflicts, create the rest
        $groupId = bin2hex(random_bytes(16)); // UUID-like group identifier
        $created = [];
        $db = Database::getInstance()->getConnection();

        foreach ($occurrences as $idx => $occ) {
            // Skip conflicting occurrences
            $isConflict = false;
            foreach ($conflicts as $c) {
                if ($c['occurrence'] === $idx + 1) {
                    $isConflict = true;
                    break;
                }
            }
            if ($isConflict) {
                continue;
            }

            $singleData = array_merge($data, [
                'start_datetime'    => $occ['start'],
                'end_datetime'      => $occ['end'],
            ]);

            $result = $this->createBooking($singleData);

            if ($result['success'] && isset($result['booking']['id'])) {
                $bId = (int) $result['booking']['id'];
                // Tag with recurring group
                $db->prepare('UPDATE bookings SET recurring_group_id = ?, is_recurring = 1 WHERE id = ?')
                   ->execute([$groupId, $bId]);
                // Record in recurring_bookings table
                $db->prepare(
                    'INSERT INTO recurring_bookings (group_id, booking_id, recurrence_type, occurrence_number)
                     VALUES (?, ?, ?, ?)'
                )->execute([$groupId, $bId, $type, $idx + 1]);

                $created[] = $result['booking'];
            }
        }

        if (empty($created)) {
            return [
                'success'   => false,
                'message'   => 'All occurrences conflict with existing bookings.',
                'conflicts' => $conflicts,
            ];
        }

        return [
            'success'   => true,
            'message'   => count($created) . ' recurring booking(s) created.',
            'bookings'  => $created,
            'conflicts' => $conflicts,
            'group_id'  => $groupId,
        ];
    }
}
