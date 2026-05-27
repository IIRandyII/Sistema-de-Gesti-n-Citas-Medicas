// ============================================================
//  reportes.js
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── HIGHLIGHT TIPO CARD AL CAMBIAR ──
  document.querySelectorAll('.tipo-card input[type="radio"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      document.querySelectorAll('.tipo-card').forEach(c => c.classList.remove('active'));
      radio.closest('.tipo-card').classList.add('active');
    });
  });

  // ── ANIMACIÓN FILAS ──
  document.querySelectorAll('tbody tr').forEach(function (row, i) {
    row.style.opacity    = '0';
    row.style.transform  = 'translateY(8px)';
    row.style.transition = 'opacity .25s ease, transform .25s ease';
    setTimeout(() => { row.style.opacity = '1'; row.style.transform = 'translateY(0)'; }, 40 * i);
  });

});