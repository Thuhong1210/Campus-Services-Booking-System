  <div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= route_url('bookings', 'myBookings') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
      <h1 class="h3 fw-bold mb-0"><i class="bi bi-star-fill text-warning me-2"></i><?= e(__('Rate Your Experience')) ?></h1>
      <p class="text-muted mb-0"><?= e($booking['resource_name']) ?> — <?= format_datetime($booking['start_datetime']) ?></p>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

          <!-- Booking Info -->
          <div class="alert alert-light border mb-4">
            <div class="row g-2 text-sm">
              <div class="col-6">
                <span class="text-muted">Reference:</span><br>
                <strong><?= e($booking['booking_reference']) ?></strong>
              </div>
              <div class="col-6">
                <span class="text-muted">Resource:</span><br>
                <strong><?= e($booking['resource_name']) ?></strong>
              </div>
              <div class="col-6">
                <span class="text-muted">Date:</span><br>
                <?= format_datetime($booking['start_datetime'], 'd/m/Y') ?>
              </div>
              <div class="col-6">
                <span class="text-muted">Time:</span><br>
                <?= format_datetime($booking['start_datetime'], 'H:i') ?> – <?= format_datetime($booking['end_datetime'], 'H:i') ?>
              </div>
            </div>
          </div>

          <form method="POST" action="<?= route_url('feedback', 'store') ?>" id="feedbackForm">
            <?= csrf_field() ?>
            <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">

            <!-- Overall Rating -->
            <div class="mb-4">
              <label class="form-label fw-semibold"><?= e(__('Overall Rating')) ?> <span class="text-danger">*</span></label>
              <div class="star-rating-widget d-flex gap-2 flex-row-reverse justify-content-end" id="overallStars">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" class="d-none" required>
                <label for="star<?= $i ?>" class="star-label fs-2 text-muted" style="cursor:pointer" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">&#9733;</label>
                <?php endfor; ?>
              </div>
              <div id="ratingText" class="small text-muted mt-1">Click a star to rate</div>
            </div>

            <!-- Cleanliness Rating -->
            <div class="mb-3">
              <label class="form-label fw-semibold"><?= e(__('Cleanliness')) ?> <small class="text-muted">(<?= e(__('optional')) ?>)</small></label>
              <div class="star-rating-widget d-flex gap-2 flex-row-reverse justify-content-end">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="cleanliness_rating" id="clean<?= $i ?>" value="<?= $i ?>" class="d-none">
                <label for="clean<?= $i ?>" class="star-label fs-3 text-muted" style="cursor:pointer">&#9733;</label>
                <?php endfor; ?>
              </div>
            </div>

            <!-- Equipment Rating -->
            <div class="mb-3">
              <label class="form-label fw-semibold"><?= e(__('Equipment Quality')) ?> <small class="text-muted">(<?= e(__('optional')) ?>)</small></label>
              <div class="star-rating-widget d-flex gap-2 flex-row-reverse justify-content-end">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="equipment_rating" id="equip<?= $i ?>" value="<?= $i ?>" class="d-none">
                <label for="equip<?= $i ?>" class="star-label fs-3 text-muted" style="cursor:pointer">&#9733;</label>
                <?php endfor; ?>
              </div>
            </div>

            <!-- Comment -->
            <div class="mb-4">
              <label for="comment" class="form-label fw-semibold"><?= e(__('Comments')) ?> <small class="text-muted">(<?= e(__('optional')) ?>)</small></label>
              <textarea name="comment" id="comment" class="form-control" rows="4"
                        placeholder="<?= e(__('Share your experience – cleanliness, equipment condition, any issues...')) ?>"></textarea>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-warning px-4 fw-semibold">
                <i class="bi bi-star-fill me-2"></i><?= e(__('Submit Feedback')) ?>
              </button>
              <a href="<?= route_url('bookings', 'show', ['id' => $booking['id']]) ?>" class="btn btn-outline-secondary">
                <?= e(__('Skip')) ?>
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

<style>
.star-rating-widget:hover label,
.star-rating-widget label:hover ~ label {
  color: #ffc107 !important;
}
.star-rating-widget input:checked ~ label {
  color: #ffc107 !important;
}
.star-label { transition: color 0.1s; }
</style>

<script>
const ratingLabels = ['','Terrible','Poor','Average','Good','Excellent'];
document.querySelectorAll('#overallStars input[type=radio]').forEach(r => {
  r.addEventListener('change', function() {
    document.getElementById('ratingText').textContent = ratingLabels[this.value] + ' (' + this.value + '/5)';
    document.getElementById('ratingText').className = 'small mt-1 fw-semibold text-warning';
  });
});

// Star hover effect for all star widgets
document.querySelectorAll('.star-rating-widget').forEach(widget => {
  const labels = widget.querySelectorAll('.star-label');
  labels.forEach((label, idx) => {
    label.addEventListener('mouseover', () => {
      labels.forEach((l, i) => {
        l.style.color = i >= idx ? '#ffc107' : '#dee2e6';
      });
    });
    label.addEventListener('mouseout', () => {
      labels.forEach(l => l.style.color = '');
    });
  });
});
</script>
