// ============================================================
//  layout.js — Comportamiento del sidebar médico
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── SIDEBAR TOGGLE ──
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
  const main    = document.querySelector('.main');

  if (toggle && sidebar && main) {
    if (localStorage.getItem('medico_sidebar_collapsed') === 'true') {
      sidebar.classList.add('collapsed');
      main.classList.add('expanded');
    }

    toggle.addEventListener('click', function () {
      const isCollapsing = !sidebar.classList.contains('collapsed');
      sidebar.style.transition = isCollapsing
        ? 'transform .35s cubic-bezier(.4,0,.2,1)'
        : 'transform .35s cubic-bezier(.2,0,0,1)';
      main.style.transition = isCollapsing
        ? 'margin-left .35s cubic-bezier(.4,0,.2,1)'
        : 'margin-left .35s cubic-bezier(.2,0,0,1)';
      sidebar.classList.toggle('collapsed');
      main.classList.toggle('expanded');
      localStorage.setItem('medico_sidebar_collapsed', sidebar.classList.contains('collapsed'));
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

    avatarDropdown.addEventListener('click', e => e.stopPropagation());
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

    logoutCancel.addEventListener('click', () => logoutModal.classList.remove('open'));
    logoutModal.addEventListener('click', (e) => {
      if (e.target === logoutModal) logoutModal.classList.remove('open');
    });
  }

});