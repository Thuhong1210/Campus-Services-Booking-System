<form method="POST" action="<?= url('index.php?page=change-password') ?>">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" minlength="8" required></div>
  <div class="mb-3"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
  <button type="submit" class="btn btn-primary">Update Password</button>
</form>