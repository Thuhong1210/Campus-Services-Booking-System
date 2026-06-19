<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="fw-bold mb-0">Edit User</h1>
    <p class="text-muted mb-0" style="font-size:13.5px">Update account information for <strong><?= e($user['full_name']) ?></strong>.</p>
  </div>
  <a href="<?= route_url('users') ?>" class="btn btn-light d-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> Back to Users
  </a>
</div>

<!-- ─── Form Card ────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <i class="bi bi-pencil-square me-2" style="color:var(--primary)"></i>
    Edit Information
  </div>
  <div class="card-body">
    <form method="POST" action="<?= route_url('users', 'update', ['id' => $user['id']]) ?>">
      <?= csrf_field() ?>

      <div class="row g-3">

        <div class="col-md-6">
          <label class="form-label">Full Name <span class="text-danger">*</span></label>
          <input
            type="text"
            name="full_name"
            class="form-control"
            value="<?= e($user['full_name']) ?>"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Email Address <span class="text-danger">*</span></label>
          <input
            type="email"
            name="email"
            class="form-control"
            value="<?= e($user['email']) ?>"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Username <span class="text-danger">*</span></label>
          <input
            type="text"
            name="username"
            class="form-control"
            value="<?= e($user['username']) ?>"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">New Password</label>
          <input
            type="password"
            name="password"
            class="form-control"
            placeholder="Leave blank to keep current password"
          >
          <div class="form-text">Only fill this if you want to change the password.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Phone Number</label>
          <input
            type="tel"
            name="phone"
            class="form-control"
            value="<?= e($user['phone'] ?? '') ?>"
            placeholder="e.g. 0901234567"
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Department</label>
          <select name="department_id" class="form-select">
            <option value="">— Not assigned —</option>
            <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['id'] ?>" <?= ($user['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
              <?= e($dept['department_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Student ID</label>
          <input
            type="text"
            name="student_code"
            class="form-control"
            value="<?= e($user['student_code'] ?? '') ?>"
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Staff ID</label>
          <input
            type="text"
            name="staff_code"
            class="form-control"
            value="<?= e($user['staff_code'] ?? '') ?>"
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Role</label>
          <select name="role_id" class="form-select">
            <?php foreach ($roles as $role): ?>
            <option
              value="<?= $role['id'] ?>"
              <?= in_array($role['role_name'], $user['roles'] ?? []) ? 'selected' : '' ?>
            >
              <?= e($role['role_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Account Status</label>
          <select name="status" class="form-select">
            <?php foreach (['active', 'inactive', 'suspended'] as $s): ?>
            <option value="<?= $s ?>" <?= $user['status'] === $s ? 'selected' : '' ?>>
              <?= ucfirst($s) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

      </div><!-- /.row -->

      <!-- ─── Form Actions ──────────────────────────────────── -->
      <div class="d-flex gap-2 mt-4 pt-3" style="border-top:var(--border-thin)">
        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill"></i> Save Changes
        </button>
        <a href="<?= route_url('users') ?>" class="btn btn-light">Cancel</a>
      </div>

    </form>
  </div>
</div>