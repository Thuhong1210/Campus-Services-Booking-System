<div class="page-header d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold mb-0">Equipment</h1>
  <a href="<?= route_url('equipment', 'create') ?>" class="btn btn-primary"><i class="bi bi-plus me-1"></i>Add Equipment</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>ID</th><th>Name</th><th>Quantity</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($equipment as $eq): ?><tr>
  <td><?= $eq['id'] ?></td><td><?= e($eq['equipment_name']) ?></td><td><?= $eq['quantity'] ?></td>
  <td><?= status_badge($eq['status'], 'resource') ?></td>
  <td><a href="<?= route_url('equipment', 'edit', ['id'=>$eq['id']]) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
  <form method="POST" action="<?= route_url('equipment', 'delete', ['id'=>$eq['id']]) ?>" class="d-inline" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Delete</button></form></td>
</tr><?php endforeach; ?>
</tbody></table></div></div>
