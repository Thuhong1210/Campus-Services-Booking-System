<?php

declare(strict_types=1);

class AuditLogController extends Controller
{
    private AuditLogRepository $auditLogRepo;
    private UserRepository $userRepo;
    private RoleRepository $roleRepo;

    public function __construct()
    {
        $this->auditLogRepo = new AuditLogRepository();
        $this->userRepo = new UserRepository();
        $this->roleRepo = new RoleRepository();
    }

    public function index(): void
    {
        Middleware::admin();

        $filters = [
            'search' => trim((string) ($this->get()['search'] ?? '')),
            'user_id' => $this->get()['user_id'] ?? '',
            'action' => $this->get()['action'] ?? '',
            'table_name' => $this->get()['table_name'] ?? '',
            'date_from' => $this->get()['date_from'] ?? '',
            'date_to' => $this->get()['date_to'] ?? '',
        ];

        $page = max(1, (int) ($this->get()['p'] ?? 1));
        $perPage = 25;
        $total = $this->auditLogRepo->count($filters);
        $pagination = paginate($total, $page, $perPage, 'index.php?page=audit-logs');

        $logs = $this->auditLogRepo->findAll($filters, $perPage, $pagination['offset']);

        $this->view('audit_logs/index', [
            'title' => 'Audit Logs',
            'logs' => $logs,
            'filters' => $filters,
            'pagination' => $pagination,
            'users' => $this->userRepo->all([], 100, 0),
            'roles' => $this->roleRepo->findAll(),
        ]);
    }
}
