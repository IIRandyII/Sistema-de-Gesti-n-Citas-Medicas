// ============================================================
//  medicos.js — Lógica de la página de médicos
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── BÚSQUEDA CON DEBOUNCE ──
  const searchInput = document.querySelector('.search-input');
  if (searchInput) {
    let timer;
    searchInput.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        searchInput.closest('form').submit();
      }, 400);
    });
  }

  // ── MODAL DETALLE ──
  const detalleModal = document.getElementById('detalleModal');
  const detalleClose = document.getElementById('detalleClose');

  if (detalleModal && detalleClose) {
    detalleClose.addEventListener('click', () => detalleModal.classList.remove('open'));
    detalleModal.addEventListener('click', (e) => { if (e.target === detalleModal) detalleModal.classList.remove('open'); });
  }

  // ── MODAL NUEVO MÉDICO ──
  const nuevoModal    = document.getElementById('nuevoModal');
  const btnNuevo      = document.getElementById('btnNuevoMedico');
  const nuevoClose    = document.getElementById('nuevoClose');
  const nuevoCancelBtn= document.getElementById('nuevoCancelBtn');

  if (nuevoModal && btnNuevo) {
    btnNuevo.addEventListener('click', () => nuevoModal.classList.add('open'));
    nuevoClose.addEventListener('click', () => nuevoModal.classList.remove('open'));
    nuevoCancelBtn.addEventListener('click', () => nuevoModal.classList.remove('open'));
    nuevoModal.addEventListener('click', (e) => { if (e.target === nuevoModal) nuevoModal.classList.remove('open'); });
  }

  // ── TOGGLE PASSWORD NUEVO MÉDICO ──
  const togglePass = document.getElementById('toggleNuevoPass');
  const nuevoPass  = document.getElementById('nuevoPass');
  if (togglePass && nuevoPass) {
    togglePass.addEventListener('click', function () {
      const visible = nuevoPass.type === 'password';
      nuevoPass.type = visible ? 'text' : 'password';
      togglePass.querySelector('i').className = visible ? 'ti ti-eye-off' : 'ti ti-eye';
    });
  }

  // ── SOLO NÚMEROS EN TELÉFONO ──
  const nuevoTel = document.getElementById('nuevoTelefono');
  if (nuevoTel) {
    nuevoTel.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });
  }

  // ── TOAST LIMPIAR URL ──
  if (window.location.search.includes('created=1')) {
    history.replaceState(null, '', 'medicos.php');
  }

  // ── ANIMACIÓN DE FILAS ──
  document.querySelectorAll('tbody tr').forEach(function (row, i) {
    row.style.opacity    = '0';
    row.style.transform  = 'translateY(8px)';
    row.style.transition = 'opacity .25s ease, transform .25s ease';
    setTimeout(function () {
      row.style.opacity   = '1';
      row.style.transform = 'translateY(0)';
    }, 40 * i);
  });

  // ── ESCAPAR CIERRA MODALES ──
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    }
  });

});

// ── VER DETALLE ──
function verDetalle(m) {
  const meses = ['enero','febrero','marzo','abril','mayo','junio',
                 'julio','agosto','septiembre','octubre','noviembre','diciembre'];
  const fecha    = new Date(m.creado_en);
  const fechaStr = fecha.getDate() + ' de ' + meses[fecha.getMonth()] + ' de ' + fecha.getFullYear();

  document.getElementById('detalleAvatar').textContent        = (m.nombre.charAt(0) + m.apellido.charAt(0)).toUpperCase();
  document.getElementById('detalleNombre').textContent        = 'Dr. ' + m.nombre + ' ' + m.apellido;
  document.getElementById('detalleEspecialidad').textContent  = m.especialidad || '—';
  document.getElementById('detalleCorreo').textContent        = m.correo;
  document.getElementById('detalleTelefono').textContent      = m.telefono || '—';
  document.getElementById('detalleCedula').textContent        = m.cedula   || '—';
  document.getElementById('detallePendientes').textContent    = m.citas_pendientes + ' citas';
  document.getElementById('detalleHoy').textContent           = m.citas_hoy + ' citas';
  document.getElementById('detalleRegistro').textContent      = fechaStr;
  document.getElementById('detalleEstado').innerHTML          =
    m.activo == 1
      ? '<span class="badge activo">Activo</span>'
      : '<span class="badge inactivo">Inactivo</span>';

  document.getElementById('detalleModal').classList.add('open');
}