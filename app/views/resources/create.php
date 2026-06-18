<div class="mb-4"><h1 class="h3 fw-bold">Add Resource</h1></div>
<form method="POST" action="<?= url('index.php?page=resources&action=store') ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Resource Name *</label><input name="resource_name" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Resource Code *</label><input name="resource_code" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Category *</label><select name="category_id" class="form-select" required><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Location *</label><input name="location" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="1"></div>
<div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach(['available','unavailable','maintenance','restricted'] as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
<div class="col-12"><label class="form-label">Equipment</label>
<div class="row g-2"><?php foreach($equipmentList ?? [] as $eq): ?><div class="col-md-4"><label class="form-check"><input type="checkbox" name="equipment_ids[]" value="<?= $eq['id'] ?>" class="form-check-input"> <?= e($eq['equipment_name']) ?></label></div><?php endforeach; ?></div>
</div>
</div><div class="mt-4"><button class="btn btn-primary">Save</button></div></form>