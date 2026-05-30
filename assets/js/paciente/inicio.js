// ============================================================
//  inicio.js — Página de inicio del paciente
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── ANIMACIÓN ITEMS ──
  document.querySelectorAll('.ultima-item, .acceso-btn, .resumen-card').forEach(function (el, i) {
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(8px)';
    el.style.transition = 'opacity .25s ease, transform .25s ease';
    setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, 50 * i);
  });

});