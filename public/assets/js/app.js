document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    document.addEventListener('click', (e) => {
      if (window.innerWidth < 992 && sidebar.classList.contains('show') &&
          !sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('show');
      }
    });
  }

  document.querySelectorAll('.alert').forEach(function (alert) {
    setTimeout(() => {
      try { bootstrap.Alert.getOrCreateInstance(alert)?.close(); } catch (_) {}
    }, 5000);
  });

  // Live booking availability check
  const bookingForm = document.getElementById('bookingForm');
  if (bookingForm) {
    const resourceSelect = bookingForm.querySelector('[name="resource_id"]');
    const dateInput = bookingForm.querySelector('[name="booking_date"]');
    const startInput = bookingForm.querySelector('[name="start_time"]');
    const endInput = bookingForm.querySelector('[name="end_time"]');
    const statusEl = document.getElementById('availabilityStatus');
    let debounceTimer;

    function checkAvailability() {
      if (!resourceSelect || !dateInput || !startInput || !endInput || !statusEl) return;
      const rid = resourceSelect.value;
      const date = dateInput.value;
      const start = startInput.value;
      const end = endInput.value;
      if (!rid || !date || !start || !end) return;

      statusEl.className = 'checking small fw-medium';
      statusEl.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Checking availability...';

      const base = typeof APP_BASE !== 'undefined' ? APP_BASE : '';
      const url = `${base}/index.php?page=bookings&action=check-conflict&resource_id=${rid}&booking_date=${date}&start_time=${start}&end_time=${end}`;

      fetch(url)
        .then(r => r.json())
        .then(data => {
          if (data.available) {
            statusEl.className = 'available small fw-medium';
            statusEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + (data.message || 'Available');
            loadEquipmentAddons(rid, `${date} ${start}:00`, `${date} ${end}:00`);
          } else {
            statusEl.className = 'unavailable small fw-medium';
            statusEl.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + (data.message || 'Not available');
            const eqSection = document.getElementById('equipmentAddonsSection');
            if (eqSection) eqSection.style.display = 'none';
          }
        })
        .catch(() => {
          statusEl.className = 'checking small';
          statusEl.textContent = 'Could not check availability.';
        });
    }

    function loadEquipmentAddons(resourceId, start, end) {
      const section = document.getElementById('equipmentAddonsSection');
      const list = document.getElementById('equipmentList');
      if (!section || !list) return;

      const base = typeof APP_BASE !== 'undefined' ? APP_BASE : '';
      const url = `${base}/api.php?endpoint=equipment/available&resource_id=${resourceId}&start_datetime=${encodeURIComponent(start)}&end_datetime=${encodeURIComponent(end)}`;

      fetch(url)
        .then(r => r.json())
        .then(res => {
          if (res.success && res.data && res.data.length > 0) {
            section.style.display = 'block';
            let html = '';
            res.data.forEach(eq => {
              const max = eq.available_qty !== undefined ? eq.available_qty : eq.quantity;
              if (max <= 0) {
                html += `
                  <div class="col-md-6">
                    <div class="p-2 border rounded bg-light opacity-50 d-flex justify-content-between align-items-center">
                      <div>
                        <span class="fw-semibold text-muted small">${eq.equipment_name}</span>
                        <span class="d-block text-danger" style="font-size:11px">Out of stock</span>
                      </div>
                      <input type="number" class="form-control form-control-sm" style="width:70px" disabled value="0">
                    </div>
                  </div>
                `;
              } else {
                html += `
                  <div class="col-md-6">
                    <div class="p-2 border rounded d-flex justify-content-between align-items-center">
                      <div>
                        <span class="fw-semibold small">${eq.equipment_name}</span>
                        <span class="d-block text-muted" style="font-size:11px">Stock: ${max} available</span>
                      </div>
                      <input type="number" name="equipment[${eq.equipment_id}]" class="form-control form-control-sm" style="width:70px"
                             min="0" max="${max}" value="0">
                    </div>
                  </div>
                `;
              }
            });
            list.innerHTML = html;
          } else {
            section.style.display = 'none';
            list.innerHTML = '';
          }
        })
        .catch(() => {
          section.style.display = 'none';
        });
    }

    [resourceSelect, dateInput, startInput, endInput].forEach(el => {
      if (el) el.addEventListener('change', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkAvailability, 400);
      });
    });
  }

  // Real-time notifications count polling
  function pollNotificationsCount() {
    const base = typeof APP_BASE !== 'undefined' ? APP_BASE : '';
    fetch(`${base}/api.php?endpoint=notifications/unread-count`)
      .then(res => res.json())
      .then(data => {
        if (data && data.success) {
          const count = parseInt(data.unread_count || 0);
          
          const topBadges = document.querySelectorAll('.notification-badge');
          topBadges.forEach(badge => {
            badge.textContent = count;
            if (count > 0) {
              badge.classList.remove('d-none');
            } else {
              badge.classList.add('d-none');
            }
          });

          const sideBadges = document.querySelectorAll('.sidebar-notification-badge');
          sideBadges.forEach(badge => {
            badge.textContent = count;
            if (count > 0) {
              badge.classList.remove('d-none');
            } else {
              badge.classList.add('d-none');
            }
          });
        }
      })
      .catch(() => {});
  }
  
  if (document.querySelector('.notification-badge') || document.querySelector('.sidebar-notification-badge')) {
    setInterval(pollNotificationsCount, 30000);
    pollNotificationsCount();
  }
});
