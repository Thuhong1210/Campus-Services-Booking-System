<div class="page-header mb-4"><h1 class="h3 fw-bold">Student Dashboard</h1></div>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card stat-card"><div class="card-body"><p class="text-muted small">Upcoming</p><h3><?= (int)($stats['upcoming']??0) ?></h3></div></div></div>
  <div class="col-md-3"><div class="card stat-card"><div class="card-body"><p class="text-muted small">Pending</p><h3><?= (int)($stats['pending']??0) ?></h3></div></div></div>
  <div class="col-md-3"><div class="card stat-card"><div class="card-body"><p class="text-muted small">Approved</p><h3><?= (int)($stats['approved']??0) ?></h3></div></div></div>
  <div class="col-md-3"><div class="card stat-card"><div class="card-body"><p class="text-muted small">Notifications</p><h3><?= (int)($unreadCount??0) ?></h3></div></div></div>
</div>
<div class="d-flex gap-2 mb-4">
  <a href="<?= url('index.php?page=resources/browse') ?>" class="btn btn-primary"><i class="bi bi-search me-1"></i>Browse Resources</a>
  <a href="<?= url('index.php?page=bookings/create') ?>" class="btn btn-outline-primary"><i class="bi bi-plus me-1"></i>Create Booking</a>
</div>
<div class="row g-4">
  <div class="col-lg-7"><div class="card"><div class="card-header bg-white fw-medium">My Upcoming Bookings</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Resource</th><th>Date</th><th>Status</th></tr></thead><tbody>
  <?php foreach($upcomingBookings as $b): ?><tr><td><?= e($b['resource_name']) ?></td><td><?= format_datetime($b['start_datetime']) ?></td><td><?= status_badge($b['status']) ?></td></tr><?php endforeach; ?>
  <?php if(empty($upcomingBookings)): ?><tr><td colspan="3" class="text-center text-muted py-4">No upcoming bookings</td></tr><?php endif; ?>
  </tbody></table></div></div></div>
  <div class="col-lg-5"><div class="card"><div class="card-header bg-white fw-medium">Notifications</div><div class="list-group list-group-flush">
  <?php foreach($notifications as $n): ?><div class="list-group-item"><strong class="small"><?= e($n['title']) ?></strong><p class="mb-0 small text-muted"><?= e($n['message']) ?></p></div><?php endforeach; ?>
  </div></div></div>
</div>
