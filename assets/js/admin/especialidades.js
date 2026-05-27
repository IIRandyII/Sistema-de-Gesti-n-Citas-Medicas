// ============================================================
//  especialidades.js
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

  // ── MODAL CREAR ──
  const crearModal  = document.getElementById('crearModal');
  const btnNueva    = document.getElementById('btnNueva');
  const crearClose  = document.getElementById('crearClose');
  const crearCancel = document.getElementById('crearCancel');

  if (crearModal && btnNueva) {
    btnNueva.addEventListener('click',   () => crearModal.classList.add('open'));
    crearClose.addEventListener('click', () => crearModal.classList.remove('open'));
    crearCancel.addEventListener('click',() => crearModal.classList.remove('open'));
    crearModal.addEventListener('click', (e) => { if (e.target === crearModal) crearModal.classList.remove('open'); });
  }

  // ── MODAL EDITAR ──
  const editarModal  = document.getElementById('editarModal');
  const editarClose  = document.getElementById('editarClose');
  const editarCancel = document.getElementById('editarCancel');

  if (editarModal) {
    editarClose.addEventListener('click',  () => editarModal.classList.remove('open'));
    editarCancel.addEventListener('click', () => editarModal.classList.remove('open'));
    editarModal.addEventListener('click',  (e) => { if (e.target === editarModal) editarModal.classList.remove('open'); });
  }

  // ── MODAL ELIMINAR ──
  const eliminarModal  = document.getElementById('eliminarModal');
  const eliminarCancel = document.getElementById('eliminarCancel');

  if (eliminarModal) {
    eliminarCancel.addEventListener('click', () => eliminarModal.classList.remove('open'));
    eliminarModal.addEventListener('click',  (e) => { if (e.target === eliminarModal) eliminarModal.classList.remove('open'); });
  }

  // ── LIMPIAR URL TOAST ──
  if (window.location.search.match(/created|updated|deleted/)) {
    history.replaceState(null, '', 'especialidades.php');
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

// ── ABRIR EDITAR ──
function abrirEditar(id, nombre) {
  document.getElementById('editarId').value     = id;
  document.getElementById('editarNombre').value = nombre;
  document.getElementById('editarModal').classList.add('open');
}

// ── ABRIR ELIMINAR ──
function abrirEliminar(id, nombre, totalMedicos) {
  document.getElementById('eliminarId').value = id;
  document.getElementById('eliminarDesc').textContent =
    totalMedicos > 0
      ? `No puedes eliminar "${nombre}" porque tiene ${totalMedicos} médico(s) asignado(s).`
      : `Esta acción eliminará permanentemente la especialidad "${nombre}". ¿Deseas continuar?`;

  const confirmBtn = document.querySelector('.modal-confirm-btn');
  confirmBtn.disabled = totalMedicos > 0;
  confirmBtn.style.opacity = totalMedicos > 0 ? '.4' : '1';
  confirmBtn.style.cursor  = totalMedicos > 0 ? 'not-allowed' : 'pointer';

  document.getElementById('eliminarModal').classList.add('open');
}