<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Booking <?= e($booking['booking_reference']) ?></h1>
    <?= status_badge($booking['status']) ?>
  </div>
  <div class="d-flex gap-2">
    <?php if (!empty($canEdit)): ?>
      <a href="<?= route_url('bookings', 'edit', ['id' => $booking['id']]) ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
    <?php endif; ?>
    <a href="<?= route_url('bookings', 'myBookings') ?>" class="btn btn-outline-primary btn-sm">Back</a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card p-4">
      <div class="row g-3">
        <div class="col-md-6"><p class="mb-2"><strong>Resource:</strong> <?= e($booking['resource_name']) ?></p><p class="mb-2"><strong>Category:</strong> <?= e($booking['category_name'] ?? '') ?></p><p class="mb-2"><strong>Location:</strong> <?= e($booking['location'] ?? '-') ?></p></div>
        <div class="col-md-6"><p class="mb-2"><strong>Start:</strong> <?= format_datetime($booking['start_datetime']) ?></p><p class="mb-2"><strong>End:</strong> <?= format_datetime($booking['end_datetime']) ?></p><p class="mb-2"><strong>Booked by:</strong> <?= e($booking['user_name'] ?? '') ?></p></div>
        <div class="col-12"><p class="mb-2"><strong>Purpose:</strong> <?= e($booking['purpose']) ?></p><p class="mb-0"><strong>Notes:</strong> <?= e($booking['additional_notes'] ?? '-') ?></p></div>
      </div>
    </div>

    <?php if (!empty($approvals)): ?>
    <div class="card mt-4">
      <div class="card-header fw-semibold">Approval History</div>
      <div class="card-body">
        <?php foreach ($approvals as $a): ?>
          <div class="border rounded p-3 mb-2">
            <div class="d-flex justify-content-between"><strong><?= ucfirst(e($a['decision'])) ?></strong><span class="text-muted small"><?= format_datetime($a['decided_at'] ?? $a['created_at']) ?></span></div>
            <p class="mb-1 small">Approver: <?= e($a['approver_name'] ?? 'System') ?></p>
            <?php if (!empty($a['comment'])): ?><p class="mb-0 text-muted"><?= e($a['comment']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($cancellation)): ?>
    <div class="card mt-4 border-secondary">
      <div class="card-header">Cancellation Record</div>
      <div class="card-body"><p class="mb-1"><strong>Reason:</strong> <?= e($cancellation['reason']) ?></p><p class="mb-0 small text-muted">Cancelled at <?= format_datetime($cancellation['cancelled_at']) ?></p></div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <?php if (!empty($canCancel)): ?>
    <div class="card p-4">
      <h6 class="fw-semibold">Cancel Booking</h6>
      <p class="small text-muted">A cancellation reason is required and will be recorded.</p>
      <form method="POST" action="<?= route_url('bookings', 'cancel', ['id' => $booking['id']]) ?>">
        <?= csrf_field() ?>
        <textarea name="reason" class="form-control mb-3" rows="3" placeholder="Cancellation reason..." required></textarea>
        <button class="btn btn-danger w-100">Confirm Cancel</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>
