<?php
$user = Auth::user();
$greeting = match (true) {
    (int) date('H') < 12 => 'Good morning',
    (int) date('H') < 18 => 'Good afternoon',
    default              => 'Good evening',
};
$firstName = explode(' ', $user['full_name'] ?? 'Student')[0];
?>

<!-- ─── Welcome Header ───────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h1 class="fw-bold mb-1"><?= $greeting ?>, <?= e($firstName) ?> 👋</h1>
    <p class="text-muted mb-0" style="font-size:13.5px">
      Here's a summary of your campus bookings and notifications.
    </p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= route_url('resources', 'browse') ?>" class="btn btn-primary d-flex align-items-center gap-2">
      <i class="bi bi-search"></i> Browse Resources
    </a>
    <a href="<?= route_url('bookings', 'create') ?>" class="btn btn-light d-flex align-items-center gap-2">
      <i class="bi bi-plus-lg"></i> Create Booking
    </a>
  </div>
</div>

<!-- ─── Stat Cards ───────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <?php $cards = [
    ['label' => 'Upcoming',      'val' => (int)($stats['upcoming']      ?? 0), 'icon' => 'bi-calendar-check', 'cls' => 'icon-primary'],
    ['label' => 'Pending',       'val' => (int)($stats['pending']       ?? 0), 'icon' => 'bi-hourglass-split','cls' => 'icon-warning'],
    ['label' => 'Approved',      'val' => (int)($stats['approved']      ?? 0), 'icon' => 'bi-check-circle',   'cls' => 'icon-success'],
    ['label' => 'Notifications', 'val' => (int)($unreadCount            ?? 0), 'icon' => 'bi-bell',           'cls' => 'icon-info'],
  ]; ?>
  <?php foreach ($cards as $c): ?>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="mb-1" style="font-size:12px;color:var(--text-muted);font-weight:500">
              <?= e($c['label']) ?>
            </p>
            <h3 class="fw-bold mb-0" style="font-size:1.6rem"><?= $c['val'] ?></h3>
          </div>
          <div class="stat-icon <?= $c['cls'] ?>">
            <i class="bi <?= e($c['icon']) ?>"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ─── Content Grid ──────────────────────────────────────────── -->
<div class="row g-4">

  <!-- Upcoming Bookings Table -->
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-calendar-week-fill" style="color:var(--primary)"></i>
          Upcoming Bookings
        </div>
        <a href="<?= route_url('bookings', 'myBookings') ?>" class="btn btn-sm btn-light">
          View All <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Resource</th>
              <th>Date & Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($upcomingBookings as $b): ?>
            <tr>
              <td class="fw-medium"><?= e($b['resource_name']) ?></td>
              <td style="color:var(--text-sub);font-size:13px;white-space:nowrap">
                <?= format_datetime($b['start_datetime']) ?>
              </td>
              <td><?= status_badge($b['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($upcomingBookings)): ?>
            <tr>
              <td colspan="3" class="text-center py-4" style="color:var(--text-muted)">
                <i class="bi bi-calendar-x d-block mb-1" style="font-size:1.75rem"></i>
                No upcoming bookings
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Recent Notifications -->
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-bell-fill" style="color:var(--warning)"></i>
          Notifications
        </div>
        <a href="<?= route_url('notifications') ?>" class="btn btn-sm btn-light">
          See All <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($notifications as $n): ?>
        <div class="list-group-item px-3 py-3"
             style="<?= !$n['is_read'] ? 'border-left:3px solid var(--primary);background:var(--primary-soft)' : '' ?>">
          <div class="d-flex align-items-start gap-2">
            <?php if (!$n['is_read']): ?>
              <span class="flex-shrink-0 rounded-circle mt-1" style="width:7px;height:7px;background:var(--primary)"></span>
            <?php else: ?>
              <span class="flex-shrink-0 rounded-circle mt-1" style="width:7px;height:7px;background:var(--border)"></span>
            <?php endif; ?>
            <div class="min-w-0">
              <div class="fw-semibold <?= $n['is_read'] ? '' : '' ?>" style="font-size:13.5px">
                <?= e($n['title']) ?>
              </div>
              <div class="text-muted" style="font-size:12.5px;margin-top:2px">
                <?= e(mb_strimwidth($n['message'] ?? '', 0, 80, '…')) ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($notifications)): ?>
        <div class="list-group-item text-center py-4" style="color:var(--text-muted)">
          <i class="bi bi-bell-slash d-block mb-1" style="font-size:1.75rem"></i>
          No notifications
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>
