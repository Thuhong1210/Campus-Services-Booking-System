<div class="mb-4"><h1 class="h3 fw-bold">Approval Request</h1></div>
<div class="card p-4 mb-4"><p><strong>Student:</strong> <?= e($booking['user_name']) ?></p><p><strong>Resource:</strong> <?= e($booking['resource_name']) ?></p><p><strong>Time:</strong> <?= format_datetime($booking['start_datetime']) ?> - <?= format_datetime($booking['end_datetime']) ?></p><p><strong>Purpose:</strong> <?= e($booking['purpose']) ?></p></div>
<div class="d-flex gap-2">
<form method="POST" action="<?= url('index.php?page=approvals&action=approve&id='.$booking['id']) ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="comment" value="Approved"><button class="btn btn-success">Approve</button></form>
<form method="POST" action="<?= url('index.php?page=approvals&action=reject&id='.$booking['id']) ?>" class="d-inline flex-grow-1"><?= csrf_field() ?>
<input name="comment" class="form-control d-inline-block w-auto" placeholder="Rejection reason" required><button class="btn btn-danger ms-2">Reject</button></form>
</div>