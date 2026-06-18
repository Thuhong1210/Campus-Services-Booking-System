<div class="mb-4"><h1 class="h3 fw-bold">Audit Logs</h1></div>
<form class="row g-2 mb-3" method="GET"><input type="hidden" name="page" value="audit-logs">
<div class="col-md-3"><input name="action" class="form-control" placeholder="Action type" value="<?= e($filters['action']??'') ?>"></div>
<div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div></form>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>ID</th><th>User</th><th>Action</th><th>Module</th><th>IP</th><th>Time</th></tr></thead><tbody>
<?php foreach($logs as $log): ?><tr><td><?= $log['id'] ?></td><td><?= e($log['user_name']??'System') ?></td><td><span class="badge bg-light text-dark"><?= e($log['action']) ?></span></td><td><?= e($log['table_name']??'-') ?></td><td><?= e($log['ip_address']??'-') ?></td><td><?= format_datetime($log['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div><?php require VIEW_PATH.'/partials/pagination.php'; ?>