<?php foreach (Flash::all() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert-<?= $type === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
      <?= e($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endforeach; ?>
<?php endforeach; ?>