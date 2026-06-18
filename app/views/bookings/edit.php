<?php
$bookingDate = date('Y-m-d', strtotime($booking['start_datetime']));
$startTime = date('H:i', strtotime($booking['start_datetime']));
$endTime = date('H:i', strtotime($booking['end_datetime']));
?>
<div class="page-header mb-4">
  <h1 class="h3 fw-bold">Edit Booking</h1>
  <p class="text-muted mb-0">Reference: <?= e($booking['booking_reference']) ?> — <?= status_badge($booking['status']) ?></p>
</div>
<div class="row g-4">
  <div class="col-lg-8">
    <form method="POST" action="<?= route_url('bookings', 'update', ['id' => $booking['id']]) ?>" class="card p-4" id="bookingForm">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-medium">Select Resource *</label>
          <select name="resource_id" class="form-select" required>
            <?php foreach ($resources as $r): ?>
              <option value="<?= $r['id'] ?>" <?= (int) $booking['resource_id'] === (int) $r['id'] ? 'selected' : '' ?>>
                <?= e($r['resource_name']) ?> (<?= e($r['resource_code']) ?>)
              </option>
            <?php endforeach; ?>
            <?php if ((int) $booking['resource_id'] && !in_array((int) $booking['resource_id'], array_column($resources, 'id'), true)): ?>
              <option value="<?= $booking['resource_id'] ?>" selected><?= e($booking['resource_name']) ?> (current)</option>
            <?php endif; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-medium">Date *</label>
          <input type="date" name="booking_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= e($bookingDate) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-medium">Start Time *</label>
          <input type="time" name="start_time" class="form-control" value="<?= e($startTime) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-medium">End Time *</label>
          <input type="time" name="end_time" class="form-control" value="<?= e($endTime) ?>" required>
        </div>
        <div class="col-12">
          <div id="availabilityStatus" class="small text-muted"></div>
        </div>
        <div class="col-12">
          <label class="form-label fw-medium">Booking Purpose *</label>
          <input name="purpose" class="form-control" value="<?= e($booking['purpose']) ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label fw-medium">Additional Notes</label>
          <textarea name="additional_notes" class="form-control" rows="2"><?= e($booking['additional_notes'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
        <a href="<?= route_url('bookings', 'my') ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
  <div class="col-lg-4">
    <div class="card p-4">
      <h6 class="fw-semibold mb-3">Edit Rules</h6>
      <ul class="small text-muted mb-0 ps-3">
        <li>Only pending or approved future bookings can be edited.</li>
        <li>Conflict detection and policy checks run on save.</li>
        <li>Approval status is not reset when editing time or resource.</li>
      </ul>
    </div>
  </div>
</div>
