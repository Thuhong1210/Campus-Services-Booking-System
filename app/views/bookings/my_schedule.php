<?php
// Filtering logic from HEAD
$view = $filters['view'] ?? 'week';
$refDate = strtotime($filters['date'] ?? date('Y-m-d'));
$filtered = $schedule;
if ($view === 'day') {
  $day = date('Y-m-d', $refDate);
  $filtered = array_filter($schedule, fn ($b) => date('Y-m-d', strtotime($b['start_datetime'])) === $day);
} elseif ($view === 'week') {
  $weekStart = strtotime('monday this week', $refDate);
  $weekEnd = strtotime('sunday this week', $refDate);
  $filtered = array_filter($schedule, function ($b) use ($weekStart, $weekEnd) {
    $t = strtotime($b['start_datetime']);
    return $t >= $weekStart && $t <= $weekEnd + 86399;
  });
} elseif ($view === 'month') {
  $month = date('Y-m', $refDate);
  $filtered = array_filter($schedule, fn ($b) => date('Y-m', strtotime($b['start_datetime'])) === $month);
}

// Group filtered bookings by date
$grouped = [];
foreach ($filtered as $b) {
    $day = date('Y-m-d', strtotime($b['start_datetime']));
    $grouped[$day][] = $b;
}
ksort($grouped);

$statusColor = [
    'approved'  => ['border' => '#10b981', 'bg' => '#f0fdf4', 'dot' => '#10b981'],
    'pending'   => ['border' => '#f59e0b', 'bg' => '#fffbeb', 'dot' => '#f59e0b'],
    'rejected'  => ['border' => '#ef4444', 'bg' => '#fef2f2', 'dot' => '#ef4444'],
    'cancelled' => ['border' => '#9499b2', 'bg' => '#f7f8fc', 'dot' => '#9499b2'],
    'completed' => ['border' => '#6366f1', 'bg' => '#eef2ff', 'dot' => '#6366f1'],
];
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="fw-bold mb-0">My Schedule</h1>
    <p class="text-muted mb-0" style="font-size:13.5px">Your upcoming and recent bookings, grouped by date.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= route_url('bookings', 'exportSchedule', $filters) ?>" class="btn btn-outline-primary d-flex align-items-center gap-2">
      <i class="bi bi-download"></i> Export Schedule
    </a>
    <a href="<?= route_url('bookings', 'create') ?>" class="btn btn-primary d-flex align-items-center gap-2">
      <i class="bi bi-calendar-plus-fill"></i> New Booking
    </a>
  </div>
</div>

<!-- ─── Filter Form ────────────────────────────────────────── -->
<form method="GET" class="card p-3 mb-4 shadow-sm border-0" style="border-radius:12px">
  <input type="hidden" name="page" value="bookings">
  <input type="hidden" name="action" value="schedule">
  <div class="row g-2 align-items-end">
    <div class="col-md-2">
      <label class="form-label fw-semibold small text-muted">View Mode</label>
      <select name="view" class="form-select text-capitalize">
        <?php foreach (['day','week','month'] as $v): ?>
          <option value="<?= $v ?>" <?= ($filters['view'] ?? 'week') === $v ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label fw-semibold small text-muted">Reference Date</label>
      <input type="date" name="date" class="form-control" value="<?= e($filters['date'] ?? date('Y-m-d')) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold small text-muted">Category</label>
      <select name="category_id" class="form-select">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($filters['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold small text-muted">Status</label>
      <select name="status" class="form-select">
        <option value="">All Statuses</option>
        <?php foreach (['pending','approved','rejected','cancelled','completed'] as $s): ?>
          <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-funnel-fill"></i> Apply Filter
      </button>
    </div>
  </div>
</form>

<!-- ─── Schedule Timeline ─────────────────────────────────────── -->
<?php if (empty($grouped)): ?>
<div class="card shadow-sm border-0" style="border-radius:16px">
  <div class="card-body text-center py-5">
    <i class="bi bi-calendar-x d-block mb-3" style="font-size:3rem;color:var(--text-muted)"></i>
    <p class="fw-semibold mb-1" style="color:var(--text-sub)">No bookings in this <?= e($view) ?> view</p>
    <p class="text-muted mb-3" style="font-size:13.5px">Try adjusting your filters or browse available resources.</p>
    <a href="<?= route_url('resources', 'browse') ?>" class="btn btn-primary px-4">Browse Resources</a>
  </div>
</div>

<?php else: ?>

<div class="row g-4">
  <div class="col-lg-8">
    <?php foreach ($grouped as $day => $items): ?>

    <!-- Day Group -->
    <div class="mb-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <!-- Date badge -->
        <div class="text-center flex-shrink-0" style="width:46px">
          <div class="fw-bold" style="font-size:22px;line-height:1;color:var(--primary)">
            <?= date('d', strtotime($day)) ?>
          </div>
          <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);letter-spacing:.05em">
            <?= date('M', strtotime($day)) ?>
          </div>
        </div>
        <!-- Day label -->
        <div style="flex:1;height:1px;background:var(--border)"></div>
        <div style="font-size:12.5px;font-weight:600;color:var(--text-sub);white-space:nowrap">
          <?php
          $ts   = strtotime($day);
          $today = date('Y-m-d');
          $tmr   = date('Y-m-d', strtotime('+1 day'));
          if ($day === $today)     echo '<span style="color:var(--primary)">Today</span>';
          elseif ($day === $tmr)  echo 'Tomorrow';
          else                     echo date('l', $ts);
          ?>
        </div>
      </div>

      <!-- Booking items for this day -->
      <?php foreach ($items as $b):
        $sc = $statusColor[$b['status']] ?? $statusColor['pending'];
      ?>
      <div class="mb-2 rounded-3 p-3 d-flex align-items-start gap-3"
           style="background:<?= $sc['bg'] ?>">

        <!-- Time column -->
        <div class="flex-shrink-0 text-center" style="min-width:56px">
          <div class="fw-bold" style="font-size:13.5px;color:var(--text-main)">
            <?= date('H:i', strtotime($b['start_datetime'])) ?>
          </div>
          <div style="font-size:11.5px;color:var(--text-muted)">
            <?= date('H:i', strtotime($b['end_datetime'])) ?>
          </div>
        </div>

        <!-- Divider dot -->
        <div class="flex-shrink-0" style="width:8px;height:8px;border-radius:50%;background:<?= $sc['dot'] ?>;margin-top:6px"></div>

        <!-- Content -->
        <div class="flex-grow-1 min-w-0">
          <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <span class="fw-semibold" style="font-size:14px"><?= e($b['resource_name']) ?></span>
            <?= status_badge($b['status']) ?>
          </div>
          <div style="font-size:13px;color:var(--text-sub)">
            <i class="bi bi-pencil-square me-1"></i><?= e($b['purpose']) ?>
          </div>
          <?php if (!empty($b['location'])): ?>
          <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
            <i class="bi bi-geo-alt me-1"></i><?= e($b['location']) ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Action -->
        <a href="<?= route_url('bookings', 'show', ['id' => $b['id']]) ?>"
           class="btn btn-sm btn-light flex-shrink-0" title="View details">
          <i class="bi bi-arrow-right"></i>
        </a>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endforeach; ?>
  </div>

  <!-- ─── Quick Stats Sidebar ───────────────────────────────── -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header">
        <i class="bi bi-bar-chart-line me-2" style="color:var(--primary)"></i>Overview (Filtered)
      </div>
      <div class="card-body">
        <?php
        $counts = array_count_values(array_column($filtered, 'status'));
        $summary = [
            'approved'  => ['label' => 'Approved',  'color' => '#10b981', 'icon' => 'bi-check-circle-fill'],
            'pending'   => ['label' => 'Pending',    'color' => '#f59e0b', 'icon' => 'bi-hourglass-split'],
            'rejected'  => ['label' => 'Rejected',   'color' => '#ef4444', 'icon' => 'bi-x-circle-fill'],
            'completed' => ['label' => 'Completed',  'color' => '#6366f1', 'icon' => 'bi-calendar-check-fill'],
        ];
        ?>
        <?php foreach ($summary as $key => $s): ?>
        <div class="d-flex align-items-center justify-content-between py-2"
             style="<?= $key !== 'completed' ? 'border-bottom:var(--border-thin)' : '' ?>">
           <div class="d-flex align-items-center gap-2">
             <i class="bi <?= $s['icon'] ?>" style="color:<?= $s['color'] ?>;font-size:15px"></i>
             <span style="font-size:13.5px;color:var(--text-sub)"><?= $s['label'] ?></span>
           </div>
           <span class="fw-bold" style="color:var(--text-main)"><?= $counts[$key] ?? 0 ?></span>
         </div>
         <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body text-center py-4">
        <i class="bi bi-plus-circle-fill d-block mb-2" style="font-size:2rem;color:var(--primary)"></i>
        <p class="fw-semibold mb-1">Need a room?</p>
        <p class="text-muted mb-3" style="font-size:13px">Browse available resources and book your next session.</p>
        <a href="<?= route_url('resources', 'browse') ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
          <i class="bi bi-search me-1"></i>Browse Resources
        </a>
        <a href="<?= route_url('bookings', 'create') ?>" class="btn btn-primary btn-sm w-100">
          <i class="bi bi-calendar-plus me-1"></i>Create Booking
        </a>
      </div>
    </div>
  </div>

</div>
<?php endif; ?>
