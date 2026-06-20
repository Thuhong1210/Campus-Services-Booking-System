<?php
$role = Auth::primaryRole();
$navItems = match ($role) {
    'Admin' => [
        ['group' => 'Overview'],
        ['page' => 'dashboard', 'action' => 'index', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
        ['page' => 'users', 'action' => 'index', 'label' => 'User Management', 'icon' => 'bi-people'],

        ['group' => 'Resources'],
        ['page' => 'resource-categories', 'action' => 'index', 'label' => 'Resource Categories', 'icon' => 'bi-tags'],
        ['page' => 'resources', 'action' => 'index', 'label' => 'Resources', 'icon' => 'bi-box'],
        ['page' => 'equipment', 'action' => 'index', 'label' => 'Equipment', 'icon' => 'bi-tools'],
        ['page' => 'time-slots', 'action' => 'index', 'label' => 'Time Slots', 'icon' => 'bi-clock'],
        ['page' => 'booking-policies', 'action' => 'index', 'label' => 'Booking Policies', 'icon' => 'bi-shield-check'],
        ['page' => 'maintenance', 'action' => 'index', 'label' => 'Maintenance', 'icon' => 'bi-wrench'],

        ['group' => 'Bookings'],
        ['page' => 'bookings', 'action' => 'index', 'label' => 'Booking Management', 'icon' => 'bi-calendar-check'],
        ['page' => 'approvals', 'action' => 'index', 'label' => 'Approval Requests', 'icon' => 'bi-check2-square'],
        ['page' => 'cancellations', 'action' => 'index', 'label' => 'Cancellations', 'icon' => 'bi-x-circle'],

        ['group' => 'Reports & System'],
        ['page' => 'reports', 'action' => 'index', 'label' => 'Usage Reports', 'icon' => 'bi-bar-chart'],
        ['page' => 'notifications', 'action' => 'index', 'label' => 'Notifications', 'icon' => 'bi-bell'],
        ['page' => 'audit-logs', 'action' => 'index', 'label' => 'Audit Logs', 'icon' => 'bi-journal-text'],
        ['page' => 'settings', 'action' => 'index', 'label' => 'Settings', 'icon' => 'bi-gear'],
    ],
    'Lecturer', 'Approver' => [
        ['page' => 'dashboard', 'action' => 'index', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
        ['page' => 'approvals', 'action' => 'index', 'label' => 'Pending Approvals', 'icon' => 'bi-check2-square'],
        ['page' => 'approvals', 'action' => 'history', 'label' => 'Approval History', 'icon' => 'bi-clock-history'],
        ['page' => 'resources', 'action' => 'browse', 'label' => 'Browse Resources', 'icon' => 'bi-search'],
        ['page' => 'bookings', 'action' => 'calendar', 'label' => 'Resource Calendar', 'icon' => 'bi-calendar3'],
        ['page' => 'bookings', 'action' => 'create', 'label' => 'Supervised Booking', 'icon' => 'bi-plus-circle'],
        ['page' => 'bookings', 'action' => 'myBookings', 'label' => 'My Bookings', 'icon' => 'bi-list-check'],
        ['page' => 'notifications', 'action' => 'index', 'label' => 'Notifications', 'icon' => 'bi-bell'],
        ['page' => 'profile', 'action' => 'index', 'label' => 'Profile', 'icon' => 'bi-person'],
    ],
    'Staff' => [
        ['page' => 'dashboard', 'action' => 'index', 'label' => 'Staff Dashboard', 'icon' => 'bi-speedometer2'],
        ['page' => 'resources', 'action' => 'browse', 'label' => 'Browse Resources', 'icon' => 'bi-search'],
        ['page' => 'bookings', 'action' => 'calendar', 'label' => 'Resource Calendar', 'icon' => 'bi-calendar3'],
        ['page' => 'bookings', 'action' => 'index', 'label' => 'All Bookings', 'icon' => 'bi-list-check'],
        ['page' => 'notifications', 'action' => 'index', 'label' => 'Notifications', 'icon' => 'bi-bell'],
        ['page' => 'profile', 'action' => 'index', 'label' => 'Profile', 'icon' => 'bi-person'],
    ],
    default => [
        ['page' => 'dashboard', 'action' => 'index', 'label' => 'Student Dashboard', 'icon' => 'bi-speedometer2'],
        ['page' => 'resources', 'action' => 'browse', 'label' => 'Browse Resources', 'icon' => 'bi-search'],
        ['page' => 'bookings', 'action' => 'calendar', 'label' => 'Resource Calendar', 'icon' => 'bi-calendar3'],
        ['page' => 'bookings', 'action' => 'create', 'label' => 'Create Booking', 'icon' => 'bi-plus-circle'],
        ['page' => 'bookings', 'action' => 'myBookings', 'label' => 'My Bookings', 'icon' => 'bi-list-check'],
        ['page' => 'bookings', 'action' => 'mySchedule', 'label' => 'My Schedule', 'icon' => 'bi-calendar-week'],
        ['page' => 'notifications', 'action' => 'index', 'label' => 'Notifications', 'icon' => 'bi-bell'],
        ['page' => 'profile', 'action' => 'index', 'label' => 'Profile', 'icon' => 'bi-person'],
    ],
};
$user = Auth::user();
$unreadNotifications = 0;
if ($user) {
    $unreadNotifications = (new NotificationRepository())->countUnread((int) $user['id']);
}

// Xác định group nào đang chứa trang active để mở sẵn
$currentPage = $_GET['page'] ?? 'dashboard';
$currentAction = $_GET['action'] ?? 'index';
$activeGroup = null;
$currentGroup = null;
foreach ($navItems as $item) {
    if (isset($item['group'])) {
        $currentGroup = $item['group'];
    } elseif (isset($item['page'])) {
        if ($item['page'] === $currentPage) {
            $activeGroup = $currentGroup;
        }
    }
}
?>
<nav id="sidebar" class="sidebar">
  <div class="sidebar-header">
    <div class="d-flex align-items-center gap-2">
      <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
      <div style="overflow:hidden">
        <div class="brand-text lh-1" style="font-size:13px;font-weight:600;white-space:nowrap;text-overflow:ellipsis;overflow:hidden" title="<?= e(setting('system_name', 'Campus Services')) ?>">
          <?= e(setting('system_name', 'Campus Services')) ?>
        </div>
        <div style="font-size:10px;color:var(--text-muted)">IS-VNU Booking</div>
      </div>
    </div>
  </div>
  <ul class="nav flex-column sidebar-nav" id="sidebarNav">
    <?php
    $groupId = 0;
    $inGroup = false;
    foreach ($navItems as $item):
        if (isset($item['group'])):
            if ($inGroup) echo '</ul></li>';
            $groupId++;
            $gid = 'sidebarGroup' . $groupId;
            $isOpen = 'true';
            $collapseClass = 'show';
            $inGroup = true;
    ?>
        <li class="nav-item sidebar-group-item">
          <a class="sidebar-group-toggle text-decoration-none"
             href="#<?= $gid ?>" data-bs-toggle="collapse" aria-expanded="<?= $isOpen ?>">
            <span class="sidebar-group-label-text"><?= e(__($item['group'])) ?></span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
          </a>
          <ul class="nav flex-column collapse <?= $collapseClass ?>" id="<?= $gid ?>">
    <?php
        else:
            $active = is_active_nav($item['page'], $item['action']);
    ?>
        <li class="nav-item">
          <a class="nav-link sidebar-link <?= $active ? 'active' : '' ?>" href="<?= route_url($item['page'], $item['action']) ?>">
            <i class="bi <?= e($item['icon']) ?>"></i><?= e(__($item['label'])) ?>
            <?php if ($item['page'] === 'notifications' && $unreadNotifications > 0): ?>
              <span class="badge bg-danger ms-auto"><?= $unreadNotifications ?></span>
            <?php endif; ?>
          </a>
        </li>
    <?php
        endif;
    endforeach;
    if ($inGroup) echo '</ul></li>';
    ?>
  </ul>
  <div class="sidebar-footer">
    <a class="nav-link sidebar-link" href="<?= url('logout.php') ?>">
      <i class="bi bi-box-arrow-right"></i><?= e(__('Logout')) ?>
    </a>
  </div>
</nav>


