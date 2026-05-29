// ============================================================
//  pacientes.js — Mis pacientes médico
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── TABS ──
  const tabs    = document.querySelectorAll('.detail-tab');
  const contents= document.querySelectorAll('.tab-content');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(t => t.classList.remove('active'));
      contents.forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      const target = document.getElementById('tab-' + tab.dataset.tab);
      if (target) target.classList.add('active');
    });
  });

  // ── BÚSQUEDA CON DEBOUNCE ──
  const searchInput = document.querySelector('.search-input');
  if (searchInput) {
    let timer;
    searchInput.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(() => searchInput.closest('form').submit(), 400);
    });
  }

  // ── LIMPIAR TOAST URL ──
  if (window.location.search.includes('nota=1')) {
    const url = new URL(window.location);
    url.searchParams.delete('nota');
    history.replaceState(null, '', url);
  }

  // ── ANIMACIÓN ITEMS ──
  document.querySelectorAll('.paciente-item, .historial-item, .nota-item').forEach(function (el, i) {
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(6px)';
    el.style.transition = 'opacity .2s ease, transform .2s ease';
    setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, 40 * i);
  });

});