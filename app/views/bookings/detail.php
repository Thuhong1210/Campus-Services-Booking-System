<?php
$statusColor = [
    'approved'  => ['bg' => '#f0fdf4', 'border' => '#10b981', 'text' => '#065f46'],
    'pending'   => ['bg' => '#fffbeb', 'border' => '#f59e0b', 'text' => '#78350f'],
    'rejected'  => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#7f1d1d'],
    'cancelled' => ['bg' => '#f7f8fc', 'border' => '#9499b2', 'text' => '#5f6580'],
    'completed' => ['bg' => '#eef2ff', 'border' => '#6366f1', 'text' => '#312e81'],
];
$sc = $statusColor[$booking['status']] ?? $statusColor['pending'];
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <h1 class="fw-bold mb-0" style="font-size:1.25rem">
        Booking <?= e($booking['booking_reference']) ?>
      </h1>
      <?= status_badge($booking['status']) ?>
    </div>
    <p class="text-muted mb-0" style="font-size:13.5px">Booking details and approval history.</p>
  </div>
  <a href="<?= route_url('bookings', 'myBookings') ?>" class="btn btn-light d-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> Back
  </a>
</div>

<div class="row g-4">

  <!-- ─── Booking Info ─────────────────────────────────────── -->
  <div class="col-lg-7">
    <div class="card mb-4">
      <!-- Status banner -->
      <div class="px-4 py-3 d-flex align-items-center gap-3 rounded-top"
           style="background:<?= $sc['bg'] ?>;border-bottom:2px solid <?= $sc['border'] ?>">
        <i class="bi bi-calendar-check-fill" style="font-size:1.5rem;color:<?= $sc['border'] ?>"></i>
        <div>
          <div class="fw-bold" style="color:<?= $sc['text'] ?>;font-size:15px">
            <?= e($booking['resource_name']) ?>
          </div>
          <div style="font-size:13px;color:<?= $sc['text'] ?>;opacity:.75">
            <?= e($booking['category_name'] ?? '') ?>
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
          ]; ?>
          <?php foreach ($details as $d): ?>
          <div class="col-sm-6">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)">
              <?= $d['label'] ?>
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

    <!-- Approval History -->
    <?php if (!empty($approvals)): ?>
    <div class="card mb-4">
      <div class="card-header">
        <i class="bi bi-clock-history me-2" style="color:var(--info)"></i>
        Approval History
      </div>
      <div class="card-body p-0">
        <?php foreach ($approvals as $i => $a): ?>
        <div class="px-4 py-3 <?= $i > 0 ? 'border-top' : '' ?>">
          <div class="d-flex align-items-center gap-2 mb-1">
            <?php if ($a['decision'] === 'approved'): ?>
              <i class="bi bi-check-circle-fill" style="color:var(--success)"></i>
              <span class="fw-semibold" style="color:var(--success)">Approved</span>
            <?php else: ?>
              <i class="bi bi-x-circle-fill" style="color:var(--danger)"></i>
              <span class="fw-semibold" style="color:var(--danger)">Rejected</span>
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
    <div class="card mt-4 border-secondary">
      <div class="card-header fw-semibold">Cancellation Record</div>
      <div class="card-body">
        <p class="mb-1"><strong>Reason:</strong> <?= e($cancellation['reason']) ?></p>
        <p class="mb-0 small text-muted">Cancelled at <?= format_datetime($cancellation['cancelled_at']) ?></p>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ─── Actions Sidebar ──────────────────────────────────── -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Actions</div>
      <div class="card-body d-flex flex-column gap-2">
        <?php if (in_array($booking['status'], ['pending', 'approved']) && strtotime($booking['start_datetime']) >= time()): ?>
        <a href="<?= route_url('bookings', 'edit', ['id' => $booking['id']]) ?>"
           class="btn btn-light d-flex align-items-center gap-2">
          <i class="bi bi-pencil"></i> Edit Booking
        </a>
        <?php endif; ?>

        <?php if (in_array($booking['status'], ['pending', 'approved'])): ?>
        <button class="btn btn-light text-danger d-flex align-items-center gap-2"
                data-bs-toggle="modal" data-bs-target="#cancelModal">
          <i class="bi bi-x-circle"></i> Cancel Booking
        </button>
        <?php endif; ?>

        <a href="<?= route_url('bookings', 'calendar') ?>"
           class="btn btn-light d-flex align-items-center gap-2">
          <i class="bi bi-calendar3"></i> View Calendar
        </a>
      </div>
    </div>
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
          <i class="bi bi-x-circle-fill text-danger"></i> Cancel Booking
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
        <textarea name="reason" class="form-control" rows="3"
                  placeholder="Please provide a reason..." required></textarea>
        <div class="form-text mt-1">
          <i class="bi bi-info-circle me-1"></i>This action cannot be undone.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep Booking</button>
        <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
