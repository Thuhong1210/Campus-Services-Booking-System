<?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
<nav class="mt-4 d-flex align-items-center justify-content-between flex-wrap gap-2" aria-label="Pagination">

  <!-- Result count -->
  <p class="mb-0" style="font-size:13px;color:var(--text-muted)">
    Showing page <strong><?= $pagination['page'] ?></strong> of <strong><?= $pagination['total_pages'] ?></strong>
    &nbsp;·&nbsp; <?= $pagination['total'] ?> total result<?= $pagination['total'] !== 1 ? 's' : '' ?>
  </p>

  <!-- Page links -->
  <ul class="pagination mb-0">
    <!-- Previous -->
    <li class="page-item <?= $pagination['page'] <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= e($pagination['base_url']) ?>&p=<?= $pagination['page'] - 1 ?>" aria-label="Previous">
        <i class="bi bi-chevron-left" style="font-size:11px"></i>
      </a>
    </li>

    <!-- Page Numbers -->
    <?php
    $current    = $pagination['page'];
    $total      = $pagination['total_pages'];
    $window     = 2; // pages each side of current
    $showFirst  = $current > $window + 1;
    $showLast   = $current < $total - $window;

    if ($showFirst):
    ?>
    <li class="page-item">
      <a class="page-link" href="<?= e($pagination['base_url']) ?>&p=1">1</a>
    </li>
    <?php if ($current > $window + 2): ?>
    <li class="page-item disabled"><span class="page-link">…</span></li>
    <?php endif; ?>
    <?php endif; ?>

    <?php for ($i = max(1, $current - $window); $i <= min($total, $current + $window); $i++): ?>
    <li class="page-item <?= $i === $current ? 'active' : '' ?>">
      <a class="page-link" href="<?= e($pagination['base_url']) ?>&p=<?= $i ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>

    <?php if ($showLast): ?>
    <?php if ($current < $total - $window - 1): ?>
    <li class="page-item disabled"><span class="page-link">…</span></li>
    <?php endif; ?>
    <li class="page-item">
      <a class="page-link" href="<?= e($pagination['base_url']) ?>&p=<?= $total ?>"><?= $total ?></a>
    </li>
    <?php endif; ?>

    <!-- Next -->
    <li class="page-item <?= $pagination['page'] >= $total ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= e($pagination['base_url']) ?>&p=<?= $pagination['page'] + 1 ?>" aria-label="Next">
        <i class="bi bi-chevron-right" style="font-size:11px"></i>
      </a>
    </li>
  </ul>

</nav>
<?php endif; ?>