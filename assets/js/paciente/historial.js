// ============================================================
//  historial.js — Historial médico del paciente
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── BÚSQUEDA CON DEBOUNCE ──
  const searchInput = document.querySelector('.search-input');
  if (searchInput) {
    let timer;
    searchInput.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(() => searchInput.closest('form').submit(), 400);
    });
  }

  // ── ANIMACIÓN ITEMS ──
  document.querySelectorAll('.historial-item, .nota-item').forEach(function (el, i) {
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(6px)';
    el.style.transition = 'opacity .2s ease, transform .2s ease';
    setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, 40 * i);
  });

});