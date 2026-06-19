<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <h1 class="h3 fw-bold mb-0">My Schedule</h1>
  <a href="<?= route_url('bookings', 'exportSchedule', $filters) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-download me-1"></i>Export CSV</a>
</div>

<form method="GET" class="card p-3 mb-4">
  <input type="hidden" name="page" value="bookings">
  <input type="hidden" name="action" value="schedule">
  <div class="row g-2 align-items-end">
    <div class="col-md-2"><label class="form-label small">View</label><select name="view" class="form-select"><?php foreach (['day','week','month'] as $v): ?><option value="<?= $v ?>" <?= ($filters['view'] ?? 'week') === $v ? 'selected' : '' ?>><?= ucfirst($v) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label small">Date</label><input type="date" name="date" class="form-control" value="<?= e($filters['date'] ?? date('Y-m-d')) ?>"></div>
    <div class="col-md-3"><label class="form-label small">Category</label><select name="category_id" class="form-select"><option value="">All</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($filters['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label small">Status</label><select name="status" class="form-select"><option value="">All</option><?php foreach (['pending','approved','rejected','cancelled','completed'] as $s): ?><option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Apply</button></div>
  </div>
</form>

<div class="card p-4">
  <?php
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
  ?>
  <?php foreach ($filtered as $b): ?>
  <div class="schedule-block mb-3 p-3 rounded border-start border-4 border-<?= match($b['status']) {'approved'=>'success','pending'=>'warning','rejected'=>'danger','cancelled'=>'secondary',default=>'primary'} ?>">
    <div class="d-flex flex-wrap justify-content-between gap-2">
      <strong><?= e($b['resource_name']) ?></strong>
      <?= status_badge($b['status']) ?>
    </div>
    <div class="text-muted small mt-1"><?= format_datetime($b['start_datetime']) ?> – <?= date('H:i', strtotime($b['end_datetime'])) ?> · <?= e($b['category_name'] ?? '') ?></div>
    <div class="small mt-1"><?= e($b['purpose']) ?></div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($filtered)): ?><p class="text-muted text-center py-4 mb-0">No bookings in this <?= e($view) ?> view.</p><?php endif; ?>
</div>
