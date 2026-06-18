<?php
$start = date('Y-m-d\TH:i', strtotime($schedule['maintenance_start']));
$end = date('Y-m-d\TH:i', strtotime($schedule['maintenance_end']));
?>
<div class="mb-4"><h1 class="h3 fw-bold">Edit Maintenance Schedule</h1></div>
<form method="POST" action="<?= route_url('maintenance', 'update', ['id' => $schedule['id']]) ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-md-6"><label class="form-label">Resource *</label><select name="resource_id" class="form-select" required><?php foreach($resources as $r): ?><option value="<?= $r['id'] ?>" <?= (int)$schedule['resource_id']===(int)$r['id']?'selected':'' ?>><?= e($r['resource_name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach(['scheduled','in_progress','completed'] as $s): ?><option value="<?= $s ?>" <?= ($schedule['status']??'')===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-6"><label class="form-label">Start *</label><input type="datetime-local" name="maintenance_start" class="form-control" value="<?= e($start) ?>" required></div>
  <div class="col-md-6"><label class="form-label">End *</label><input type="datetime-local" name="maintenance_end" class="form-control" value="<?= e($end) ?>" required></div>
  <div class="col-12"><label class="form-label">Reason *</label><textarea name="reason" class="form-control" rows="3" required><?= e($schedule['reason']) ?></textarea></div>
</div>
<div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Update</button><a href="<?= route_url('maintenance') ?>" class="btn btn-outline-secondary">Back</a></div></form>
