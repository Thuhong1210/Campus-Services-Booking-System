<div class="mb-4"><h1 class="h3 fw-bold">Add Booking Policy</h1></div>
<form method="POST" action="<?= url('index.php?page=booking-policies&action=store') ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Policy Name</label><input name="policy_name" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Category</label><select name="category_id" class="form-select" required><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Max Duration (hrs)</label><input type="number" step="0.5" name="max_duration_hours" class="form-control" value="2"></div>
<div class="col-md-4"><label class="form-label">Weekly Quota</label><input type="number" name="weekly_quota" class="form-control" value="5"></div>
<div class="col-md-4"><label class="form-label">Peak Limit/Week</label><input type="number" name="max_peak_slots_per_week" class="form-control" value="2"></div>
<div class="col-md-4"><label class="form-label">Requires Approval</label><select name="requires_approval" class="form-select"><option value="0">No</option><option value="1">Yes</option></select></div>
<div class="col-md-4"><label class="form-label">Auto Approval</label><select name="auto_approval_enabled" class="form-select"><option value="1">Yes</option><option value="0">No</option></select></div>
</div><div class="mt-4"><button class="btn btn-primary">Save</button></div></form>