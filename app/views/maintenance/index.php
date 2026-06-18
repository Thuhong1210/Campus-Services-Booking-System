<div class="page-header d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold mb-0">Maintenance Schedules</h1>
  <a href="<?= route_url('maintenance', 'create') ?>" class="btn btn-primary"><i class="bi bi-plus me-1"></i>Schedule Maintenance</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Resource</th><th>Start</th><th>End</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($schedules as $s): ?><tr>
  <td><?= e($s['resource_name']) ?></td><td><?= format_datetime($s['maintenance_start']) ?></td><td><?= format_datetime($s['maintenance_end']) ?></td>
  <td><?= e($s['reason']) ?></td><td><?= status_badge($s['status'], 'resource') ?></td>
  <td><a href="<?= route_url('maintenance', 'edit', ['id' => $s['id']]) ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a><form method="POST" action="<?= route_url('maintenance', 'delete', ['id'=>$s['id']]) ?>" class="d-inline" onsubmit="return confirm('Remove schedule?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Remove</button></form></td>
</tr><?php endforeach; ?>
</tbody></table></div></div>
