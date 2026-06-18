<div class="mb-4"><h1 class="h3 fw-bold">Add Category</h1></div>
<form method="POST" action="<?= url('index.php?page=resource-categories&action=store') ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Category Name *</label><input name="category_name" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
<div class="col-md-4"><label class="form-label">Requires Approval</label><select name="requires_approval" class="form-select"><option value="0">No</option><option value="1">Yes</option></select></div>
<div class="col-md-4"><label class="form-label">Max Hours/Day</label><input type="number" step="0.5" name="max_booking_hours_per_day" class="form-control" value="4"></div>
<div class="col-md-4"><label class="form-label">Peak Limit/Week</label><input type="number" name="max_peak_slots_per_week" class="form-control" value="2"></div>
<div class="col-md-4"><label class="form-label">Max Hours/Week</label><input type="number" step="0.5" name="max_booking_hours_per_week" class="form-control" value="10"></div>
<div class="col-md-4"><label class="form-label">Cancellation Deadline (hrs)</label><input type="number" name="cancellation_deadline_hours" class="form-control" value="24"></div>
</div>
<div class="mt-4"><button class="btn btn-primary">Save</button><a href="<?= url('index.php?page=resource-categories') ?>" class="btn btn-outline-secondary ms-2">Cancel</a></div></form>