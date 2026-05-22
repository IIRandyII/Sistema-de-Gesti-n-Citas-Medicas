// ============================================================
//  auth.js — Utilidades compartidas para Login y Register
//  CitaÁgil · Sistema de citas médicas
// ============================================================

/**
 * Muestra u oculta el mensaje de error de un campo.
 * Espera que el span.error-msg sea el hermano siguiente del input.
 * @param {string} errorId  - id del span.error-msg
 * @param {boolean} show    - true = mostrar error
 */
function setError(errorId, show) {
  const el = document.getElementById(errorId);
  if (!el) return;
  el.style.display = show ? 'block' : 'none';
  const input = el.previousElementSibling;
  if (input && input.tagName === 'INPUT') {
    input.classList.toggle('error', show);
  }
}

/**
 * Valida formato de correo electrónico.
 * @param {string} email
 * @returns {boolean}
 */
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}