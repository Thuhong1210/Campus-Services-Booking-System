<?php
$statusColor = [
    'approved'  => ['bg' => 'linear-gradient(135deg,#f0fdf4,#dcfce7)', 'border' => '#10b981', 'text' => '#065f46', 'icon' => 'bi-check-circle-fill'],
    'pending'   => ['bg' => 'linear-gradient(135deg,#fffbeb,#fef3c7)', 'border' => '#f59e0b', 'text' => '#78350f', 'icon' => 'bi-hourglass-split'],
    'cancelled' => ['bg' => 'linear-gradient(135deg,#f7f8fc,#f1f5f9)', 'border' => '#9499b2', 'text' => '#5f6580', 'icon' => 'bi-x-circle-fill'],
    'completed' => ['bg' => 'linear-gradient(135deg,#eef2ff,#e0e7ff)', 'border' => '#6366f1', 'text' => '#312e81', 'icon' => 'bi-award-fill'],
    'rejected'  => ['bg' => 'linear-gradient(135deg,#fef2f2,#fee2e2)', 'border' => '#ef4444', 'text' => '#7f1d1d', 'icon' => 'bi-x-octagon-fill'],
];
$sc = $statusColor[$booking['status']] ?? $statusColor['pending'];
$isAdminOrStaff = Auth::hasAnyRole(['Admin', 'Staff', 'Lecturer', 'Approver']);
$isOwner  = ((int)$booking['user_id'] === (int)Auth::id());
$canAction = ($isOwner || $isAdminOrStaff);
$isCheckedIn = (int)$booking['checked_in'] === 1;
$start = strtotime($booking['start_datetime']);
$end   = strtotime($booking['end_datetime']);
$now   = time();
$minutesOverdue = $start < $now ? round(($now - $start) / 60) : 0;
?>

<div class="row justify-content-center g-4 my-2">
  <div class="col-md-7 col-lg-5">

    <!-- ─── Main Card ── -->
    <div class="card border-0 shadow rounded-4 overflow-hidden">

      <!-- Banner Header -->
      <div class="px-4 pt-4 pb-3 text-center border-bottom"
           style="background:<?= $sc['bg'] ?>;border-color:<?= $sc['border'] ?>!important">
        <i class="bi <?= $sc['icon'] ?> d-block mb-2" style="font-size:2.5rem;color:<?= $sc['border'] ?>"></i>
        <h5 class="fw-bold mb-1" style="color:<?= $sc['text'] ?>"><?= e(__('QR Code Check-in')) ?></h5>
        <div class="d-flex justify-content-center align-items-center gap-2 mt-1 flex-wrap">
          <code class="px-2 py-1 rounded bg-white border font-monospace fw-bold" style="font-size:12px;color:<?= $sc['text'] ?>">
            <?= e($booking['booking_reference']) ?>
          </code>
          <?= status_badge($booking['status']) ?>
          <?php if ($isCheckedIn): ?>
            <span class="badge bg-info text-white"><i class="bi bi-check2-circle me-1"></i><?= e(__('Checked In')) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="card-body p-4">

        <!-- Resource Info -->
        <div class="rounded-3 p-3 mb-4 border-0" style="background:#f8fafc">
          <div class="fw-bold text-dark-blue fs-6 mb-1">
            <i class="bi bi-building me-2 text-primary"></i><?= e($booking['resource_name']) ?>
            <span class="text-muted fw-normal small">(<?= e($booking['resource_code'] ?? '') ?>)</span>
          </div>
          <div class="text-muted small"><i class="bi bi-geo-alt-fill me-1 text-danger"></i><?= e($booking['location'] ?? '—') ?></div>
        </div>

        <!-- Details Grid -->
        <div class="row g-3 mb-4">
          <div class="col-6">
            <div class="text-muted fw-semibold" style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em"><?= e(__('Date')) ?></div>
            <div class="fw-medium"><i class="bi bi-calendar3 me-1 text-primary small"></i><?= date('d/m/Y', $start) ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted fw-semibold" style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em"><?= e(__('Time')) ?></div>
            <div class="fw-medium"><i class="bi bi-clock me-1 text-success small"></i><?= date('H:i', $start) ?> – <?= date('H:i', $end) ?></div>
          </div>
          <div class="col-12">
            <div class="text-muted fw-semibold" style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em"><?= e(__('Booked by')) ?></div>
            <div class="fw-medium"><i class="bi bi-person-circle me-1 text-secondary small"></i><?= e($booking['user_name'] ?? '—') ?></div>
          </div>
          <div class="col-12">
            <div class="text-muted fw-semibold" style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em"><?= e(__('Purpose')) ?></div>
            <div style="font-size:13.5px;color:var(--text-sub)"><?= e($booking['purpose']) ?></div>
          </div>
          <?php if ($isCheckedIn && !empty($booking['check_in_time'])): ?>
          <div class="col-12">
            <div class="alert alert-success border-0 mb-0 py-2 small d-flex align-items-center gap-2">
              <i class="bi bi-calendar-check-fill"></i>
              <span><strong><?= e(__('Check-in Time')) ?>:</strong> <?= format_datetime($booking['check_in_time']) ?></span>
            </div>
          </div>
          <?php endif; ?>
          <?php if ($minutesOverdue > 0 && !$isCheckedIn && $booking['status'] === 'approved'): ?>
          <div class="col-12">
            <div class="alert border-0 mb-0 py-2 small d-flex align-items-center gap-2
              <?= $minutesOverdue >= 15 ? 'alert-danger' : 'alert-warning' ?>">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <?php if ($minutesOverdue >= 15): ?>
                <span><?= e(__('This booking has passed the 15-minute auto-release window.')) ?></span>
              <?php else: ?>
                <span><?= sprintf(e(__('⏰ %d minutes past start – %d minutes until auto-release!')), $minutesOverdue, 15 - $minutesOverdue) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Action Form -->
        <?php if ($canAction): ?>
          <form method="POST" action="<?= route_url('bookings', 'check-in', ['token' => $booking['qr_token']]) ?>">
            <?= csrf_field() ?>

            <?php if ($booking['status'] === 'approved' && !$isCheckedIn): ?>
              <?php if ($now < $start - 900): // too early ?>
                <div class="alert alert-info text-center small mb-3 border-0 rounded-3">
                  <i class="bi bi-info-circle-fill me-1"></i>
                  <?= e(__('Check-in is only allowed starting 15 minutes before the booking start time.')) ?>
                </div>
                <button type="button" class="btn btn-secondary w-100 py-2 rounded-3 fw-bold" disabled>
                  <i class="bi bi-clock me-2"></i><?= e(__('Check In (Not Yet)')) ?>
                </button>
              <?php elseif ($now > $end): // expired ?>
                <div class="alert alert-danger text-center small mb-3 border-0 rounded-3">
                  <i class="bi bi-x-circle-fill me-1"></i>
                  <?= e(__('This booking period has ended.')) ?>
                </div>
                <button type="button" class="btn btn-secondary w-100 py-2 rounded-3 fw-bold" disabled>
                  <i class="bi bi-x me-2"></i><?= e(__('Check-in Expired')) ?>
                </button>
              <?php else: ?>
                <input type="hidden" name="check_action" value="checkin">
                <button type="submit"
                        class="btn btn-success w-100 py-2 rounded-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 fs-6">
                  <i class="bi bi-shield-check fs-5"></i>
                  <?= e(__('Confirm Check In')) ?>
                </button>
              <?php endif; ?>

            <?php elseif ($isCheckedIn && $booking['status'] === 'approved'): ?>
              <input type="hidden" name="check_action" value="checkout">
              <button type="submit"
                      class="btn btn-primary w-100 py-2 rounded-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 fs-6">
                <i class="bi bi-box-arrow-right fs-5"></i>
                <?= e(__('Confirm Check Out')) ?>
              </button>

            <?php else: ?>
              <div class="alert alert-light text-center small text-muted border mb-0 rounded-3">
                <i class="bi bi-info-circle me-1"></i>
                <?= e(__('No check-in actions available for this booking status.')) ?>
              </div>
            <?php endif; ?>
          </form>
        <?php else: ?>
          <div class="alert alert-danger text-center small mb-0 border-0 rounded-3">
            <i class="bi bi-shield-slash-fill me-1"></i>
            <?= e(__('You do not have permission to check in / check out this booking.')) ?>
          </div>
        <?php endif; ?>

        <div class="mt-4 d-flex gap-2 justify-content-center">
          <a href="<?= route_url('bookings', 'show', ['id' => $booking['id']]) ?>" class="btn btn-light rounded-3 btn-sm px-4">
            <i class="bi bi-eye me-1"></i><?= e(__('Booking Details')) ?>
          </a>
          <a href="<?= route_url('bookings', 'myBookings') ?>" class="btn btn-light rounded-3 btn-sm px-4">
            <i class="bi bi-list-ul me-1"></i><?= e(__('My Bookings')) ?>
          </a>
        </div>

      </div>
    </div>

  </div>
</div>
