<div class="mb-4"><h1 class="h3 fw-bold">Schedule Maintenance</h1></div>
<form method="POST" action="<?= route_url('maintenance', 'store') ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-md-6"><label class="form-label">Resource *</label><select name="resource_id" class="form-select" required><?php foreach($resources as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['resource_name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="scheduled">Scheduled</option><option value="in_progress">In Progress</option></select></div>
  <div class="col-md-6"><label class="form-label">Start *</label><input type="datetime-local" name="maintenance_start" class="form-control" required></div>
  <div class="col-md-6"><label class="form-label">End *</label><input type="datetime-local" name="maintenance_end" class="form-control" required></div>
  <div class="col-12"><label class="form-label">Reason *</label><textarea name="reason" class="form-control" rows="3" required></textarea></div>
</div>
<div class="alert alert-warning mt-3 small">Resources under maintenance cannot be booked during the scheduled period.</div>
<div class="mt-3"><button class="btn btn-primary">Schedule</button></div></form>
