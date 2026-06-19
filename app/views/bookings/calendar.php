<?php
$currentUserId = Auth::id();
$isAdmin       = Auth::isAdmin();
?>

<!-- ─── FullCalendar CSS ──────────────────────────────────────── -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h1 class="fw-bold mb-0">Resource Calendar</h1>
    <p class="text-muted mb-0" style="font-size:13.5px">View all approved and pending bookings across campus resources.</p>
  </div>
  <a href="<?= route_url('bookings', 'create') ?>" class="btn btn-primary d-flex align-items-center gap-2">
    <i class="bi bi-calendar-plus-fill"></i> New Booking
  </a>
</div>

<!-- ─── Filter + Legend Bar ──────────────────────────────────── -->
<div class="card mb-4">
  <div class="card-body py-3">
    <div class="d-flex align-items-center flex-wrap gap-3 justify-content-between">

      <!-- Resource Filter -->
      <form class="d-flex align-items-center gap-2 flex-wrap" method="GET">
        <input type="hidden" name="page"   value="bookings">
        <input type="hidden" name="action" value="calendar">
        <label class="form-label mb-0 fw-medium" style="font-size:13.5px;white-space:nowrap">
          Filter by resource:
        </label>
        <select name="resource_id" class="form-select form-select-sm" style="min-width:200px;max-width:280px">
          <option value="">All Resources</option>
          <?php foreach ($resources as $r): ?>
          <option value="<?= $r['id'] ?>" <?= ($filters['resource_id'] ?? '') == $r['id'] ? 'selected' : '' ?>>
            <?= e($r['resource_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">
          <i class="bi bi-funnel me-1"></i>Filter
        </button>
        <?php if (!empty($filters['resource_id'])): ?>
        <a href="<?= route_url('bookings', 'calendar') ?>" class="btn btn-sm btn-light">Reset</a>
        <?php endif; ?>
      </form>

      <!-- Legend -->
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <?php $legend = [
          ['color' => '#4361ee', 'label' => 'My Booking'],
          ['color' => '#93aee8', 'label' => 'Others'],
          ['color' => '#f59e0b', 'label' => 'Pending'],
          ['color' => '#ef4444', 'label' => 'Cancelled'],
          ['color' => '#9499b2', 'label' => 'Completed'],
        ]; ?>
        <?php foreach ($legend as $l): ?>
        <span class="d-flex align-items-center gap-1" style="font-size:12.5px;color:var(--text-sub)">
          <span style="width:10px;height:10px;background:<?= $l['color'] ?>;border-radius:3px;display:inline-block;flex-shrink:0"></span>
          <?= $l['label'] ?>
        </span>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<!-- ─── Calendar Card ────────────────────────────────────────── -->
<div class="card" style="overflow:hidden">
  <div class="card-body p-3">
    <div id="resourceCalendar"></div>
  </div>
</div>

<!-- ─── FullCalendar Styles ──────────────────────────────────── -->
<style>
/* Global font */
.fc { font-family: 'Inter', system-ui, sans-serif !important; }

/* Toolbar */
.fc .fc-toolbar { padding: 10px 4px 6px; }
.fc .fc-toolbar-title {
  font-size: 1rem !important;
  font-weight: 700 !important;
  color: var(--text-main) !important;
}

/* Buttons */
.fc .fc-button {
  border-radius: var(--radius-sm) !important;
  font-size: 12.5px !important;
  font-weight: 500 !important;
  padding: 5px 12px !important;
  box-shadow: none !important;
  border: var(--border-thin) !important;
}
.fc .fc-button-primary {
  background: var(--bg-white) !important;
  border-color: var(--border) !important;
  color: var(--text-sub) !important;
}
.fc .fc-button-primary:hover {
  background: var(--bg-muted) !important;
  border-color: var(--border) !important;
  color: var(--text-main) !important;
}
.fc .fc-button-active,
.fc .fc-button-primary:not(.fc-button-active):focus {
  background: var(--primary) !important;
  border-color: var(--primary) !important;
  color: #fff !important;
}
.fc .fc-today-button {
  background: var(--primary-soft) !important;
  border-color: var(--border) !important;
  color: var(--primary) !important;
  font-weight: 600 !important;
}

/* Header cells */
.fc .fc-col-header-cell {
  background: var(--bg-muted) !important;
  font-weight: 600 !important;
  font-size: 12px !important;
  color: var(--text-muted) !important;
  text-transform: uppercase;
  letter-spacing: .04em;
  padding: 8px 0 !important;
  border-color: var(--border) !important;
}
.fc .fc-col-header-cell a { color: inherit !important; text-decoration: none; }

/* Day cells */
.fc .fc-daygrid-day.fc-day-today,
.fc .fc-timegrid-col.fc-day-today {
  background: #fafbff !important;
}

/* Day numbers */
.fc .fc-daygrid-day-number {
  font-size: 12.5px;
  color: var(--text-sub);
  padding: 4px 8px !important;
}
.fc .fc-day-today .fc-daygrid-day-number {
  color: var(--primary);
  font-weight: 700;
}

/* Time slots */
.fc .fc-timegrid-slot { height: 42px !important; }
.fc .fc-timegrid-slot-label {
  font-size: 11.5px !important;
  color: var(--text-muted) !important;
  font-weight: 500;
}

/* Grid borders */
.fc-theme-standard td,
.fc-theme-standard th,
.fc-theme-standard .fc-scrollgrid {
  border-color: var(--border) !important;
}

/* Events */
.fc .fc-event {
  border: none !important;
  border-radius: var(--radius-sm) !important;
  font-size: 12px !important;
  font-weight: 500 !important;
  padding: 2px 6px !important;
  box-shadow: none !important;
}
.fc .fc-event:hover { opacity: .88; }
.fc .fc-event-title { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Now indicator */
.fc .fc-timegrid-now-indicator-line {
  border-color: var(--primary) !important;
  border-width: 2px !important;
}
.fc .fc-timegrid-now-indicator-arrow {
  border-top-color: var(--primary) !important;
  border-bottom-color: var(--primary) !important;
}

/* Daygrid more link */
.fc .fc-daygrid-more-link {
  font-size: 11.5px;
  color: var(--primary);
  font-weight: 600;
}

/* Month view more events */
.fc .fc-popover {
  border: var(--border-thin) !important;
  border-radius: var(--radius) !important;
  box-shadow: var(--shadow) !important;
}
.fc .fc-popover-header {
  background: var(--bg-muted) !important;
  font-size: 12.5px !important;
  font-weight: 600 !important;
}
</style>

<!-- ─── Calendar Script ───────────────────────────────────────── -->
<script>
(function () {

const MY_ID    = <?= (int) $currentUserId ?>;
const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;

const rawEvents = <?= json_encode(array_map(function ($b) use ($currentUserId, $isAdmin) {
    $isOwner   = (int) $b['user_id'] === (int) $currentUserId;
    $canClick  = $isOwner || $isAdmin;

    $colorMap = [
        'approved'  => $isOwner || $isAdmin ? '#4361ee' : '#93aee8',
        'pending'   => '#f59e0b',
        'cancelled' => '#ef4444',
        'completed' => '#9499b2',
    ];

    return [
        'id'    => $b['id'],
        'title' => ($b['resource_name'] ?? '') . ' · ' . ($b['user_name'] ?? ''),
        'start' => $b['start_datetime'],
        'end'   => $b['end_datetime'],
        'color' => $colorMap[$b['status']] ?? '#4361ee',
        'url'   => $canClick
            ? 'index.php?page=bookings&action=show&id=' . $b['id']
            : null,
        'extendedProps' => [
            'canClick'   => $canClick,
            'status'     => $b['status'],
            'purpose'    => $b['purpose'] ?? '',
            'resource'   => $b['resource_name'] ?? '',
            'user'       => $b['user_name'] ?? '',
        ],
    ];
}, $events)) ?>;

document.addEventListener('DOMContentLoaded', function () {

  const el = document.getElementById('resourceCalendar');
  if (!el) return;

  const cal = new FullCalendar.Calendar(el, {
    initialView : 'timeGridWeek',
    headerToolbar: {
      left  : 'prev,next today',
      center: 'title',
      right : 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    buttonText: {
      today: 'Today',
      month: 'Month',
      week : 'Week',
      day  : 'Day',
    },
    events       : rawEvents,
    height       : 680,
    nowIndicator : true,
    slotMinTime  : '06:00:00',
    slotMaxTime  : '22:00:00',
    allDaySlot   : false,
    dayMaxEvents : 3,         // +N more link in month view
    firstDay     : 1,         // Monday first

    // Tooltip on hover
    eventDidMount(info) {
      const p = info.event.extendedProps;
      if (!p.canClick) {
        info.el.style.cursor = 'default';
      }
      // Simple title tooltip
      info.el.title = [
        p.resource,
        p.user,
        p.purpose ? '📝 ' + p.purpose : '',
        '● ' + p.status.toUpperCase(),
      ].filter(Boolean).join('\n');
    },

    // Block click on others' bookings
    eventClick(info) {
      if (!info.event.extendedProps.canClick) {
        info.jsEvent.preventDefault();
        return;
      }
      if (info.event.url) {
        info.jsEvent.preventDefault();
        window.location.href = info.event.url;
      }
    },
  });

  cal.render();
});

})();
</script>
