<div class="page-header mb-4"><h1 class="h3 fw-bold"><?= e(__('Lecturer Dashboard')) ?></h1></div>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card stat-card"><div class="card-body"><p class="text-muted small"><?= e(__('Pending Approvals')) ?></p><h3><?= (int)($stats['pending_approvals']??0) ?></h3></div></div></div>
</div>
<div class="row g-4">
  <div class="col-lg-6"><div class="card"><div class="card-header bg-white fw-medium"><?= e(__('Pending Approval Queue')) ?></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th><?= e(__('Student')) ?></th><th><?= e(__('Resource')) ?></th><th><?= e(__('Date')) ?></th><th></th></tr></thead><tbody>
  <?php foreach($pendingApprovals as $a): ?><tr><td><?= e($a['user_name']) ?></td><td><?= e($a['resource_name']) ?></td><td><?= format_datetime($a['start_datetime']) ?></td><td><a href="<?= url('index.php?page=approvals/show&id='.$a['booking_id']) ?>" class="btn btn-sm btn-primary"><?= e(__('Review')) ?></a></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
  <div class="col-lg-6"><div class="card"><div class="card-header bg-white fw-medium"><?= e(__('Recent Approval History')) ?></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th><?= e(__('Booking')) ?></th><th><?= e(__('Decision')) ?></th><th><?= e(__('Time')) ?></th></tr></thead><tbody>
  <?php foreach($recentHistory as $h): ?><tr><td><?= e($h['booking_reference']) ?></td><td><?= status_badge($h['decision']) ?></td><td><?= format_datetime($h['decided_at']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
</div>
