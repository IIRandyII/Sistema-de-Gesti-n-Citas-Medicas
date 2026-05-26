// ============================================================
//  layout.js — Comportamiento del sidebar (reutilizable)
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── SIDEBAR TOGGLE ──
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
  const main    = document.querySelector('.main');

  if (toggle && sidebar && main) {
    if (localStorage.getItem('sidebar_collapsed') === 'true') {
      sidebar.classList.add('collapsed');
      main.classList.add('expanded');
    }

    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('collapsed');
      main.classList.toggle('expanded');
      localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
    });
  }

  // ── AVATAR DROPDOWN ──
  const avatarBtn      = document.getElementById('avatarBtn');
  const avatarDropdown = document.getElementById('avatarDropdown');
  const avatarChevron  = document.getElementById('avatarChevron');

  if (avatarBtn && avatarDropdown) {
    avatarBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const isOpen = avatarDropdown.classList.toggle('open');
      if (avatarChevron) avatarChevron.classList.toggle('open', isOpen);
    });

    document.addEventListener('click', function () {
      avatarDropdown.classList.remove('open');
      if (avatarChevron) avatarChevron.classList.remove('open');
    });

    avatarDropdown.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  }

  // ── MODAL CERRAR SESIÓN ──
  const logoutBtn    = document.getElementById('logoutBtn');
  const logoutModal  = document.getElementById('logoutModal');
  const logoutCancel = document.getElementById('logoutCancel');

  if (logoutBtn && logoutModal) {
    logoutBtn.addEventListener('click', function () {
      if (avatarDropdown) avatarDropdown.classList.remove('open');
      if (avatarChevron)  avatarChevron.classList.remove('open');
      logoutModal.classList.add('open');
    });

    logoutCancel.addEventListener('click', function () {
      logoutModal.classList.remove('open');
    });

    logoutModal.addEventListener('click', function (e) {
      if (e.target === logoutModal) logoutModal.classList.remove('open');
    });
  }

});