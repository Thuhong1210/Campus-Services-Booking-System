<div class="page-header mb-4">
  <h1 class="h3 fw-bold">Dashboard Overview</h1>
  <p class="text-muted">Welcome back. Here's what's happening across the campus today.</p>
</div>
<div class="row g-3 mb-4">
  <?php $cards = [
    ['label'=>'Total Users','val'=>$stats['total_users']??0,'icon'=>'bi-people','color'=>'primary'],
    ['label'=>'Total Resources','val'=>$stats['total_resources']??0,'icon'=>'bi-box','color'=>'info'],
    ['label'=>'Active Bookings','val'=>$stats['active_bookings']??0,'icon'=>'bi-calendar-check','color'=>'success'],
    ['label'=>'Pending Approvals','val'=>$stats['pending_approvals']??0,'icon'=>'bi-hourglass','color'=>'warning'],
    ['label'=>'Cancelled','val'=>$stats['cancelled']??0,'icon'=>'bi-x-circle','color'=>'secondary'],
    ['label'=>'Utilization %','val'=>($stats['utilization_rate']??0).'%','icon'=>'bi-graph-up','color'=>'primary'],
  ]; foreach($cards as $c): ?>
  <div class="col-md-4 col-lg-2"><div class="card stat-card h-100"><div class="card-body">
    <div class="d-flex justify-content-between"><div><p class="text-muted small mb-1"><?= e($c['label']) ?></p><h3 class="fw-bold mb-0"><?= e((string)$c['val']) ?></h3></div>
    <div class="stat-icon bg-<?= $c['color'] ?>-subtle text-<?= $c['color'] ?>"><i class="bi <?= $c['icon'] ?>"></i></div></div>
  </div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-4 mb-4">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header bg-white fw-medium">Bookings by Category</div><div class="card-body"><canvas id="chartCategory" height="200"></canvas></div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-header bg-white fw-medium">Peak Hour Bookings</div><div class="card-body"><canvas id="chartPeak" height="200"></canvas></div></div></div>
</div>
<div class="row g-4">
  <div class="col-lg-6"><div class="card"><div class="card-header bg-white fw-medium">Recent Activity</div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>User</th><th>Action</th><th>Time</th></tr></thead><tbody>
  <?php foreach($recentActivity as $log): ?><tr><td><?= e($log['user_name']??'System') ?></td><td><span class="badge bg-light text-dark"><?= e($log['action']) ?></span></td><td class="small"><?= format_datetime($log['created_at']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
  <div class="col-lg-6"><div class="card"><div class="card-header bg-white fw-medium">Upcoming Bookings</div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Ref</th><th>Student</th><th>Resource</th><th>Status</th></tr></thead><tbody>
  <?php foreach($upcomingBookings as $b): ?><tr><td><?= e($b['booking_reference']) ?></td><td><?= e($b['user_name']??'') ?></td><td><?= e($b['resource_name']??'') ?></td><td><?= status_badge($b['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
</div>
<script>
const cd = <?= json_encode($chartData ?? []) ?>;
if(document.getElementById('chartCategory')) new Chart(document.getElementById('chartCategory'),{type:'bar',data:{labels:cd.category_labels||[],datasets:[{label:'Bookings',data:cd.category_data||[],backgroundColor:'#3C83F6'}]}});
if(document.getElementById('chartPeak')) new Chart(document.getElementById('chartPeak'),{type:'line',data:{labels:cd.peak_labels||[],datasets:[{label:'Peak Bookings',data:cd.peak_data||[],borderColor:'#21C45D',fill:false}]}});
</script>
