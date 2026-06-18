<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 fw-bold mb-0">User Management</h1><a href="<?= url('index.php?page=users&action=create') ?>" class="btn btn-primary"><i class="bi bi-plus me-1"></i>Add New User</a></div>
<form class="row g-2 mb-3" method="GET"><input type="hidden" name="page" value="users">
  <div class="col-md-3"><input name="search" class="form-control" placeholder="Search..." value="<?= e($filters['search']??'') ?>"></div>
  <div class="col-md-2"><select name="status" class="form-select"><option value="">All Status</option><?php foreach(['active','inactive','suspended'] as $s): ?><option value="<?= $s ?>" <?= ($filters['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select name="department_id" class="form-select"><option value="">All Departments</option><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>" <?= ($filters['department_id']??'')==$d['id']?'selected':'' ?>><?= e($d['department_name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($users as $u): ?><tr><td><?= $u['id'] ?></td><td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['roles']??'') ?></td><td><?= e($u['department_name']??'-') ?></td><td><?= status_badge($u['status'],'resource') ?></td>
<td><a href="<?= url('index.php?page=users&action=show&id='.$u['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
<a href="<?= url('index.php?page=users&action=edit&id='.$u['id']) ?>" class="btn btn-sm btn-outline-secondary">Edit</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require VIEW_PATH.'/partials/pagination.php'; ?>
