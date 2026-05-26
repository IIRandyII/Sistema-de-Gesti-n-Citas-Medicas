// ============================================================
//  layout.js — Comportamiento del sidebar (reutilizable)
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
  const main    = document.querySelector('.main');

  if (!toggle || !sidebar || !main) return;

  // Restaurar estado guardado
  if (localStorage.getItem('sidebar_collapsed') === 'true') {
    sidebar.classList.add('collapsed');
    main.classList.add('expanded');
  }

  toggle.addEventListener('click', function () {
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('expanded');
    localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
  });
});