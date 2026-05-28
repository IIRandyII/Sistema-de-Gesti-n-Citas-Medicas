// ============================================================
//  agenda.js — Lógica de la agenda médica
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── ANIMACIÓN CITAS ──
  document.querySelectorAll('.cita-row, .cita-chip').forEach(function (el, i) {
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(6px)';
    el.style.transition = 'opacity .2s ease, transform .2s ease';
    setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, 40 * i);
  });

});