<div class="page-header mb-4"><h1 class="h3 fw-bold">My Bookings</h1></div>

<form method="GET" class="card p-3 mb-4">
  <input type="hidden" name="page" value="bookings">
  <input type="hidden" name="action" value="my">
  <div class="row g-2 align-items-end">
    <div class="col-md-4"><input name="search" class="form-control" placeholder="Search reference or purpose..." value="<?= e($filters['search'] ?? '') ?>"></div>
    <div class="col-md-3"><select name="status" class="form-select"><option value="">All Status</option><?php foreach (['pending','approved','rejected','cancelled','completed','expired'] as $s): ?><option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
  </div>
</form>

<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Reference</th><th>Resource</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($bookings as $b): ?><tr><td><code><?= e($b['booking_reference']) ?></code></td><td><?= e($b['resource_name']) ?></td><td><?= date('d/m/Y', strtotime($b['start_datetime'])) ?></td><td><?= date('H:i', strtotime($b['start_datetime'])) ?>-<?= date('H:i', strtotime($b['end_datetime'])) ?></td><td><?= status_badge($b['status']) ?></td>
<td class="text-nowrap"><a href="<?= url('index.php?page=bookings&action=show&id='.$b['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
<?php if(in_array($b['status'],['pending','approved']) && strtotime($b['start_datetime']) >= time()): ?>
<a href="<?= route_url('bookings', 'edit', ['id' => $b['id']]) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
<button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $b['id'] ?>">Cancel</button>
<div class="modal fade" id="cancelModal<?= $b['id'] ?>"><div class="modal-dialog"><form method="POST" action="<?= url('index.php?page=bookings&action=cancel&id='.$b['id']) ?>" class="modal-content"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Cancel Booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p class="small text-warning">This action will record your cancellation reason and time.</p><label class="form-label">Reason *</label><textarea name="reason" class="form-control" required></textarea></div>
<div class="modal-footer"><button class="btn btn-danger">Confirm Cancel</button></div></form></div></div><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(empty($bookings)): ?><tr><td colspan="6" class="text-center text-muted py-4">No bookings found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require VIEW_PATH.'/partials/pagination.php'; ?>
