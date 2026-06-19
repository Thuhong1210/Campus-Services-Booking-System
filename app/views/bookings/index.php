<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <h1 class="h3 fw-bold mb-0">Booking Management</h1>
  <?php if (Auth::isAdmin()): ?>
  <a href="<?= route_url('bookings', 'create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i>Create Booking</a>
  <?php endif; ?>
</div>

<form method="GET" class="card p-3 mb-4">
  <input type="hidden" name="page" value="bookings">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><input name="search" class="form-control" placeholder="Search reference, purpose..." value="<?= e($filters['search'] ?? '') ?>"></div>
    <div class="col-md-2"><select name="status" class="form-select"><option value="">All Status</option><?php foreach (['pending','approved','rejected','cancelled','completed'] as $s): ?><option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><select name="resource_id" class="form-select"><option value="">All Resources</option><?php foreach ($resources as $r): ?><option value="<?= $r['id'] ?>" <?= ($filters['resource_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= e($r['resource_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from'] ?? '') ?>" placeholder="From"></div>
    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to'] ?? '') ?>" placeholder="To"></div>
    <div class="col-md-1"><button class="btn btn-outline-primary w-100">Filter</button></div>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Reference</th><th>Student</th><th>Resource</th><th>Date/Time</th><th>Purpose</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
        <tr>
          <td><code><?= e($b['booking_reference']) ?></code></td>
          <td><?= e($b['user_name'] ?? '') ?></td>
          <td><?= e($b['resource_name'] ?? '') ?></td>
          <td><?= format_datetime($b['start_datetime']) ?></td>
          <td><?= e($b['purpose']) ?></td>
          <td><?= status_badge($b['status']) ?></td>
          <td class="text-nowrap">
            <a href="<?= route_url('bookings', 'show', ['id' => $b['id']]) ?>" class="btn btn-sm btn-outline-primary">View</a>
            <?php if (Auth::isAdmin() && in_array($b['status'], ['pending', 'approved'], true)): ?>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $b['id'] ?>">Cancel</button>
            <div class="modal fade" id="cancelModal<?= $b['id'] ?>">
              <div class="modal-dialog"><form method="POST" action="<?= route_url('bookings', 'cancel', ['id' => $b['id']]) ?>" class="modal-content"><?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Admin Cancel Booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><label class="form-label">Cancellation reason *</label><textarea name="reason" class="form-control" required></textarea></div>
                <div class="modal-footer"><button class="btn btn-danger">Confirm Cancel</button></div>
              </form></div>
            </div>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($bookings)): ?><tr><td colspan="7" class="text-center text-muted py-4">No bookings found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require VIEW_PATH . '/partials/pagination.php'; ?>
