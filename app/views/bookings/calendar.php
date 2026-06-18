<div class="mb-4"><h1 class="h3 fw-bold">Resource Calendar</h1></div>
<form class="row g-2 mb-4" method="GET"><input type="hidden" name="page" value="bookings/calendar">
<div class="col-md-4"><select name="resource_id" class="form-select"><option value="">All Resources</option><?php foreach($resources as $r): ?><option value="<?= $r['id'] ?>" <?= ($filters['resource_id']??'')==$r['id']?'selected':'' ?>><?= e($r['resource_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><button class="btn btn-primary">Filter</button></div></form>
<div class="card p-4">
<?php foreach($events as $e): ?>
<div class="calendar-event mb-2 p-2 rounded bg-light border-start border-4 border-primary">
<strong><?= e($e['resource_name']) ?></strong> · <?= format_datetime($e['start_datetime']) ?> - <?= date('H:i', strtotime($e['end_datetime'])) ?>
<br><small><?= e($e['user_name']??'') ?> · <?= status_badge($e['status']) ?></small>
</div>
<?php endforeach; ?>
<?php if(empty($events)): ?><p class="text-muted text-center py-4">No bookings found</p><?php endif; ?>
</div>
