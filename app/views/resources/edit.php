<div class="mb-4"><h1 class="h3 fw-bold">Edit Resource</h1></div>
<form method="POST" action="<?= url('index.php?page=resources&action=update&id='.$resource['id']) ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Resource Name</label><input name="resource_name" class="form-control" value="<?= e($resource['resource_name']) ?>" required></div>
<div class="col-md-6"><label class="form-label">Resource Code</label><input name="resource_code" class="form-control" value="<?= e($resource['resource_code']) ?>" required></div>
<div class="col-md-6"><label class="form-label">Category</label><select name="category_id" class="form-select"><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $resource['category_id']==$c['id']?'selected':'' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Location</label><input name="location" class="form-control" value="<?= e($resource['location']) ?>"></div>
<div class="col-md-4"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="<?= $resource['capacity'] ?>"></div>
<div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach(['available','unavailable','maintenance','restricted'] as $s): ?><option value="<?= $s ?>" <?= $resource['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= e($resource['description']??'') ?></textarea></div>
<div class="col-12"><label class="form-label">Equipment</label>
<div class="row g-2"><?php foreach($equipmentList ?? [] as $eq): ?><div class="col-md-4"><label class="form-check"><input type="checkbox" name="equipment_ids[]" value="<?= $eq['id'] ?>" class="form-check-input" <?= in_array((int)$eq['id'], $assignedEquipment ?? [], true) ? 'checked' : '' ?>> <?= e($eq['equipment_name']) ?></label></div><?php endforeach; ?></div>
</div>
</div><div class="mt-4"><button class="btn btn-primary">Update</button></div></form>