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
          } else {
            statusEl.className = 'unavailable small fw-medium';
            statusEl.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + (data.message || 'Not available');
          }
        })
        .catch(() => {
          statusEl.className = 'checking small';
          statusEl.textContent = 'Could not check availability.';
        });
    }

    [resourceSelect, dateInput, startInput, endInput].forEach(el => {
      if (el) el.addEventListener('change', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkAvailability, 400);
      });
    });
  }
});
