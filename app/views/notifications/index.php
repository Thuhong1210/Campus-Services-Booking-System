<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="fw-bold mb-0">Notifications</h1>
    <p class="text-muted mb-0" style="font-size:13.5px">
      <?= count($notifications) ?> notification<?= count($notifications) !== 1 ? 's' : '' ?> total.
    </p>
  </div>
  <?php if (!empty($notifications)): ?>
  <form method="POST" action="<?= route_url('notifications', 'markAllRead') ?>">
    <?= csrf_field() ?>
    <button class="btn btn-light d-flex align-items-center gap-2">
      <i class="bi bi-check2-all"></i> Mark All as Read
    </button>
  </form>
  <?php endif; ?>
</div>

<!-- ─── Notification List ─────────────────────────────────────── -->
<?php if (empty($notifications)): ?>
<div class="card">
  <div class="card-body text-center py-5">
    <i class="bi bi-bell-slash d-block mb-3" style="font-size:3rem;color:var(--text-muted)"></i>
    <p class="fw-semibold mb-1" style="color:var(--text-sub)">All caught up!</p>
    <p class="text-muted mb-0" style="font-size:13.5px">You have no notifications at this time.</p>
  </div>
</div>

<?php else: ?>

<div class="card">
  <?php foreach ($notifications as $i => $n):
    $isUnread = !$n['is_read'];
    $typeIcon = match ($n['type'] ?? '') {
        'booking_approved' => ['icon' => 'bi-check-circle-fill', 'color' => '#10b981'],
        'booking_rejected' => ['icon' => 'bi-x-circle-fill',     'color' => '#ef4444'],
        'booking_pending'  => ['icon' => 'bi-hourglass-split',   'color' => '#f59e0b'],
        'booking_cancelled'=> ['icon' => 'bi-slash-circle-fill', 'color' => '#9499b2'],
        default            => ['icon' => 'bi-bell-fill',         'color' => 'var(--primary)'],
    };
  ?>
  <div class="px-4 py-3 d-flex align-items-start gap-3 <?= $i > 0 ? 'border-top' : '' ?>"
       style="<?= $isUnread ? 'background:var(--primary-soft)' : '' ?>">

    <!-- Type icon -->
    <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle"
         style="width:38px;height:38px;background:<?= $isUnread ? 'rgba(67,97,238,.12)' : 'var(--bg-muted)' ?>">
      <i class="bi <?= $typeIcon['icon'] ?>" style="font-size:16px;color:<?= $typeIcon['color'] ?>"></i>
    </div>

    <!-- Content -->
    <div class="flex-grow-1 min-w-0">
      <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
        <div>
          <span class="fw-semibold" style="font-size:14px;color:<?= $isUnread ? 'var(--text-main)' : 'var(--text-sub)' ?>">
            <?= $isUnread ? '' : '' ?><?= e($n['title']) ?>
          </span>
          <?php if ($isUnread): ?>
          <span class="ms-2 rounded-pill px-2 py-0"
                style="background:var(--primary);color:#fff;font-size:10px;font-weight:700;vertical-align:middle">
            NEW
          </span>
          <?php endif; ?>
        </div>
        <span class="flex-shrink-0 badge"
              style="background:var(--bg-muted);color:var(--text-muted);font-weight:500">
          <?= e(ucwords(str_replace('_', ' ', $n['type'] ?? ''))) ?>
        </span>
      </div>

      <p class="mb-2 mt-1" style="font-size:13.5px;color:var(--text-sub)"><?= e($n['message']) ?></p>

      <div class="d-flex align-items-center justify-content-between gap-2">
        <small style="color:var(--text-muted)">
          <i class="bi bi-clock me-1"></i><?= format_datetime($n['created_at']) ?>
        </small>
        <?php if ($isUnread): ?>
        <form method="POST" action="<?= route_url('notifications', 'markRead', ['id' => $n['id']]) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-primary" style="font-size:12px;padding:3px 10px;border-radius:20px">
            <i class="bi bi-check2 me-1"></i>Mark as read
          </button>
        </form>
        <?php else: ?>
        <span style="font-size:12.5px;color:var(--success)">
          <i class="bi bi-check2-circle me-1"></i>Read
        </span>
        <?php endif; ?>
      </div>
    </div>

  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>
