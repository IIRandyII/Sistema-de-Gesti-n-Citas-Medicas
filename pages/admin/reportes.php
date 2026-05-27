<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$current_page = 'reportes';
$page_title   = 'Reportes';

$pdo = getDB();

$tipo       = $_GET['tipo']       ?? 'citas_fecha';
$fecha_ini  = $_GET['fecha_ini']  ?? date('Y-m-01');
$fecha_fin  = $_GET['fecha_fin']  ?? date('Y-m-d');
$medico_id  = (int)($_GET['medico_id']  ?? 0);
$esp_id     = (int)($_GET['esp_id']     ?? 0);

$medicos        = $pdo->query("SELECT u.id, CONCAT(u.nombre, ' ', u.apellido) AS nombre FROM usuarios u JOIN medicos m ON m.usuario_id = u.id WHERE u.activo = 1 ORDER BY u.nombre")->fetchAll();
$especialidades = $pdo->query("SELECT * FROM especialidades ORDER BY nombre")->fetchAll();

$resultados = [];
$columnas   = [];
$total      = 0;

// ── EXPORTAR CSV ──
$exportar = isset($_GET['exportar']);

if (isset($_GET['tipo'])) {

    switch ($tipo) {

        case 'citas_fecha':
            $columnas = ['Paciente', 'Médico', 'Especialidad', 'Fecha', 'Hora', 'Estatus', 'Motivo'];
            $where    = "WHERE c.fecha BETWEEN ? AND ?";
            $params   = [$fecha_ini, $fecha_fin];
            if ($medico_id) { $where .= " AND med.usuario_id = ?"; $params[] = $medico_id; }
            if ($esp_id)    { $where .= " AND med.especialidad_id = ?"; $params[] = $esp_id; }
            $stmt = $pdo->prepare("
                SELECT CONCAT(p.nombre,' ',p.apellido) AS paciente,
                       CONCAT(u.nombre,' ',u.apellido) AS medico,
                       e.nombre AS especialidad,
                       c.fecha, c.hora, c.estatus, c.motivo
                FROM citas c
                JOIN usuarios p   ON c.paciente_id  = p.id
                JOIN medicos med  ON c.medico_id     = med.id
                JOIN usuarios u   ON med.usuario_id  = u.id
                JOIN especialidades e ON med.especialidad_id = e.id
                {$where}
                ORDER BY c.fecha DESC, c.hora DESC
            ");
            $stmt->execute($params);
            $resultados = $stmt->fetchAll();
            break;

        case 'citas_medico':
            $columnas = ['Médico', 'Especialidad', 'Total citas', 'Pendientes', 'Confirmadas', 'Completadas', 'Canceladas'];
            $stmt = $pdo->prepare("
                SELECT CONCAT(u.nombre,' ',u.apellido) AS medico,
                       e.nombre AS especialidad,
                       COUNT(c.id) AS total,
                       SUM(c.estatus = 'pendiente')  AS pendientes,
                       SUM(c.estatus = 'confirmada') AS confirmadas,
                       SUM(c.estatus = 'completada') AS completadas,
                       SUM(c.estatus = 'cancelada')  AS canceladas
                FROM citas c
                JOIN medicos med ON c.medico_id = med.id
                JOIN usuarios u  ON med.usuario_id = u.id
                JOIN especialidades e ON med.especialidad_id = e.id
                WHERE c.fecha BETWEEN ? AND ?
                GROUP BY med.id, u.nombre, u.apellido, e.nombre
                ORDER BY total DESC
            ");
            $stmt->execute([$fecha_ini, $fecha_fin]);
            $resultados = $stmt->fetchAll();
            break;

        case 'citas_especialidad':
            $columnas = ['Especialidad', 'Total citas', 'Pendientes', 'Confirmadas', 'Completadas', 'Canceladas'];
            $stmt = $pdo->prepare("
                SELECT e.nombre AS especialidad,
                       COUNT(c.id) AS total,
                       SUM(c.estatus = 'pendiente')  AS pendientes,
                       SUM(c.estatus = 'confirmada') AS confirmadas,
                       SUM(c.estatus = 'completada') AS completadas,
                       SUM(c.estatus = 'cancelada')  AS canceladas
                FROM citas c
                JOIN medicos med ON c.medico_id = med.id
                JOIN especialidades e ON med.especialidad_id = e.id
                WHERE c.fecha BETWEEN ? AND ?
                GROUP BY e.id, e.nombre
                ORDER BY total DESC
            ");
            $stmt->execute([$fecha_ini, $fecha_fin]);
            $resultados = $stmt->fetchAll();
            break;

        case 'pacientes_mes':
            $columnas = ['Mes', 'Año', 'Nuevos pacientes'];
            $stmt = $pdo->prepare("
                SELECT DATE_FORMAT(creado_en, '%M') AS mes,
                       YEAR(creado_en) AS anio,
                       COUNT(*) AS total
                FROM usuarios
                WHERE rol = 'paciente'
                  AND creado_en BETWEEN ? AND ?
                GROUP BY YEAR(creado_en), MONTH(creado_en)
                ORDER BY creado_en DESC
            ");
            $stmt->execute([$fecha_ini . ' 00:00:00', $fecha_fin . ' 23:59:59']);
            $resultados = $stmt->fetchAll();
            break;
    }

    $total = count($resultados);

    // ── EXPORTAR CSV ──
    if ($exportar && !empty($resultados)) {
        $nombres = [
            'citas_fecha'        => 'reporte_citas_por_fecha',
            'citas_medico'       => 'reporte_citas_por_medico',
            'citas_especialidad' => 'reporte_citas_por_especialidad',
            'pacientes_mes'      => 'reporte_pacientes_por_mes',
        ];
        $archivo = ($nombres[$tipo] ?? 'reporte') . '_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, $columnas);
        foreach ($resultados as $row) fputcsv($out, array_values($row));
        fclose($out);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/reportes.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/admin/layout.php'; ?>

  <div class="content">

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Reportes</h1>
        <p class="page-sub">Genera reportes del sistema por diferentes criterios.</p>
      </div>
    </div>

    <form method="GET" id="reporteForm">

      <!-- Selector tipo -->
      <div class="tipo-grid">
        <?php
        $tipos = [
          'citas_fecha'        => ['icon' => 'ti-calendar-stats', 'label' => 'Citas por fecha'],
          'citas_medico'       => ['icon' => 'ti-stethoscope',    'label' => 'Citas por médico'],
          'citas_especialidad' => ['icon' => 'ti-clipboard-list', 'label' => 'Citas por especialidad'],
          'pacientes_mes'      => ['icon' => 'ti-users',          'label' => 'Pacientes por mes'],
        ];
        foreach ($tipos as $val => $t): ?>
          <label class="tipo-card <?= $tipo === $val ? 'active' : '' ?>">
            <input type="radio" name="tipo" value="<?= $val ?>" <?= $tipo === $val ? 'checked' : '' ?> onchange="this.form.submit()">
            <i class="ti <?= $t['icon'] ?>"></i>
            <span><?= $t['label'] ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <!-- Filtros -->
      <div class="filtros-card">
        <div class="filtros-row">
          <div class="filtro-field">
            <label>Fecha inicio</label>
            <input type="date" name="fecha_ini" value="<?= $fecha_ini ?>" class="filtro-input">
          </div>
          <div class="filtro-field">
            <label>Fecha fin</label>
            <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" class="filtro-input">
          </div>
          <?php if ($tipo === 'citas_fecha'): ?>
          <div class="filtro-field">
            <label>Médico</label>
            <select name="medico_id" class="filtro-select">
              <option value="0">Todos los médicos</option>
              <?php foreach ($medicos as $m): ?>
                <option value="<?= $m['id'] ?>" <?= $medico_id === (int)$m['id'] ? 'selected' : '' ?>>
                  Dr. <?= htmlspecialchars($m['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filtro-field">
            <label>Especialidad</label>
            <select name="esp_id" class="filtro-select">
              <option value="0">Todas</option>
              <?php foreach ($especialidades as $e): ?>
                <option value="<?= $e['id'] ?>" <?= $esp_id === (int)$e['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($e['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="filtro-actions">
            <button type="submit" class="btn-generar">
              <i class="ti ti-search"></i> Generar
            </button>
            <?php if (!empty($resultados)): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['exportar' => '1'])) ?>"
                 class="btn-exportar">
                <i class="ti ti-download"></i> CSV
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </form>

    <!-- Resultados -->
    <?php if (isset($_GET['tipo'])): ?>
    <div class="table-card">
      <div class="table-header">
        <span class="table-titulo">
          <i class="ti ti-table"></i>
          <?= $tipos[$tipo]['label'] ?>
        </span>
        <span class="table-total"><?= $total ?> resultado<?= $total !== 1 ? 's' : '' ?></span>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <?php foreach ($columnas as $col): ?>
                <th><?= $col ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($resultados)): ?>
              <tr>
                <td colspan="<?= count($columnas) ?>">
                  <div class="empty-state">
                    <i class="ti ti-table-off"></i>
                    <p>No hay datos para el criterio seleccionado.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($resultados as $row): ?>
                <tr>
                  <?php foreach (array_values($row) as $k => $val): ?>
                    <td>
                      <?php
                      if ($columnas[$k] === 'Estatus') {
                          echo '<span class="badge ' . $val . '">' . ucfirst($val) . '</span>';
                      } elseif ($columnas[$k] === 'Fecha') {
                          echo date('d/m/Y', strtotime($val));
                      } elseif ($columnas[$k] === 'Hora') {
                          echo substr($val, 0, 5);
                      } else {
                          echo htmlspecialchars($val ?? '—');
                      }
                      ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/admin/layout.js"></script>
<script src="/CitaAgil1/assets/js/admin/reportes.js"></script>
</body>
</html>