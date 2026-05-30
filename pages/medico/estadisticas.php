<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('medico');

$current_page = 'estadisticas';
$page_title   = 'Mis estadísticas';

$pdo = getDB();

// ── OBTENER ID MÉDICO ──
$stmt = $pdo->prepare("SELECT m.id, e.nombre AS especialidad FROM medicos m JOIN especialidades e ON e.id = m.especialidad_id WHERE m.usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch();
$medico_id           = $medico['id'] ?? null;
$medico_especialidad = $medico['especialidad'] ?? 'Médico';

// ── TARJETAS RESUMEN ──
$total_citas = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ?");
$total_citas->execute([$medico_id]);
$cnt_total = $total_citas->fetchColumn();

$completadas = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND estatus = 'completada'");
$completadas->execute([$medico_id]);
$cnt_completadas = $completadas->fetchColumn();

$canceladas = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND estatus = 'cancelada'");
$canceladas->execute([$medico_id]);
$cnt_canceladas = $canceladas->fetchColumn();

$pacientes = $pdo->prepare("SELECT COUNT(DISTINCT paciente_id) FROM citas WHERE medico_id = ?");
$pacientes->execute([$medico_id]);
$cnt_pacientes = $pacientes->fetchColumn();

// ── CITAS POR MES ──
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(fecha, '%b %Y') AS mes,
           MONTH(fecha) AS num_mes,
           YEAR(fecha)  AS anio,
           COUNT(*)     AS total
    FROM citas
    WHERE medico_id = ? AND fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(fecha), MONTH(fecha)
    ORDER BY YEAR(fecha), MONTH(fecha)
");
$stmt->execute([$medico_id]);
$citas_mes = $stmt->fetchAll();

// ── CITAS POR ESTATUS ──
$stmt = $pdo->prepare("SELECT estatus, COUNT(*) AS total FROM citas WHERE medico_id = ? GROUP BY estatus");
$stmt->execute([$medico_id]);
$citas_estatus = $stmt->fetchAll();

// ── TOP 5 PACIENTES ──
$stmt = $pdo->prepare("
    SELECT CONCAT(u.nombre, ' ', u.apellido) AS paciente,
           COUNT(c.id) AS total
    FROM citas c
    JOIN usuarios u ON c.paciente_id = u.id
    WHERE c.medico_id = ?
    GROUP BY c.paciente_id, u.nombre, u.apellido
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute([$medico_id]);
$top_pacientes = $stmt->fetchAll();

// ── CITAS POR DÍA DE SEMANA ──
$stmt = $pdo->prepare("
    SELECT DAYOFWEEK(fecha) AS dia_num,
           DAYNAME(fecha)   AS dia_nombre,
           COUNT(*)         AS total
    FROM citas
    WHERE medico_id = ?
    GROUP BY DAYOFWEEK(fecha), DAYNAME(fecha)
    ORDER BY DAYOFWEEK(fecha)
");
$stmt->execute([$medico_id]);
$citas_dia_raw = $stmt->fetchAll();

$dias_es = [
    'Sunday' => 'Dom', 'Monday' => 'Lun', 'Tuesday' => 'Mar',
    'Wednesday' => 'Mié', 'Thursday' => 'Jue', 'Friday' => 'Vie', 'Saturday' => 'Sáb'
];

$labels_mes      = array_column($citas_mes, 'mes');
$data_mes        = array_column($citas_mes, 'total');
$labels_estatus  = array_column($citas_estatus, 'estatus');
$data_estatus    = array_column($citas_estatus, 'total');
$labels_pacientes= array_column($top_pacientes, 'paciente');
$data_pacientes  = array_column($top_pacientes, 'total');
$labels_dias     = array_map(fn($r) => $dias_es[$r['dia_nombre']] ?? $r['dia_nombre'], $citas_dia_raw);
$data_dias       = array_column($citas_dia_raw, 'total');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/estadisticas.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/medico/layout.php'; ?>

  <div class="content">

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Mis estadísticas</h1>
        <p class="page-sub">Resumen de tu actividad y rendimiento.</p>
      </div>
    </div>

    <!-- Tarjetas resumen -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="ti ti-calendar"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_total ?></div>
          <div class="stat-label">Total citas</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon teal"><i class="ti ti-circle-check"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_completadas ?></div>
          <div class="stat-label">Completadas</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red"><i class="ti ti-calendar-x"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_canceladas ?></div>
          <div class="stat-label">Canceladas</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="ti ti-users"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_pacientes ?></div>
          <div class="stat-label">Pacientes atendidos</div>
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
        <div class="chart-body">
          <canvas id="chartEstatus"></canvas>
        </div>
      </div>
    </div>

    <!-- Gráficas fila 2 -->
    <div class="charts-grid-2">
      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-titulo"><i class="ti ti-users"></i> Top 5 pacientes</span>
          <span class="chart-sub">Con más citas</span>
        </div>
        <div class="chart-body">
          <canvas id="chartPacientes"></canvas>
        </div>
      </div>
      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-titulo"><i class="ti ti-calendar-week"></i> Citas por día</span>
          <span class="chart-sub">Rendimiento semanal</span>
        </div>
        <div class="chart-body">
          <canvas id="chartDias"></canvas>
        </div>
      </div>
    </div>

  </div>
</div>

</div><!-- .layout -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="/CitaAgil1/assets/js/medico/layout.js"></script>
<script>
const LABELS_MES       = <?= json_encode($labels_mes) ?>;
const DATA_MES         = <?= json_encode($data_mes) ?>;
const LABELS_ESTATUS   = <?= json_encode($labels_estatus) ?>;
const DATA_ESTATUS     = <?= json_encode($data_estatus) ?>;
const LABELS_PACIENTES = <?= json_encode($labels_pacientes) ?>;
const DATA_PACIENTES   = <?= json_encode($data_pacientes) ?>;
const LABELS_DIAS      = <?= json_encode($labels_dias) ?>;
const DATA_DIAS        = <?= json_encode($data_dias) ?>;
</script>
<script src="/CitaAgil1/assets/js/medico/estadisticas.js"></script>
</body>
</html>