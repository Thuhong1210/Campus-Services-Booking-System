<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold mb-0">Notifications</h1>
  <form method="POST" action="<?= url('index.php?page=notifications&action=mark-all-read') ?>">
    <?= csrf_field() ?>
    <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
      <i class="bi bi-check2-all"></i> Mark All Read
    </button>
  </form>
</div>

<div class="row g-3">
  <?php foreach($notifications as $n): ?>
  <div class="col-md-6">
    <div class="card h-100 shadow-sm <?= $n['is_read'] ? '' : 'border-primary border-2' ?>" style="border-radius:12px">
      <div class="card-body d-flex flex-column gap-2">

        <div class="d-flex justify-content-between align-items-start">
          <div class="d-flex align-items-center gap-2">
            <?php if (!$n['is_read']): ?>
              <span class="bg-primary rounded-circle d-inline-block" style="width:8px;height:8px;flex-shrink:0"></span>
            <?php else: ?>
              <span class="bg-secondary rounded-circle d-inline-block opacity-25" style="width:8px;height:8px;flex-shrink:0"></span>
            <?php endif; ?>
            <strong class="<?= $n['is_read'] ? 'text-muted' : '' ?>"><?= e($n['title']) ?></strong>
          </div>
          <span class="badge rounded-pill bg-light text-secondary border" style="font-size:10px;white-space:nowrap">
            <?= e(str_replace('_', ' ', $n['type'])) ?>
          </span>
        </div>

        <p class="mb-0 small text-secondary"><?= e($n['message']) ?></p>

        <div class="d-flex justify-content-between align-items-center mt-auto pt-1">
          <small class="text-muted"><i class="bi bi-clock me-1"></i><?= format_datetime($n['created_at']) ?></small>
          <?php if (!$n['is_read']): ?>
            <form method="POST" action="<?= url('index.php?page=notifications&action=mark-read&id='.$n['id']) ?>">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-primary d-flex align-items-center gap-1" style="font-size:12px;padding:3px 10px;border-radius:20px">
                <i class="bi bi-check2"></i> Mark as read
              </button>
            </form>
          <?php else: ?>
            <span class="text-success small"><i class="bi bi-check2-circle me-1"></i>Read</span>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($notifications)): ?>
  <div class="col-12 text-center text-muted py-5">
    <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
    No notifications yet
  </div>
  <?php endif; ?>
</div>
