<?php
$avatarUrl = !empty($user['avatar'])
    ? asset('avatars/' . $user['avatar'])
    : null;
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $user['full_name']), 0, 2))));
$roles = implode(', ', array_column($user['roles'] ?? [], 'role_name'));
?>

<div class="mb-4"><h1 class="h3 fw-bold">My Profile</h1></div>

<div class="row g-4">

  <!-- Left: Avatar card -->
  <div class="col-md-3">
    <div class="card shadow-sm text-center p-4" style="border-radius:16px">
      <div class="mb-3 position-relative d-inline-block mx-auto">
        <?php if ($avatarUrl): ?>
          <img src="<?= $avatarUrl ?>" alt="Avatar"
               class="rounded-circle border border-3 border-primary shadow"
               style="width:110px;height:110px;object-fit:cover">
        <?php else: ?>
          <div class="rounded-circle border border-3 border-primary shadow d-flex align-items-center justify-content-center bg-primary text-white fw-bold mx-auto"
               style="width:110px;height:110px;font-size:36px">
            <?= $initials ?>
          </div>
        <?php endif; ?>
      </div>
      <h5 class="fw-bold mb-0"><?= e($user['full_name']) ?></h5>
      <span class="badge bg-primary-subtle text-primary mt-1"><?= e($roles) ?></span>
      <?php if (!empty($user['student_code'])): ?>
        <p class="text-muted small mt-2 mb-0"><i class="bi bi-person-badge me-1"></i><?= e($user['student_code']) ?></p>
      <?php endif; ?>
      <?php if (!empty($user['staff_code'])): ?>
        <p class="text-muted small mt-2 mb-0"><i class="bi bi-briefcase me-1"></i><?= e($user['staff_code']) ?></p>
      <?php endif; ?>
      <p class="text-muted small mb-0 mt-1"><i class="bi bi-envelope me-1"></i><?= e($user['email']) ?></p>

      <!-- Avatar upload form -->
      <form method="POST" action="<?= url('index.php?page=profile&action=upload-avatar') ?>"
            enctype="multipart/form-data" class="mt-3">
        <?= csrf_field() ?>
        <label class="btn btn-outline-primary btn-sm w-100" style="border-radius:20px;cursor:pointer">
          <i class="bi bi-camera me-1"></i> Change Photo
          <input type="file" name="avatar" accept="image/*" class="d-none"
                 onchange="this.form.submit()">
        </label>
      </form>
    </div>
  </div>

  <!-- Right: Edit form -->
  <div class="col-md-9">
    <div class="card shadow-sm p-4" style="border-radius:16px">
      <h5 class="fw-semibold mb-4 border-bottom pb-2">Personal Information</h5>
      <form method="POST" action="<?= url('index.php?page=profile&action=update') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Full Name</label>
            <input name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly>
            <input type="hidden" name="email" value="<?= e($user['email']) ?>">
            <small class="text-muted">Email cannot be changed</small>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Phone</label>
            <input name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="Enter phone number">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Department</label>
            <select name="department_id" class="form-select">
              <option value="">-- Select Department --</option>
              <?php foreach($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($user['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                  <?= e($d['department_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Update Profile</button>
          <a href="<?= url('index.php?page=change-password') ?>" class="btn btn-outline-secondary px-4">
            <i class="bi bi-lock me-1"></i>Change Password
          </a>
        </div>
      </form>
    </div>
  </div>

</div>
