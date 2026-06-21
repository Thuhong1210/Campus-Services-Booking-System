<?php
declare(strict_types=1);

class ImportController extends Controller
{
    private ImportService $importService;

    public function __construct()
    {
        $this->importService = new ImportService();
    }

    public function index(): void
    {
        Middleware::admin();
        $categoryRepo = new ResourceCategoryRepository();
        $this->view('import/index', [
            'title'      => 'Import CSV/Excel',
            'categories' => $categoryRepo->findAll(['status' => 'active']),
        ]);
    }

    /** POST – Preview users CSV */
    public function previewUsers(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        if (empty($_FILES['csv_file']['tmp_name'])) {
            Flash::error('Please select a CSV file.');
            redirect('index.php?page=import');
        }

        $result = $this->importService->previewUsers($_FILES['csv_file']);
        $_SESSION['import_preview'] = [
            'type'   => 'users',
            'valid'  => $result['valid'],
            'errors' => $result['errors'],
        ];

        $categoryRepo = new ResourceCategoryRepository();
        $this->view('import/preview', [
            'title'   => 'Preview User Import',
            'type'    => 'users',
            'valid'   => $result['valid'],
            'errors'  => $result['errors'],
            'categories' => $categoryRepo->findAll(['status' => 'active']),
        ]);
    }

    /** POST – Confirm user import */
    public function confirmUsers(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $preview = $_SESSION['import_preview'] ?? null;
        if (!$preview || $preview['type'] !== 'users' || empty($preview['valid'])) {
            Flash::error('No valid data to import. Please upload again.');
            redirect('index.php?page=import');
        }

        $result = $this->importService->importUsers($preview['valid'], (int) Auth::id());
        unset($_SESSION['import_preview']);

        if ($result['success']) {
            Flash::success($result['message']);
        } else {
            Flash::error($result['message']);
        }
        redirect('index.php?page=users');
    }

    /** POST – Preview resources CSV */
    public function previewResources(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        if (empty($_FILES['csv_file']['tmp_name'])) {
            Flash::error('Please select a CSV file.');
            redirect('index.php?page=import');
        }

        $result = $this->importService->previewResources($_FILES['csv_file']);
        $_SESSION['import_preview'] = [
            'type'   => 'resources',
            'valid'  => $result['valid'],
            'errors' => $result['errors'],
        ];

        $categoryRepo = new ResourceCategoryRepository();
        $this->view('import/preview', [
            'title'   => 'Preview Resource Import',
            'type'    => 'resources',
            'valid'   => $result['valid'],
            'errors'  => $result['errors'],
            'categories' => $categoryRepo->findAll(['status' => 'active']),
        ]);
    }

    /** POST – Confirm resource import */
    public function confirmResources(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $preview = $_SESSION['import_preview'] ?? null;
        if (!$preview || $preview['type'] !== 'resources' || empty($preview['valid'])) {
            Flash::error('No valid data to import. Please upload again.');
            redirect('index.php?page=import');
        }

        $result = $this->importService->importResources($preview['valid'], (int) Auth::id());
        unset($_SESSION['import_preview']);

        if ($result['success']) {
            Flash::success($result['message']);
        } else {
            Flash::error($result['message']);
        }
        redirect('index.php?page=resources');
    }
}
