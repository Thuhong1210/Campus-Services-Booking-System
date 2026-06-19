<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="fw-bold mb-0">Add New User</h1>
    <p class="text-muted mb-0" style="font-size:13.5px">Create a new system account and assign a role.</p>
  </div>
  <a href="<?= route_url('users') ?>" class="btn btn-light d-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> Back to Users
  </a>
</div>

<!-- ─── Form Card ────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <i class="bi bi-person-plus me-2" style="color:var(--primary)"></i>
    User Information
  </div>
  <div class="card-body">
    <form method="POST" action="<?= route_url('users', 'store') ?>">
      <?= csrf_field() ?>

      <div class="row g-3">

        <div class="col-md-6">
          <label class="form-label">Full Name <span class="text-danger">*</span></label>
          <input
            type="text"
            name="full_name"
            class="form-control"
            placeholder="e.g. Nguyen Van A"
            value="<?= e(old('full_name')) ?>"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Email Address <span class="text-danger">*</span></label>
          <input
            type="email"
            name="email"
            class="form-control"
            placeholder="e.g. user@example.com"
            value="<?= e(old('email')) ?>"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Username <span class="text-danger">*</span></label>
          <input
            type="text"
            name="username"
            class="form-control"
            placeholder="e.g. nguyenvana"
            value="<?= e(old('username')) ?>"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Password <span class="text-danger">*</span></label>
          <input
            type="password"
            name="password"
            class="form-control"
            placeholder="Minimum 8 characters"
            minlength="8"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Phone Number</label>
          <input
            type="tel"
            name="phone"
            class="form-control"
            placeholder="e.g. 0901234567"
            value="<?= e(old('phone')) ?>"
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Department</label>
          <select name="department_id" class="form-select">
            <option value="">— Not assigned —</option>
            <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['id'] ?>">
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
            placeholder="e.g. 21521234"
            value="<?= e(old('student_code')) ?>"
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Staff ID</label>
          <input
            type="text"
            name="staff_code"
            class="form-control"
            placeholder="e.g. GV001"
            value="<?= e(old('staff_code')) ?>"
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Role <span class="text-danger">*</span></label>
          <select name="role_id" class="form-select" required>
            <?php foreach ($roles as $role): ?>
            <option value="<?= $role['id'] ?>">
              <?= e($role['role_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Account Status</label>
          <select name="status" class="form-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>

      </div><!-- /.row -->

      <!-- ─── Form Actions ──────────────────────────────────── -->
      <div class="d-flex gap-2 mt-4 pt-3" style="border-top:var(--border-thin)">
        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
          <i class="bi bi-person-check-fill"></i> Create User
        </button>
        <a href="<?= route_url('users') ?>" class="btn btn-light">Cancel</a>
      </div>

    </form>
  </div>
</div>