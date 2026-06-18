<div class="page-header mb-4">
  <h1 class="h3 fw-bold">Staff Dashboard</h1>
  <p class="text-muted">Campus operations overview</p>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card stat-card"><div class="card-body"><p class="text-muted small">Active Bookings</p><h3><?= (int)($stats['active_bookings']??0) ?></h3></div></div></div>
  <div class="col-md-4"><div class="card stat-card"><div class="card-body"><p class="text-muted small">Pending</p><h3><?= (int)($stats['pending']??0) ?></h3></div></div></div>
  <div class="col-md-4"><div class="card stat-card"><div class="card-body"><p class="text-muted small">Cancelled Today</p><h3><?= (int)($stats['cancelled']??0) ?></h3></div></div></div>
</div>
<div class="d-flex gap-2 mb-4">
  <a href="<?= route_url('bookings', 'calendar') ?>" class="btn btn-primary"><i class="bi bi-calendar3 me-1"></i>Resource Calendar</a>
  <a href="<?= route_url('bookings') ?>" class="btn btn-outline-primary"><i class="bi bi-list me-1"></i>All Bookings</a>
</div>
<div class="row g-4">
  <div class="col-lg-8"><div class="card"><div class="card-header bg-white fw-medium">Upcoming Campus Bookings</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Resource</th><th>User</th><th>Time</th><th>Status</th></tr></thead><tbody>
  <?php foreach($upcomingBookings as $b): ?><tr><td><?= e($b['resource_name']) ?></td><td><?= e($b['user_name']??'') ?></td><td><?= format_datetime($b['start_datetime']) ?></td><td><?= status_badge($b['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
  <div class="col-lg-4"><div class="card"><div class="card-header bg-white fw-medium">Notifications</div><div class="list-group list-group-flush">
  <?php foreach($notifications as $n): ?><div class="list-group-item small"><strong><?= e($n['title']) ?></strong><p class="mb-0 text-muted"><?= e($n['message']) ?></p></div><?php endforeach; ?>
  </div></div></div>
</div>
