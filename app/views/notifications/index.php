<div class="d-flex justify-content-between mb-4"><h1 class="h3 fw-bold">Notifications</h1>
<form method="POST" action="<?= url('index.php?page=notifications&action=mark-all-read') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-primary">Mark All Read</button></form></div>
<div class="row g-3"><?php foreach($notifications as $n): ?>
<div class="col-md-6"><div class="card notification-card <?= $n['is_read']?'':'border-primary' ?>"><div class="card-body">
<div class="d-flex justify-content-between"><strong><?= e($n['title']) ?></strong><span class="badge bg-light text-dark"><?= e($n['type']) ?></span></div>
<p class="mb-1 small"><?= e($n['message']) ?></p><small class="text-muted"><?= format_datetime($n['created_at']) ?></small>
<?php if(!$n['is_read']): ?><form method="POST" action="<?= url('index.php?page=notifications&action=mark-read&id='.$n['id']) ?>" class="mt-2"><?= csrf_field() ?><button class="btn btn-sm btn-link p-0">Mark as read</button></form><?php endif; ?>
</div></div></div><?php endforeach; ?></div>