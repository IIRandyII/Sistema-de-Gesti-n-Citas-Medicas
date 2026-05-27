<?php
// ============================================================
//  pages/admin/estadisticas.php
//  CitaÁgil · Sistema de citas médicas
// ============================================================

require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$current_page = 'estadisticas';
$page_title   = 'Estadísticas';

$pdo = getDB();

// ── TARJETAS RESUMEN ──
$total_citas      = $pdo->query("SELECT COUNT(*) FROM citas")->fetchColumn();
$total_pacientes  = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'paciente' AND activo = 1")->fetchColumn();
$total_medicos    = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'medico' AND activo = 1")->fetchColumn();
$total_esp        = $pdo->query("SELECT COUNT(*) FROM especialidades")->fetchColumn();

// ── CITAS POR MES (últimos 6 meses) ──
$citas_mes = $pdo->query("
    SELECT DATE_FORMAT(fecha, '%b %Y') AS mes,
           MONTH(fecha) AS num_mes,
           YEAR(fecha)  AS anio,
           COUNT(*)     AS total
    FROM citas
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(fecha), MONTH(fecha)
    ORDER BY YEAR(fecha), MONTH(fecha)
")->fetchAll();

// ── CITAS POR ESTATUS ──
$citas_estatus = $pdo->query("
    SELECT estatus, COUNT(*) AS total
    FROM citas
    GROUP BY estatus
")->fetchAll();

// ── CITAS POR ESPECIALIDAD ──
$citas_especialidad = $pdo->query("
    SELECT e.nombre AS especialidad, COUNT(c.id) AS total
    FROM citas c
    JOIN medicos m ON c.medico_id = m.id
    JOIN especialidades e ON m.especialidad_id = e.id
    GROUP BY e.id, e.nombre
    ORDER BY total DESC
")->fetchAll();

// ── TOP 5 MÉDICOS ──
$top_medicos = $pdo->query("
    SELECT CONCAT(u.nombre, ' ', u.apellido) AS medico,
           COUNT(c.id) AS total
    FROM citas c
    JOIN medicos m ON c.medico_id = m.id
    JOIN usuarios u ON m.usuario_id = u.id
    GROUP BY m.id, u.nombre, u.apellido
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

// ── PREPARAR DATOS PARA JS ──
$labels_mes    = array_column($citas_mes, 'mes');
$data_mes      = array_column($citas_mes, 'total');

$labels_estatus = array_column($citas_estatus, 'estatus');
$data_estatus   = array_column($citas_estatus, 'total');

$labels_esp    = array_column($citas_especialidad, 'especialidad');
$data_esp      = array_column($citas_especialidad, 'total');

$labels_medicos = array_column($top_medicos, 'medico');
$data_medicos   = array_column($top_medicos, 'total');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/estadisticas.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/admin/layout.php'; ?>

  <div class="content">

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Estadísticas</h1>
        <p class="page-sub">Resumen visual del comportamiento del sistema.</p>
      </div>
    </div>

    <!-- Tarjetas resumen -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon green"><i class="ti ti-calendar"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $total_citas ?></div>
          <div class="stat-label">Total citas</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue"><i class="ti ti-users"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $total_pacientes ?></div>
          <div class="stat-label">Pacientes activos</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber"><i class="ti ti-stethoscope"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $total_medicos ?></div>
          <div class="stat-label">Médicos activos</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon teal"><i class="ti ti-clipboard-list"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $total_esp ?></div>
          <div class="stat-label">Especialidades</div>
        </div>
      </div>
    </div>

    <!-- Gráficas fila 1 -->
    <div class="charts-grid-2">
      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-titulo"><i class="ti ti-chart-bar"></i> Citas por mes</span>
          <span class="chart-sub">Últimos 6 meses</span>
        </div>
        <div class="chart-body">
          <canvas id="chartMes"></canvas>
        </div>
      </div>
      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-titulo"><i class="ti ti-chart-donut"></i> Citas por estatus</span>
          <span class="chart-sub">Distribución general</span>
        </div>
        <div class="chart-body chart-body-donut">
          <canvas id="chartEstatus"></canvas>
        </div>
      </div>
    </div>

    <!-- Gráficas fila 2 -->
    <div class="charts-grid-2">
      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-titulo"><i class="ti ti-chart-bar"></i> Citas por especialidad</span>
          <span class="chart-sub">Total histórico</span>
        </div>
        <div class="chart-body">
          <canvas id="chartEspecialidad"></canvas>
        </div>
      </div>
      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-titulo"><i class="ti ti-award"></i> Top 5 médicos</span>
          <span class="chart-sub">Con más citas</span>
        </div>
        <div class="chart-body">
          <canvas id="chartMedicos"></canvas>
        </div>
      </div>
    </div>

  </div>
</div>

</div><!-- .layout -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="/CitaAgil1/assets/js/admin/layout.js"></script>
<script>
const LABELS_MES      = <?= json_encode($labels_mes) ?>;
const DATA_MES        = <?= json_encode($data_mes) ?>;
const LABELS_ESTATUS  = <?= json_encode($labels_estatus) ?>;
const DATA_ESTATUS    = <?= json_encode($data_estatus) ?>;
const LABELS_ESP      = <?= json_encode($labels_esp) ?>;
const DATA_ESP        = <?= json_encode($data_esp) ?>;
const LABELS_MEDICOS  = <?= json_encode($labels_medicos) ?>;
const DATA_MEDICOS    = <?= json_encode($data_medicos) ?>;
</script>
<script src="/CitaAgil1/assets/js/admin/estadisticas.js"></script>
</body>
</html>