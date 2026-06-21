<div class="text-center mb-4">
  <div class="brand-icon mb-3"><i class="bi bi-key-fill"></i></div>
  <h4 class="fw-bold"><?= e(__('Reset Password')) ?></h4>
  <p class="text-muted"><?= e(__('Enter your new password below.')) ?></p>
</div>

<form method="POST" action="<?= route_url('password-reset', 'update') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">

  <div class="mb-3">
    <label class="form-label fw-semibold"><?= e(__('New Password')) ?></label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock"></i></span>
      <input type="password" name="new_password" id="new_password" class="form-control"
             placeholder="<?= e(__('At least 8 characters')) ?>" required minlength="8">
      <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('new_password')"><i class="bi bi-eye"></i></button>
    </div>
  </div>

  <div class="mb-4">
    <label class="form-label fw-semibold"><?= e(__('Confirm New Password')) ?></label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
      <input type="password" name="confirm_password" id="confirm_password" class="form-control"
             placeholder="<?= e(__('Repeat new password')) ?>" required minlength="8">
    </div>
  </div>

  <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
    <i class="bi bi-check2-circle me-2"></i><?= e(__('Reset Password')) ?>
  </button>
</form>

<div class="text-center mt-3">
  <a href="<?= url('login.php') ?>" class="text-muted small"><i class="bi bi-arrow-left me-1"></i><?= e(__('Back to Login')) ?></a>
</div>

<script>
function togglePwd(id) {
  const f = document.getElementById(id);
  f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
