<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="fw-bold mb-0">User Management</h1>
    <p class="text-muted mb-0" style="font-size:13.5px">Manage all system users and their roles.</p>
  </div>
  <a href="<?= route_url('users', 'create') ?>" class="btn btn-primary d-flex align-items-center gap-2">
    <i class="bi bi-person-plus-fill"></i> Add New User
  </a>
</div>

<!-- ─── Filter Bar ───────────────────────────────────────────── -->
<div class="card mb-4">
  <div class="card-body py-3">
    <form class="row g-2 align-items-end" method="GET">
      <input type="hidden" name="page" value="users">

      <div class="col-12 col-md-4">
        <label class="form-label">Search</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input
            name="search"
            class="form-control"
            placeholder="Name, email, username..."
            value="<?= e($filters['search'] ?? '') ?>"
          >
        </div>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <?php foreach (['active', 'inactive', 'suspended'] as $s): ?>
          <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
            <?= ucfirst($s) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select">
          <option value="">All Departments</option>
          <?php foreach ($departments as $dept): ?>
          <option value="<?= $dept['id'] ?>" <?= ($filters['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
            <?= e($dept['department_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1">
          <i class="bi bi-funnel me-1"></i> Filter
        </button>
        <a href="<?= route_url('users') ?>" class="btn btn-light">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- ─── Users Table ──────────────────────────────────────────── -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>User</th>
          <th>Email</th>
          <th>Role</th>
          <th>Department</th>
          <th>Status</th>
          <th style="width:120px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="color:var(--text-muted)"><?= $u['id'] ?></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="avatar-circle" style="width:30px;height:30px;font-size:12px">
                <?= strtoupper(substr($u['full_name'] ?? 'U', 0, 1)) ?>
              </div>
              <span class="fw-medium"><?= e($u['full_name']) ?></span>
            </div>
          </td>
          <td style="color:var(--text-sub)"><?= e($u['email']) ?></td>
          <td>
            <?php foreach (explode(',', $u['roles'] ?? '') as $r): ?>
              <?php if (trim($r)): ?>
              <span class="badge" style="background:var(--primary-soft);color:var(--primary);font-weight:500">
                <?= e(trim($r)) ?>
              </span>
              <?php endif; ?>
            <?php endforeach; ?>
          </td>
          <td style="color:var(--text-sub)"><?= e($u['department_name'] ?? '—') ?></td>
          <td><?= status_badge($u['status'], 'resource') ?></td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= route_url('users', 'show', ['id' => $u['id']]) ?>"
                 class="btn btn-sm btn-light" title="View">
                <i class="bi bi-eye"></i>
              </a>
              <a href="<?= route_url('users', 'edit', ['id' => $u['id']]) ?>"
                 class="btn btn-sm btn-light" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>

        <?php if (empty($users)): ?>
        <tr>
          <td colspan="7" class="text-center py-5" style="color:var(--text-muted)">
            <i class="bi bi-people d-block mb-2" style="font-size:2rem"></i>
            No users found matching your criteria.
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ─── Pagination ───────────────────────────────────────────── -->
<?php require VIEW_PATH . '/partials/pagination.php'; ?>
