<div class="mb-4"><h1 class="h3 fw-bold">Edit Equipment</h1></div>
<form method="POST" action="<?= route_url('equipment', 'update', ['id'=>$item['id']]) ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-md-6"><label class="form-label">Equipment Name</label><input name="equipment_name" class="form-control" value="<?= e($item['equipment_name']) ?>" required></div>
  <div class="col-md-3"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" value="<?= $item['quantity'] ?>"></div>
  <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach(['available','unavailable','maintenance'] as $s): ?><option value="<?= $s ?>" <?= $item['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= e($item['description']??'') ?></textarea></div>
</div>
<div class="mt-4"><button class="btn btn-primary">Update</button></div></form>
