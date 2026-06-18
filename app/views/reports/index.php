<div class="d-flex justify-content-between mb-4"><h1 class="h3 fw-bold">Usage Reports</h1>
<form method="POST" action="<?= url('index.php?page=reports&action=export-csv') ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-outline-primary">Export CSV</button></form></div>
<div class="row g-3 mb-4">
<?php foreach([['Total Bookings',$summary['total_bookings']??0],['Approved',$summary['total_approved']??0],['Cancelled',$summary['total_cancelled']??0],['Utilization',($summary['avg_utilization']??0).'%']] as [$l,$v]): ?>
<div class="col-md-3"><div class="card stat-card"><div class="card-body"><p class="text-muted small"><?= $l ?></p><h3><?= e((string)$v) ?></h3></div></div></div><?php endforeach; ?>
</div>
<div class="row g-4 mb-4"><div class="col-lg-6"><div class="card p-3"><canvas id="chartResources" height="200"></canvas></div></div>
<div class="col-lg-6"><div class="card p-3"><canvas id="chartApproval" height="200"></canvas></div></div></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Resource</th><th>Category</th><th>Bookings</th><th>Approved</th><th>Hours</th><th>Utilization</th></tr></thead><tbody>
<?php foreach($reports as $r): ?><tr><td><?= e($r['resource_name']) ?></td><td><?= e($r['category_name']??'') ?></td><td><?= $r['total_bookings'] ?></td><td><?= $r['total_approved'] ?></td><td><?= $r['total_hours'] ?></td><td><?= $r['utilization_rate'] ?>%</td></tr><?php endforeach; ?>
</tbody></table></div></div>
<script>const rd=<?= json_encode($chartData??[]) ?>;if(document.getElementById('chartResources'))new Chart(document.getElementById('chartResources'),{type:'bar',data:{labels:rd.resource_labels||[],datasets:[{data:rd.resource_data||[],backgroundColor:'#3C83F6'}]}});
if(document.getElementById('chartApproval'))new Chart(document.getElementById('chartApproval'),{type:'doughnut',data:{labels:['Approved','Rejected','Cancelled'],datasets:[{data:[rd.approved||0,rd.rejected||0,rd.cancelled||0],backgroundColor:['#21C45D','#DC3848','#6c757d']}]}});</script>