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

<!-- ─── Filter Form ────────────────────────────────────────── -->
<div class="card mb-4 border-0 shadow-sm" style="border-radius:12px">
  <div class="card-body p-3">
    <form method="GET" action="index.php" class="row g-3 align-items-end">
      <input type="hidden" name="page" value="bookings">
      <input type="hidden" name="action" value="myBookings">

      <!-- Search Input -->
      <div class="col-12 col-md-3">
        <label class="form-label small fw-semibold text-muted">Search Keyword</label>
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0 text-muted">
            <i class="bi bi-search"></i>
          </span>
          <input type="text" name="search" class="form-control border-start-0"
                 placeholder="Ref, purpose, resource..." 
                 value="<?= e($filters['search'] ?? '') ?>">
        </div>
      </div>

      <!-- Status Filter -->
      <div class="col-6 col-md-2">
        <label class="form-label small fw-semibold text-muted">Status</label>
        <select name="status" class="form-select">
          <option value="">All Statuses</option>
          <?php foreach (['pending', 'approved', 'rejected', 'cancelled', 'completed', 'expired'] as $s): ?>
            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
              <?= ucfirst($s) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Category Filter -->
      <div class="col-6 col-md-2">
        <label class="form-label small fw-semibold text-muted">Category</label>
        <select name="category_id" class="form-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= (string)($filters['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['category_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Date From Filter -->
      <div class="col-6 col-md-2">
        <label class="form-label small fw-semibold text-muted">From Date</label>
        <input type="date" name="date_from" class="form-control" 
               value="<?= e($filters['date_from'] ?? '') ?>">
      </div>

      <!-- Date To Filter -->
      <div class="col-6 col-md-2">
        <label class="form-label small fw-semibold text-muted">To Date</label>
        <input type="date" name="date_to" class="form-control" 
               value="<?= e($filters['date_to'] ?? '') ?>">
      </div>

      <!-- Action Buttons -->
      <div class="col-12 col-md-1 d-flex gap-2">
        <button type="submit" class="btn btn-primary w-100" title="Apply filters">
          <i class="bi bi-funnel-fill"></i>
        </button>
        <a href="index.php?page=bookings&action=myBookings" class="btn btn-outline-secondary w-100" title="Reset filters">
          <i class="bi bi-arrow-counterclockwise"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- ─── Bookings Table ────────────────────────────────────────── -->
<div class="card mb-3">
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

              <!-- Rate (completed bookings without feedback) -->
              <?php if ($b['status'] === 'completed'): ?>
              <a href="<?= route_url('feedback', 'create', ['booking_id' => $b['id']]) ?>"
                 class="btn btn-sm btn-light text-warning" title="Rate this booking">
                <i class="bi bi-star-fill"></i>
              </a>
              <?php endif; ?>

              <!-- Cancel recurring series -->
              <?php if (!empty($b['recurring_group_id']) && in_array($b['status'], ['pending','approved'])): ?>
              <button type="button" class="btn btn-sm btn-light text-danger"
                      data-bs-toggle="modal"
                      data-bs-target="#cancelRecModal<?= $b['id'] ?>"
                      title="Cancel entire recurring series">
                <i class="bi bi-arrow-repeat"></i>
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

<?php require VIEW_PATH.'/partials/pagination.php'; ?>

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
