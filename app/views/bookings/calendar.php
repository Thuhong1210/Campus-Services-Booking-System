<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold mb-0">Resource Calendar</h1>
  <div class="d-flex gap-3 align-items-center">
    <span class="d-flex align-items-center gap-1 small"><span style="width:12px;height:12px;background:#0d6efd;border-radius:3px;display:inline-block"></span> My Booking</span>
    <span class="d-flex align-items-center gap-1 small"><span style="width:12px;height:12px;background:#6c9bd2;border-radius:3px;display:inline-block"></span> Others</span>
    <span class="d-flex align-items-center gap-1 small"><span style="width:12px;height:12px;background:#ffc107;border-radius:3px;display:inline-block"></span> Pending</span>
    <span class="d-flex align-items-center gap-1 small"><span style="width:12px;height:12px;background:#dc3545;border-radius:3px;display:inline-block"></span> Cancelled</span>
    <span class="d-flex align-items-center gap-1 small"><span style="width:12px;height:12px;background:#6c757d;border-radius:3px;display:inline-block"></span> Completed</span>
  </div>
</div>

<div class="card shadow-sm mb-4" style="border-radius:12px">
  <div class="card-body py-3">
    <form class="row g-2 align-items-center mb-0" method="GET">
      <input type="hidden" name="page" value="bookings">
      <input type="hidden" name="action" value="calendar">
      <div class="col-auto">
        <label class="col-form-label fw-semibold small">Filter by resource:</label>
      </div>
      <div class="col-md-4">
        <select name="resource_id" class="form-select form-select-sm">
          <option value="">All Resources</option>
          <?php foreach($resources as $r): ?>
            <option value="<?= $r['id'] ?>" <?= ($filters['resource_id']??'')==$r['id']?'selected':'' ?>>
              <?= e($r['resource_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
      </div>
    </form>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<style>
  .fc { font-family: inherit; }
  .fc .fc-toolbar-title { font-size: 1.1rem; font-weight: 700; }
  .fc .fc-button { border-radius: 8px !important; font-size: 13px !important; padding: 4px 12px !important; }
  .fc .fc-button-primary { background: #0d6efd !important; border-color: #0d6efd !important; }
  .fc .fc-button-primary:not(.fc-button-active):hover { background: #0b5ed7 !important; }
  .fc .fc-button-active { background: #084298 !important; }
  .fc .fc-col-header-cell { background: #f8f9fa; font-weight: 600; font-size: 13px; }
  .fc .fc-timegrid-slot { height: 40px !important; }
  .fc .fc-event { border-radius: 6px !important; border: none !important; font-size: 12px !important; padding: 2px 6px !important; }
  .fc .fc-daygrid-event { border-radius: 6px !important; }
  .fc-theme-standard td, .fc-theme-standard th { border-color: #e9ecef; }
</style>

<div class="card shadow-sm" style="border-radius:12px;overflow:hidden">
  <div class="card-body p-3">
    <div id="calendar"></div>
  </div>
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
        'extendedProps' => ['canClick' => $canClick, 'status' => $b['status']],
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
        buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day' },
        events: events,
        height: 680,
        nowIndicator: true,
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: false,
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
