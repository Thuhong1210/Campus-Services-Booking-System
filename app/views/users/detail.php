<div class="mb-4"><h1 class="h3 fw-bold"><?= e($user['full_name']) ?></h1></div>
<div class="row g-4">
  <div class="col-md-4"><div class="card p-4 text-center"><div class="avatar-circle lg mx-auto mb-3"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
    <h5><?= e($user['full_name']) ?></h5><p class="text-muted"><?= e($user['email']) ?></p>
    <p>Role: <?= e(implode(', ', $user['roles'] ?? [])) ?></p><?= status_badge($user['status'],'resource') ?>
  </div></div>
  <div class="col-md-8"><div class="card"><div class="card-header">Recent Bookings</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Ref</th><th>Resource</th><th>Date</th><th>Status</th></tr></thead><tbody>
  <?php foreach($recentBookings??[] as $b): ?><tr><td><?= e($b['booking_reference']) ?></td><td><?= e($b['resource_name']) ?></td><td><?= format_datetime($b['start_datetime']) ?></td><td><?= status_badge($b['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
</div>