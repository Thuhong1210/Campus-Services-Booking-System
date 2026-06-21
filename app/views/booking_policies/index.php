<div class="container-fluid px-0 py-2">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0 text-gradient"><i class="bi bi-shield-lock me-2 text-primary"></i><?= e(__('Booking Policies')) ?></h1>
      <p class="text-muted mb-0"><?= e(__('Configure system rules, weekly quotas, and approval chains for booking categories')) ?></p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= url('index.php?page=booking-policies&action=simulate') ?>" class="btn btn-outline-primary rounded-pill px-3">
        <i class="bi bi-cpu me-1"></i><?= e(__('Policy Simulator')) ?>
      </a>
      <a href="<?= url('index.php?page=booking-policies&action=create') ?>" class="btn btn-primary rounded-pill px-3 shadow-premium">
        <i class="bi bi-plus-circle me-1"></i><?= e(__('Add Policy')) ?>
      </a>
    </div>
  </div>

  <div class="card shadow-premium border-0 rounded-4 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4"><?= e(__('Policy Name')) ?></th>
            <th><?= e(__('Category')) ?></th>
            <th><?= e(__('Max Duration')) ?></th>
            <th><?= e(__('Weekly Quota')) ?></th>
            <th><?= e(__('Peak Slots Limit')) ?></th>
            <th><?= e(__('Requires Approval')) ?></th>
            <th><?= e(__('Auto Approval')) ?></th>
            <th class="pe-4 text-end"><?= e(__('Actions')) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($policies)): ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-shield-slash d-block fs-2 mb-2"></i>
                <?= e(__('No policies configured.')) ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($policies as $p): ?>
              <tr>
                <td class="ps-4 fw-medium text-dark"><?= e($p['policy_name']) ?></td>
                <td><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill px-3"><?= e($p['category_name']) ?></span></td>
                <td><strong class="text-primary"><?= e($p['max_duration_hours']) ?>h</strong></td>
                <td><?= e($p['weekly_quota'] ?? 5) ?></td>
                <td><?= e($p['max_peak_slots_per_week'] ?? 2) ?></td>
                <td>
                  <?php if ($p['requires_approval']): ?>
                    <span class="text-danger small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Yes</span>
                  <?php else: ?>
                    <span class="text-muted small"><i class="bi bi-x-circle me-1"></i>No</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($p['auto_approval_enabled'] ?? 0): ?>
                    <span class="text-success small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Yes</span>
                  <?php else: ?>
                    <span class="text-muted small"><i class="bi bi-x-circle me-1"></i>No</span>
                  <?php endif; ?>
                </td>
                <td class="pe-4 text-end">
                  <a href="<?= url('index.php?page=booking-policies&action=edit&id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-pencil me-1"></i><?= e(__('Edit')) ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>