<div class="mb-4">
  <h1 class="h3 fw-bold text-dark-blue"><?= e(__('System Settings')) ?></h1>
  <p class="text-muted"><?= e(__('Configure system parameters and default booking rules.')) ?></p>
</div>

<div class="row">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
      <form method="POST" action="<?= route_url('settings', 'update') ?>">
        <?= csrf_field() ?>

        <!-- Section 1: General Configuration -->
        <h5 class="fw-semibold mb-3 text-primary">
          <i class="bi bi-gear-fill me-2"></i><?= e(__('General Configuration')) ?>
        </h5>
        
        <div class="mb-3">
          <label for="system_name" class="form-label fw-medium"><?= e(__('System Name')) ?></label>
          <input type="text" id="system_name" name="system_name" class="form-control rounded-3" value="<?= e($settings['system_name'] ?? '') ?>" required>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="default_timezone" class="form-label fw-medium"><?= e(__('Default Timezone')) ?></label>
            <select id="default_timezone" name="default_timezone" class="form-select rounded-3" required>
              <?php
              $timezones = [
                  'Asia/Ho_Chi_Minh' => 'Asia/Ho_Chi_Minh (GMT+7)',
                  'Asia/Bangkok' => 'Asia/Bangkok (GMT+7)',
                  'Asia/Singapore' => 'Asia/Singapore (GMT+8)',
                  'UTC' => 'UTC (GMT+0)',
                  'Europe/London' => 'Europe/London (GMT+1)',
                  'America/New_York' => 'America/New_York (GMT-4)',
              ];
              foreach ($timezones as $tz => $label):
              ?>
                <option value="<?= e($tz) ?>" <?= ($settings['default_timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label for="language_default" class="form-label fw-medium"><?= e(__('Default Language')) ?></label>
            <select id="language_default" name="language_default" class="form-select rounded-3" required>
              <option value="en" <?= ($settings['language_default'] ?? '') === 'en' ? 'selected' : '' ?>><?= e(__('English')) ?></option>
              <option value="vi" <?= ($settings['language_default'] ?? '') === 'vi' ? 'selected' : '' ?>><?= e(__('Vietnamese')) ?></option>
            </select>
          </div>
        </div>

        <hr class="my-4 text-muted">

        <!-- Section 2: Default Booking Policies -->
        <h5 class="fw-semibold mb-3 text-primary">
          <i class="bi bi-shield-lock-fill me-2"></i><?= e(__('Default Booking Policies')) ?>
        </h5>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="default_booking_limit" class="form-label fw-medium"><?= e(__('Weekly Peak-Hour Limit')) ?></label>
            <div class="input-group">
              <input type="number" id="default_booking_limit" name="default_booking_limit" class="form-control rounded-start-3" min="0" value="<?= e($settings['default_booking_limit'] ?? '2') ?>" required>
              <span class="input-group-text rounded-end-3"><?= e(__('slots/week')) ?></span>
            </div>
            <div class="form-text"><?= e(__('Maximum peak hour slots a student can book in a week.')) ?></div>
          </div>

          <div class="col-md-6 mb-3">
            <label for="default_cancellation_deadline" class="form-label fw-medium"><?= e(__('Default Cancellation Deadline')) ?></label>
            <div class="input-group">
              <input type="number" id="default_cancellation_deadline" name="default_cancellation_deadline" class="form-control rounded-start-3" min="0" value="<?= e($settings['default_cancellation_deadline'] ?? '24') ?>" required>
              <span class="input-group-text rounded-end-3"><?= e(__('hours')) ?></span>
            </div>
            <div class="form-text"><?= e(__('Required time buffer before booking start to cancel a slot.')) ?></div>
          </div>
        </div>

        <hr class="my-4 text-muted">

        <!-- Section 3: System Mode & Alerts -->
        <h5 class="fw-semibold mb-3 text-primary">
          <i class="bi bi-toggle-on me-2"></i><?= e(__('System Controls')) ?>
        </h5>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="maintenance_mode" class="form-label fw-medium"><?= e(__('Maintenance Mode')) ?></label>
            <select id="maintenance_mode" name="maintenance_mode" class="form-select rounded-3" required>
              <option value="0" <?= ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : '' ?>><?= e(__('Disabled')) ?></option>
              <option value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : '' ?>><?= e(__('Enabled')) ?></option>
            </select>
            <div class="form-text text-danger"><?= e(__('Locks all new student booking creations and displays a maintenance alert.')) ?></div>
          </div>

          <div class="col-md-6 mb-3">
            <label for="email_notification_enabled" class="form-label fw-medium"><?= e(__('Email Notifications')) ?></label>
            <select id="email_notification_enabled" name="email_notification_enabled" class="form-select rounded-3" required>
              <option value="0" <?= ($settings['email_notification_enabled'] ?? '1') === '0' ? 'selected' : '' ?>><?= e(__('Disabled')) ?></option>
              <option value="1" <?= ($settings['email_notification_enabled'] ?? '1') === '1' ? 'selected' : '' ?>><?= e(__('Enabled')) ?></option>
            </select>
            <div class="form-text"><?= e(__('Allows system notifications to be dispatched to user emails.')) ?></div>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold rounded-3 shadow-sm">
            <i class="bi bi-save me-2"></i><?= e(__('Save Settings')) ?>
          </button>
          <a href="<?= route_url('dashboard') ?>" class="btn btn-outline-secondary px-4 py-2 rounded-3">
            <?= e(__('Cancel')) ?>
          </a>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-light">
      <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i><?= e(__('System Information')) ?></h5>
      <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size: 14px;">
        <li class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted"><?= e(__('Database')) ?>:</span>
          <span class="fw-medium text-dark">campus_services_booking</span>
        </li>
        <li class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted"><?= e(__('System Version')) ?>:</span>
          <span class="fw-medium text-dark">1.2.0</span>
        </li>
        <li class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted"><?= e(__('Active Session Name')) ?>:</span>
          <code class="text-primary font-monospace"><?= e($settings['session_name']) ?></code>
        </li>
        <li class="d-flex justify-content-between py-1">
          <span class="text-muted"><?= e(__('Server Timezone')) ?>:</span>
          <span class="badge bg-secondary"><?= e(date_default_timezone_get()) ?></span>
        </li>
      </ul>
    </div>
  </div>
</div>