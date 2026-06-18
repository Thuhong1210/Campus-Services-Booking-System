<div class="page-header mb-4">
  <h1 class="h3 fw-bold">Create Booking</h1>
  <p class="text-muted mb-0">Submit a booking request. The system validates availability, conflicts, and policies on the server.</p>
</div>
<div class="row g-4">
  <div class="col-lg-8">
    <form method="POST" action="<?= route_url('bookings', 'store') ?>" class="card p-4" id="bookingForm">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-medium">Select Resource *</label>
          <select name="resource_id" class="form-select" required>
            <option value="">Choose a resource</option>
            <?php foreach ($resources as $r): ?>
              <option value="<?= $r['id'] ?>" <?= ($preselectedResourceId ?? 0) == $r['id'] ? 'selected' : '' ?>>
                <?= e($r['resource_name']) ?> (<?= e($r['resource_code']) ?>) — <?= e($r['location']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-medium">Date *</label>
          <input type="date" name="booking_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= e(old('booking_date', date('Y-m-d', strtotime('+1 day')))) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-medium">Start Time *</label>
          <input type="time" name="start_time" class="form-control" value="<?= e(old('start_time', '08:00')) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-medium">End Time *</label>
          <input type="time" name="end_time" class="form-control" value="<?= e(old('end_time', '10:00')) ?>" required>
        </div>
        <div class="col-12">
          <div id="availabilityStatus" class="small text-muted"></div>
        </div>
        <div class="col-12">
          <label class="form-label fw-medium">Booking Purpose *</label>
          <input name="purpose" class="form-control" value="<?= e(old('purpose')) ?>" placeholder="e.g. Group project meeting, lab session" required>
        </div>
        <div class="col-12">
          <label class="form-label fw-medium">Additional Notes</label>
          <textarea name="additional_notes" class="form-control" rows="2" placeholder="Optional details"><?= e(old('additional_notes')) ?></textarea>
        </div>
      </div>
      <button type="submit" class="btn btn-primary mt-4 px-4"><i class="bi bi-calendar-plus me-1"></i>Submit Booking</button>
    </form>
  </div>
  <div class="col-lg-4">
    <div class="card p-4">
      <h6 class="fw-semibold mb-3">Backend Validation Checklist</h6>
      <div class="checklist-item"><i class="bi bi-check-circle-fill"></i> Resource availability</div>
      <div class="checklist-item"><i class="bi bi-check-circle-fill"></i> Booking conflict detection</div>
      <div class="checklist-item"><i class="bi bi-check-circle-fill"></i> Resource status check</div>
      <div class="checklist-item"><i class="bi bi-check-circle-fill"></i> Booking policy validation</div>
      <div class="checklist-item"><i class="bi bi-check-circle-fill"></i> Peak-hour limit (max 2/week)</div>
      <div class="checklist-item"><i class="bi bi-check-circle-fill"></i> Approval requirement</div>
      <div class="checklist-item"><i class="bi bi-check-circle-fill"></i> Maintenance schedule check</div>
      <hr>
      <p class="small text-muted mb-0">Laboratory and Media Studio bookings require lecturer/admin approval.</p>
    </div>
  </div>
</div>
