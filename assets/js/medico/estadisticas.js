// ============================================================
//  estadisticas.js — Estadísticas médico
//  CitaÁgil · Sistema de citas médicas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  const verde      = '#2e7d4f';
  const verdeClaro = '#e8f5ee';
  const azul       = '#2e7d4f';
  const azulClaro  = '#e8f5ee';
  const amber      = '#d4930a';
  const amberClaro = '#fff8e6';
  const rojo       = '#c0392b';
  const rojoClaro  = '#fef0f0';
  const teal       = '#1a8a5a';
  const tealClaro  = '#e0f5ec';

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
        backgroundColor: azulClaro,
        borderColor: azul,
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
        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
        x: { grid: { display: false } }
      }
    }
  });

  // ── CITAS POR ESTATUS ──
  const coloresEstatus = {
    pendiente:  [amberClaro, amber],
    confirmada: [azulClaro,  azul],
    completada: [tealClaro,  teal],
    cancelada:  [rojoClaro,  rojo],
  };

  new Chart(document.getElementById('chartEstatus'), {
    type: 'doughnut',
    data: {
      labels: LABELS_ESTATUS.map(e => e.charAt(0).toUpperCase() + e.slice(1)),
      datasets: [{
        data: DATA_ESTATUS,
        backgroundColor: LABELS_ESTATUS.map(e => (coloresEstatus[e] || ['#eee','#999'])[0]),
        borderColor:     LABELS_ESTATUS.map(e => (coloresEstatus[e] || ['#eee','#999'])[1]),
        borderWidth: 2,
        hoverOffset: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 } }
      }
    }
  });

  // ── TOP PACIENTES ──
  new Chart(document.getElementById('chartPacientes'), {
    type: 'bar',
    data: {
      labels: LABELS_PACIENTES,
      datasets: [{
        label: 'Citas',
        data: DATA_PACIENTES,
        backgroundColor: tealClaro,
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
        x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
        y: { grid: { display: false } }
      }
    }
  });

  // ── CITAS POR DÍA ──
  new Chart(document.getElementById('chartDias'), {
    type: 'bar',
    data: {
      labels: LABELS_DIAS,
      datasets: [{
        label: 'Citas',
        data: DATA_DIAS,
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
        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
        x: { grid: { display: false } }
      }
    }
  });

});