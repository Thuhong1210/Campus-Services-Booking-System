<?php
$statusColor = [
    'approved'  => ['bg' => '#f0fdf4', 'border' => '#10b981', 'text' => '#065f46'],
    'pending'   => ['bg' => '#fffbeb', 'border' => '#f59e0b', 'text' => '#78350f'],
    'rejected'  => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#7f1d1d'],
    'cancelled' => ['bg' => '#f7f8fc', 'border' => '#9499b2', 'text' => '#5f6580'],
    'completed' => ['bg' => '#eef2ff', 'border' => '#6366f1', 'text' => '#312e81'],
];
$sc = $statusColor[$booking['status']] ?? $statusColor['pending'];
$isCheckedIn = (int)($booking['checked_in'] ?? 0) === 1;
$isNoShow    = (int)($booking['is_no_show']  ?? 0) === 1;
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <h1 class="fw-bold mb-0" style="font-size:1.25rem">
        <?= e(__('Booking')) ?> <?= e($booking['booking_reference']) ?>
      </h1>
      <?= status_badge($booking['status']) ?>
      <?php if ($isCheckedIn): ?>
        <span class="badge bg-info text-white"><i class="bi bi-check-circle me-1"></i><?= e(__('Checked In')) ?></span>
      <?php elseif ($isNoShow): ?>
        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= e(__('No Show')) ?></span>
      <?php endif; ?>
    </div>
    <p class="text-muted mb-0" style="font-size:13.5px"><?= e(__('Booking details and approval history.')) ?></p>
  </div>
  <a href="<?= route_url('bookings', 'myBookings') ?>" class="btn btn-light d-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> <?= e(__('Back')) ?>
  </a>
</div>

<div class="row g-4">

  <!-- ─── Booking Info ─────────────────────────────────────── -->
  <div class="col-lg-7">
    <div class="card mb-4 border-0 shadow-sm rounded-3 overflow-hidden">
      <!-- Status banner -->
      <div class="px-4 py-3 d-flex align-items-center gap-3 rounded-top"
           style="background:<?= $sc['bg'] ?>;border-bottom:2px solid <?= $sc['border'] ?>">
        <i class="bi bi-calendar-check-fill" style="font-size:1.5rem;color:<?= $sc['border'] ?>"></i>
        <div>
          <div class="fw-bold" style="color:<?= $sc['text'] ?>;font-size:15px">
            <?= e($booking['resource_name']) ?>
          </div>
          <div style="font-size:13px;color:<?= $sc['text'] ?>;opacity:.75">
            <?= e($booking['category_name'] ?? '') ?> · <?= e($booking['location'] ?? '') ?>
          </div>
        </div>
      </div>

      <div class="card-body">
        <div class="row g-3">
          <?php $details = [
            ['icon' => 'bi-calendar3',       'label' => 'Start',    'val' => format_datetime($booking['start_datetime'])],
            ['icon' => 'bi-calendar3',       'label' => 'End',      'val' => format_datetime($booking['end_datetime'])],
            ['icon' => 'bi-pencil-square',   'label' => 'Purpose',  'val' => $booking['purpose']],
            ['icon' => 'bi-person-fill',     'label' => 'Booked by','val' => $booking['user_name'] ?? '—'],
            ['icon' => 'bi-sticky',          'label' => 'Notes',    'val' => $booking['additional_notes'] ?? '—'],
            ['icon' => 'bi-qr-code-scan',    'label' => 'Check-in', 'val' => (
                $isCheckedIn
                  ? __('✓ Checked In') . ' — ' . format_datetime($booking['check_in_time'])
                  : ($isNoShow
                      ? __('⚠ No Show – Auto Released')
                      : __('Not Checked In'))
            )],
          ]; ?>
          <?php foreach ($details as $d): ?>
          <div class="col-sm-6">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)">
              <?= e(__($d['label'])) ?>
            </div>
            <div class="d-flex align-items-start gap-2 mt-1">
              <i class="bi <?= $d['icon'] ?> flex-shrink-0 mt-1" style="font-size:14px;color:var(--text-muted)"></i>
              <span style="font-size:14px;font-weight:500;color:var(--text-sub)"><?= e($d['val']) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Equipment Addons -->
    <?php if (!empty($equipments)): ?>
    <div class="card mb-4 border-0 shadow-sm rounded-3">
      <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-tools text-primary" style="font-size:1.1rem"></i>
        <span class="fw-semibold"><?= e(__('Booked Equipment Addons')) ?></span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size:14px">
            <thead class="table-light">
              <tr>
                <th class="ps-4 border-0" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em"><?= e(__('Equipment Name')) ?></th>
                <th class="border-0 text-center" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;width:120px"><?= e(__('Quantity')) ?></th>
                <th class="pe-4 border-0 text-end" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;width:120px"><?= e(__('Status')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($equipments as $eq): ?>
              <tr>
                <td class="ps-4 border-0 py-3">
                  <div class="fw-semibold" style="color:var(--text-sub)"><?= e($eq['equipment_name']) ?></div>
                  <?php if (!empty($eq['description'])): ?>
                  <div class="text-muted small mt-0.5"><?= e($eq['description']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="border-0 text-center py-3 fw-bold" style="color:var(--text-sub)">
                  <?= e($eq['quantity']) ?>
                </td>
                <td class="pe-4 border-0 text-end py-3">
                  <?php if ($eq['status'] === 'available'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1" style="font-size:12px"><?= e(__('Available')) ?></span>
                  <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1" style="font-size:12px"><?= e($eq['status']) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Approval History -->
    <?php if (!empty($approvals)): ?>
    <div class="card mb-4 border-0 shadow-sm rounded-3">
      <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-clock-history" style="color:var(--info)"></i>
        <?= e(__('Approval History')) ?>
      </div>
      <div class="card-body p-0">
        <?php foreach ($approvals as $i => $a): ?>
        <div class="px-4 py-3 <?= $i > 0 ? 'border-top' : '' ?>">
          <div class="d-flex align-items-center gap-2 mb-1">
            <?php if ($a['decision'] === 'approved'): ?>
              <i class="bi bi-check-circle-fill" style="color:var(--success)"></i>
              <span class="fw-semibold" style="color:var(--success)"><?= e(__('Approved')) ?></span>
            <?php else: ?>
              <i class="bi bi-x-circle-fill" style="color:var(--danger)"></i>
              <span class="fw-semibold" style="color:var(--danger)"><?= e(__('Rejected')) ?></span>
            <?php endif; ?>
            <span class="text-muted" style="font-size:12.5px">by <?= e($a['approver_name']) ?></span>
          </div>
          <?php if (!empty($a['comment'])): ?>
          <p class="mb-0" style="font-size:13.5px;color:var(--text-sub)"><?= e($a['comment']) ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Cancellation Record -->
    <?php if (!empty($cancellation)): ?>
    <div class="card border-0 shadow-sm rounded-3 border-danger">
      <div class="card-header fw-semibold text-danger bg-danger-subtle border-0">
        <i class="bi bi-x-circle me-2"></i><?= e(__('Cancellation Record')) ?>
      </div>
      <div class="card-body">
        <p class="mb-1"><strong><?= e(__('Reason')) ?>:</strong> <?= e($cancellation['reason']) ?></p>
        <p class="mb-0 small text-muted"><?= e(__('Cancelled at')) ?> <?= format_datetime($cancellation['cancelled_at']) ?></p>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ─── Actions Sidebar ──────────────────────────────────── -->
  <div class="col-lg-5">

    <!-- Action Buttons -->
    <div class="card mb-3 border-0 shadow-sm rounded-3">
      <div class="card-header bg-white border-bottom fw-semibold"><?= e(__('Actions')) ?></div>
      <div class="card-body d-flex flex-column gap-2">
        <?php if (in_array($booking['status'], ['pending', 'approved']) && strtotime($booking['start_datetime']) >= time()): ?>
        <a href="<?= route_url('bookings', 'edit', ['id' => $booking['id']]) ?>"
           class="btn btn-light d-flex align-items-center gap-2">
          <i class="bi bi-pencil"></i> <?= e(__('Edit Booking')) ?>
        </a>
        <?php endif; ?>

        <?php if (in_array($booking['status'], ['pending', 'approved'])): ?>
        <button class="btn btn-light text-danger d-flex align-items-center gap-2"
                data-bs-toggle="modal" data-bs-target="#cancelModal">
          <i class="bi bi-x-circle"></i> <?= e(__('Cancel Booking')) ?>
        </button>
        <?php endif; ?>

        <!-- ICS Export Button (prominent) -->
        <a href="<?= route_url('bookings', 'export-ics', ['id' => $booking['id']]) ?>"
           class="btn btn-outline-primary d-flex align-items-center gap-2">
          <i class="bi bi-calendar-event-fill"></i> <?= e(__('Add to Calendar (.ICS)')) ?>
          <span class="badge bg-primary-subtle text-primary ms-auto small">Google · Outlook · Apple</span>
        </a>

        <a href="<?= route_url('bookings', 'calendar') ?>"
           class="btn btn-light d-flex align-items-center gap-2">
          <i class="bi bi-calendar3"></i> <?= e(__('View Calendar')) ?>
        </a>
      </div>
    </div>

    <!-- QR Code Check-in Card -->
    <?php if ($booking['status'] === 'approved' && !empty($booking['qr_token'])): ?>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
      <div class="card-header d-flex align-items-center gap-2 fw-bold"
           style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:#065f46;border-bottom:2px solid #10b981">
        <i class="bi bi-qr-code fs-5"></i>
        <?= e(__('QR Code Check-in')) ?>
        <?php if ($isCheckedIn): ?>
          <span class="badge bg-success ms-auto"><?= e(__('Checked In')) ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body text-center p-4">
        <?php
        $checkInUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . route_url('bookings', 'check-in', ['token' => $booking['qr_token']]);
        $qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=10&data=' . urlencode($checkInUrl);
        ?>
        <div class="d-inline-block p-3 bg-white rounded-3 shadow-sm border mb-3">
          <img src="<?= $qrSrc ?>" alt="Check-in QR Code" class="d-block" style="width:160px;height:160px">
        </div>
        <div class="small text-muted font-monospace mb-3"><?= e($booking['booking_reference']) ?></div>

        <?php if ($isCheckedIn): ?>
          <div class="alert alert-success border-0 mb-3 py-2 small">
            <i class="bi bi-check-circle-fill me-1"></i>
            <?= e(__('Checked in at')) ?>: <?= format_datetime($booking['check_in_time']) ?>
          </div>
        <?php endif; ?>

        <a href="<?= route_url('bookings', 'check-in', ['token' => $booking['qr_token']]) ?>"
           class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2 py-2">
          <i class="bi bi-qr-code-scan fs-5"></i>
          <?= e($isCheckedIn ? __('View Check-in Page') : __('Go to Check-in Page')) ?>
        </a>

        <p class="text-muted small mt-3 mb-0">
          <i class="bi bi-info-circle me-1"></i>
          <?= e(__('Scan this QR code or click the button above to check in. Auto-release applies after 15 minutes if not checked in.')) ?>
        </p>
      </div>
    </div>
    <?php elseif ($booking['status'] === 'pending'): ?>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
      <div class="card-header fw-bold bg-warning-subtle text-warning border-0">
        <i class="bi bi-hourglass-split me-2"></i><?= e(__('Awaiting Approval')) ?>
      </div>
      <div class="card-body text-center py-4">
        <i class="bi bi-hourglass-split d-block mb-2 fs-1 text-warning"></i>
        <p class="text-muted mb-0 small"><?= e(__('Your booking is waiting for approval. QR code will be available once approved.')) ?></p>
      </div>
    </div>
    <?php elseif ($isNoShow): ?>
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
      <div class="card-header fw-bold bg-danger-subtle text-danger border-0">
        <i class="bi bi-exclamation-triangle me-2"></i><?= e(__('No-Show – Auto Released')) ?>
      </div>
      <div class="card-body text-center py-4">
        <i class="bi bi-person-dash d-block mb-2 fs-1 text-danger"></i>
        <p class="text-muted mb-0 small"><?= e(__('This booking was auto-cancelled after 15 minutes of no check-in.')) ?></p>
      </div>
    </div>
    <?php endif; ?>

  </div>

</div>

<!-- ─── Cancel Modal ──────────────────────────────────────────── -->
<?php if (in_array($booking['status'], ['pending', 'approved'])): ?>
<div class="modal fade" id="cancelModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="<?= route_url('bookings', 'cancel', ['id' => $booking['id']]) ?>" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <i class="bi bi-x-circle-fill text-danger"></i> <?= e(__('Cancel Booking')) ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label"><?= e(__('Cancellation Reason')) ?> <span class="text-danger">*</span></label>
        <textarea name="reason" class="form-control" rows="3"
                  placeholder="<?= e(__('Please provide a reason...')) ?>" required></textarea>
        <div class="form-text mt-1">
          <i class="bi bi-info-circle me-1"></i><?= e(__('This action cannot be undone.')) ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e(__('Keep Booking')) ?></button>
        <button type="submit" class="btn btn-danger"><?= e(__('Confirm Cancellation')) ?></button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
