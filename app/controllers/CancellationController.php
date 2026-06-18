<?php

declare(strict_types=1);

class CancellationController extends Controller
{
    private CancellationRepository $cancellationRepo;
    private ResourceRepository $resourceRepo;

    public function __construct()
    {
        $this->cancellationRepo = new CancellationRepository();
        $this->resourceRepo = new ResourceRepository();
    }

    public function index(): void
    {
        Middleware::admin();

        $filters = [
            'search' => trim((string) ($this->get()['search'] ?? '')),
            'resource_id' => $this->get()['resource_id'] ?? '',
            'role' => $this->get()['role'] ?? '',
            'date_from' => $this->get()['date_from'] ?? '',
            'date_to' => $this->get()['date_to'] ?? '',
        ];

        $page = max(1, (int) ($this->get()['p'] ?? 1));
        $perPage = 20;
        $total = $this->cancellationRepo->count($filters);
        $pagination = paginate($total, $page, $perPage, 'index.php?page=cancellations');

        $cancellations = $this->cancellationRepo->findAll($filters, $perPage, $pagination['offset']);

        $this->view('cancellations/index', [
            'title' => 'Cancellation Management',
            'cancellations' => $cancellations,
            'filters' => $filters,
            'pagination' => $pagination,
            'resources' => $this->resourceRepo->findAll([], 100, 0),
        ]);
    }
}
