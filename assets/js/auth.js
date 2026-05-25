// ============================================================
//  auth.js — Utilidades compartidas para Login y Register
//  CitaÁgil · Sistema de citas médicas
// ============================================================

/**
 * Muestra u oculta el mensaje de error de un campo.
 */
function setError(errorId, show) {
  const el = document.getElementById(errorId);
  if (!el) return;
  el.style.display = show ? 'block' : 'none';

  const prev = el.previousElementSibling;
  const input = prev && prev.tagName === 'INPUT'
    ? prev
    : prev && prev.classList.contains('input-wrap')
      ? prev.querySelector('input')
      : null;

  if (input) input.classList.toggle('error', show);
}

/**
 * Valida formato de correo electrónico.
 */
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// ── TOGGLE PASSWORD ──
// Escucha clics en cualquier .icon-right con data-target
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.icon-right[data-target]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var inputId = btn.getAttribute('data-target');
      var input   = document.getElementById(inputId);
      if (!input) return;

      var visible = input.type === 'password';
      input.type  = visible ? 'text' : 'password';
      btn.style.color = visible ? 'var(--green-main)' : '';

      btn.innerHTML = visible
        ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    });
  });
});