<?php
$isMaintenance = (setting('maintenance_mode', '0') === '1' && !Auth::isAdmin());
?>
<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="fw-bold mb-0"><?= e(__($title ?? 'Create Booking')) ?></h1>
    <p class="text-muted mb-0" style="font-size:13.5px">
      <?= e(__('Fill in the details below. All fields are validated on the server before submission.')) ?>
    </p>
  </div>
  <a href="<?= route_url('bookings', 'myBookings') ?>" class="btn btn-light d-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> <?= e(__('My Bookings')) ?>
  </a>
</div>

<div class="row g-4">

  <!-- ─── Booking Form ─────────────────────────────────────── -->
  <div class="col-lg-8">
    <?php if ($isMaintenance): ?>
      <div class="alert alert-danger d-flex align-items-start gap-3 mb-4 rounded-3 border-0 shadow-sm p-3">
        <i class="bi bi-exclamation-triangle-fill fs-4 text-danger flex-shrink-0 mt-1"></i>
        <div>
          <h6 class="alert-heading fw-bold mb-1"><?= e(__('System Under Maintenance')) ?></h6>
          <p class="mb-0 text-secondary" style="font-size: 13.5px;">
            <?= e(__('System is under maintenance. Booking feature is temporarily locked.')) ?>
          </p>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($recommendations)): ?>
      <div class="rounded-3 border border-warning-subtle shadow-sm p-4 mb-4" style="background:linear-gradient(135deg,#fffbeb,#fef9ee)">
        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px">
            <i class="bi bi-lightbulb-fill text-white fs-5"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1 text-warning-emphasis"><?= e(__('Time Slot Conflict – Smart Recommendations')) ?></h6>
            <p class="mb-0 text-secondary" style="font-size:13.5px">
              <?= e(__('The selected resource is already booked. Here are the best available alternatives we found:')) ?>
            </p>
          </div>
        </div>

        <?php if (!empty($recommendations['alternative_slots'])): ?>
          <div class="mb-4">
            <div class="fw-bold small text-uppercase text-muted mb-3 d-flex align-items-center gap-2" style="font-size:11px;letter-spacing:0.06em">
              <i class="bi bi-clock-fill text-warning"></i>
              <?= e(__('Available Time Slots (Same Room)')) ?>
            </div>
            <div class="row g-2">
              <?php foreach ($recommendations['alternative_slots'] as $i => $slot): ?>
                <div class="col-sm-6 col-lg-4">
                  <button type="button"
                          class="btn w-100 text-start p-3 rounded-3 border border-warning slot-btn"
                          style="background:white;transition:all .2s"
                          onclick="applySlot('<?= $slot['booking_date'] ?>', '<?= $slot['start_time'] ?>', '<?= $slot['end_time'] ?>')"
                          onmouseover="this.style.background='#fffbeb';this.style.borderColor='#f59e0b'"
                          onmouseout="this.style.background='white';this.style.borderColor='#fde68a'">
                    <div class="d-flex align-items-center gap-2 mb-1">
                      <i class="bi bi-calendar-check text-warning"></i>
                      <span class="fw-semibold text-dark" style="font-size:13px"><?= date('D, d/m/Y', strtotime($slot['booking_date'])) ?></span>
                    </div>
                    <div class="text-muted small">
                      <i class="bi bi-clock me-1"></i><?= $slot['start_time'] ?> – <?= $slot['end_time'] ?>
                    </div>
                    <div class="mt-2">
                      <span class="badge bg-warning-subtle text-warning border border-warning-subtle small">
                        <i class="bi bi-lightning-charge-fill me-1"></i><?= e(__('Click to Apply')) ?>
                      </span>
                    </div>
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($recommendations['alternative_resources'])): ?>
          <div>
            <div class="fw-bold small text-uppercase text-muted mb-3 d-flex align-items-center gap-2" style="font-size:11px;letter-spacing:0.06em">
              <i class="bi bi-building text-primary"></i>
              <?= e(__('Alternative Rooms (Same Category, Same Time)')) ?>
            </div>
            <div class="row g-2">
              <?php foreach ($recommendations['alternative_resources'] as $res): ?>
                <div class="col-sm-6 col-lg-4">
                  <button type="button"
                          class="btn w-100 text-start p-3 rounded-3 border border-primary-subtle"
                          style="background:white;transition:all .2s"
                          onclick="applyResource(<?= (int)$res['id'] ?>)"
                          onmouseover="this.style.background='#eff6ff'"
                          onmouseout="this.style.background='white'">
                    <div class="d-flex align-items-center gap-2 mb-1">
                      <i class="bi bi-box-seam text-primary"></i>
                      <span class="fw-semibold text-dark" style="font-size:13px"><?= e($res['resource_name']) ?></span>
                    </div>
                    <div class="text-muted small">
                      <i class="bi bi-tag me-1"></i><?= e($res['resource_code']) ?>
                      <?php if (!empty($res['location'])): ?>
                        &nbsp;·&nbsp;<i class="bi bi-geo-alt me-1"></i><?= e($res['location']) ?>
                      <?php endif; ?>
                    </div>
                    <div class="mt-2">
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                        <i class="bi bi-arrow-right-circle me-1"></i><?= e(__('Switch to this room')) ?>
                      </span>
                    </div>
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (empty($recommendations['alternative_slots']) && empty($recommendations['alternative_resources'])): ?>
          <div class="text-center text-muted py-2">
            <i class="bi bi-calendar-x d-block fs-2 mb-2"></i>
            <?= e(__('No alternative slots or resources found for this period.')) ?>
          </div>
        <?php endif; ?>

        <!-- Join Waitlist Option -->
        <hr class="my-3">
        <div class="text-center">
          <p class="text-muted small mb-2"><?= e(__('Or join the waitlist for your original slot:')) ?></p>
          <form method="POST" action="<?= route_url('waitlist', 'store') ?>" id="waitlistForm">
            <?= csrf_field() ?>
            <input type="hidden" name="resource_id" id="wl_resource_id" value="<?= (int)old('resource_id') ?>">
            <input type="hidden" name="start_datetime" id="wl_start" value="<?= e(old('start_datetime', (old('booking_date')?' '.old('booking_date').' '.old('start_time', '08:00').':00':''))) ?>">
            <input type="hidden" name="end_datetime" id="wl_end" value="">
            <button type="submit" class="btn btn-warning" onclick="
              document.getElementById('wl_resource_id').value = document.getElementById('resourceSelect').value;
              var d = document.getElementById('bookingDate').value;
              var s = document.getElementById('startTime').value;
              var e2 = document.getElementById('endTime').value;
              document.getElementById('wl_start').value = d+' '+s+':00';
              document.getElementById('wl_end').value = d+' '+e2+':00';
            ">
              <i class="bi bi-hourglass-split me-1"></i><?= e(__('Join Waitlist for this Slot')) ?>
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>


    <div class="card">
      <div class="card-header">
        <i class="bi bi-calendar-plus me-2" style="color:var(--primary)"></i>
        <?= e(__('Booking Details')) ?>
      </div>
      <div class="card-body">
        <form method="POST" action="<?= route_url('bookings', 'store') ?>" id="bookingForm">
          <?= csrf_field() ?>

          <fieldset <?= $isMaintenance ? 'disabled' : '' ?> class="border-0 p-0 m-0">
            <div class="row g-3">

              <?php if (!empty($canSupervise)): ?>
              <div class="col-12">
                <label class="form-label fw-semibold"><?= e(__('Book For Student (Supervised)')) ?></label>
                <select name="student_user_id" class="form-select">
                  <option value=""><?= e(__('Myself / Current User')) ?></option>
                  <?php foreach ($students ?? [] as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= old('student_user_id') == $s['id'] ? 'selected' : '' ?>>
                      <?= e($s['full_name']) ?> — <?= e($s['email']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text"><i class="bi bi-info-circle me-1"></i><?= e(__('Lecturers and admins can create supervised bookings for academic activities.')) ?></div>
              </div>
              <?php endif; ?>

              <!-- Resource -->
              <div class="col-12">
                <label class="form-label fw-semibold"><?= e(__('Select Resource')) ?> <span class="text-danger">*</span></label>
                <select name="resource_id" id="resourceSelect" class="form-select" required>
                  <option value=""><?= e(__('— Choose a resource —')) ?></option>
                  <?php foreach ($resources as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= ($preselectedResourceId ?? 0) == $r['id'] ? 'selected' : '' ?>>
                    <?= e($r['resource_name']) ?> (<?= e($r['resource_code']) ?>) — <?= e($r['location'] ?? '') ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Date -->
              <div class="col-md-4">
                <label class="form-label fw-semibold"><?= e(__('Date')) ?> <span class="text-danger">*</span></label>
                <input
                  type="date"
                  name="booking_date"
                  id="bookingDate"
                  class="form-control"
                  min="<?= date('Y-m-d') ?>"
                  value="<?= e(old('booking_date', date('Y-m-d', strtotime('+1 day')))) ?>"
                  required
                >
              </div>

              <!-- Start Time -->
              <div class="col-md-4">
                <label class="form-label fw-semibold"><?= e(__('Start Time')) ?> <span class="text-danger">*</span></label>
                <input
                  type="time"
                  name="start_time"
                  id="startTime"
                  class="form-control"
                  value="<?= e(old('start_time', '08:00')) ?>"
                  required
                >
              </div>

              <!-- End Time -->
              <div class="col-md-4">
                <label class="form-label fw-semibold"><?= e(__('End Time')) ?> <span class="text-danger">*</span></label>
                <input
                  type="time"
                  name="end_time"
                  id="endTime"
                  class="form-control"
                  value="<?= e(old('end_time', '10:00')) ?>"
                  required
                >
              </div>

              <!-- Availability Status -->
              <div class="col-12">
                <div id="availabilityStatus" class="d-none rounded-3 px-3 py-2" style="font-size:13px"></div>
              </div>

              <!-- Purpose -->
              <div class="col-12">
                <label class="form-label fw-semibold"><?= e(__('Booking Purpose')) ?> <span class="text-danger">*</span></label>
                <input
                  type="text"
                  name="purpose"
                  class="form-control"
                  value="<?= e(old('purpose')) ?>"
                  placeholder="<?= e(__('e.g. Group project meeting, Lab session for assignment...')) ?>"
                  required
                >
              </div>

              <!-- Notes -->
              <div class="col-12">
                <label class="form-label fw-semibold"><?= e(__('Additional Notes')) ?></label>
                <textarea name="additional_notes" class="form-control" rows="3"
                          placeholder="<?= e(__('Any special requirements or details (optional)')) ?>"><?= e(old('additional_notes')) ?></textarea>
              </div>

              <!-- Recurring Booking Toggle -->
              <div class="col-12">
                <div class="card bg-light border-0">
                  <div class="card-body py-3">
                    <div class="form-check form-switch mb-2">
                      <input class="form-check-input" type="checkbox" name="is_recurring" id="isRecurring" value="1"
                             onchange="document.getElementById('recurringOptions').style.display=this.checked?'block':'none'"
                             <?= old('is_recurring') ? 'checked' : '' ?>>
                      <label class="form-check-label fw-semibold" for="isRecurring">
                        <i class="bi bi-arrow-repeat me-1 text-primary"></i><?= e(__('Recurring Booking')) ?>
                      </label>
                    </div>
                    <div id="recurringOptions" style="display:<?= old('is_recurring') ? 'block' : 'none' ?>">
                      <div class="row g-2">
                        <div class="col-sm-6">
                          <label class="form-label small fw-semibold"><?= e(__('Repeat Every')) ?></label>
                          <select name="recurring_type" class="form-select form-select-sm">
                            <option value="weekly" <?= old('recurring_type') === 'weekly' ? 'selected' : '' ?>><?= e(__('Week')) ?></option>
                            <option value="monthly" <?= old('recurring_type') === 'monthly' ? 'selected' : '' ?>><?= e(__('Month')) ?></option>
                          </select>
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label small fw-semibold"><?= e(__('Occurrences')) ?> (2–<?= setting('recurring_max_occurrences', 12) ?>)</label>
                          <input type="number" name="recurring_count" class="form-control form-control-sm"
                                 min="2" max="<?= setting('recurring_max_occurrences', 12) ?>"
                                 value="<?= (int) old('recurring_count', 4) ?>">
                        </div>
                      </div>
                      <p class="text-muted small mb-0 mt-2">
                        <i class="bi bi-info-circle me-1"></i><?= e(__('Occurrences with conflicts will be automatically skipped.')) ?>
                      </p>
              </div>
            </div>
          </div>

          <!-- Equipment Addons Section -->
          <div class="col-12 mt-3" id="equipmentAddonsSection" style="display:none">
            <label class="form-label fw-semibold"><i class="bi bi-tools me-1 text-primary"></i><?= e(__('Request Equipment Addons')) ?></label>
            <div class="card border border-light-subtle rounded-3">
              <div class="card-body py-2 px-3">
                <p class="text-muted small mb-2"><?= e(__('The following equipment is available in this room/resource:')) ?></p>
                <div id="equipmentList" class="row g-2">
                  <!-- Loaded via AJAX -->
                </div>
              </div>
            </div>
          </div>

        </div><!-- /.row -->

            <!-- Submit -->
            <div class="mt-4 pt-3 d-flex gap-2" style="border-top:var(--border-thin)">
              <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check-fill"></i> <?= e(__('Submit Booking')) ?>
              </button>
              <a href="<?= route_url('resources', 'browse') ?>" class="btn btn-light"><?= e(__('Cancel')) ?></a>
            </div>
          </fieldset>

        </form>
      </div>
    </div>
  </div>

  <!-- ─── Validation Checklist Sidebar ─────────────────────── -->
  <div class="col-lg-4">

    <!-- Checklist -->
    <div class="card mb-3">
      <div class="card-header">
        <i class="bi bi-shield-check me-2" style="color:var(--success)"></i>
        <?= e(__('Server Validation')) ?>
      </div>
      <div class="card-body">
        <?php $checks = [
          __('Resource availability & status'),
          __('Conflict detection (no double-booking)'),
          __('Booking policy limits'),
          __('Peak-hour limit (max 2 / week)'),
          __('Maintenance schedule check'),
          __('Approval requirement check'),
        ]; ?>
        <?php foreach ($checks as $check): ?>
        <div class="checklist-item">
          <i class="bi bi-check-circle-fill" style="color:var(--success)"></i>
          <span style="font-size:13.5px;color:var(--text-sub)"><?= e($check) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Notice card -->
    <div class="rounded-3 p-3" style="background:#fffbeb;border:1px solid #fde68a">
      <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" style="color:#f59e0b"></i>
        <div style="font-size:13px;color:#78350f">
          <strong><?= e(__('Approval Required')) ?></strong><br>
          <?= __('Bookings for <em>Laboratories</em> and <em>Media Studios</em> are set to <strong>Pending</strong> and must be approved by a lecturer or administrator.') ?>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
function applySlot(date, start, end) {
  document.getElementById('bookingDate').value = date;
  document.getElementById('startTime').value = start;
  document.getElementById('endTime').value = end;
  
  // Trigger change
  const el = document.getElementById('bookingDate');
  if (el) {
    const event = new Event('change');
    el.dispatchEvent(event);
  }
  
  // Visual effect
  const card = document.querySelector('.card');
  if (card) {
    card.style.transition = 'all 0.3s ease';
    card.style.boxShadow = '0 0 15px rgba(245, 158, 11, 0.4)';
    setTimeout(() => card.style.boxShadow = '', 1000);
  }
}

function applyResource(id) {
  document.getElementById('resourceSelect').value = id;
  
  // Trigger change
  const el = document.getElementById('resourceSelect');
  if (el) {
    const event = new Event('change');
    el.dispatchEvent(event);
  }
  
  // Visual effect
  const card = document.querySelector('.card');
  if (card) {
    card.style.transition = 'all 0.3s ease';
    card.style.boxShadow = '0 0 15px rgba(67, 97, 238, 0.4)';
    setTimeout(() => card.style.boxShadow = '', 1000);
  }
}
</script>
