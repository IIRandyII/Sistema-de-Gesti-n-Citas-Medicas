// ============================================================
//  disponibilidad.js
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── LIMPIAR URL TOAST ──
  if (window.location.search.match(/horario|excepcion|deleted/)) {
    history.replaceState(null, '', 'disponibilidad.php');
  }

  // ── INICIALIZAR ESTADO DÍAS ──
  document.querySelectorAll('.dia-toggle input').forEach(function (checkbox) {
    const num = checkbox.name.match(/\d+/)[0];
    actualizarDia(num, checkbox.checked);
  });

});

function toggleDia(num, activo) {
  actualizarDia(num, activo);
}

function actualizarDia(num, activo) {
  const horario = document.getElementById('horario_' + num);
  const label   = document.getElementById('label_' + num);
  if (horario) {
    horario.style.opacity       = activo ? '1' : '.4';
    horario.style.pointerEvents = activo ? 'all' : 'none';
  }
  if (label) {
    label.classList.toggle('activo', activo);
  }
}