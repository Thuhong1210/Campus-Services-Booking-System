<?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
<nav><ul class="pagination justify-content-center">
  <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
    <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
      <a class="page-link" href="<?= e($pagination['base_url']) ?>&p=<?= $i ?>"><?= $i ?></a>
    </li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>