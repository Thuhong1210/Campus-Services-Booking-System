<?php
$chartData = $chartData ?? [];
$stats     = $stats     ?? [];
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="page-header mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div>
    <h1 class="fw-bold mb-1"><?= e(__('Dashboard Overview')) ?></h1>
    <p class="text-muted mb-0"><?= e(__("Welcome back. Here's what's happening across the campus today.")) ?></p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="index.php?page=reports" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
      <i class="bi bi-graph-up-arrow"></i> <?= e(__('Full Analytics')) ?>
    </a>
    <a href="index.php?page=bookings" class="btn btn-primary btn-sm d-flex align-items-center gap-2">
      <i class="bi bi-calendar-check-fill"></i> <?= e(__('Manage Bookings')) ?>
    </a>
  </div>
</div>

<!-- ─── Stat Cards ───────────────────────────────────────────── -->
<?php
$cards = [
    ['label' => 'Total Users',       'val' => $stats['total_users']       ?? 0,        'icon' => 'bi-people-fill',      'cls' => 'icon-primary'],
    ['label' => 'Total Resources',   'val' => $stats['total_resources']   ?? 0,        'icon' => 'bi-box-seam-fill',    'cls' => 'icon-accent'],
    ['label' => 'Active Bookings',   'val' => ($stats['approved'] ?? 0),              'icon' => 'bi-calendar-check-fill','cls' => 'icon-success'],
    ['label' => 'Pending Approvals', 'val' => $stats['pending_approvals'] ?? 0,        'icon' => 'bi-hourglass-split',  'cls' => 'icon-warning'],
    ['label' => 'Cancelled',         'val' => $stats['cancelled']         ?? 0,        'icon' => 'bi-x-circle-fill',    'cls' => 'icon-danger'],
    ['label' => 'Total Bookings',    'val' => $stats['total']             ?? 0,        'icon' => 'bi-collection-fill',  'cls' => 'icon-info'],
];
?>
<div class="row g-3 mb-4">
  <?php foreach ($cards as $card): ?>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="mb-1" style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em">
              <?= e(__($card['label'])) ?>
            </p>
            <h3 class="fw-bold mb-0" style="font-size:1.6rem">
              <?= e((string) $card['val']) ?>
            </h3>
          </div>
          <div class="stat-icon <?= $card['cls'] ?>">
            <i class="bi <?= e($card['icon']) ?>"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ─── Advanced Analytics Charts ───────────────────────────── -->
<div class="row g-4 mb-4">

  <!-- Bookings by Category (Bar) -->
  <div class="col-lg-6">
    <div class="card h-100 border-0 shadow-sm rounded-3">
      <div class="card-header bg-white fw-bold text-dark-blue border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart-fill" style="color:var(--primary)"></i>
        <?= e(__('Bookings by Category')) ?>
      </div>
      <div class="card-body"><canvas id="chartCategory" height="200"></canvas></div>
    </div>
  </div>

  <!-- Monthly Booking Trend (Line) -->
  <div class="col-lg-6">
    <div class="card h-100 border-0 shadow-sm rounded-3">
      <div class="card-header bg-white fw-bold text-dark-blue border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-graph-up" style="color:var(--success)"></i>
        <?= e(__('Monthly Booking Trend')) ?>
      </div>
      <div class="card-body"><canvas id="chartPeak" height="200"></canvas></div>
    </div>
  </div>

  <!-- Booking Status Breakdown (Doughnut) -->
  <div class="col-lg-4">
    <div class="card h-100 border-0 shadow-sm rounded-3">
      <div class="card-header bg-white fw-bold text-dark-blue border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-pie-chart-fill" style="color:var(--danger)"></i>
        <?= e(__('Booking Status Breakdown')) ?>
      </div>
      <div class="card-body d-flex justify-content-center align-items-center" style="max-height:260px">
        <canvas id="chartStatus"></canvas>
      </div>
    </div>
  </div>

  <!-- Peak vs Off-Peak (Pie) -->
  <div class="col-lg-4">
    <div class="card h-100 border-0 shadow-sm rounded-3">
      <div class="card-header bg-white fw-bold text-dark-blue border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-activity" style="color:var(--warning)"></i>
        <?= e(__('Peak vs Off-Peak (30 days)')) ?>
      </div>
      <div class="card-body d-flex justify-content-center align-items-center" style="max-height:260px">
        <canvas id="chartPeakPie"></canvas>
      </div>
    </div>
  </div>

  <!-- Top Resources (Horizontal Bar) -->
  <div class="col-lg-4">
    <div class="card h-100 border-0 shadow-sm rounded-3">
      <div class="card-header bg-white fw-bold text-dark-blue border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-trophy-fill" style="color:#f59e0b"></i>
        <?= e(__('Top Used Resources')) ?>
      </div>
      <div class="card-body"><canvas id="chartTopResources" height="200"></canvas></div>
    </div>
  </div>

</div>

<!-- ─── Recent Tables ─────────────────────────────────────────── -->
<div class="row g-4">

  <!-- Recent Activity -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white fw-bold text-dark-blue border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-clock-history" style="color:var(--accent)"></i>
        <?= e(__('Recent Activity')) ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th><?= e(__('User')) ?></th>
              <th><?= e(__('Action')) ?></th>
              <th><?= e(__('Time')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentActivity as $log): ?>
            <tr>
              <td class="fw-medium"><?= e($log['user_name'] ?? 'System') ?></td>
              <td>
                <span class="badge" style="background:var(--bg-muted);color:var(--text-sub);font-weight:500">
                  <?= e($log['action']) ?>
                </span>
              </td>
              <td style="color:var(--text-muted);font-size:12.5px">
                <?= format_datetime($log['created_at']) ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentActivity)): ?>
            <tr><td colspan="3" class="text-center py-4 text-muted"><?= e(__('No recent activity.')) ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Upcoming Bookings -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-3">
      <div class="card-header bg-white fw-bold text-dark-blue border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-calendar-event" style="color:var(--primary)"></i>
        <?= e(__('Upcoming Bookings')) ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th><?= e(__('Ref')) ?></th>
              <th><?= e(__('Student')) ?></th>
              <th><?= e(__('Resource')) ?></th>
              <th><?= e(__('Status')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($upcomingBookings as $booking): ?>
            <tr>
              <td>
                <a href="index.php?page=bookings&action=show&id=<?= $booking['id'] ?>" class="text-decoration-none">
                  <code style="font-size:12px;color:var(--primary)"><?= e($booking['booking_reference']) ?></code>
                </a>
              </td>
              <td class="fw-medium"><?= e($booking['user_name'] ?? '') ?></td>
              <td style="color:var(--text-sub)"><?= e($booking['resource_name'] ?? '') ?></td>
              <td><?= status_badge($booking['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($upcomingBookings)): ?>
            <tr><td colspan="4" class="text-center py-4 text-muted"><?= e(__('No upcoming bookings.')) ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- ─── Chart Scripts ──────────────────────────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const cd = <?= json_encode($chartData) ?>;

  Chart.defaults.font = { family: 'Inter, system-ui, sans-serif', size: 12 };
  Chart.defaults.color = '#9499b2';

  const COLORS = {
    primary:   '#4361ee',
    success:   '#10b981',
    warning:   '#f59e0b',
    danger:    '#ef4444',
    info:      '#3C83F6',
    secondary: '#64748b',
    accent:    '#8b5cf6',
  };
  const grid = '#e8eaf0';

  // ── Bar: Bookings by Category ──
  const catEl = document.getElementById('chartCategory');
  if (catEl && (cd.category_labels || []).length) {
    const bgColors = [COLORS.primary, COLORS.success, COLORS.warning, COLORS.danger, COLORS.info, COLORS.accent];
    new Chart(catEl, {
      type: 'bar',
      data: {
        labels: cd.category_labels || [],
        datasets: [{
          label: '<?= e(__('Bookings')) ?>',
          data: cd.category_data || [],
          backgroundColor: bgColors.slice(0, (cd.category_labels || []).length),
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false } },
          y: { grid: { color: grid }, beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }

  // ── Line: Monthly Trend ──
  const peakEl = document.getElementById('chartPeak');
  if (peakEl && (cd.peak_labels || []).length) {
    new Chart(peakEl, {
      type: 'line',
      data: {
        labels: cd.peak_labels || [],
        datasets: [{
          label: '<?= e(__('Bookings')) ?>',
          data: cd.peak_data || [],
          borderColor: COLORS.success,
          backgroundColor: 'rgba(16,185,129,.08)',
          borderWidth: 2.5,
          pointBackgroundColor: COLORS.success,
          pointRadius: 4,
          fill: true,
          tension: 0.4,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false } },
          y: { grid: { color: grid }, beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }

  // ── Doughnut: Booking Status Breakdown ──
  const statusEl = document.getElementById('chartStatus');
  const approved  = <?= (int)($stats['approved']  ?? 0) ?>;
  const pending   = <?= (int)($stats['pending']   ?? 0) ?>;
  const cancelled = <?= (int)($stats['cancelled'] ?? 0) ?>;
  const rejected  = <?= (int)($stats['rejected']  ?? 0) ?>;
  const completed = <?= (int)($stats['completed'] ?? 0) ?>;
  if (statusEl) {
    new Chart(statusEl, {
      type: 'doughnut',
      data: {
        labels: ['<?= e(__('Approved')) ?>', '<?= e(__('Pending')) ?>', '<?= e(__('Cancelled')) ?>', '<?= e(__('Rejected')) ?>', '<?= e(__('Completed')) ?>'],
        datasets: [{
          data: [approved, pending, cancelled, rejected, completed],
          backgroundColor: [COLORS.success, COLORS.warning, COLORS.secondary, COLORS.danger, COLORS.primary],
          hoverOffset: 8,
          borderWidth: 2,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } } }
      }
    });
  }

  // ── Pie: Peak vs Off-Peak ──
  const peakPieEl = document.getElementById('chartPeakPie');
  if (peakPieEl) {
    new Chart(peakPieEl, {
      type: 'pie',
      data: {
        labels: ['<?= e(__('Peak Hour')) ?>', '<?= e(__('Off-Peak')) ?>'],
        datasets: [{
          data: [cd.peak || 0, cd.off_peak || 0],
          backgroundColor: [COLORS.warning, COLORS.secondary],
          hoverOffset: 8,
          borderWidth: 2,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } } }
      }
    });
  }

  // ── Horizontal Bar: Top Resources ──
  const topResEl = document.getElementById('chartTopResources');
  if (topResEl && (cd.resource_labels || []).length) {
    new Chart(topResEl, {
      type: 'bar',
      data: {
        labels: cd.resource_labels || [],
        datasets: [{
          label: '<?= e(__('Bookings')) ?>',
          data: cd.resource_data || [],
          backgroundColor: COLORS.info,
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: grid }, beginAtZero: true, ticks: { precision: 0 } },
          y: { grid: { display: false } }
        }
      }
    });
  }

});
</script>
