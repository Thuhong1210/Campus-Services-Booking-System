<div class="mb-4"><h1 class="h3 fw-bold">Pending Approval Queue</h1></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Booking</th><th>Student</th><th>Resource</th><th>Date/Time</th><th>Purpose</th><th>Actions</th></tr></thead><tbody>
<?php foreach($pending as $p): ?><tr><td><?= e($p['booking_reference']) ?></td><td><?= e($p['user_name']) ?></td><td><?= e($p['resource_name']) ?></td><td><?= format_datetime($p['start_datetime']) ?></td><td><?= e($p['purpose']) ?></td>
<td><a href="<?= url('index.php?page=approvals&action=show&id='.$p['id']) ?>" class="btn btn-sm btn-primary">Review</a></td></tr><?php endforeach; ?>
<?php if(empty($pending)): ?><tr><td colspan="6" class="text-center text-muted py-4">No pending approvals</td></tr><?php endif; ?>
</tbody></table></div></div>