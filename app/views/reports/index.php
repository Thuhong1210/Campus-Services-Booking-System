<?php
$summary  = $summary  ?? [];
$chartData = $chartData ?? [];
$insights  = $insights  ?? [];
$reports   = $reports   ?? [];
$filters   = $filters   ?? [];
$categories = $categories ?? [];
$resources  = $resources  ?? [];
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1"><i class="bi bi-graph-up-arrow me-2 text-primary"></i><?= e(__('Usage Reports & Analytics')) ?></h1>
    <p class="text-muted mb-0"><?= e(__('Statistics calculated from live booking records in the database.')) ?></p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a href="<?= route_url('reports', 'export-csv', $filters) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
    <a href="<?= route_url('reports', 'export-excel', $filters) ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
    <a href="<?= route_url('reports', 'export-pdf', $filters) ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
  </div>
</div>

<!-- ─── Filters ──────────────────────────────────────────────── -->
<form method="GET" class="card p-3 mb-4 border-0 shadow-sm rounded-3">
  <input type="hidden" name="page" value="reports">
  <div class="row g-3 align-items-end">
    <div class="col-md-2"><label class="form-label small fw-semibold"><?= e(__('From')) ?></label><input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from']) ?>"></div>
    <div class="col-md-2"><label class="form-label small fw-semibold"><?= e(__('To')) ?></label><input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to']) ?>"></div>
    <div class="col-md-2"><label class="form-label small fw-semibold"><?= e(__('Category')) ?></label>
      <select name="category_id" class="form-select"><option value="">All</option>
        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($filters['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-3"><label class="form-label small fw-semibold"><?= e(__('Resource')) ?></label>
      <select name="resource_id" class="form-select"><option value="">All Resources</option>
        <?php foreach ($resources as $r): ?><option value="<?= $r['id'] ?>" <?= ($filters['resource_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= e($r['resource_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-2"><label class="form-label small fw-semibold"><?= e(__('Report Type')) ?></label>
      <select name="report_type" class="form-select"><?php foreach (['weekly', 'monthly', 'semester'] as $t): ?><option value="<?= $t ?>" <?= ($filters['report_type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-1"><button class="btn btn-primary w-100"><?= e(__('Filter')) ?></button></div>
  </div>
</form>

<!-- ─── Generate Report ──────────────────────────────────────── -->
<form method="POST" action="<?= route_url('reports', 'generate') ?>" class="card p-3 mb-4 border-0 shadow-sm rounded-3">
  <?= csrf_field() ?>
  <input type="hidden" name="date_from" value="<?= e($filters['date_from']) ?>">
  <input type="hidden" name="date_to" value="<?= e($filters['date_to']) ?>">
  <input type="hidden" name="report_type" value="<?= e($filters['report_type']) ?>">
  <div class="row g-3 align-items-end">
    <div class="col-md-8">
      <label class="form-label small fw-semibold"><?= e(__('Generate On-Demand Report')) ?></label>
      <select name="resource_id" class="form-select" required>
        <option value=""><?= e(__('Select resource to generate report...')) ?></option>
        <?php foreach ($resources as $r): ?>
          <option value="<?= $r['id'] ?>" <?= ($filters['resource_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= e($r['resource_name']) ?> (<?= e($r['resource_code']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4"><button class="btn btn-success w-100"><i class="bi bi-graph-up me-1"></i><?= e(__('Generate Report')) ?></button></div>
  </div>
</form>

<!-- ─── KPI Cards ────────────────────────────────────────────── -->
<div class="row g-3 mb-5">
  <?php
  $kpis = [
    ['Total Bookings',        $summary['total']                ?? 0,       'bi-collection-fill',    'primary',   ''],
    ['Bookings This Month',   $summary['bookings_this_month']  ?? 0,       'bi-calendar-month',     'success',   ''],
    ['Approval Rate',         ($summary['approval_rate']       ?? 0),      'bi-patch-check-fill',   'info',      '%'],
    ['Cancellation Rate',     ($summary['cancellation_rate']   ?? 0),      'bi-slash-circle-fill',  'warning',   '%'],
    ['No-show Rate',          ($summary['no_show_rate']        ?? 0),      'bi-exclamation-octagon-fill', 'danger',  '%'],
    ['Avg Utilization',       ($summary['avg_utilization']     ?? 0),      'bi-speedometer2',       'secondary', '%'],
  ];
  foreach ($kpis as [$label, $value, $icon, $color, $suffix]):
  ?>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card stat-card h-100 border-0 shadow-sm rounded-3 overflow-hidden" style="border-top:3px solid var(--bs-<?= $color ?>)!important;">
      <div class="card-body d-flex align-items-center gap-3 py-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-<?= $color ?>-subtle text-<?= $color ?>" style="width:44px;height:44px;font-size:1.2rem">
          <i class="bi <?= $icon ?>"></i>
        </div>
        <div>
          <p class="text-muted mb-0 fw-semibold" style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em"><?= e(__($label)) ?></p>
          <h5 class="mb-0 fw-bold"><?= e((string)$value) ?><?= $suffix ?></h5>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ─── Charts Row 1 ─────────────────────────────────────────── -->
<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-header bg-white fw-bold border-bottom-0 pt-3 pb-1 d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart-fill text-primary"></i><?= e(__('Most Used Resources (Top 5)')) ?>
      </div>
      <div class="card-body"><canvas id="chartResources" height="200"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-header bg-white fw-bold border-bottom-0 pt-3 pb-1 d-flex align-items-center gap-2">
        <i class="bi bi-grid-fill text-info"></i><?= e(__('Bookings by Category')) ?>
      </div>
      <div class="card-body"><canvas id="chartCategory" height="200"></canvas></div>
    </div>
  </div>
</div>

<!-- ─── Charts Row 2 ─────────────────────────────────────────── -->
<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-header bg-white fw-bold border-bottom-0 pt-3 pb-1 d-flex align-items-center gap-2">
        <i class="bi bi-people-fill text-success"></i><?= e(__('Bookings by Department')) ?>
      </div>
      <div class="card-body"><canvas id="chartDepartment" height="200"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-header bg-white fw-bold border-bottom-0 pt-3 pb-1 d-flex align-items-center gap-2">
        <i class="bi bi-clock-fill text-warning"></i><?= e(__('Peak Booking Hours Distribution')) ?>
      </div>
      <div class="card-body"><canvas id="chartHours" height="200"></canvas></div>
    </div>
  </div>
</div>

<!-- ─── Charts Row 3 ─────────────────────────────────────────── -->
<div class="row g-4 mb-5">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-header bg-white fw-bold border-bottom-0 pt-3 pb-1 d-flex align-items-center gap-2">
        <i class="bi bi-pie-chart-fill text-danger"></i><?= e(__('Approval vs Rejection vs Cancellation')) ?>
      </div>
      <div class="card-body d-flex justify-content-center align-items-center" style="min-height:260px">
        <canvas id="chartApproval" style="max-height:240px"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-3 h-100">
      <div class="card-header bg-white fw-bold border-bottom-0 pt-3 pb-1 d-flex align-items-center gap-2">
        <i class="bi bi-activity text-secondary"></i><?= e(__('Peak vs Off-Peak Usage (30 days)')) ?>
      </div>
      <div class="card-body d-flex justify-content-center align-items-center" style="min-height:260px">
        <canvas id="chartPeak" style="max-height:240px"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- ─── Overused / Underused ─────────────────────────────────── -->
<div class="row g-4 mb-4">
  <div class="col-md-6">
    <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
      <div class="card-header bg-success-subtle text-success fw-bold border-0 d-flex align-items-center gap-2">
        <i class="bi bi-arrow-up-circle"></i><?= e(__('Overused Resources (30 days)')) ?>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($insights['overused'] ?? [] as $i => $r): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center py-3">
            <div class="d-flex align-items-center gap-3">
              <span class="badge rounded-circle bg-success-subtle text-success fw-bold" style="width:28px;height:28px;display:flex!important;align-items:center;justify-content:center"><?= $i+1 ?></span>
              <div>
                <div class="fw-semibold"><?= e($r['resource_name']) ?></div>
                <div class="text-muted small"><?= e($r['category_name']) ?></div>
              </div>
            </div>
            <span class="badge bg-success text-white rounded-pill px-3"><?= $r['booking_count'] ?> <?= e(__('bookings')) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if (empty($insights['overused'])): ?><div class="list-group-item text-muted text-center py-4"><?= e(__('No data yet')) ?></div><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
      <div class="card-header bg-warning-subtle text-warning fw-bold border-0 d-flex align-items-center gap-2">
        <i class="bi bi-arrow-down-circle"></i><?= e(__('Underused Resources (30 days)')) ?>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($insights['underused'] ?? [] as $i => $r): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center py-3">
            <div class="d-flex align-items-center gap-3">
              <span class="badge rounded-circle bg-warning-subtle text-warning fw-bold" style="width:28px;height:28px;display:flex!important;align-items:center;justify-content:center"><?= $i+1 ?></span>
              <div>
                <div class="fw-semibold"><?= e($r['resource_name']) ?></div>
                <div class="text-muted small"><?= e($r['category_name']) ?></div>
              </div>
            </div>
            <span class="badge bg-secondary text-white rounded-pill px-3"><?= $r['booking_count'] ?> <?= e(__('bookings')) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if (empty($insights['underused'])): ?><div class="list-group-item text-muted text-center py-4"><?= e(__('No data yet')) ?></div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ─── Stored Reports Table ─────────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
  <div class="card-header fw-bold bg-white text-dark-blue border-bottom py-3"><?= e(__('Stored Usage Reports')) ?></div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th><?= e(__('Resource')) ?></th><th><?= e(__('Category')) ?></th><th><?= e(__('Type')) ?></th>
          <th><?= e(__('Period')) ?></th><th><?= e(__('Bookings')) ?></th><th><?= e(__('Approved')) ?></th>
          <th><?= e(__('Hours')) ?></th><th><?= e(__('Utilization')) ?></th><th><?= e(__('Generated')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td class="fw-medium text-dark-blue"><?= e($r['resource_name']) ?></td>
          <td><?= e($r['category_name'] ?? '') ?></td>
          <td><?= e(ucfirst($r['report_type'])) ?></td>
          <td><?= e($r['period_start']) ?> – <?= e($r['period_end']) ?></td>
          <td><?= $r['total_bookings'] ?></td><td><?= $r['total_approved'] ?></td>
          <td><?= $r['total_hours'] ?>h</td>
          <td>
            <?php $rate = (float)$r['utilization_rate']; $rColor = $rate >= 70 ? 'success' : ($rate >= 40 ? 'warning' : 'secondary'); ?>
            <div class="d-flex align-items-center gap-2">
              <div class="progress flex-grow-1" style="height:6px;min-width:60px">
                <div class="progress-bar bg-<?= $rColor ?>" style="width:<?= min(100,$rate) ?>%"></div>
              </div>
              <span class="badge bg-<?= $rColor ?>-subtle text-<?= $rColor ?> small"><?= $r['utilization_rate'] ?>%</span>
            </div>
          </td>
          <td class="text-muted small"><?= format_datetime($r['generated_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($reports)): ?>
        <tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-inbox me-2 fs-4 d-block mb-2"></i><?= e(__('No stored reports for selected filters. Generate one above.')) ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const rd = <?= json_encode($chartData ?? []) ?>;

  Chart.defaults.font = { family: 'Inter, system-ui, sans-serif', size: 12 };
  Chart.defaults.color = '#6b7280';

  const C = {
    primary:   '#4361ee',
    success:   '#10b981',
    info:      '#3C83F6',
    warning:   '#f59e0b',
    danger:    '#ef4444',
    secondary: '#64748b',
    accent:    '#8b5cf6',
    purple:    '#a855f7',
  };
  const grid = 'rgba(0,0,0,.05)';
  const palette = [C.primary, C.success, C.info, C.warning, C.danger, C.accent, C.purple, C.secondary];

  // Helper: create gradient
  const grad = (ctx, color, alpha = 0.15) => {
    const g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);
    g.addColorStop(0, color.replace(')', `,${alpha})`).replace('rgb', 'rgba'));
    g.addColorStop(1, 'rgba(255,255,255,0)');
    return g;
  };

  // 1. Most Used Resources (Horizontal Bar)
  if (document.getElementById('chartResources') && (rd.resource_labels||[]).length) {
    new Chart(document.getElementById('chartResources'), {
      type: 'bar',
      data: { labels: rd.resource_labels||[], datasets: [{ label: '<?= e(__('Bookings')) ?>', data: rd.resource_data||[], backgroundColor: palette, borderRadius: 5, borderSkipped: false }] },
      options: { indexAxis:'y', responsive:true, plugins:{legend:{display:false}}, scales:{ x:{grid:{color:grid},beginAtZero:true,ticks:{precision:0}}, y:{grid:{display:false}} } }
    });
  }

  // 2. Bookings by Category
  if (document.getElementById('chartCategory') && (rd.category_labels||[]).length) {
    new Chart(document.getElementById('chartCategory'), {
      type: 'bar',
      data: { labels: rd.category_labels||[], datasets: [{ label: '<?= e(__('Bookings')) ?>', data: rd.category_data||[], backgroundColor: palette, borderRadius: 6, borderSkipped: false }] },
      options: { responsive:true, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}}, y:{grid:{color:grid},beginAtZero:true,ticks:{precision:0}} } }
    });
  }

  // 3. Bookings by Department
  if (document.getElementById('chartDepartment') && (rd.dept_labels||[]).length) {
    new Chart(document.getElementById('chartDepartment'), {
      type: 'bar',
      data: { labels: rd.dept_labels||[], datasets: [{ label: '<?= e(__('Bookings')) ?>', data: rd.dept_data||[], backgroundColor: C.primary, borderRadius: 6, borderSkipped: false }] },
      options: { responsive:true, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}}, y:{grid:{color:grid},beginAtZero:true,ticks:{precision:0}} } }
    });
  }

  // 4. Peak Hours Distribution (Line + fill)
  if (document.getElementById('chartHours') && (rd.hour_labels||[]).length) {
    const ctx4 = document.getElementById('chartHours').getContext('2d');
    new Chart(ctx4, {
      type: 'line',
      data: {
        labels: rd.hour_labels||[],
        datasets: [{
          label: '<?= e(__('Bookings')) ?>',
          data: rd.hour_data||[],
          borderColor: C.warning,
          backgroundColor: 'rgba(245,158,11,.10)',
          borderWidth: 2.5,
          pointBackgroundColor: C.warning,
          pointRadius: 4, pointHoverRadius: 6,
          fill: true, tension: 0.4
        }]
      },
      options: { responsive:true, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}}, y:{grid:{color:grid},beginAtZero:true,ticks:{precision:0}} } }
    });
  }

  // 5. Approval vs Rejection vs Cancellation (Doughnut)
  if (document.getElementById('chartApproval')) {
    new Chart(document.getElementById('chartApproval'), {
      type: 'doughnut',
      data: {
        labels: ['<?= e(__('Approved')) ?>', '<?= e(__('Rejected')) ?>', '<?= e(__('Cancelled')) ?>'],
        datasets: [{ data: [rd.approved||0, rd.rejected||0, rd.cancelled||0], backgroundColor: [C.success, C.danger, C.secondary], hoverOffset:8, borderWidth:2 }]
      },
      options: { responsive:true, maintainAspectRatio:false, cutout:'60%', plugins:{legend:{position:'bottom',labels:{padding:14,font:{size:11}}}} }
    });
  }

  // 6. Peak vs Off-Peak (Pie)
  if (document.getElementById('chartPeak')) {
    new Chart(document.getElementById('chartPeak'), {
      type: 'pie',
      data: {
        labels: ['<?= e(__('Peak Hour')) ?>', '<?= e(__('Off-Peak')) ?>'],
        datasets: [{ data: [rd.peak||0, rd.off_peak||0], backgroundColor: [C.warning, C.secondary], hoverOffset:8, borderWidth:2 }]
      },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom',labels:{padding:14,font:{size:11}}}} }
    });
  }
});
</script>
