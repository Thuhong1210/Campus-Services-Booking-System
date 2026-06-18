<div class="mb-4"><h1 class="h3 fw-bold">Edit Time Slot</h1></div>
<form method="POST" action="<?= url('index.php?page=time-slots&action=update&id='.$timeSlot['id']) ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Resource</label><select name="resource_id" class="form-select"><?php foreach($resources as $r): ?><option value="<?= $r['id'] ?>" <?= $timeSlot['resource_id']==$r['id']?'selected':'' ?>><?= e($r['resource_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Day</label><select name="day_of_week" class="form-select"><?php for($i=0;$i<7;$i++): ?><option value="<?= $i ?>" <?= (int)$timeSlot['day_of_week']===$i?'selected':'' ?>><?= day_name($i) ?></option><?php endfor; ?></select></div>
<div class="col-md-4"><label class="form-label">Start</label><input type="time" name="start_time" class="form-control" value="<?= date('H:i',strtotime($timeSlot['start_time'])) ?>"></div>
<div class="col-md-4"><label class="form-label">End</label><input type="time" name="end_time" class="form-control" value="<?= date('H:i',strtotime($timeSlot['end_time'])) ?>"></div>
<div class="col-md-4"><label class="form-label">Peak</label><select name="is_peak" class="form-select"><option value="0">No</option><option value="1" <?= $timeSlot['is_peak']?'selected':'' ?>>Yes</option></select></div>
<div class="col-md-4"><label class="form-label">Active</label><select name="is_active" class="form-select"><option value="1">Active</option><option value="0">Disabled</option></select></div>
</div><div class="mt-4"><button class="btn btn-primary">Update</button></div></form>