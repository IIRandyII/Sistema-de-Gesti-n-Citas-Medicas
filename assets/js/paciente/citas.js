// ============================================================
//  citas.js — Mis citas del paciente
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── LIMPIAR URL TOAST ──
  if (window.location.search.match(/agendada|cancelada|reprogramada/)) {
    const url = new URL(window.location);
    ['agendada','cancelada','reprogramada'].forEach(p => url.searchParams.delete(p));
    history.replaceState(null, '', url);
  }

  // ── MODAL DETALLE ──
  const detalleModal = document.getElementById('detalleModal');
  const detalleClose = document.getElementById('detalleClose');

  if (detalleModal && detalleClose) {
    detalleClose.addEventListener('click', () => detalleModal.classList.remove('open'));
    detalleModal.addEventListener('click', (e) => { if (e.target === detalleModal) detalleModal.classList.remove('open'); });
  }

  // ── MODAL CANCELAR ──
  const cancelarModal  = document.getElementById('cancelarModal');
  const cancelarCancel = document.getElementById('cancelarCancel');

  if (cancelarModal && cancelarCancel) {
    cancelarCancel.addEventListener('click', () => cancelarModal.classList.remove('open'));
    cancelarModal.addEventListener('click', (e) => { if (e.target === cancelarModal) cancelarModal.classList.remove('open'); });
  }

  // ── ESC CIERRA MODALES ──
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    }
  });

  // ── ANIMACIÓN FILAS ──
  document.querySelectorAll('tbody tr').forEach(function (row, i) {
    row.style.opacity    = '0';
    row.style.transform  = 'translateY(8px)';
    row.style.transition = 'opacity .25s ease, transform .25s ease';
    setTimeout(() => { row.style.opacity = '1'; row.style.transform = 'translateY(0)'; }, 40 * i);
  });

});

// ── VER DETALLE ──
function verDetalle(c) {
  const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  const fecha = new Date(c.fecha + 'T00:00:00');
  const fechaStr = fecha.getDate() + ' de ' + meses[fecha.getMonth()] + ' de ' + fecha.getFullYear();

  const badges = {
    pendiente:  '<span class="badge pendiente">Pendiente</span>',
    confirmada: '<span class="badge confirmada">Confirmada</span>',
    cancelada:  '<span class="badge cancelada">Cancelada</span>',
    completada: '<span class="badge completada">Completada</span>',
  };

  document.getElementById('detEstado').innerHTML       = badges[c.estatus] || '';
  document.getElementById('detMedico').textContent     = 'Dr. ' + c.medico;
  document.getElementById('detEspecialidad').textContent = c.especialidad;
  document.getElementById('detFecha').textContent      = fechaStr;
  document.getElementById('detHora').textContent       = c.hora.substring(0,5) + ' hrs';
  document.getElementById('detMotivo').textContent     = c.motivo || '—';

  document.getElementById('detalleModal').classList.add('open');
}

// ── CONFIRMAR CANCELAR ──
function confirmarCancelar(citaId) {
  document.getElementById('cancelarCitaId').value = citaId;
  document.getElementById('cancelarModal').classList.add('open');
}