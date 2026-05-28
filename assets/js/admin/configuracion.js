// ============================================================
//  configuracion.js
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── LIMPIAR URL TOAST ──
  if (window.location.search.match(/perfil|password|sistema/)) {
    history.replaceState(null, '', 'configuracion.php');
  }

});

// ── TOGGLE PASSWORD ──
function togglePass(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon  = btn.querySelector('i');
  const visible = input.type === 'password';
  input.type    = visible ? 'text' : 'password';
  icon.className = visible ? 'ti ti-eye-off' : 'ti ti-eye';
}