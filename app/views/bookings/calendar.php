<div class="mb-4"><h1 class="h3 fw-bold">Resource Calendar</h1></div>

<form class="row g-2 mb-4" method="GET">
  <input type="hidden" name="page" value="bookings">
  <input type="hidden" name="action" value="calendar">
  <div class="col-md-4">
    <select name="resource_id" class="form-select">
      <option value="">All Resources</option>
      <?php foreach($resources as $r): ?>
        <option value="<?= $r['id'] ?>" <?= ($filters['resource_id']??'')==$r['id']?'selected':'' ?>>
          <?= e($r['resource_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><button class="btn btn-primary">Filter</button></div>
</form>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<div class="card p-3">
  <div id="calendar"></div>
</div>

<?php
$currentUserId = Auth::id();
$isAdmin = Auth::isAdmin();
?>
<script>
const currentUserId = <?= (int)$currentUserId ?>;
const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

const events = <?= json_encode(array_map(function($b) use ($currentUserId, $isAdmin) {
    $isOwner = (int)$b['user_id'] === (int)$currentUserId;
    $canClick = $isOwner || $isAdmin;
    return [
        'id'    => $b['id'],
        'title' => $b['resource_name'] . ' — ' . ($b['user_name'] ?? ''),
        'start' => $b['start_datetime'],
        'end'   => $b['end_datetime'],
        'color' => match($b['status']) {
            'approved'  => $isOwner || $isAdmin ? '#0d6efd' : '#6c9bd2',
            'pending'   => '#ffc107',
            'cancelled' => '#dc3545',
            'completed' => '#6c757d',
            default     => '#0d6efd'
        },
        'url'           => $canClick ? 'index.php?page=bookings&action=show&id=' . $b['id'] : null,
        'extendedProps' => ['canClick' => $canClick],
    ];
}, $events)) ?>;

document.addEventListener('DOMContentLoaded', function () {
    const cal = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: events,
        height: 650,
        eventClick: function (info) {
            if (!info.event.extendedProps.canClick) {
                info.jsEvent.preventDefault();
                return;
            }
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        },
        eventDidMount: function (info) {
            if (!info.event.extendedProps.canClick) {
                info.el.style.cursor = 'default';
                info.el.title = 'Booked by another user';
            }
        }
    });
    cal.render();
});
</script>
