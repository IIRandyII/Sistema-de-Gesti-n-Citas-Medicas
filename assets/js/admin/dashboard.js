// ============================================================
//  dashboard.js — Lógica del dashboard admin
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
  // Animación de entrada de las stat cards
  document.querySelectorAll('.stat-card').forEach(function (card, i) {
    card.style.opacity = '0';
    card.style.transform = 'translateY(16px)';
    card.style.transition = 'opacity .35s ease, transform .35s ease';
    setTimeout(function () {
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, 80 * i);
  });
});