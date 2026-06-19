<?php
// Flash messages: success | error → maps to alert-success | alert-danger
foreach (Flash::all() as $type => $messages):
    $alertClass = match ($type) {
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
        default   => 'alert-success',
    };
    $iconClass = match ($type) {
        'error'   => 'bi-exclamation-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info'    => 'bi-info-circle-fill',
        default   => 'bi-check-circle-fill',
    };
    foreach ($messages as $msg):
?>
<div class="alert <?= $alertClass ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
  <i class="bi <?= $iconClass ?> flex-shrink-0"></i>
  <span><?= e($msg) ?></span>
  <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php
    endforeach;
endforeach;
?>