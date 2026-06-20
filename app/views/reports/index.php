<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Usage Reports</h1>
    <p class="text-muted mb-0">Statistics calculated from live booking records in the database.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a href="<?= route_url('reports', 'export-csv', $filters) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
    <a href="<?= route_url('reports', 'export-excel', $filters) ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
    <a href="<?= route_url('reports', 'export-pdf', $filters) ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
  </div>
</div>

<form method="GET" class="card p-3 mb-4">
  <input type="hidden" name="page" value="reports">
  <div class="row g-3 align-items-end">
    <div class="col-md-2"><label class="form-label small">From</label><input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from']) ?>"></div>
    <div class="col-md-2"><label class="form-label small">To</label><input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to']) ?>"></div>
    <div class="col-md-2"><label class="form-label small">Category</label><select name="category_id" class="form-select"><option value="">All</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($filters['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label small">Resource</label><select name="resource_id" class="form-select"><option value="">All Resources</option><?php foreach ($resources as $r): ?><option value="<?= $r['id'] ?>" <?= ($filters['resource_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= e($r['resource_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label small">Report Type</label><select name="report_type" class="form-select"><?php foreach (['weekly', 'monthly', 'semester'] as $t): ?><option value="<?= $t ?>" <?= ($filters['report_type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-1"><button class="btn btn-primary w-100">Filter</button></div>
  </div>
</form>

<form method="POST" action="<?= route_url('reports', 'generate') ?>" class="card p-3 mb-4">
  <?= csrf_field() ?>
  <input type="hidden" name="date_from" value="<?= e($filters['date_from']) ?>">
  <input type="hidden" name="date_to" value="<?= e($filters['date_to']) ?>">
  <input type="hidden" name="report_type" value="<?= e($filters['report_type']) ?>">
  <div class="row g-3 align-items-end">
    <div class="col-md-8">
      <label class="form-label small fw-medium">Generate On-Demand Report</label>
      <select name="resource_id" class="form-select" required>
        <option value="">Select resource to generate report...</option>
        <?php foreach ($resources as $r): ?>
          <option value="<?= $r['id'] ?>" <?= ($filters['resource_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= e($r['resource_name']) ?> (<?= e($r['resource_code']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4"><button class="btn btn-success w-100"><i class="bi bi-graph-up me-1"></i>Generate Report</button></div>
  </div>
</form>

<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['Total Bookings', $summary['total'] ?? 0, 'bi-calendar-check', 'primary'],
    ['Approved', $summary['approved'] ?? 0, 'bi-check-circle', 'success'],
    ['Cancelled', $summary['cancelled'] ?? 0, 'bi-x-circle', 'secondary'],
    ['Approval Rate', ($summary['approval_rate'] ?? 0) . '%', 'bi-patch-check', 'info'],
    ['Cancellation Rate', ($summary['cancellation_rate'] ?? 0) . '%', 'bi-slash-circle', 'warning'],
    ['Utilization', ($summary['avg_utilization'] ?? 0) . '%', 'bi-speedometer2', 'primary'],
  ];
  foreach ($cards as [$label, $value, $icon, $color]):
  ?>
  <div class="col-md-4 col-lg-2">
    <div class="card stat-card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-<?= $color ?>-subtle text-<?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
        <div><p class="text-muted small mb-0"><?= $label ?></p><h4 class="mb-0 fw-bold"><?= e((string) $value) ?></h4></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-6"><div class="card p-3 h-100"><h6 class="fw-semibold mb-3">Most Used Resources</h6><canvas id="chartResources" height="200"></canvas></div></div>
  <div class="col-lg-6"><div class="card p-3 h-100"><h6 class="fw-semibold mb-3">Bookings by Category</h6><canvas id="chartCategory" height="200"></canvas></div></div>
  <div class="col-lg-6"><div class="card p-3 h-100">
    <h6 class="fw-semibold mb-3">Approval vs Rejection vs Cancellation</h6>
    <div style="position:relative;max-height:280px;display:flex;justify-content:center;">
      <canvas id="chartApproval"></canvas>
    </div>
  </div></div>
  <div class="col-lg-6"><div class="card p-3 h-100">
    <h6 class="fw-semibold mb-3">Peak vs Off-Peak Usage (30 days)</h6>
    <div style="position:relative;max-height:280px;display:flex;justify-content:center;">
      <canvas id="chartPeak"></canvas>
    </div>
  </div></div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-success-subtle fw-semibold"><i class="bi bi-arrow-up-circle me-1"></i>Overused Resources (30 days)</div>
      <div class="list-group list-group-flush">
        <?php foreach ($insights['overused'] ?? [] as $r): ?>
          <div class="list-group-item d-flex justify-content-between"><span><?= e($r['resource_name']) ?></span><span class="badge bg-success"><?= $r['booking_count'] ?> bookings</span></div>
        <?php endforeach; ?>
        <?php if (empty($insights['overused'])): ?><div class="list-group-item text-muted">No data yet</div><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-warning-subtle fw-semibold"><i class="bi bi-arrow-down-circle me-1"></i>Underused Resources (30 days)</div>
      <div class="list-group list-group-flush">
        <?php foreach ($insights['underused'] ?? [] as $r): ?>
          <div class="list-group-item d-flex justify-content-between"><span><?= e($r['resource_name']) ?></span><span class="badge bg-secondary"><?= $r['booking_count'] ?> bookings</span></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold">Stored Usage Reports</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Resource</th><th>Category</th><th>Type</th><th>Period</th><th>Bookings</th><th>Approved</th><th>Hours</th><th>Utilization</th><th>Generated</th></tr></thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td><?= e($r['resource_name']) ?></td>
          <td><?= e($r['category_name'] ?? '') ?></td>
          <td><?= ucfirst(e($r['report_type'])) ?></td>
          <td><?= e($r['period_start']) ?> – <?= e($r['period_end']) ?></td>
          <td><?= $r['total_bookings'] ?></td>
          <td><?= $r['total_approved'] ?></td>
          <td><?= $r['total_hours'] ?></td>
          <td><span class="badge bg-primary"><?= $r['utilization_rate'] ?>%</span></td>
          <td><?= format_datetime($r['generated_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($reports)): ?><tr><td colspan="9" class="text-center text-muted py-4">No stored reports for selected filters. Generate one above.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const rd = <?= json_encode($chartData ?? []) ?>;

  if (document.getElementById('chartResources')) {
    new Chart(document.getElementById('chartResources'), {
      type: 'bar',
      data: { labels: rd.resource_labels || [], datasets: [{ label: 'Bookings', data: rd.resource_data || [], backgroundColor: '#1e3a5f', borderRadius: 4 }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
  }
  if (document.getElementById('chartCategory')) {
    new Chart(document.getElementById('chartCategory'), {
      type: 'bar',
      data: { labels: rd.category_labels || [], datasets: [{ data: rd.category_data || [], backgroundColor: '#3C83F6', borderRadius: 4 }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
  }
  if (document.getElementById('chartApproval')) {
    const elA = document.getElementById('chartApproval');
    elA.style.maxHeight = '260px';
    new Chart(elA, {
      type: 'doughnut',
      data: { labels: ['Approved', 'Rejected', 'Cancelled'], datasets: [{ data: [rd.approved || 0, rd.rejected || 0, rd.cancelled || 0], backgroundColor: ['#21C45D', '#DC3848', '#6c757d'] }] },
      options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
    });
  }
  if (document.getElementById('chartPeak')) {
    const elP = document.getElementById('chartPeak');
    elP.style.maxHeight = '260px';
    new Chart(elP, {
      type: 'pie',
      data: { labels: ['Peak Hour', 'Off-Peak'], datasets: [{ data: [rd.peak || 0, rd.off_peak || 0], backgroundColor: ['#f59e0b', '#94a3b8'] }] },
      options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
    });
  }
});
</script>
