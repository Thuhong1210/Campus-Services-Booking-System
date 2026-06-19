<?php
$initials = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
$roleList  = implode(', ', $userRoles ?? []);
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="fw-bold mb-0">User Profile</h1>
    <p class="text-muted mb-0" style="font-size:13.5px">Viewing details for <?= e($user['full_name']) ?>.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= route_url('users', 'edit', ['id' => $user['id']]) ?>" class="btn btn-primary d-flex align-items-center gap-2">
      <i class="bi bi-pencil"></i> Edit User
    </a>
    <a href="<?= route_url('users') ?>" class="btn btn-light d-flex align-items-center gap-2">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>
</div>

<!-- ─── Profile Layout ───────────────────────────────────────── -->
<div class="row g-4">

  <!-- Profile Card -->
  <div class="col-md-4 col-lg-3">
    <div class="card text-center">
      <div class="card-body py-4">
        <div class="avatar-circle lg mx-auto mb-3"><?= $initials ?></div>
        <h5 class="fw-bold mb-1"><?= e($user['full_name']) ?></h5>
        <p class="mb-1" style="font-size:13px;color:var(--text-sub)"><?= e($user['email']) ?></p>
        <p class="mb-3" style="font-size:12.5px;color:var(--text-muted)">@<?= e($user['username'] ?? '') ?></p>
        <div class="mb-3">
          <?php foreach (explode(',', $roleList) as $r): ?>
            <?php if (trim($r)): ?>
            <span class="badge" style="background:var(--primary-soft);color:var(--primary);font-weight:600">
              <?= e(trim($r)) ?>
            </span>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <?= status_badge($user['status'], 'resource') ?>
      </div>
    </div>

    <!-- Meta Info -->
    <div class="card mt-3">
      <div class="card-header">Details</div>
      <div class="card-body" style="font-size:13.5px">
        <?php $meta = [
          ['icon' => 'bi-building',     'label' => 'Department',  'val' => $user['department_name'] ?? '—'],
          ['icon' => 'bi-telephone',    'label' => 'Phone',       'val' => $user['phone'] ?? '—'],
          ['icon' => 'bi-mortarboard',  'label' => 'Student ID',  'val' => $user['student_code'] ?? '—'],
          ['icon' => 'bi-person-badge', 'label' => 'Staff ID',    'val' => $user['staff_code'] ?? '—'],
        ]; ?>
        <?php foreach ($meta as $m): ?>
        <div class="d-flex align-items-start gap-2 mb-2">
          <i class="bi <?= $m['icon'] ?> mt-1" style="color:var(--text-muted);width:16px;flex-shrink:0"></i>
          <div>
            <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em">
              <?= $m['label'] ?>
            </div>
            <div class="fw-medium" style="color:var(--text-sub)"><?= e($m['val']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Bookings History -->
  <div class="col-md-8 col-lg-9">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-calendar-check" style="color:var(--primary)"></i>
        Recent Bookings
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Resource</th>
              <th>Date & Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentBookings ?? [] as $booking): ?>
            <tr>
              <td>
                <code style="font-size:12px;color:var(--primary)">
                  <?= e($booking['booking_reference']) ?>
                </code>
              </td>
              <td class="fw-medium"><?= e($booking['resource_name']) ?></td>
              <td style="color:var(--text-sub);font-size:13px">
                <?= format_datetime($booking['start_datetime']) ?>
              </td>
              <td><?= status_badge($booking['status']) ?></td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($recentBookings)): ?>
            <tr>
              <td colspan="4" class="text-center py-5" style="color:var(--text-muted)">
                <i class="bi bi-calendar-x d-block mb-2" style="font-size:2rem"></i>
                No bookings found for this user.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>