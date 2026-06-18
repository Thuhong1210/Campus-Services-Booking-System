<div class="mb-4"><h1 class="h3 fw-bold">Add Time Slot</h1></div>
<form method="POST" action="<?= url('index.php?page=time-slots&action=store') ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Resource</label><select name="resource_id" class="form-select" required><?php foreach($resources as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['resource_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Day of Week</label><select name="day_of_week" class="form-select"><?php for($i=0;$i<7;$i++): ?><option value="<?= $i ?>"><?= day_name($i) ?></option><?php endfor; ?></select></div>
<div class="col-md-4"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Peak Hour</label><select name="is_peak" class="form-select"><option value="0">No</option><option value="1">Yes</option></select></div>
</div><div class="mt-4"><button class="btn btn-primary">Save</button></div></form>