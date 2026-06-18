<div class="d-flex justify-content-between mb-4"><h1 class="h3 fw-bold">Resources</h1><a href="<?= url('index.php?page=resources&action=create') ?>" class="btn btn-primary">Add Resource</a></div>
<form class="row g-2 mb-3" method="GET"><input type="hidden" name="page" value="resources">
<div class="col-md-3"><input name="search" class="form-control" placeholder="Search..." value="<?= e($filters['search']??'') ?>"></div>
<div class="col-md-2"><select name="status" class="form-select"><option value="">All Status</option><?php foreach(['available','unavailable','maintenance','restricted'] as $s): ?><option value="<?= $s ?>" <?= ($filters['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div></form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Location</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($resources as $r): ?><tr><td><?= e($r['resource_code']) ?></td><td><?= e($r['resource_name']) ?></td><td><?= e($r['category_name']??'') ?></td><td><?= e($r['location']) ?></td><td><?= $r['capacity'] ?></td><td><?= status_badge($r['status'],'resource') ?></td>
<td><a href="<?= url('index.php?page=resources&action=show&id='.$r['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
<a href="<?= url('index.php?page=resources&action=edit&id='.$r['id']) ?>" class="btn btn-sm btn-outline-secondary">Edit</a></td></tr><?php endforeach; ?>
</tbody></table></div></div><?php require VIEW_PATH.'/partials/pagination.php'; ?>