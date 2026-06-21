  <div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= route_url('maintenance') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
      <h1 class="h3 fw-bold mb-0"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i><?= e(__('Maintenance Impact Report')) ?></h1>
      <p class="text-muted mb-0"><?= e($schedule['resource_name']) ?> — <?= format_datetime($schedule['maintenance_start']) ?> → <?= format_datetime($schedule['maintenance_end']) ?></p>
    </div>
  </div>

  <!-- Maintenance Details Card -->
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-semibold mb-3"><i class="bi bi-wrench me-2 text-warning"></i>Maintenance Details</h6>
          <table class="table table-sm table-borderless mb-0">
            <tr><td class="text-muted" style="width:140px">Resource</td><td class="fw-semibold"><?= e($schedule['resource_name']) ?> (<?= e($schedule['resource_code']) ?>)</td></tr>
            <tr><td class="text-muted">Status</td><td><?= status_badge($schedule['status'], 'resource') ?></td></tr>
            <tr><td class="text-muted">Start</td><td><?= format_datetime($schedule['maintenance_start']) ?></td></tr>
            <tr><td class="text-muted">End</td><td><?= format_datetime($schedule['maintenance_end']) ?></td></tr>
            <tr><td class="text-muted">Reason</td><td><?= e($schedule['reason']) ?></td></tr>
            <tr><td class="text-muted">Created By</td><td><?= e($schedule['created_by_name']) ?></td></tr>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex flex-column justify-content-center text-center">
          <div class="display-4 fw-bold text-<?= count($impacted) > 0 ? 'danger' : 'success' ?>"><?= count($impacted) ?></div>
          <p class="text-muted mb-3">Bookings Affected</p>
          <?php if (count($impacted) > 0): ?>
          <!-- Quick Actions -->
          <?php if ($schedule['status'] === 'scheduled'): ?>
          <form method="POST" action="<?= route_url('maintenance', 'activate') ?>" class="mb-2">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$schedule['id'] ?>">
            <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Activate maintenance and notify affected users?')">
              <i class="bi bi-play-circle me-1"></i>Activate & Notify Users
            </button>
          </form>
          <?php else: ?>
          <form method="POST" action="<?= route_url('maintenance', 'notifyImpacted') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$schedule['id'] ?>">
            <button type="submit" class="btn btn-outline-warning w-100">
              <i class="bi bi-bell me-1"></i>Re-send Notifications (<?= count($impacted) ?> users)
            </button>
          </form>
          <?php endif; ?>
          <?php endif; ?>
          <?php if ($schedule['status'] === 'in_progress'): ?>
          <form method="POST" action="<?= route_url('maintenance', 'complete') ?>" class="mt-2">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$schedule['id'] ?>">
            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Mark maintenance as complete and restore resource?')">
              <i class="bi bi-check2-circle me-1"></i>Mark Complete & Restore Resource
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Impacted Bookings Table -->
  <?php if (empty($impacted)): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
      <h5 class="mt-3 text-success">No Bookings Affected</h5>
      <p class="text-muted">There are no active bookings conflicting with this maintenance window.</p>
    </div>
  </div>
  <?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex align-items-center">
      <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar-x text-danger me-2"></i>Affected Bookings (<?= count($impacted) ?>)</h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Reference</th>
            <th>User</th>
            <th>Start</th>
            <th>End</th>
            <th>Purpose</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($impacted as $b): ?>
          <tr>
            <td><a href="<?= route_url('bookings', 'show', ['id' => $b['id']]) ?>" class="fw-semibold text-primary"><?= e($b['booking_reference']) ?></a></td>
            <td><?= e($b['user_name']) ?></td>
            <td><?= format_datetime($b['start_datetime']) ?></td>
            <td><?= format_datetime($b['end_datetime'], 'H:i') ?></td>
            <td><?= e($b['purpose']) ?></td>
            <td><?= status_badge($b['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
