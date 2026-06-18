<div class="mb-4"><h1 class="h3 fw-bold">Browse Resources</h1></div>
<form class="row g-2 mb-4" method="GET"><input type="hidden" name="page" value="resources/browse">
<div class="col-md-4"><input name="search" class="form-control" placeholder="Search resources..." value="<?= e($filters['search']??'') ?>"></div>
<div class="col-md-3"><select name="category_id" class="form-select"><option value="">All Categories</option><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Search</button></div></form>
<div class="row g-4">
<?php foreach($resources as $r): ?><div class="col-md-4"><div class="card resource-card h-100"><div class="card-body">
<h5><?= e($r['resource_name']) ?></h5><p class="text-muted small mb-2"><?= e($r['category_name']??'') ?> · <?= e($r['location']) ?></p>
<p class="small">Capacity: <?= $r['capacity'] ?></p><?= status_badge($r['status'],'resource') ?>
<div class="mt-3 d-flex gap-2"><a href="<?= url('index.php?page=resources&action=show&id='.$r['id']) ?>" class="btn btn-sm btn-outline-primary">Details</a>
<a href="<?= url('index.php?page=bookings&action=create&resource_id='.$r['id']) ?>" class="btn btn-sm btn-primary">Book Now</a></div>
</div></div></div><?php endforeach; ?>
</div>