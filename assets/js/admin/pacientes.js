// ============================================================
//  pacientes.js — Lógica de la página de pacientes
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── BÚSQUEDA EN TIEMPO REAL (debounce) ──
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
    detalleClose.addEventListener('click', function () {
      detalleModal.classList.remove('open');
    });

    detalleModal.addEventListener('click', function (e) {
      if (e.target === detalleModal) detalleModal.classList.remove('open');
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') detalleModal.classList.remove('open');
    });
  }

  // ── ANIMACIÓN DE FILAS ──
  document.querySelectorAll('tbody tr').forEach(function (row, i) {
    row.style.opacity = '0';
    row.style.transform = 'translateY(8px)';
    row.style.transition = 'opacity .25s ease, transform .25s ease';
    setTimeout(function () {
      row.style.opacity = '1';
      row.style.transform = 'translateY(0)';
    }, 40 * i);
  });

});

// ── VER DETALLE ──
function verDetalle(p) {
  const meses = ['enero','febrero','marzo','abril','mayo','junio',
                 'julio','agosto','septiembre','octubre','noviembre','diciembre'];

  const fecha = new Date(p.creado_en);
  const fechaStr = fecha.getDate() + ' de ' + meses[fecha.getMonth()] + ' de ' + fecha.getFullYear();

  document.getElementById('detalleAvatar').textContent =
    (p.nombre.charAt(0) + p.apellido.charAt(0)).toUpperCase();

  document.getElementById('detalleNombre').textContent    = p.nombre + ' ' + p.apellido;
  document.getElementById('detalleCorreo').textContent    = p.correo;
  document.getElementById('detalleTelefono').textContent  = p.telefono || '—';
  document.getElementById('detalleRegistro').textContent  = fechaStr;
  document.getElementById('detalleCitas').textContent     = p.total_citas + ' citas';
  document.getElementById('detalleEstado').innerHTML      =
    p.activo == 1
      ? '<span class="badge activo">Activo</span>'
      : '<span class="badge inactivo">Inactivo</span>';

  document.getElementById('detalleModal').classList.add('open');
}