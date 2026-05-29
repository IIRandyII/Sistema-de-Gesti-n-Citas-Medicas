// ============================================================
//  agenda.js — Lógica de la agenda médica
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── ANIMACIÓN CITAS ──
  document.querySelectorAll('.cita-row, .cita-chip, tbody tr').forEach(function (el, i) {
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(6px)';
    el.style.transition = 'opacity .2s ease, transform .2s ease';
    setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, 40 * i);
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

});