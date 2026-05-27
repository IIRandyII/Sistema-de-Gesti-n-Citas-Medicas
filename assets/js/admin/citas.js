// ============================================================
//  citas.js — Lógica de la página de citas admin
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── BÚSQUEDA CON DEBOUNCE ──
  const searchInput = document.querySelector('.search-input');
  if (searchInput) {
    let timer;
    searchInput.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(() => searchInput.closest('form').submit(), 400);
    });
  }

  // ── MODAL DETALLE ──
  const detalleModal = document.getElementById('detalleModal');
  const detalleClose = document.getElementById('detalleClose');

  if (detalleModal && detalleClose) {
    detalleClose.addEventListener('click', () => detalleModal.classList.remove('open'));
    detalleModal.addEventListener('click', (e) => {
      if (e.target === detalleModal) detalleModal.classList.remove('open');
    });
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
  const meses = ['enero','febrero','marzo','abril','mayo','junio',
                 'julio','agosto','septiembre','octubre','noviembre','diciembre'];

  const fecha     = new Date(c.fecha + 'T00:00:00');
  const fechaStr  = fecha.getDate() + ' de ' + meses[fecha.getMonth()] + ' de ' + fecha.getFullYear();
  const registro  = new Date(c.creado_en);
  const regStr    = registro.getDate() + ' de ' + meses[registro.getMonth()] + ' de ' + registro.getFullYear();

  const badges = {
    pendiente:  '<span class="badge pendiente">Pendiente</span>',
    confirmada: '<span class="badge confirmada">Confirmada</span>',
    cancelada:  '<span class="badge cancelada">Cancelada</span>',
    completada: '<span class="badge completada">Completada</span>',
  };

  document.getElementById('detalleEstadoBadge').innerHTML  = badges[c.estatus] || c.estatus;
  document.getElementById('detallePaciente').textContent   = c.paciente;
  document.getElementById('detalleMedico').textContent     = 'Dr. ' + c.medico;
  document.getElementById('detalleEspecialidad').textContent = c.especialidad;
  document.getElementById('detalleFecha').textContent      = fechaStr;
  document.getElementById('detalleHora').textContent       = c.hora.substring(0, 5);
  document.getElementById('detalleMotivo').textContent     = c.motivo || '—';
  document.getElementById('detalleRegistro').textContent   = regStr;

  document.getElementById('detalleModal').classList.add('open');
}