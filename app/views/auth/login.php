<form method="POST" action="<?= url('login.php') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label fw-medium">Student ID / Email / Username</label>
    <div class="input-group">
      <span class="input-group-text bg-white"><i class="bi bi-envelope text-muted"></i></span>
      <input type="text" name="login" class="form-control" value="<?= e(old('login')) ?>" placeholder="student@example.com" required autofocus>
    </div>
  </div>
  <div class="mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <label class="form-label fw-medium mb-0">Password</label>
      <a href="<?= route_url('forgot-password') ?>" class="small text-primary text-decoration-none">Forgot password?</a>
    </div>
    <div class="input-group mt-1">
      <span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span>
      <input type="password" name="password" class="form-control" required>
    </div>
  </div>
  <div class="mb-4 form-check">
    <input type="checkbox" class="form-check-input" id="remember">
    <label class="form-check-label small" for="remember">Remember me on this device</label>
  </div>
  <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign In to Campus Services</button>
  <div class="mt-4 p-3 bg-light rounded-3 small text-muted">
    <div class="fw-semibold text-dark mb-1">Demo Accounts</div>
    Admin: admin@example.com / admin123<br>
    Student: student@example.com / student123<br>
    Lecturer: lecturer@example.com / lecturer123
  </div>
</form>
