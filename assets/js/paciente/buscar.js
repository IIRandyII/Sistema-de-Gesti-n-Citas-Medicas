// ============================================================
//  buscar.js — Buscar médico y agendar cita
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  if (PASO !== 3) return;

  const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                 'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

  let currentDate = FECHA_SEL ? new Date(FECHA_SEL + 'T00:00:00') : new Date();
  let viewYear    = currentDate.getFullYear();
  let viewMonth   = currentDate.getMonth();

  function renderCalendario() {
    const calMes  = document.getElementById('calMes');
    const calDias = document.getElementById('calDias');
    if (!calMes || !calDias) return;

    calMes.textContent = meses[viewMonth] + ' ' + viewYear;
    calDias.innerHTML  = '';

    const hoy       = new Date();
    hoy.setHours(0,0,0,0);
    const primerDia = new Date(viewYear, viewMonth, 1);
    const diasMes   = new Date(viewYear, viewMonth + 1, 0).getDate();

    // Día de la semana del primer día (0=Dom → ajustar a Lun=0)
    let inicioSem = primerDia.getDay() - 1;
    if (inicioSem < 0) inicioSem = 6;

    // Celdas vacías
    for (let i = 0; i < inicioSem; i++) {
      calDias.innerHTML += '<div class="cal-dia"></div>';
    }

    for (let d = 1; d <= diasMes; d++) {
      const fecha  = new Date(viewYear, viewMonth, d);
      fecha.setHours(0,0,0,0);
      const fechaStr = fecha.toISOString().split('T')[0];

      // Día de semana ISO (1=Lun, 7=Dom)
      let diaSem = fecha.getDay();
      if (diaSem === 0) diaSem = 7;

      const esHoy       = fecha.getTime() === hoy.getTime();
      const esPasado    = fecha < hoy;
      const disponible  = DIAS_DISPONIBLES.includes(diaSem) && !esPasado;
      const bloqueado   = FECHAS_BLOQUEADAS.includes(fechaStr);
      const seleccionado= fechaStr === FECHA_SEL;

      let clases = 'cal-dia';
      if (esPasado)    clases += ' pasado';
      else if (bloqueado)   clases += ' bloqueado';
      else if (disponible)  clases += ' disponible';
      if (esHoy)       clases += ' hoy';
      if (seleccionado)clases += ' seleccionado';

      if (disponible && !bloqueado) {
        calDias.innerHTML += `<div class="${clases}" onclick="seleccionarFecha('${fechaStr}')">${d}</div>`;
      } else {
        calDias.innerHTML += `<div class="${clases}">${d}</div>`;
      }
    }
  }

  document.getElementById('calPrev')?.addEventListener('click', function () {
    viewMonth--;
    if (viewMonth < 0) { viewMonth = 11; viewYear--; }
    renderCalendario();
  });

  document.getElementById('calNext')?.addEventListener('click', function () {
    viewMonth++;
    if (viewMonth > 11) { viewMonth = 0; viewYear++; }
    renderCalendario();
  });

  renderCalendario();

});

function seleccionarFecha(fecha) {
  const url = `?paso=3&especialidad=${ESP_ID}&medico=${MEDICO_ID}&fecha=${fecha}`;
  window.location.href = url;
}