<div class="page-header mb-4">
  <h1 class="h3 fw-bold">Browse Resources</h1>
  <p class="text-muted mb-0">Search and book shared campus facilities at IS-VNU.</p>
</div>

<form class="card p-3 mb-4" method="GET">
  <input type="hidden" name="page" value="resources">
  <input type="hidden" name="action" value="browse">
  <div class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small">Keyword</label><input name="search" class="form-control" placeholder="Search by name or code..." value="<?= e($filters['search'] ?? '') ?>"></div>
    <div class="col-md-3"><label class="form-label small">Category</label><select name="category_id" class="form-select"><option value="">All Categories</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($filters['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label small">Location</label><input name="location" class="form-control" placeholder="Building, floor..." value="<?= e($filters['location'] ?? '') ?>"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button></div>
  </div>
</form>

<div class="row g-4">
  <?php foreach ($resources as $r): ?>
  <div class="col-md-6 col-lg-4">
    <div class="card resource-card h-100 shadow-sm">
      <div class="card-img-top resource-card-img d-flex align-items-center justify-content-center bg-light"><i class="bi bi-building text-primary" style="font-size:2.5rem"></i></div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
          <h5 class="card-title mb-0"><?= e($r['resource_name']) ?></h5>
          <?= status_badge($r['status'], 'resource') ?>
        </div>
        <p class="text-muted small mb-2"><i class="bi bi-tag me-1"></i><?= e($r['category_name'] ?? '') ?></p>
        <p class="small mb-1"><i class="bi bi-geo-alt me-1"></i><?= e($r['location']) ?></p>
        <p class="small mb-3"><i class="bi bi-people me-1"></i>Capacity: <?= (int) $r['capacity'] ?></p>
        <div class="d-flex gap-2">
          <a href="<?= route_url('resources', 'show', ['id' => $r['id']]) ?>" class="btn btn-sm btn-outline-primary flex-fill">Details</a>
          <a href="<?= route_url('bookings', 'create', ['resource_id' => $r['id']]) ?>" class="btn btn-sm btn-primary flex-fill">Book Now</a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($resources)): ?>
  <div class="col-12"><div class="empty-state card p-5 text-center text-muted"><i class="bi bi-inbox display-4 d-block mb-3"></i>No resources match your search.</div></div>
  <?php endif; ?>
</div>
