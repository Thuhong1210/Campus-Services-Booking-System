  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0"><i class="bi bi-star-half text-warning me-2"></i><?= e(__('Feedback & Ratings')) ?></h1>
      <p class="text-muted mb-0"><?= e(__('Monitor resource quality based on user feedback')) ?></p>
    </div>
  </div>

  <!-- Summary Table -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
      <h6 class="mb-0 fw-semibold"><?= e(__('Average Ratings by Resource')) ?></h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Resource</th>
            <th>Code</th>
            <th>Reviews</th>
            <th>Avg Rating</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($summary)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No feedback yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($summary as $row): ?>
          <tr>
            <td class="fw-semibold"><?= e($row['resource_name']) ?></td>
            <td class="text-muted"><?= e($row['resource_code']) ?></td>
            <td><?= $row['total_reviews'] ?></td>
            <td>
              <?php
                $avg = (float) $row['avg_rating'];
                $color = $avg >= 4 ? 'success' : ($avg >= 3 ? 'warning' : 'danger');
              ?>
              <span class="badge bg-<?= $color ?> px-2"><?= number_format($avg, 1) ?>/5</span>
              <span class="text-warning ms-1">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <?= $s <= $avg ? '★' : '☆' ?>
                <?php endfor; ?>
              </span>
            </td>
            <td>
              <a href="<?= route_url('feedback', 'resource', ['resource_id' => $row['resource_id']]) ?>"
                 class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye me-1"></i>View Reviews
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Resource Detail View -->
  <?php if ($resourceFeedback && $resource): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
      <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-square-text me-2"></i>Reviews for: <?= e($resource['resource_name']) ?></h6>
      <span class="badge bg-secondary"><?= $resourceFeedback['averages']['total_reviews'] ?> reviews</span>
    </div>
    <div class="card-body">
      <!-- Rating Summary Row -->
      <div class="row g-3 mb-4">
        <div class="col-md-3 text-center">
          <div class="display-3 fw-bold text-warning"><?= number_format((float)$resourceFeedback['averages']['avg_rating'], 1) ?></div>
          <div class="text-muted">Overall</div>
          <div class="text-warning fs-4">
            <?php $avg = (float)$resourceFeedback['averages']['avg_rating']; ?>
            <?php for ($s=1;$s<=5;$s++): ?><?= $s<=$avg?'★':'☆'?><?php endfor; ?>
          </div>
        </div>
        <div class="col-md-3 text-center">
          <div class="display-5 fw-bold"><?= number_format((float)$resourceFeedback['averages']['avg_cleanliness'], 1) ?></div>
          <div class="text-muted">Cleanliness</div>
        </div>
        <div class="col-md-3 text-center">
          <div class="display-5 fw-bold"><?= number_format((float)$resourceFeedback['averages']['avg_equipment'], 1) ?></div>
          <div class="text-muted">Equipment</div>
        </div>
        <div class="col-md-3">
          <div class="fw-semibold mb-2">Distribution</div>
          <?php
          $dist = array_column($resourceFeedback['distribution'], 'count', 'rating');
          $total = $resourceFeedback['averages']['total_reviews'];
          ?>
          <?php for ($s=5;$s>=1;$s--): ?>
          <?php $cnt = $dist[$s] ?? 0; $pct = $total > 0 ? ($cnt/$total*100) : 0; ?>
          <div class="d-flex align-items-center gap-2 mb-1 small">
            <span style="width:15px"><?= $s ?>★</span>
            <div class="progress flex-fill" style="height:8px">
              <div class="progress-bar bg-warning" style="width:<?= $pct ?>%"></div>
            </div>
            <span style="width:25px" class="text-muted"><?= $cnt ?></span>
          </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- Reviews List -->
      <?php foreach ($resourceFeedback['reviews'] as $review): ?>
      <div class="border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <strong><?= e($review['user_name']) ?></strong>
            <span class="text-warning ms-2">
              <?php for ($s=1;$s<=5;$s++): ?><?= $s<=(int)$review['rating']?'★':'☆'?><?php endfor; ?>
            </span>
            <span class="badge bg-warning text-dark ms-1"><?= $review['rating'] ?>/5</span>
          </div>
          <small class="text-muted"><?= format_datetime($review['created_at']) ?></small>
        </div>
        <div class="small text-muted mt-1">
          Booking: <?= e($review['booking_reference']) ?> — <?= format_datetime($review['start_datetime'], 'd/m/Y') ?>
        </div>
        <?php if ($review['comment']): ?>
        <p class="mt-2 mb-0"><?= e($review['comment']) ?></p>
        <?php endif; ?>
        <?php if ($review['cleanliness_rating'] || $review['equipment_rating']): ?>
        <div class="mt-2 d-flex gap-3 small text-muted">
          <?php if ($review['cleanliness_rating']): ?>
          <span><i class="bi bi-sparkles me-1"></i>Cleanliness: <?= $review['cleanliness_rating'] ?>/5</span>
          <?php endif; ?>
          <?php if ($review['equipment_rating']): ?>
          <span><i class="bi bi-tools me-1"></i>Equipment: <?= $review['equipment_rating'] ?>/5</span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
