  <div class="mb-4">
    <h1 class="h3 fw-bold mb-0"><i class="bi bi-upload text-primary me-2"></i><?= e(__('Import CSV / Excel')) ?></h1>
    <p class="text-muted mb-0"><?= e(__('Bulk import users or resources from CSV files')) ?></p>
  </div>

  <div class="row g-4">

    <!-- Import Users Card -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent">
          <h6 class="mb-0 fw-semibold"><i class="bi bi-people-fill text-primary me-2"></i><?= e(__('Import Users')) ?></h6>
        </div>
        <div class="card-body">
          <div class="alert alert-light border mb-3">
            <strong>CSV Format:</strong>
            <code class="d-block mt-1 small">full_name, username, email, password, phone, student_code, staff_code</code>
          </div>

          <!-- Sample CSV download -->
          <div class="mb-3">
            <a href="data:text/csv;charset=utf-8,full_name%2Cusername%2Cemail%2Cpassword%2Cphone%2Cstudent_code%0AJohn+Doe%2Cjohndoe%2Cjohn@example.com%2CPass123!%2C0901234567%2CSV2024010"
               download="sample_users.csv" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-download me-1"></i>Download Sample CSV
            </a>
          </div>

          <form method="POST" action="<?= route_url('import', 'previewUsers') ?>" enctype="multipart/form-data"
                id="importUsersForm">
            <?= csrf_field() ?>
            <div class="upload-zone mb-3" id="userDropZone"
                 onclick="document.getElementById('userCsv').click()"
                 style="border:2px dashed var(--primary);border-radius:12px;padding:30px;text-align:center;cursor:pointer;transition:.2s">
              <i class="bi bi-cloud-upload fs-2 text-primary"></i>
              <p class="mt-2 mb-0">Drag & drop CSV here or <strong>click to browse</strong></p>
              <span id="userFileName" class="small text-muted"></span>
            </div>
            <input type="file" name="csv_file" id="userCsv" accept=".csv,.txt" class="d-none"
                   onchange="document.getElementById('userFileName').textContent=this.files[0]?.name||''">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-eye me-1"></i>Preview Import
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Import Resources Card -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent">
          <h6 class="mb-0 fw-semibold"><i class="bi bi-box-fill text-success me-2"></i><?= e(__('Import Resources')) ?></h6>
        </div>
        <div class="card-body">
          <div class="alert alert-light border mb-3">
            <strong>CSV Format:</strong>
            <code class="d-block mt-1 small">resource_code, resource_name, location, category_id, capacity, description</code>
          </div>

          <!-- Category Reference -->
          <div class="mb-3">
            <small class="text-muted fw-semibold">Category IDs:</small>
            <div class="d-flex flex-wrap gap-2 mt-1">
              <?php foreach ($categories as $cat): ?>
              <span class="badge bg-light text-dark border">#<?= $cat['id'] ?> – <?= e($cat['category_name']) ?></span>
              <?php endforeach; ?>
            </div>
          </div>

          <form method="POST" action="<?= route_url('import', 'previewResources') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="upload-zone mb-3"
                 onclick="document.getElementById('resCsv').click()"
                 style="border:2px dashed #198754;border-radius:12px;padding:30px;text-align:center;cursor:pointer;transition:.2s">
              <i class="bi bi-cloud-upload fs-2 text-success"></i>
              <p class="mt-2 mb-0">Drag & drop CSV here or <strong>click to browse</strong></p>
              <span id="resFileName" class="small text-muted"></span>
            </div>
            <input type="file" name="csv_file" id="resCsv" accept=".csv,.txt" class="d-none"
                   onchange="document.getElementById('resFileName').textContent=this.files[0]?.name||''">
            <button type="submit" class="btn btn-success w-100">
              <i class="bi bi-eye me-1"></i>Preview Import
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>

  <!-- Instructions -->
  <div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
      <h6 class="fw-semibold mb-3"><i class="bi bi-info-circle text-info me-2"></i>Import Guidelines</h6>
      <ul class="mb-0 small">
        <li>First row must be the <strong>header row</strong> with exact column names</li>
        <li>Maximum <strong><?= setting('import_max_rows', 500) ?> rows</strong> per import</li>
        <li>The system will <strong>validate all rows</strong> before asking for confirmation</li>
        <li>Rows with errors will be highlighted — only valid rows are imported</li>
        <li>Import uses a database transaction — partial failures are logged</li>
      </ul>
    </div>
  </div>
