<?php
declare(strict_types=1);

class AuditLogService
{
    private AuditLogRepository $auditLogRepo;

    public function __construct()
    {
        $this->auditLogRepo = new AuditLogRepository();
    }

    public function log(
        string $action,
        ?string $tableName = null,
        ?int $recordId = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?int $userId = null
    ): int {
        return $this->auditLogRepo->create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_value' => $oldValue !== null ? (is_string($oldValue) ? $oldValue : json_encode($oldValue)) : null,
            'new_value' => $newValue !== null ? (is_string($newValue) ? $newValue : json_encode($newValue)) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
