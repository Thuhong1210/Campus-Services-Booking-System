<header class="topbar bg-white border-bottom px-3 px-lg-4 py-2 d-flex align-items-center justify-content-between">
  <div class="d-flex align-items-center gap-2 flex-grow-1">
    <button class="btn btn-link d-lg-none p-0 text-dark" id="sidebarToggle" aria-label="Toggle menu">
      <i class="bi bi-list fs-4"></i>
    </button>
    <?php if (!empty($breadcrumbs)): ?>
      <nav aria-label="breadcrumb" class="d-none d-md-block">
        <ol class="breadcrumb mb-0 small">
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
      <h2 class="h6 mb-0 fw-semibold text-truncate d-none d-md-block"><?= e($title ?? '') ?></h2>
    <?php endif; ?>
  </div>
  <div class="d-flex align-items-center gap-3">
    <a href="<?= route_url('notifications') ?>" class="position-relative text-muted text-decoration-none" title="Notifications">
      <i class="bi bi-bell fs-5"></i>
      <?php if ($unreadNotifications > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge"><?= $unreadNotifications ?></span>
      <?php endif; ?>
    </a>
    <div class="text-end d-none d-sm-block">
      <div class="fw-medium small lh-1"><?= e($user['full_name'] ?? '') ?></div>
      <div class="text-muted" style="font-size:11px"><?= e($role) ?></div>
    </div>
    <div class="avatar-circle" title="<?= e($user['full_name'] ?? '') ?>"><?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?></div>
  </div>
</header>
