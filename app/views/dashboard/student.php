<?php
$greeting = 'Hello';
$hour = (int)date('H');
if ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 18) $greeting = 'Good afternoon';
else $greeting = 'Good evening';
$firstName = explode(' ', $user['full_name'] ?? 'Student')[0];
?>

<!-- Welcome Banner -->
<div class="welcome-banner rounded-4 p-4 mb-4 d-flex align-items-center justify-content-between"
     style="background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 60%, #60a5fa 100%); color:#fff">
  <div>
    <h2 class="fw-bold mb-1" style="font-size:1.4rem"><?= $greeting ?>, <?= e($firstName) ?>! 👋</h2>
    <p class="mb-0 opacity-75 small">Welcome to IS-VNU Campus Services Booking</p>
  </div>
  <div class="d-none d-md-flex gap-2">
    <a href="<?= url('index.php?page=resources&action=browse') ?>" class="btn btn-light btn-sm fw-semibold">
      <i class="bi bi-search me-1"></i>Browse Resources
    </a>
    <a href="<?= url('index.php?page=bookings&action=create') ?>" class="btn btn-warning btn-sm fw-semibold text-dark">
      <i class="bi bi-plus-circle me-1"></i>Create Booking
    </a>
  </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:48px;height:48px;background:#dbeafe">
          <i class="bi bi-calendar-event text-primary fs-5"></i>
        </div>
        <div>
          <p class="text-muted small mb-0">Upcoming</p>
          <h3 class="fw-bold mb-0"><?= (int)($stats['upcoming'] ?? 0) ?></h3>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:48px;height:48px;background:#fef9c3">
          <i class="bi bi-hourglass-split text-warning fs-5"></i>
        </div>
        <div>
          <p class="text-muted small mb-0">Pending</p>
          <h3 class="fw-bold mb-0"><?= (int)($stats['pending'] ?? 0) ?></h3>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:48px;height:48px;background:#dcfce7">
          <i class="bi bi-check-circle text-success fs-5"></i>
        </div>
        <div>
          <p class="text-muted small mb-0">Approved</p>
          <h3 class="fw-bold mb-0"><?= (int)($stats['approved'] ?? 0) ?></h3>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:48px;height:48px;background:#fce7f3">
          <i class="bi bi-bell text-danger fs-5"></i>
        </div>
        <div>
          <p class="text-muted small mb-0">Notifications</p>
          <h3 class="fw-bold mb-0"><?= (int)($unreadCount ?? 0) ?></h3>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Charts Section -->
<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card h-100" style="border-radius:14px">
      <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>My Booking Statuses</span>
      </div>
      <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 250px; position: relative;">
        <div style="width: 100%; height: 230px;">
          <canvas id="statusChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100" style="border-radius:14px">
      <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><i class="bi bi-bar-chart-fill me-2 text-success"></i>Bookings by Category</span>
      </div>
      <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 250px; position: relative;">
        <div style="width: 100%; height: 230px;">
          <canvas id="categoryChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="row g-4">

  <!-- Upcoming Bookings & Recommendations -->
  <div class="col-lg-7">
    
    <!-- Recommended Resources -->
    <div class="card mb-4" style="border-radius:14px">
      <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><i class="bi bi-hand-thumbs-up me-2 text-warning"></i>Recommended Resources</span>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php foreach ($recommendedResources as $res): ?>
          <div class="col-md-4">
            <div class="card h-100 border-0 bg-light shadow-sm" style="border-radius:10px">
              <div class="card-body p-3 d-flex flex-column">
                <span class="badge bg-white text-primary border align-self-start mb-2" style="font-size:10px">
                  <?= e($res['category_name']) ?>
                </span>
                <h6 class="fw-bold mb-1" style="font-size:13px; min-height: 38px;"><?= e($res['resource_name']) ?></h6>
                <p class="text-muted mb-3 small flex-grow-1" style="font-size:11px">
                  <i class="bi bi-geo-alt me-1"></i><?= e($res['location']) ?><br>
                  <i class="bi bi-people me-1"></i>Capacity: <?= e($res['capacity']) ?>
                </p>
                <a href="<?= url('index.php?page=bookings&action=create&resource_id='.$res['id']) ?>" 
                   class="btn btn-primary btn-sm w-100 fw-semibold text-center" style="font-size:11px">
                  <i class="bi bi-calendar-plus me-1"></i>Quick Book
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Upcoming Bookings -->
    <div class="card" style="border-radius:14px">
      <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <span class="fw-semibold"><i class="bi bi-calendar-check me-2 text-primary"></i>My Upcoming Bookings</span>
        <a href="<?= url('index.php?page=bookings&action=myBookings') ?>" class="btn btn-outline-primary btn-sm">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Resource</th>
              <th>Date & Time</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($upcomingBookings as $b): ?>
            <tr>
              <td>
                <div class="fw-medium small"><?= e($b['resource_name']) ?></div>
                <div class="text-muted" style="font-size:11px"><?= e($b['booking_reference'] ?? '') ?></div>
              </td>
              <td class="small"><?= format_datetime($b['start_datetime']) ?></td>
              <td><?= status_badge($b['status']) ?></td>
              <td>
                <a href="<?= url('index.php?page=bookings&action=show&id='.$b['id']) ?>"
                   class="btn btn-sm btn-outline-secondary" style="font-size:11px">Details</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($upcomingBookings)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                <i class="bi bi-calendar-x d-block fs-3 mb-2 opacity-50"></i>
                No upcoming bookings
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Notifications & Limits -->
  <div class="col-lg-5">
    
    <!-- Weekly Quota Tracker -->
    <div class="card mb-4" style="border-radius:14px">
      <div class="card-header bg-white border-bottom d-flex align-items-center py-3">
        <span class="fw-semibold"><i class="bi bi-pie-chart me-2 text-info"></i>Weekly Quota Tracker</span>
      </div>
      <div class="card-body">
        <!-- Peak Hours slots limit -->
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span class="small fw-medium text-muted">Weekly Peak-Hour Bookings</span>
            <span class="small fw-semibold"><?= (int)$peakUsed ?> / <?= (int)$peakLimit ?> slots</span>
          </div>
          <div class="progress" style="height: 8px;">
            <?php 
              $peakBarClass = 'bg-primary';
              if ($peakPercentage >= 90) $peakBarClass = 'bg-danger';
              elseif ($peakPercentage >= 70) $peakBarClass = 'bg-warning';
            ?>
            <div class="progress-bar <?= $peakBarClass ?>" role="progressbar" 
                 style="width: <?= $peakPercentage ?>%" 
                 aria-valuenow="<?= $peakUsed ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="<?= $peakLimit ?>"></div>
          </div>
        </div>
        
        <!-- Category Hours Limits -->
        <?php foreach ($quotaUsage as $quota): 
            if ($quota['limit_hours'] <= 0) continue;
            $barClass = 'bg-success';
            if ($quota['percentage'] >= 90) $barClass = 'bg-danger';
            elseif ($quota['percentage'] >= 70) $barClass = 'bg-warning';
        ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span class="small fw-medium text-muted"><?= e($quota['category_name']) ?></span>
            <span class="small fw-semibold"><?= round($quota['used_hours'], 1) ?> / <?= round($quota['limit_hours'], 1) ?> hrs</span>
          </div>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar <?= $barClass ?>" role="progressbar" 
                 style="width: <?= $quota['percentage'] ?>%" 
                 aria-valuenow="<?= $quota['used_hours'] ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="<?= $quota['limit_hours'] ?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Notifications -->
    <div class="card" style="border-radius:14px">
      <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <span class="fw-semibold"><i class="bi bi-bell me-2 text-danger"></i>Notifications</span>
        <a href="<?= url('index.php?page=notifications') ?>" class="btn btn-outline-primary btn-sm">View All</a>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach($notifications as $n): ?>
        <div class="list-group-item px-3 py-3 <?= $n['is_read'] ? '' : 'bg-primary bg-opacity-5 border-start border-primary border-3' ?>">
          <div class="d-flex gap-2 align-items-start">
            <div class="flex-shrink-0 mt-1">
              <?php
                $icon = match($n['type'] ?? '') {
                    'booking_approved' => '<i class="bi bi-check-circle-fill text-success"></i>',
                    'booking_rejected' => '<i class="bi bi-x-circle-fill text-danger"></i>',
                    'booking_cancelled' => '<i class="bi bi-dash-circle-fill text-warning"></i>',
                    'pending_approval' => '<i class="bi bi-hourglass-split text-primary"></i>',
                    default => '<i class="bi bi-info-circle-fill text-secondary"></i>',
                };
                echo $icon;
              ?>
            </div>
            <div>
              <div class="fw-semibold small"><?= e($n['title']) ?></div>
              <div class="text-muted small"><?= e($n['message']) ?></div>
              <div class="text-muted mt-1" style="font-size:10px">
                <i class="bi bi-clock me-1"></i><?= format_datetime($n['created_at']) ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($notifications)): ?>
        <div class="list-group-item text-center text-muted py-4">
          <i class="bi bi-bell-slash d-block fs-3 mb-2 opacity-50"></i>
          No notifications
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- Quick Links -->
<div class="row g-3 mt-2">
  <div class="col-12">
    <div class="card" style="border-radius:14px">
      <div class="card-body py-3">
        <p class="fw-semibold small text-muted mb-3">QUICK ACTIONS</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('index.php?page=resources&action=browse') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-search me-1"></i>Browse Resources
          </a>
          <a href="<?= url('index.php?page=bookings&action=create') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Create Booking
          </a>
          <a href="<?= url('index.php?page=bookings&action=calendar') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calendar3 me-1"></i>Resource Calendar
          </a>
          <a href="<?= url('index.php?page=bookings&action=myBookings') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-list-check me-1"></i>My Bookings
          </a>
          <a href="<?= url('index.php?page=bookings&action=mySchedule') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calendar-week me-1"></i>My Schedule
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Status Chart
    const statusData = <?= json_encode($chartData['bookings_by_status'] ?? []) ?>;
    const statusLabels = statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
    const statusCounts = statusData.map(item => parseInt(item.count));
    
    // Status colors mapping
    const statusColors = {
        'pending': '#f59e0b',
        'approved': '#10b981',
        'rejected': '#ef4444',
        'cancelled': '#6b7280',
        'completed': '#3b82f6',
        'expired': '#9ca3af'
    };
    
    const bgColors = statusData.map(item => statusColors[item.status.toLowerCase()] || '#cbd5e1');

    if (statusCounts.length > 0) {
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: bgColors,
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { family: 'Inter', size: 12 }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('statusChart').parentElement.innerHTML = `
            <div class="text-muted text-center py-4">
                <i class="bi bi-pie-chart d-block fs-3 mb-2 opacity-50"></i>
                No bookings data for status chart
            </div>
        `;
    }

    // 2. Category Chart
    const catData = <?= json_encode($chartData['bookings_by_category'] ?? []) ?>;
    const catLabels = catData.map(item => item.category_name);
    const catCounts = catData.map(item => parseInt(item.count));

    if (catCounts.length > 0) {
        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Bookings',
                    data: catCounts,
                    backgroundColor: 'rgba(59, 130, 246, 0.85)',
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: { family: 'Inter', size: 11 }
                        }
                    },
                    x: {
                        ticks: {
                            font: { family: 'Inter', size: 11 }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('categoryChart').parentElement.innerHTML = `
            <div class="text-muted text-center py-4">
                <i class="bi bi-bar-chart d-block fs-3 mb-2 opacity-50"></i>
                No bookings data for category chart
            </div>
        `;
    }
});
</script>
