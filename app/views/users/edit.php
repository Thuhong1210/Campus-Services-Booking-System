<div class="mb-4"><h1 class="h3 fw-bold">Edit User</h1></div>
<form method="POST" action="<?= url('index.php?page=users&action=update&id='.$user['id']) ?>" class="card p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Full Name</label><input name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required></div>
    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
    <div class="col-md-6"><label class="form-label">Username</label><input name="username" class="form-control" value="<?= e($user['username']) ?>" required></div>
    <div class="col-md-6"><label class="form-label">New Password (leave blank to keep)</label><input type="password" name="password" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($user['phone']??'') ?>"></div>
    <div class="col-md-6"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">-- Select --</option><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>" <?= ($user['department_id']??'')==$d['id']?'selected':'' ?>><?= e($d['department_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Student ID</label><input name="student_code" class="form-control" value="<?= e($user['student_code']??'') ?>"></div>
    <div class="col-md-6"><label class="form-label">Staff ID</label><input name="staff_code" class="form-control" value="<?= e($user['staff_code']??'') ?>"></div>
    <div class="col-md-6"><label class="form-label">Role</label><select name="role_id" class="form-select"><?php foreach($roles as $r): ?><option value="<?= $r['id'] ?>" <?= in_array($r['role_name'],$user['roles']??[])?'selected':'' ?>><?= e($r['role_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach(['active','inactive','suspended'] as $s): ?><option value="<?= $s ?>" <?= $user['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
  </div>
  <div class="mt-4"><button class="btn btn-primary">Update</button><a href="<?= url('index.php?page=users') ?>" class="btn btn-outline-secondary ms-2">Cancel</a></div>
</form>