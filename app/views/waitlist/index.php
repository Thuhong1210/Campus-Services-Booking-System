  <!-- Page Header -->
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0"><i class="bi bi-hourglass-split me-2 text-warning"></i><?= e(__('My Waitlist')) ?></h1>
      <p class="text-muted mb-0"><?= e(__('Manage your waitlist entries for fully-booked resources')) ?></p>
    </div>
    <a href="<?= route_url('bookings', 'create') ?>" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i><?= e(__('New Booking')) ?>
    </a>
  </div>

  <?php if (empty($entries)): ?>
  <div class="card shadow-sm border-0">
    <div class="card-body text-center py-5">
      <i class="bi bi-hourglass text-muted" style="font-size:3rem"></i>
      <h5 class="mt-3 text-muted"><?= e(__('No Waitlist Entries')) ?></h5>
      <p class="text-muted"><?= e(__('When a resource is fully booked, you can join its waitlist and be notified when a slot opens up.')) ?></p>
      <a href="<?= route_url('resources', 'browse') ?>" class="btn btn-outline-primary mt-2">
        <i class="bi bi-search me-1"></i><?= e(__('Browse Resources')) ?>
      </a>
    </div>
  </div>
  <?php else: ?>

  <!-- Status Legend -->
  <div class="d-flex gap-2 flex-wrap mb-3">
    <span class="badge bg-secondary px-3 py-2"><i class="bi bi-clock me-1"></i><?= e(__('Waiting')) ?></span>
    <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-bell me-1"></i><?= e(__('Notified – Action Required')) ?></span>
    <span class="badge bg-success px-3 py-2"><i class="bi bi-check me-1"></i><?= e(__('Confirmed')) ?></span>
    <span class="badge bg-danger px-3 py-2"><i class="bi bi-x me-1"></i><?= e(__('Expired / Cancelled')) ?></span>
  </div>

  <div class="row g-3">
    <?php foreach ($entries as $entry): ?>
    <?php
      $statusClass = match($entry['status']) {
          'waiting'   => 'border-secondary',
          'notified'  => 'border-warning',
          'confirmed' => 'border-success',
          'expired'   => 'border-danger opacity-75',
          'cancelled' => 'border-danger opacity-75',
          default     => 'border-secondary',
      };
      $badgeClass = match($entry['status']) {
          'waiting'   => 'bg-secondary',
          'notified'  => 'bg-warning text-dark',
          'confirmed' => 'bg-success',
          'expired', 'cancelled' => 'bg-danger',
          default     => 'bg-secondary',
      };
    ?>
    <div class="col-md-6 col-xl-4">
      <div class="card shadow-sm border-2 <?= $statusClass ?> h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="card-title mb-0 fw-semibold"><?= e($entry['resource_name']) ?></h6>
            <span class="badge <?= $badgeClass ?>"><?= e(ucfirst($entry['status'])) ?></span>
          </div>
          <p class="text-muted small mb-1"><i class="bi bi-geo-alt me-1"></i><?= e($entry['location']) ?></p>
          <hr class="my-2">
          <div class="small">
            <div class="mb-1"><i class="bi bi-calendar-event me-1 text-primary"></i>
              <strong><?= e(__('Desired Slot')) ?>:</strong><br>
              <?= format_datetime($entry['desired_start'], 'd/m/Y H:i') ?> – <?= format_datetime($entry['desired_end'], 'H:i') ?>
            </div>
            <div class="mb-1"><i class="bi bi-clock-history me-1 text-muted"></i>
              <strong><?= e(__('Joined')) ?>:</strong> <?= format_datetime($entry['created_at']) ?>
            </div>
            <?php if ($entry['status'] === 'notified' && $entry['expires_at']): ?>
            <div class="alert alert-warning py-2 px-3 mb-0 mt-2">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              <strong><?= e(__('Expires at')) ?>:</strong> <?= format_datetime($entry['expires_at']) ?>
              <br><small><?= e(__('Confirm quickly before this slot expires!')) ?></small>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <?php if (in_array($entry['status'], ['waiting', 'notified'])): ?>
        <div class="card-footer bg-transparent d-flex gap-2">
          <?php if ($entry['status'] === 'notified'): ?>
          <form method="POST" action="<?= route_url('waitlist', 'confirm') ?>" class="flex-fill">
            <?= csrf_field() ?>
            <input type="hidden" name="waitlist_id" value="<?= (int)$entry['id'] ?>">
            <button type="submit" class="btn btn-success btn-sm w-100">
              <i class="bi bi-check2-circle me-1"></i><?= e(__('Confirm Booking')) ?>
            </button>
          </form>
          <?php endif; ?>
          <form method="POST" action="<?= route_url('waitlist', 'cancel') ?>" class="<?= $entry['status'] === 'notified' ? '' : 'flex-fill' ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="waitlist_id" value="<?= (int)$entry['id'] ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                    onclick="return confirm('<?= e(__('Remove from waitlist?')) ?>')">
              <i class="bi bi-x-circle me-1"></i><?= e(__('Remove')) ?>
            </button>
          </form>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
