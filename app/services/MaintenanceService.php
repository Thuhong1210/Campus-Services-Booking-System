<?php
declare(strict_types=1);

class MaintenanceService
{
    private MaintenanceRepository $maintenanceRepo;
    private BookingRepository $bookingRepo;
    private ResourceRepository $resourceRepo;
    private NotificationService $notificationService;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->maintenanceRepo     = new MaintenanceRepository();
        $this->bookingRepo         = new BookingRepository();
        $this->resourceRepo        = new ResourceRepository();
        $this->notificationService = new NotificationService();
        $this->auditLog            = new AuditLogService();
    }

    /**
     * Detect bookings impacted by a maintenance window.
     */
    public function detectImpactedBookings(int $resourceId, string $start, string $end): array
    {
        return $this->bookingRepo->findConflicts($resourceId, $start, $end);
    }

    /**
     * Notify all users whose bookings fall within a maintenance window.
     */
    public function notifyImpactedUsers(int $maintenanceId): array
    {
        $maintenance = $this->maintenanceRepo->findById($maintenanceId);
        if (!$maintenance) {
            return ['success' => false, 'message' => 'Maintenance record not found.'];
        }

        $impacted = $this->detectImpactedBookings(
            (int) $maintenance['resource_id'],
            $maintenance['maintenance_start'],
            $maintenance['maintenance_end']
        );

        $resource = $this->resourceRepo->findById((int) $maintenance['resource_id']);
        $resourceName = $resource['resource_name'] ?? 'the resource';
        $notified = 0;

        foreach ($impacted as $booking) {
            $this->notificationService->notify(
                (int) $booking['user_id'],
                'Booking Affected by Maintenance',
                sprintf(
                    'Your booking %s for %s (%s to %s) has been affected by a scheduled maintenance. Reason: %s. Please check with admin for rescheduling.',
                    $booking['booking_reference'],
                    $resourceName,
                    date('d/m/Y H:i', strtotime($booking['start_datetime'])),
                    date('H:i', strtotime($booking['end_datetime'])),
                    $maintenance['reason']
                ),
                'resource_maintenance',
                (int) $booking['id']
            );
            $notified++;
        }

        $this->auditLog->log('maintenance_notify', 'maintenance_schedules', $maintenanceId, null, [
            'notified_users' => $notified,
        ]);

        return [
            'success'         => true,
            'impacted_count'  => count($impacted),
            'notified_count'  => $notified,
            'message'         => "Notified $notified affected user(s).",
        ];
    }

    /**
     * Activate a maintenance schedule: set status to in_progress and lock resource.
     */
    public function activateMaintenance(int $maintenanceId, int $adminId): array
    {
        $maintenance = $this->maintenanceRepo->findById($maintenanceId);
        if (!$maintenance) {
            return ['success' => false, 'message' => 'Maintenance record not found.'];
        }

        $this->maintenanceRepo->update($maintenanceId, ['status' => 'in_progress']);
        $this->resourceRepo->update((int) $maintenance['resource_id'], ['status' => 'maintenance']);

        $this->auditLog->log('activate_maintenance', 'maintenance_schedules', $maintenanceId,
            ['status' => $maintenance['status']],
            ['status' => 'in_progress'],
            $adminId
        );

        // Notify impacted users
        $this->notifyImpactedUsers($maintenanceId);

        return ['success' => true, 'message' => 'Maintenance activated and affected users notified.'];
    }

    /**
     * Complete maintenance: restore resource to available.
     */
    public function completeMaintenance(int $maintenanceId, int $adminId): array
    {
        $maintenance = $this->maintenanceRepo->findById($maintenanceId);
        if (!$maintenance) {
            return ['success' => false, 'message' => 'Maintenance record not found.'];
        }

        $this->maintenanceRepo->update($maintenanceId, ['status' => 'completed']);
        $this->resourceRepo->update((int) $maintenance['resource_id'], ['status' => 'available']);

        $this->auditLog->log('complete_maintenance', 'maintenance_schedules', $maintenanceId,
            ['status' => $maintenance['status']],
            ['status' => 'completed'],
            $adminId
        );

        return ['success' => true, 'message' => 'Maintenance completed. Resource is now available.'];
    }

    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        return $this->maintenanceRepo->findAll($filters, $limit, $offset);
    }

    public function getById(int $id): ?array
    {
        return $this->maintenanceRepo->findById($id);
    }
}
