<div class="mb-4"><h1 class="h3 fw-bold"><?= e($resource['resource_name']) ?></h1><p class="text-muted"><?= e($resource['resource_code']) ?> · <?= e($resource['location']) ?></p></div>
<div class="row g-4">
<div class="col-md-8"><div class="card p-4"><h5>Details</h5><p><?= e($resource['description']??'No description') ?></p>
<p>Category: <?= e($resource['category_name']??'') ?></p><p>Capacity: <?= $resource['capacity'] ?></p><?= status_badge($resource['status'],'resource') ?>
<?php if(!empty($equipment)): ?><h6 class="mt-3">Equipment</h6><ul><?php foreach($equipment as $eq): ?><li><?= e($eq['equipment_name']) ?> (x<?= $eq['quantity'] ?>)</li><?php endforeach; ?></ul><?php endif; ?>
<a href="<?= url('index.php?page=bookings&action=create&resource_id='.$resource['id']) ?>" class="btn btn-primary mt-3">Create Booking</a></div></div>
<div class="col-md-4"><div class="card"><div class="card-header">Current Bookings</div><div class="list-group list-group-flush">
<?php foreach($currentBookings??[] as $b): ?>
  <div class="list-group-item small"><strong><?= format_datetime($b['start_datetime']) ?></strong><br><?= status_badge($b['status']) ?></div>
<?php endforeach; ?>
</div></div></div></div>