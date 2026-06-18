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

        $policyErrors = $this->policyService->validate($data, $resource, $userRoles);
        if (!empty($policyErrors)) {
            return ['success' => false, 'message' => implode(' ', $policyErrors)];
        }

        $conflicts = $this->bookingRepo->findConflicts(
            (int) $data['resource_id'],
            $data['start_datetime'],
            $data['end_datetime']
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
            $status = 'pending';
        }

        $reference = $this->bookingRepo->generateReference();

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
        ]);

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
}
