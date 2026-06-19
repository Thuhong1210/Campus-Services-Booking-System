<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="fw-bold mb-0">My Bookings</h1>
    <p class="text-muted mb-0" style="font-size:13.5px">All your booking requests and their current status.</p>
  </div>
  <a href="<?= route_url('bookings', 'create') ?>" class="btn btn-primary d-flex align-items-center gap-2">
    <i class="bi bi-calendar-plus-fill"></i> New Booking
  </a>
</div>

<!-- ─── Bookings Table ────────────────────────────────────────── -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Resource</th>
          <th>Date</th>
          <th>Time</th>
          <th>Status</th>
          <th style="width:130px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
        <tr>
          <td>
            <code style="font-size:12px;color:var(--primary)">
              <?= e($b['booking_reference']) ?>
            </code>
          </td>
          <td class="fw-medium"><?= e($b['resource_name']) ?></td>
          <td style="color:var(--text-sub);white-space:nowrap">
            <i class="bi bi-calendar3 me-1"></i>
            <?= date('d/m/Y', strtotime($b['start_datetime'])) ?>
          </td>
          <td style="color:var(--text-sub);white-space:nowrap">
            <i class="bi bi-clock me-1"></i>
            <?= date('H:i', strtotime($b['start_datetime'])) ?> – <?= date('H:i', strtotime($b['end_datetime'])) ?>
          </td>
          <td><?= status_badge($b['status']) ?></td>
          <td>
            <div class="d-flex gap-1">
              <!-- View -->
              <a href="<?= route_url('bookings', 'show', ['id' => $b['id']]) ?>"
                 class="btn btn-sm btn-light" title="View Details">
                <i class="bi bi-eye"></i>
              </a>

              <!-- Edit (only future + pending/approved) -->
              <?php if (in_array($b['status'], ['pending', 'approved']) && strtotime($b['start_datetime']) >= time()): ?>
              <a href="<?= route_url('bookings', 'edit', ['id' => $b['id']]) ?>"
                 class="btn btn-sm btn-light" title="Edit Booking">
                <i class="bi bi-pencil"></i>
              </a>
              <?php endif; ?>

              <!-- Cancel -->
              <?php if (in_array($b['status'], ['pending', 'approved'])): ?>
              <button type="button"
                      class="btn btn-sm btn-light text-danger"
                      data-bs-toggle="modal"
                      data-bs-target="#cancelModal<?= $b['id'] ?>"
                      title="Cancel Booking">
                <i class="bi bi-x-lg"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>

        <?php if (empty($bookings)): ?>
        <tr>
          <td colspan="6" class="text-center py-5" style="color:var(--text-muted)">
            <i class="bi bi-calendar-x d-block mb-2" style="font-size:2.5rem"></i>
            You have no bookings yet.
            <div class="mt-2">
              <a href="<?= route_url('bookings', 'create') ?>" class="btn btn-sm btn-primary">
                Create your first booking
              </a>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ─── Cancel Modals ─────────────────────────────────────────── -->
<?php foreach ($bookings as $b): ?>
<?php if (in_array($b['status'], ['pending', 'approved'])): ?>
<div class="modal fade" id="cancelModal<?= $b['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="<?= route_url('bookings', 'cancel', ['id' => $b['id']]) ?>" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <i class="bi bi-x-circle-fill text-danger"></i>
          Cancel Booking
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="rounded-3 p-3 mb-3" style="background:#fef2f2;border-left:3px solid #ef4444">
          <div class="fw-semibold" style="font-size:13.5px"><?= e($b['resource_name']) ?></div>
          <div style="font-size:12.5px;color:var(--text-sub)">
            <?= date('d/m/Y H:i', strtotime($b['start_datetime'])) ?> –
            <?= date('H:i', strtotime($b['end_datetime'])) ?>
          </div>
        </div>
        <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
        <textarea name="reason" class="form-control" rows="3"
                  placeholder="Please provide a reason for cancellation..." required></textarea>
        <div class="form-text mt-1">
          <i class="bi bi-info-circle me-1"></i>
          This action is irreversible. Your reason will be recorded.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep Booking</button>
        <button type="submit" class="btn btn-danger d-flex align-items-center gap-2">
          <i class="bi bi-x-circle-fill"></i> Confirm Cancellation
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php endforeach; ?>