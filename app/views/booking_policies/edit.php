<div class="mb-4"><h1 class="h3 fw-bold">Edit Policy</h1></div>
<form method="POST" action="<?= url('index.php?page=booking-policies&action=update&id='.$policy['id']) ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Policy Name</label><input name="policy_name" class="form-control" value="<?= e($policy['policy_name']) ?>"></div>
<div class="col-md-6"><label class="form-label">Category</label><select name="category_id" class="form-select"><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $policy['category_id']==$c['id']?'selected':'' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Max Duration</label><input type="number" step="0.5" name="max_duration_hours" class="form-control" value="<?= e($policy['max_duration_hours']) ?>"></div>
<div class="col-md-4"><label class="form-label">Requires Approval</label><select name="requires_approval" class="form-select"><option value="0">No</option><option value="1" <?= $policy['requires_approval']?'selected':'' ?>>Yes</option></select></div>
</div><div class="mt-4"><button class="btn btn-primary">Update</button></div></form>