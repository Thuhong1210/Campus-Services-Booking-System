<?php
$avatarUrl = !empty($user['avatar']) ? asset('avatars/' . $user['avatar']) : null;
$initials  = strtoupper(implode('', array_map(
    fn($w) => $w[0],
    array_slice(explode(' ', $user['full_name']), 0, 2)
)));
$rolesList = implode(', ', $user['roles'] ?? Auth::roles());
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="mb-4">
  <h1 class="fw-bold mb-0">My Profile</h1>
  <p class="text-muted mb-0" style="font-size:13.5px">Manage your personal information and account settings.</p>
</div>

<div class="row g-4">

  <!-- ─── Left: Avatar Card ────────────────────────────────── -->
  <div class="col-md-4 col-lg-3">
    <div class="card text-center">
      <div class="card-body py-4">

        <!-- Avatar -->
        <div class="mb-3 position-relative d-inline-block">
          <?php if ($avatarUrl): ?>
          <img src="<?= $avatarUrl ?>" alt="Avatar"
               class="rounded-circle"
               style="width:100px;height:100px;object-fit:cover;border:3px solid var(--border)">
          <?php else: ?>
          <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto"
               style="width:100px;height:100px;font-size:2rem;background:var(--primary-soft);color:var(--primary);border:3px solid var(--border)">
            <?= $initials ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Name & Role -->
        <h5 class="fw-bold mb-1"><?= e($user['full_name']) ?></h5>
        <div class="mb-2">
          <span class="badge" style="background:var(--primary-soft);color:var(--primary);font-weight:600">
            <?= e($rolesList) ?>
          </span>
        </div>

        <!-- Quick info -->
        <div class="d-flex flex-column gap-1 text-start mt-3" style="font-size:13px;color:var(--text-sub)">
          <div><i class="bi bi-envelope me-2" style="color:var(--text-muted)"></i><?= e($user['email']) ?></div>
          <?php if (!empty($user['student_code'])): ?>
          <div><i class="bi bi-mortarboard me-2" style="color:var(--text-muted)"></i><?= e($user['student_code']) ?></div>
          <?php endif; ?>
          <?php if (!empty($user['staff_code'])): ?>
          <div><i class="bi bi-briefcase me-2" style="color:var(--text-muted)"></i><?= e($user['staff_code']) ?></div>
          <?php endif; ?>
          <?php if (!empty($user['phone'])): ?>
          <div><i class="bi bi-telephone me-2" style="color:var(--text-muted)"></i><?= e($user['phone']) ?></div>
          <?php endif; ?>
        </div>

        <!-- Change Photo -->
        <form method="POST" action="<?= route_url('profile', 'upload-avatar') ?>"
              enctype="multipart/form-data" class="mt-4">
          <?= csrf_field() ?>
          <label class="btn btn-light w-100" style="cursor:pointer">
            <i class="bi bi-camera me-2"></i>Change Photo
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="d-none"
                   onchange="this.form.submit()">
          </label>
          <div class="form-text text-center mt-1" style="font-size:11px">JPG, PNG, GIF, WEBP &bull; Max 10MB</div>
        </form>
      </div>
    </div>
  </div>

  <!-- ─── Right: Edit Form ─────────────────────────────────── -->
  <div class="col-md-8 col-lg-9">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-person-fill me-2" style="color:var(--primary)"></i>
        Personal Information
      </div>
      <div class="card-body">
        <form method="POST" action="<?= route_url('profile', 'update') ?>">
          <?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="full_name" class="form-control"
                     value="<?= e($user['full_name']) ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control"
                     value="<?= e($user['email']) ?>"
                     style="background:var(--bg-muted);color:var(--text-muted)"
                     readonly>
              <input type="hidden" name="email" value="<?= e($user['email']) ?>">
              <div class="form-text"><i class="bi bi-lock me-1"></i>Email cannot be changed.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Phone Number</label>
              <input type="tel" name="phone" class="form-control"
                     value="<?= e($user['phone'] ?? '') ?>"
                     placeholder="e.g. 0901234567">
            </div>

            <div class="col-md-6">
              <label class="form-label">Department</label>
              <select name="department_id" class="form-select">
                <option value="">— Not assigned —</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>"
                        <?= ($user['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                  <?= e($dept['department_name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

          </div><!-- /.row -->

          <!-- Actions -->
          <div class="d-flex gap-2 mt-4 pt-3" style="border-top:var(--border-thin)">
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill"></i> Save Changes
            </button>
            <a href="<?= route_url('change-password') ?>" class="btn btn-light d-flex align-items-center gap-2">
              <i class="bi bi-lock"></i> Change Password
            </a>
          </div>

        </form>
      </div>
    </div>
  </div>

</div>
