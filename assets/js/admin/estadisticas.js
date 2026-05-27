// ============================================================
//  estadisticas.js
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  const verde      = '#2e7d4f';
  const verdeClaro = '#e8f5ee';
  const azul       = '#3b6dd4';
  const azulClaro  = '#e8f0fb';
  const amber      = '#d4930a';
  const amberClaro = '#fff8e6';
  const rojo       = '#c0392b';
  const rojoClaro  = '#fef0f0';
  const teal       = '#1a8a5a';

  Chart.defaults.font.family = "'Nunito', sans-serif";
  Chart.defaults.font.size   = 12;
  Chart.defaults.color       = '#6b8a74';

  // ── CITAS POR MES ──
  new Chart(document.getElementById('chartMes'), {
    type: 'bar',
    data: {
      labels: LABELS_MES,
      datasets: [{
        label: 'Citas',
        data: DATA_MES,
        backgroundColor: verdeClaro,
        borderColor: verde,
        borderWidth: 2,
        borderRadius: 8,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 },
          grid: { color: '#f0f0f0' }
        },
        x: { grid: { display: false } }
      }
    }
  });

  // ── CITAS POR ESTATUS ──
  const coloresEstatus = {
    pendiente:  [amberClaro, amber],
    confirmada: [verdeClaro, verde],
    completada: [azulClaro,  azul],
    cancelada:  [rojoClaro,  rojo],
  };

  const bgEstatus     = LABELS_ESTATUS.map(e => (coloresEstatus[e] || ['#eee','#999'])[0]);
  const borderEstatus = LABELS_ESTATUS.map(e => (coloresEstatus[e] || ['#eee','#999'])[1]);

  new Chart(document.getElementById('chartEstatus'), {
    type: 'doughnut',
    data: {
      labels: LABELS_ESTATUS.map(e => e.charAt(0).toUpperCase() + e.slice(1)),
      datasets: [{
        data: DATA_ESTATUS,
        backgroundColor: bgEstatus,
        borderColor: borderEstatus,
        borderWidth: 2,
        hoverOffset: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 }
        }
      }
    }
  });

  // ── CITAS POR ESPECIALIDAD ──
  new Chart(document.getElementById('chartEspecialidad'), {
    type: 'bar',
    data: {
      labels: LABELS_ESP,
      datasets: [{
        label: 'Citas',
        data: DATA_ESP,
        backgroundColor: azulClaro,
        borderColor: azul,
        borderWidth: 2,
        borderRadius: 8,
        borderSkipped: false,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: {
          beginAtZero: true,
          ticks: { stepSize: 1 },
          grid: { color: '#f0f0f0' }
        },
        y: { grid: { display: false } }
      }
    }
  });

  // ── TOP 5 MÉDICOS ──
  new Chart(document.getElementById('chartMedicos'), {
    type: 'bar',
    data: {
      labels: LABELS_MEDICOS.map(n => 'Dr. ' + n),
      datasets: [{
        label: 'Citas',
        data: DATA_MEDICOS,
        backgroundColor: '#e0f5ec',
        borderColor: teal,
        borderWidth: 2,
        borderRadius: 8,
        borderSkipped: false,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: {
          beginAtZero: true,
          ticks: { stepSize: 1 },
          grid: { color: '#f0f0f0' }
        },
        y: { grid: { display: false } }
      }
    }
  });

});