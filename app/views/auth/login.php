<form method="POST" action="<?= url('login.php') ?>">
  <?= csrf_field() ?>

  <!-- ─── Login Field ────────────────────────────────────────── -->
  <div class="mb-3">
    <label class="form-label fw-medium" for="login">Student ID / Email / Username</label>
    <div class="input-group">
      <span class="input-group-text">
        <i class="bi bi-person"></i>
      </span>
      <input
        type="text"
        id="login"
        name="login"
        class="form-control"
        placeholder="student@example.com"
        value="<?= e(old('login')) ?>"
        required
        autofocus
      >
    </div>
  </div>

  <!-- ─── Password Field ────────────────────────────────────── -->
  <div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <label class="form-label fw-medium mb-0" for="password">Password</label>
      <a href="<?= route_url('forgot-password') ?>" class="small text-decoration-none" style="color:var(--primary)">
        Forgot password?
      </a>
    </div>
    <div class="input-group">
      <span class="input-group-text">
        <i class="bi bi-lock"></i>
      </span>
      <input
        type="password"
        id="password"
        name="password"
        class="form-control"
        placeholder="Enter your password"
        required
      >
    </div>
  </div>

  <!-- ─── Submit ────────────────────────────────────────────── -->
  <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3">
    Sign In to Campus Services
  </button>

</form>
