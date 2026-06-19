<?php
$chartData = $chartData ?? [];
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="page-header mb-4">
  <h1 class="fw-bold mb-1">Dashboard Overview</h1>
  <p class="text-muted mb-0">Welcome back. Here's what's happening across the campus today.</p>
</div>

<!-- ─── Stat Cards ───────────────────────────────────────────── -->
<?php
$cards = [
    ['label' => 'Total Users',       'val' => $stats['total_users']      ?? 0,        'icon' => 'bi-people',         'cls' => 'icon-primary'],
    ['label' => 'Total Resources',   'val' => $stats['total_resources']  ?? 0,        'icon' => 'bi-box',            'cls' => 'icon-accent'],
    ['label' => 'Active Bookings',   'val' => $stats['active_bookings']  ?? 0,        'icon' => 'bi-calendar-check', 'cls' => 'icon-success'],
    ['label' => 'Pending Approvals', 'val' => $stats['pending_approvals']?? 0,        'icon' => 'bi-hourglass-split','cls' => 'icon-warning'],
    ['label' => 'Cancelled',         'val' => $stats['cancelled']        ?? 0,        'icon' => 'bi-x-circle',       'cls' => 'icon-danger'],
    ['label' => 'Utilization',       'val' => ($stats['utilization_rate']?? 0) . '%', 'icon' => 'bi-graph-up-arrow', 'cls' => 'icon-info'],
];
?>

<div class="row g-3 mb-4">
  <?php foreach ($cards as $card): ?>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="mb-1" style="font-size:12px;color:var(--text-muted);font-weight:500">
              <?= e($card['label']) ?>
            </p>
            <h3 class="fw-bold mb-0" style="font-size:1.5rem">
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

<!-- ─── Charts ───────────────────────────────────────────────── -->
<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart-fill" style="color:var(--primary)"></i>
        Bookings by Category
      </div>
      <div class="card-body">
        <canvas id="chartCategory" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-activity" style="color:var(--success)"></i>
        Peak Hour Bookings
      </div>
      <div class="card-body">
        <canvas id="chartPeak" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- ─── Recent Tables ─────────────────────────────────────────── -->
<div class="row g-4">

  <!-- Recent Activity -->
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-clock-history" style="color:var(--accent)"></i>
        Recent Activity
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>User</th>
              <th>Action</th>
              <th>Time</th>
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
            <tr>
              <td colspan="3" class="text-center py-4" style="color:var(--text-muted)">
                No recent activity.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Upcoming Bookings -->
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-calendar-event" style="color:var(--primary)"></i>
        Upcoming Bookings
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Ref</th>
              <th>Student</th>
              <th>Resource</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($upcomingBookings as $booking): ?>
            <tr>
              <td>
                <code style="font-size:12px;color:var(--primary)">
                  <?= e($booking['booking_reference']) ?>
                </code>
              </td>
              <td class="fw-medium"><?= e($booking['user_name'] ?? '') ?></td>
              <td style="color:var(--text-sub)"><?= e($booking['resource_name'] ?? '') ?></td>
              <td><?= status_badge($booking['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($upcomingBookings)): ?>
            <tr>
              <td colspan="4" class="text-center py-4" style="color:var(--text-muted)">
                No upcoming bookings.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- ─── Chart Scripts ──────────────────────────────────────────── -->
<script>
(function () {
  const cd = <?= json_encode($chartData) ?>;

  const defaultFont = { family: 'Inter, system-ui, sans-serif', size: 12 };
  Chart.defaults.font = defaultFont;
  Chart.defaults.color = '#9499b2';

  const gridColor = '#e8eaf0';

  // Bar chart — Bookings by Category
  const catEl = document.getElementById('chartCategory');
  if (catEl) {
    new Chart(catEl, {
      type: 'bar',
      data: {
        labels: cd.category_labels || [],
        datasets: [{
          label: 'Bookings',
          data: cd.category_data || [],
          backgroundColor: '#4361ee',
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false } },
          y: { grid: { color: gridColor }, beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }

  // Line chart — Peak Hours
  const peakEl = document.getElementById('chartPeak');
  if (peakEl) {
    new Chart(peakEl, {
      type: 'line',
      data: {
        labels: cd.peak_labels || [],
        datasets: [{
          label: 'Bookings',
          data: cd.peak_data || [],
          borderColor: '#10b981',
          backgroundColor: 'rgba(16,185,129,.08)',
          borderWidth: 2,
          pointBackgroundColor: '#10b981',
          pointRadius: 4,
          fill: true,
          tension: 0.35,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false } },
          y: { grid: { color: gridColor }, beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }
})();
</script>
