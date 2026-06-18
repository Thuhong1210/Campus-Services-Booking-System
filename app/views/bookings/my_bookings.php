<div class="mb-4"><h1 class="h3 fw-bold">My Bookings</h1></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Reference</th><th>Resource</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($bookings as $b): ?><tr><td><?= e($b['booking_reference']) ?></td><td><?= e($b['resource_name']) ?></td><td><?= date('d/m/Y', strtotime($b['start_datetime'])) ?></td><td><?= date('H:i', strtotime($b['start_datetime'])) ?>-<?= date('H:i', strtotime($b['end_datetime'])) ?></td><td><?= status_badge($b['status']) ?></td>
<td><a href="<?= url('index.php?page=bookings&action=show&id='.$b['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
<?php if(in_array($b['status'],['pending','approved']) && strtotime($b['start_datetime']) >= time()): ?>
<a href="<?= route_url('bookings', 'edit', ['id' => $b['id']]) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
<?php endif; ?>
<?php if(in_array($b['status'],['pending','approved'])): ?><button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $b['id'] ?>">Cancel</button>
<div class="modal fade" id="cancelModal<?= $b['id'] ?>"><div class="modal-dialog"><form method="POST" action="<?= url('index.php?page=bookings&action=cancel&id='.$b['id']) ?>" class="modal-content"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Cancel Booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p class="small text-warning">This action will record your cancellation reason and time.</p><label class="form-label">Reason *</label><textarea name="reason" class="form-control" required></textarea></div>
<div class="modal-footer"><button class="btn btn-danger">Confirm Cancel</button></div></form></div></div><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>