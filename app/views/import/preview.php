  <div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= route_url('import') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
      <h1 class="h3 fw-bold mb-0"><i class="bi bi-table text-primary me-2"></i><?= e(__('Import Preview')) ?></h1>
      <p class="text-muted mb-0"><?= e($type === 'users' ? __('Users') : __('Resources')) ?> — <?= count($valid) ?> valid, <?= count($errors) ?> errors</p>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
  <div class="card border-0 shadow-sm mb-4 border-danger">
    <div class="card-header bg-transparent text-danger">
      <h6 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= count($errors) ?> Row(s) with Errors (will be skipped)</h6>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Row</th><th>Issues</th><?php if ($type === 'users'): ?><th>Email</th><th>Username</th><?php endif; ?></tr></thead>
        <tbody>
          <?php foreach ($errors as $err): ?>
          <tr class="table-danger">
            <td><?= $err['row'] ?></td>
            <td><?= implode('; ', array_map('htmlspecialchars', $err['errors'])) ?></td>
            <?php if ($type === 'users'): ?>
            <td><?= e($err['data']['email'] ?? '') ?></td>
            <td><?= e($err['data']['username'] ?? '') ?></td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($valid)): ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
      <h6 class="mb-0 text-success"><i class="bi bi-check-circle-fill me-2"></i><?= count($valid) ?> Valid Row(s) Ready to Import</h6>
    </div>
    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light sticky-top">
          <tr>
            <?php foreach (array_keys($valid[0]) as $col): ?>
            <th><?= e(str_replace('_', ' ', ucfirst($col))) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($valid as $row): ?>
          <tr>
            <?php foreach ($row as $key => $val): ?>
            <td><?= $key === 'password' ? '••••••••' : e($val) ?></td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="d-flex gap-3 justify-content-end">
    <a href="<?= route_url('import') ?>" class="btn btn-outline-secondary">
      <i class="bi bi-x-circle me-1"></i>Cancel
    </a>
    <form method="POST" action="<?= route_url('import', $type === 'users' ? 'confirmUsers' : 'confirmResources') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-primary" onclick="return confirm('Import <?= count($valid) ?> record(s)?')">
        <i class="bi bi-cloud-upload me-1"></i>Confirm Import (<?= count($valid) ?> records)
      </button>
    </form>
  </div>
  <?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <i class="bi bi-exclamation-circle text-warning" style="font-size:3rem"></i>
      <h5 class="mt-3">No Valid Records</h5>
      <p class="text-muted">All rows had errors. Please fix your CSV file and try again.</p>
      <a href="<?= route_url('import') ?>" class="btn btn-primary mt-2">Try Again</a>
    </div>
  </div>
  <?php endif; ?>
