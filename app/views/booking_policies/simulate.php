<div class="container-fluid px-4 py-4">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0 text-gradient"><i class="bi bi-cpu me-2"></i><?= e(__('Policy Rule Simulator')) ?></h1>
      <p class="text-muted mb-0"><?= e(__('Test and validate booking scenarios against your active booking policies')) ?></p>
    </div>
    <a href="<?= url('index.php?page=booking-policies') ?>" class="btn btn-outline-secondary rounded-pill">
      <i class="bi bi-arrow-left me-1"></i><?= e(__('Back to Policies')) ?>
    </a>
  </div>

  <div class="row g-4">
    <!-- Simulator Form Card -->
    <div class="col-lg-6">
      <div class="card shadow-premium border-0 rounded-4 overflow-hidden h-100">
        <div class="card-header bg-dark text-white py-3">
          <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-sliders me-2"></i><?= e(__('Simulation Parameters')) ?></h5>
        </div>
        <div class="card-body p-4">
          <form id="simulationForm" method="POST" action="<?= url('index.php?page=booking-policies&action=simulate') ?>">
            <?= csrf_field() ?>

            <!-- User Selector -->
            <div class="mb-3">
              <label class="form-label fw-medium"><i class="bi bi-person me-1 text-primary"></i><?= e(__('Simulated User')) ?></label>
              <select name="user_id" id="simUser" class="form-select rounded-3 shadow-sm" required>
                <option value=""><?= e(__('Select a User (Simulated Role)')) ?></option>
                <?php foreach ($users as $u): ?>
                  <option value="<?= $u['id'] ?>">
                    <?= e($u['full_name']) ?> (<?= e($u['roles'] ?? 'Student') ?>)
                  </option>
                <?php endphp ?>
              </select>
            </div>

            <!-- Resource Selector -->
            <div class="mb-3">
              <label class="form-label fw-medium"><i class="bi bi-door-closed me-1 text-success"></i><?= e(__('Simulated Resource')) ?></label>
              <select name="resource_id" id="simResource" class="form-select rounded-3 shadow-sm" required>
                <option value=""><?= e(__('Select a Resource')) ?></option>
                <?php foreach ($resources as $r): ?>
                  <option value="<?= $r['id'] ?>" data-category="<?= e($r['category_id']) ?>">
                    <?= e($r['resource_name']) ?> [<?= e($r['resource_code']) ?>]
                  </option>
                <?php endphp ?>
              </select>
            </div>

            <!-- Datetime Range -->
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label fw-medium"><i class="bi bi-calendar-play me-1 text-warning"></i><?= e(__('Start Datetime')) ?></label>
                <input type="datetime-local" name="start_datetime" id="simStart" class="form-control rounded-3 shadow-sm" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium"><i class="bi bi-calendar-stop me-1 text-danger"></i><?= e(__('End Datetime')) ?></label>
                <input type="datetime-local" name="end_datetime" id="simEnd" class="form-control rounded-3 shadow-sm" required>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold shadow-premium" id="btnRunSimulation">
              <i class="bi bi-play-fill me-1"></i><?= e(__('Run Policy Check')) ?>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Results Display -->
    <div class="col-lg-6">
      <div class="card shadow-premium border-0 rounded-4 overflow-hidden h-100 bg-light-premium">
        <div class="card-header bg-secondary text-white py-3 d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0 fw-semibold"><i class="bi bi-terminal me-2"></i><?= e(__('Simulation Result')) ?></h5>
          <span class="badge bg-dark rounded-pill py-1 px-3" id="resultBadge">Idle</span>
        </div>
        <div class="card-body p-4 d-flex flex-column justify-content-center align-items-center text-center" id="resultContainer">
          <div class="idle-state">
            <i class="bi bi-cpu-fill text-muted mb-3" style="font-size: 4rem;"></i>
            <h5 class="text-muted fw-medium"><?= e(__('Ready for Simulation')) ?></h5>
            <p class="text-muted mb-0 small"><?= e(__('Select simulation parameters and click "Run Policy Check" to begin testing.')) ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('simulationForm');
    const resultContainer = document.getElementById('resultContainer');
    const resultBadge = document.getElementById('resultBadge');
    const btnSubmit = document.getElementById('btnRunSimulation');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // UI Feedback
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Running...';
        resultBadge.className = 'badge bg-info text-dark rounded-pill py-1 px-3';
        resultBadge.innerText = 'Testing';

        resultContainer.innerHTML = `
            <div class="spinner-border text-primary my-4" style="width: 3rem; height: 3rem;" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">Analyzing rules and booking history database...</p>
        `;

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-play-fill me-1"></i> Run Policy Check';

            if (data.success) {
                resultBadge.className = 'badge bg-success rounded-pill py-1 px-3';
                resultBadge.innerText = 'PASS';
                resultContainer.innerHTML = `
                    <div class="card bg-success-subtle border-0 rounded-4 p-4 w-100 mb-3 shadow-sm text-success">
                        <i class="bi bi-check-circle-fill text-success mb-3" style="font-size: 3rem;"></i>
                        <h4 class="alert-heading fw-bold mb-2">${data.message}</h4>
                        <p class="mb-0 small text-dark">The specified user roles allow booking this resource during the selected times slot without violating any weekly limits, duration caps, or peak hours quota.</p>
                    </div>
                `;
            } else {
                resultBadge.className = 'badge bg-danger rounded-pill py-1 px-3';
                resultBadge.innerText = 'FAIL';
                
                let errorsHtml = '';
                if (data.errors && data.errors.length > 0) {
                    errorsHtml = `<ul class="list-group list-group-flush text-start border rounded-3 mt-3 w-100">`;
                    data.errors.forEach(err => {
                        errorsHtml += `
                            <li class="list-group-item list-group-item-danger d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> ${err}
                            </li>
                        `;
                    });
                    errorsHtml += `</ul>`;
                }

                resultContainer.innerHTML = `
                    <div class="card bg-danger-subtle border-0 rounded-4 p-4 w-100 text-danger shadow-sm">
                        <i class="bi bi-x-circle-fill text-danger mb-3" style="font-size: 3rem;"></i>
                        <h4 class="alert-heading fw-bold mb-2">${data.message}</h4>
                        <p class="mb-0 small text-dark">The simulation detected one or more booking policies violations listed below:</p>
                        ${errorsHtml}
                    </div>
                `;
            }
        })
        .catch(err => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-play-fill me-1"></i> Run Policy Check';
            resultBadge.className = 'badge bg-danger rounded-pill py-1 px-3';
            resultBadge.innerText = 'ERROR';
            resultContainer.innerHTML = `
                <div class="text-danger">
                    <i class="bi bi-exclamation-octagon-fill text-danger mb-2" style="font-size: 3rem;"></i>
                    <h5>Simulation Failed</h5>
                    <p class="small text-muted">An error occurred while communicating with the server.</p>
                </div>
            `;
        });
    });
});
</script>
