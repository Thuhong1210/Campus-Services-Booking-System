<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="fw-bold mb-0">Create Booking</h1>
    <p class="text-muted mb-0" style="font-size:13.5px">
      Fill in the details below. All fields are validated on the server before submission.
    </p>
  </div>
  <a href="<?= route_url('bookings', 'myBookings') ?>" class="btn btn-light d-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> My Bookings
  </a>
</div>

<div class="row g-4">

  <!-- ─── Booking Form ─────────────────────────────────────── -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-calendar-plus me-2" style="color:var(--primary)"></i>
        Booking Details
      </div>
      <div class="card-body">
        <form method="POST" action="<?= route_url('bookings', 'store') ?>" id="bookingForm">
          <?= csrf_field() ?>

          <div class="row g-3">

            <?php if (!empty($canSupervise)): ?>
            <div class="col-12">
              <label class="form-label fw-semibold">Book For Student (Supervised)</label>
              <select name="student_user_id" class="form-select">
                <option value="">Myself / Current User</option>
                <?php foreach ($students ?? [] as $s): ?>
                  <option value="<?= $s['id'] ?>" <?= old('student_user_id') == $s['id'] ? 'selected' : '' ?>>
                    <?= e($s['full_name']) ?> — <?= e($s['email']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text"><i class="bi bi-info-circle me-1"></i>Lecturers and admins can create supervised bookings for academic activities.</div>
            </div>
            <?php endif; ?>

            <!-- Resource -->
            <div class="col-12">
              <label class="form-label fw-semibold">Select Resource <span class="text-danger">*</span></label>
              <select name="resource_id" id="resourceSelect" class="form-select" required>
                <option value="">— Choose a resource —</option>
                <?php foreach ($resources as $r): ?>
                <option value="<?= $r['id'] ?>" <?= ($preselectedResourceId ?? 0) == $r['id'] ? 'selected' : '' ?>>
                  <?= e($r['resource_name']) ?> (<?= e($r['resource_code']) ?>) — <?= e($r['location'] ?? '') ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Date -->
            <div class="col-md-4">
              <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
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
              <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
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
              <label class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
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
              <label class="form-label fw-semibold">Booking Purpose <span class="text-danger">*</span></label>
              <input
                type="text"
                name="purpose"
                class="form-control"
                value="<?= e(old('purpose')) ?>"
                placeholder="e.g. Group project meeting, Lab session for assignment..."
                required
              >
            </div>

            <!-- Notes -->
            <div class="col-12">
              <label class="form-label fw-semibold">Additional Notes</label>
              <textarea name="additional_notes" class="form-control" rows="3"
                        placeholder="Any special requirements or details (optional)"><?= e(old('additional_notes')) ?></textarea>
            </div>

          </div><!-- /.row -->

          <!-- Submit -->
          <div class="mt-4 pt-3 d-flex gap-2" style="border-top:var(--border-thin)">
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
              <i class="bi bi-calendar-check-fill"></i> Submit Booking
            </button>
            <a href="<?= route_url('resources', 'browse') ?>" class="btn btn-light">Cancel</a>
          </div>

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
        Server Validation
      </div>
      <div class="card-body">
        <?php $checks = [
          'Resource availability & status',
          'Conflict detection (no double-booking)',
          'Booking policy limits',
          'Peak-hour limit (max 2 / week)',
          'Maintenance schedule check',
          'Approval requirement check',
        ]; ?>
        <?php foreach ($checks as $check): ?>
        <div class="checklist-item">
          <i class="bi bi-check-circle-fill" style="color:var(--success)"></i>
          <span style="font-size:13.5px;color:var(--text-sub)"><?= $check ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Notice card -->
    <div class="rounded-3 p-3" style="background:#fffbeb;border:1px solid #fde68a">
      <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" style="color:#f59e0b"></i>
        <div style="font-size:13px;color:#78350f">
          <strong>Approval Required</strong><br>
          Bookings for <em>Laboratories</em> and <em>Media Studios</em> are set to
          <strong>Pending</strong> and must be approved by a lecturer or administrator.
        </div>
      </div>
    </div>

  </div>
</div>
