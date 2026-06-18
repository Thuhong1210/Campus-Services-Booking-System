<div class="mb-4"><h1 class="h3 fw-bold">Approval History</h1></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>ID</th><th>Booking</th><th>Approver</th><th>Decision</th><th>Comment</th><th>Time</th></tr></thead><tbody>
<?php foreach($history as $h): ?><tr><td><?= $h['id'] ?></td><td><?= e($h['booking_reference']) ?></td><td><?= e($h['approver_name']) ?></td><td><?= status_badge($h['decision']) ?></td><td><?= e($h['comment']??'') ?></td><td><?= format_datetime($h['decided_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>