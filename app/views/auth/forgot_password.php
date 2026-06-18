<form method="POST" action="<?= url('index.php?page=forgot-password') ?>">
  <?= csrf_field() ?>
  <p class="text-muted small mb-3">Enter your email and we will send reset instructions.</p>
  <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e(old('email')) ?>" required></div>
  <button type="submit" class="btn btn-primary w-100">Submit</button>
  <a href="<?= url('login.php') ?>" class="btn btn-link w-100 mt-2">Back to Login</a>
</form>