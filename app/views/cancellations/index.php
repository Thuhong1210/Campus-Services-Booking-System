<div class="mb-4"><h1 class="h3 fw-bold">Cancellations</h1></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>ID</th><th>Booking</th><th>Resource</th><th>Cancelled By</th><th>Reason</th><th>Time</th></tr></thead><tbody>
<?php foreach($cancellations as $c): ?><tr><td><?= $c['id'] ?></td><td><?= e($c['booking_reference']) ?></td><td><?= e($c['resource_name']) ?></td><td><?= e($c['cancelled_by_name']) ?></td><td><?= e($c['reason']) ?></td><td><?= format_datetime($c['cancelled_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>