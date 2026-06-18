<?php require VIEW_PATH . '/layouts/header.php'; ?>
<div class="d-flex" id="wrapper">
  <?php require VIEW_PATH . '/layouts/sidebar.php'; ?>
  <div id="page-content" class="flex-grow-1">
    <?php require VIEW_PATH . '/layouts/topbar.php'; ?>
    <main class="main-content p-3 p-lg-4">
      <?php require VIEW_PATH . '/partials/flash.php'; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<?php require VIEW_PATH . '/layouts/footer.php'; ?>
