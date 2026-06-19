<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Login') ?> | <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
  <div class="auth-card card" style="max-width:440px;width:100%">
    <div class="card-top-bar"></div>
    <div class="card-body p-4 p-md-5">
      <div class="text-center mb-4">
        <div class="auth-icon mx-auto mb-3"><i class="bi bi-mortarboard-fill"></i></div>
        <h1 class="h4 fw-bold">IS-VNU Services</h1>
        <p class="text-muted small">Campus Services Booking System</p>
      </div>
      <?php require VIEW_PATH . '/partials/flash.php'; ?>
      <?= $content ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>