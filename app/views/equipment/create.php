<div class="mb-4"><h1 class="h3 fw-bold">Add Equipment</h1></div>
<form method="POST" action="<?= route_url('equipment', 'store') ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-md-6"><label class="form-label">Equipment Name *</label><input name="equipment_name" class="form-control" required></div>
  <div class="col-md-3"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" value="1" min="1"></div>
  <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="available">Available</option><option value="unavailable">Unavailable</option><option value="maintenance">Maintenance</option></select></div>
  <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
</div>
<div class="mt-4"><button class="btn btn-primary">Save</button></div></form>
