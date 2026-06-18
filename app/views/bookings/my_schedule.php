<div class="mb-4"><h1 class="h3 fw-bold">My Schedule</h1></div>
<div class="card p-4"><?php foreach($schedule as $b): ?>
<div class="schedule-block mb-3 p-3 rounded border-start border-4 border-<?= match($b['status']){'approved'=>'success','pending'=>'warning','rejected'=>'danger','cancelled'=>'secondary',default=>'primary'} ?>">
<strong><?= e($b['resource_name']) ?></strong> · <?= format_datetime($b['start_datetime']) ?> - <?= date('H:i', strtotime($b['end_datetime'])) ?><br><?= status_badge($b['status']) ?> · <?= e($b['purpose']) ?>
</div><?php endforeach; ?><?php if(empty($schedule)): ?><p class="text-muted text-center py-4">No bookings in schedule</p><?php endif; ?></div>