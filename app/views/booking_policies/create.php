<div class="container-fluid px-4 py-4">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0 text-gradient"><i class="bi bi-shield-plus me-2 text-primary"></i><?= e(__('Add Booking Policy')) ?></h1>
      <p class="text-muted mb-0"><?= e(__('Create a new custom booking rule policy set for resource categories')) ?></p>
    </div>
    <a href="<?= url('index.php?page=booking-policies') ?>" class="btn btn-outline-secondary rounded-pill">
      <i class="bi bi-arrow-left me-1"></i><?= e(__('Back to Policies')) ?>
    </a>
  </div>

  <div class="row g-4">
    <!-- Form Card -->
    <div class="col-lg-8">
      <form id="policyForm" method="POST" action="<?= url('index.php?page=booking-policies&action=store') ?>" class="card shadow-premium border-0 rounded-4 p-4">
        <?= csrf_field() ?>

        <div class="row g-3">
          <!-- Policy Name -->
          <div class="col-md-6">
            <label class="form-label fw-medium"><?= e(__('Policy Name')) ?></label>
            <input name="policy_name" id="policyName" class="form-control rounded-3 shadow-sm" placeholder="e.g. Study Room Standard Policy" required>
          </div>

          <!-- Category Selection -->
          <div class="col-md-6">
            <label class="form-label fw-medium"><?= e(__('Resource Category')) ?></label>
            <select name="category_id" id="policyCategory" class="form-select rounded-3 shadow-sm" required>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" data-name="<?= e($c['category_name']) ?>"><?= e($c['category_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Max Duration Slider -->
          <div class="col-md-6 my-3">
            <label class="form-label fw-medium d-flex justify-content-between">
              <span><?= e(__('Max Duration per Booking')) ?></span>
              <span class="text-primary fw-bold" id="maxDurationVal">2.0 hrs</span>
            </label>
            <input type="range" class="form-range" min="0.5" max="12" step="0.5" name="max_duration_hours" id="maxDuration" value="2.0">
          </div>

          <!-- Weekly Quota Slider -->
          <div class="col-md-6 my-3">
            <label class="form-label fw-medium d-flex justify-content-between">
              <span><?= e(__('Weekly Booking Quota')) ?></span>
              <span class="text-primary fw-bold" id="weeklyQuotaVal">5 bookings</span>
            </label>
            <input type="range" class="form-range" min="1" max="20" step="1" name="weekly_quota" id="weeklyQuota" value="5">
          </div>

          <!-- Peak Limit Slider -->
          <div class="col-md-6 my-3">
            <label class="form-label fw-medium d-flex justify-content-between">
              <span><?= e(__('Max Peak Hours Slots per Week')) ?></span>
              <span class="text-primary fw-bold" id="peakLimitVal">2 slots</span>
            </label>
            <input type="range" class="form-range" min="0" max="10" step="1" name="max_peak_slots_per_week" id="peakLimit" value="2">
          </div>

          <!-- Cancellation Deadline Slider -->
          <div class="col-md-6 my-3">
            <label class="form-label fw-medium d-flex justify-content-between">
              <span><?= e(__('Cancellation Deadline')) ?></span>
              <span class="text-primary fw-bold" id="cancelDeadlineVal">24 hrs</span>
            </label>
            <input type="range" class="form-range" min="1" max="72" step="1" name="cancellation_deadline_hours" id="cancelDeadline" value="24">
          </div>

          <!-- Requires Approval Option -->
          <div class="col-md-6">
            <label class="form-label fw-medium"><?= e(__('Requires Lecturer Approval')) ?></label>
            <select name="requires_approval" id="requiresApproval" class="form-select rounded-3 shadow-sm">
              <option value="0">No (Auto-approves if criteria met)</option>
              <option value="1">Yes (Always requires manual signoff)</option>
            </select>
          </div>

          <!-- Auto Approval Option -->
          <div class="col-md-6" id="autoApprovalWrapper">
            <label class="form-label fw-medium"><?= e(__('Auto Approval Enabled')) ?></label>
            <select name="auto_approval_enabled" id="autoApproval" class="form-select rounded-3 shadow-sm">
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
          <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-premium">
            <i class="bi bi-check-lg me-1"></i><?= e(__('Create Policy')) ?>
          </button>
          <a href="<?= url('index.php?page=booking-policies') ?>" class="btn btn-outline-secondary rounded-pill px-4 py-2">
            <?= e(__('Cancel')) ?>
          </a>
        </div>
      </form>
    </div>

    <!-- Live Preview Card -->
    <div class="col-lg-4">
      <div class="card shadow-premium border-0 rounded-4 p-4 bg-light-premium h-100 d-flex flex-column justify-content-between">
        <div>
          <h5 class="fw-bold mb-3"><i class="bi bi-eye text-primary me-2"></i>Live Rule Preview</h5>
          <p class="text-muted small">See how these policy conditions will be written and enforced in the system live:</p>
          
          <div class="card bg-white border rounded-3 p-3 mb-3 shadow-sm">
            <h6 class="fw-bold text-dark mb-2" id="previewTitle">Standard Policy</h6>
            <div class="text-muted small" id="previewBody">
              Loading policy summary...
            </div>
          </div>
        </div>

        <div class="alert alert-info py-2 px-3 mb-0 rounded-3 small">
          <i class="bi bi-info-circle-fill me-1"></i>
          These values serve as limits for standard bookings. Lecturers have double allocations automatically.
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sliders = [
        { el: document.getElementById('maxDuration'), val: document.getElementById('maxDurationVal'), suffix: ' hrs' },
        { el: document.getElementById('weeklyQuota'), val: document.getElementById('weeklyQuotaVal'), suffix: ' bookings' },
        { el: document.getElementById('peakLimit'), val: document.getElementById('peakLimitVal'), suffix: ' slots' },
        { el: document.getElementById('cancelDeadline'), val: document.getElementById('cancelDeadlineVal'), suffix: ' hrs before' }
    ];

    const policyName = document.getElementById('policyName');
    const policyCategory = document.getElementById('policyCategory');
    const requiresApproval = document.getElementById('requiresApproval');
    const autoApproval = document.getElementById('autoApproval');
    const autoApprovalWrapper = document.getElementById('autoApprovalWrapper');

    const previewTitle = document.getElementById('previewTitle');
    const previewBody = document.getElementById('previewBody');

    // Update Slider Labels and Live Preview
    function updatePreview() {
        // Slider value labels
        sliders.forEach(s => {
            s.val.innerText = parseFloat(s.el.value).toFixed(s.el.step.includes('.') ? 1 : 0) + s.suffix;
        });

        // Toggle auto approval wrapper based on requires approval setting
        if (requiresApproval.value === '0') {
            autoApproval.value = '1';
            autoApprovalWrapper.style.opacity = '0.5';
            autoApprovalWrapper.style.pointerEvents = 'none';
        } else {
            autoApprovalWrapper.style.opacity = '1';
            autoApprovalWrapper.style.pointerEvents = 'auto';
        }

        const name = policyName.value.trim() || 'Custom Policy';
        const categoryOpt = policyCategory.options[policyCategory.selectedIndex];
        const categoryName = categoryOpt ? categoryOpt.getAttribute('data-name') : 'Resource';

        previewTitle.innerText = name;

        const maxDur = parseFloat(document.getElementById('maxDuration').value).toFixed(1);
        const quota = document.getElementById('weeklyQuota').value;
        const peak = document.getElementById('peakLimit').value;
        const deadline = document.getElementById('cancelDeadline').value;
        const reqAppr = requiresApproval.value === '1';
        const autoAppr = autoApproval.value === '1';

        let desc = `When a user books a resource in the <strong>${categoryName}</strong> category, the following rules apply:`;
        desc += `<ul class="ps-3 mt-2 mb-0">`;
        desc += `<li>The maximum booking duration is <strong>${maxDur} hours</strong>.</li>`;
        desc += `<li>Each user can make up to <strong>${quota} bookings</strong> per week.</li>`;
        desc += `<li>A maximum of <strong>${peak} peak slot bookings</strong> per week is allowed.</li>`;
        desc += `<li>Cancellations are allowed up to <strong>${deadline} hours</strong> before start time.</li>`;
        if (reqAppr) {
            desc += `<li>Bookings <strong>require approval</strong> from a Lecturer/Approver.`;
            if (autoAppr) {
                desc += ` (Auto-approves if category guidelines are matched)`;
            }
            desc += `</li>`;
        } else {
            desc += `<li>Bookings will be <strong>automatically approved</strong>.</li>`;
        }
        desc += `</ul>`;

        previewBody.innerHTML = desc;
    }

    // Attach listeners
    sliders.forEach(s => s.el.addEventListener('input', updatePreview));
    [policyName, policyCategory, requiresApproval, autoApproval].forEach(el => el.addEventListener('change', updatePreview));
    policyName.addEventListener('input', updatePreview);

    updatePreview();
});
</script>