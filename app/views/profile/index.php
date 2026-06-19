<div class="page-header mb-4"><h1 class="h3 fw-bold">My Profile</h1></div>
<form method="POST" action="<?= url('index.php?page=profile&action=update') ?>" class="card p-4"><?= csrf_field() ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Full Name</label><input name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" readonly></div>
<div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($user['phone']??'') ?>"></div>
<div class="col-md-6"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">Select department</option><?php foreach ($departments ?? [] as $d): ?><option value="<?= $d['id'] ?>" <?= (int)($user['department_id']??0)===(int)$d['id']?'selected':'' ?>><?= e($d['department_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Student ID</label><input class="form-control" value="<?= e($user['student_code'] ?? '-') ?>" readonly></div>
<div class="col-md-6"><label class="form-label">Role</label><input class="form-control" value="<?= e(implode(', ', $user['roles'] ?? Auth::roles())) ?>" readonly></div>
</div><div class="mt-4"><button class="btn btn-primary">Update Profile</button><a href="<?= url('index.php?page=change-password') ?>" class="btn btn-outline-secondary ms-2">Change Password</a></div></form>
