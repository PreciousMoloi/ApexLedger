/* APEXLedger shared app behaviour
   - Auto logout after 30 minutes inactivity (NFR: Security/Availability)
   - Dark mode persistence
   - Mobile sidebar toggle
*/
(function () {
  // ---- Mobile sidebar ----
  window.toggleSidebar = function () {
    var sb = document.getElementById('sidebar');
    if (sb) sb.classList.toggle('open');
  };

  // ---- Dark mode ----
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }
  window.toggleTheme = function () {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
  };

  // ---- Auto logout after 30 minutes of inactivity ----
  if (document.body.getAttribute('data-auth') === '1') {
    var TIMEOUT_MS = 30 * 60 * 1000; // 30 minutes per NFR Availability spec
    var WARN_MS = TIMEOUT_MS - 60 * 1000; // warn 1 min before
    var warnTimer, logoutTimer, warned = false;

    function resetTimers() {
      clearTimeout(warnTimer);
      clearTimeout(logoutTimer);
      warned = false;
      var banner = document.getElementById('idleWarning');
      if (banner) banner.style.display = 'none';
      warnTimer = setTimeout(showWarning, WARN_MS);
      logoutTimer = setTimeout(doLogout, TIMEOUT_MS);
    }
    function showWarning() {
      warned = true;
      var banner = document.getElementById('idleWarning');
      if (banner) banner.style.display = 'flex';
    }
    function doLogout() {
      window.location.href = 'logout.php?reason=idle';
    }
    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(function (evt) {
      document.addEventListener(evt, function () {
        if (warned) resetTimers(); else { clearTimeout(warnTimer); clearTimeout(logoutTimer); warnTimer = setTimeout(showWarning, WARN_MS); logoutTimer = setTimeout(doLogout, TIMEOUT_MS); }
      }, { passive: true });
    });
    resetTimers();
  }
})();
