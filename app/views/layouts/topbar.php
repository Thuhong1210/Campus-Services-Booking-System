<?php
$user = Auth::user();
$role = Auth::primaryRole();
$unreadNotifications = 0;
if ($user) {
    $unreadNotifications = (new NotificationRepository())->countUnread((int) $user['id']);
}
?>
<header class="topbar">
  <button class="btn btn-sm btn-light d-lg-none border-0 p-1 me-2" id="sidebarToggle" aria-label="Toggle menu">
    <i class="bi bi-list fs-5"></i>
  </button>

  <div class="flex-grow-1">
    <?php if (!empty($breadcrumbs)): ?>
      <nav aria-label="breadcrumb" class="d-none d-md-block">
        <ol class="breadcrumb mb-0">
          <?php foreach ($breadcrumbs as $i => $crumb): ?>
            <li class="breadcrumb-item <?= $i === count($breadcrumbs) - 1 ? 'active' : '' ?>">
              <?php if (!empty($crumb['url']) && $i < count($breadcrumbs) - 1): ?>
                <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
              <?php else: ?>
                <?= e($crumb['label']) ?>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </nav>
    <?php else: ?>
      <span class="topbar-title d-none d-md-inline"><?= e($title ?? '') ?></span>
    <?php endif; ?>
  </div>

  <div class="d-flex align-items-center gap-3">
    <a href="<?= route_url('notifications') ?>" class="position-relative text-decoration-none"
       style="color:var(--text-muted)" title="Notifications">
      <i class="bi bi-bell" style="font-size:18px"></i>
      <?php if ($unreadNotifications > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
          <?= $unreadNotifications ?>
        </span>
      <?php endif; ?>
    </a>

    <div class="d-none d-sm-flex flex-column text-end" style="line-height:1.2">
      <span class="fw-semibold" style="font-size:13px"><?= e($user['full_name'] ?? '') ?></span>
      <span style="font-size:11px;color:var(--text-muted)"><?= e($role) ?></span>
    </div>

    <a href="<?= route_url('profile') ?>" class="avatar-circle text-decoration-none" title="Profile">
      <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
    </a>
  </div>
</header>
