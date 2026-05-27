<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$current_page = 'citas';
$page_title   = 'Citas';

$pdo = getDB();

// ── PARÁMETROS ──
$buscar    = trim($_GET['q']      ?? '');
$filtro    = $_GET['filtro']      ?? 'todos';
$fecha     = $_GET['fecha']       ?? '';
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina= 10;
$offset    = ($pagina - 1) * $por_pagina;

// ── TOTALES ──
$total_todos      = $pdo->query("SELECT COUNT(*) FROM citas")->fetchColumn();
$total_hoy        = $pdo->query("SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()")->fetchColumn();
$total_pendientes = $pdo->query("SELECT COUNT(*) FROM citas WHERE estatus = 'pendiente'")->fetchColumn();
$total_completadas= $pdo->query("SELECT COUNT(*) FROM citas WHERE estatus = 'completada'")->fetchColumn();

// ── WHERE DINÁMICO ──
$where  = "WHERE 1=1";
$params = [];

if ($buscar) {
    $where   .= " AND (p.nombre LIKE ? OR p.apellido LIKE ? OR m.nombre LIKE ? OR m.apellido LIKE ?)";
    $like     = "%{$buscar}%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

if ($filtro !== 'todos') { $where .= " AND c.estatus = ?"; $params[] = $filtro; }
if ($fecha)              { $where .= " AND c.fecha = ?";   $params[] = $fecha;  }

// ── TOTAL FILTRADO ──
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM citas c
    JOIN usuarios p ON c.paciente_id = p.id
    JOIN medicos med ON c.medico_id = med.id
    JOIN usuarios m ON med.usuario_id = m.id
    {$where}
");
$stmt->execute($params);
$total_filtrado = $stmt->fetchColumn();
$total_paginas  = max(1, ceil($total_filtrado / $por_pagina));
$pagina         = min($pagina, $total_paginas);

// ── CITAS ──
$stmt = $pdo->prepare("
    SELECT
        c.id, c.fecha, c.hora, c.motivo, c.estatus, c.creado_en,
        CONCAT(p.nombre, ' ', p.apellido) AS paciente,
        p.nombre AS paciente_nombre, p.apellido AS paciente_apellido,
        CONCAT(m.nombre, ' ', m.apellido) AS medico,
        e.nombre AS especialidad
    FROM citas c
    JOIN usuarios p   ON c.paciente_id = p.id
    JOIN medicos med  ON c.medico_id   = med.id
    JOIN usuarios m   ON med.usuario_id = m.id
    JOIN especialidades e ON med.especialidad_id = e.id
    {$where}
    ORDER BY c.fecha DESC, c.hora DESC
    LIMIT {$por_pagina} OFFSET {$offset}
");
$stmt->execute($params);
$citas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/citas.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/admin/layout.php'; ?>

  <div class="content">

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Citas</h1>
        <p class="page-sub">Historial completo de citas médicas del sistema.</p>
      </div>
    </div>

    <!-- Tarjetas resumen -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon green"><i class="ti ti-calendar"></i></div>
        <div class="summary-info">
          <div class="summary-value"><?= $total_todos ?></div>
          <div class="summary-label">Total citas</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon blue"><i class="ti ti-calendar-event"></i></div>
        <div class="summary-info">
          <div class="summary-value"><?= $total_hoy ?></div>
          <div class="summary-label">Citas hoy</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon amber"><i class="ti ti-clock-hour-4"></i></div>
        <div class="summary-info">
          <div class="summary-value"><?= $total_pendientes ?></div>
          <div class="summary-label">Pendientes</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon teal"><i class="ti ti-circle-check"></i></div>
        <div class="summary-info">
          <div class="summary-value"><?= $total_completadas ?></div>
          <div class="summary-label">Completadas</div>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="table-card">
      <div class="table-toolbar">
        <div class="toolbar-left">
          <div class="filter-tabs">
            <?php
            $filtros = ['todos' => 'Todos', 'pendiente' => 'Pendientes',
                        'confirmada' => 'Confirmadas', 'cancelada' => 'Canceladas', 'completada' => 'Completadas'];
            foreach ($filtros as $val => $label): ?>
              <a href="?filtro=<?= $val ?>&q=<?= urlencode($buscar) ?>&fecha=<?= urlencode($fecha) ?>"
                 class="filter-tab <?= $filtro === $val ? 'active' : '' ?>">
                <?= $label ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="toolbar-right">
          <form method="GET" class="fecha-form">
            <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
            <input type="hidden" name="q"      value="<?= htmlspecialchars($buscar) ?>">
            <div class="fecha-wrap">
              <i class="ti ti-calendar"></i>
              <input type="date" name="fecha" class="fecha-input"
                     value="<?= htmlspecialchars($fecha) ?>"
                     onchange="this.form.submit()">
            </div>
          </form>
          <form method="GET" class="search-form">
            <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
            <input type="hidden" name="fecha"  value="<?= htmlspecialchars($fecha) ?>">
            <div class="search-wrap">
              <i class="ti ti-search search-icon"></i>
              <input type="text" name="q" class="search-input"
                     placeholder="Buscar paciente o médico..."
                     value="<?= htmlspecialchars($buscar) ?>">
              <?php if ($buscar): ?>
                <a href="?filtro=<?= $filtro ?>&fecha=<?= urlencode($fecha) ?>" class="search-clear">
                  <i class="ti ti-x"></i>
                </a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Médico</th>
              <th>Especialidad</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Estatus</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($citas)): ?>
              <tr>
                <td colspan="7">
                  <div class="empty-state">
                    <i class="ti ti-calendar-off"></i>
                    <p><?= $buscar || $fecha ? 'No se encontraron citas con ese criterio.' : 'No hay citas registradas aún.' ?></p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($citas as $c): ?>
                <tr>
                  <td>
                    <div class="patient-cell">
                      <div class="mini-avatar">
                        <?= strtoupper(substr($c['paciente_nombre'], 0, 1) . substr($c['paciente_apellido'], 0, 1)) ?>
                      </div>
                      <span class="patient-name"><?= htmlspecialchars($c['paciente']) ?></span>
                    </div>
                  </td>
                  <td class="text-muted">Dr. <?= htmlspecialchars($c['medico']) ?></td>
                  <td><span class="esp-badge"><?= htmlspecialchars($c['especialidad']) ?></span></td>
                  <td class="text-muted"><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                  <td class="text-muted"><?= substr($c['hora'], 0, 5) ?></td>
                  <td><span class="badge <?= $c['estatus'] ?>"><?= ucfirst($c['estatus']) ?></span></td>
                  <td>
                    <button class="action-icon" title="Ver detalle"
                            onclick="verDetalle(<?= htmlspecialchars(json_encode($c)) ?>)">
                      <i class="ti ti-eye"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($total_paginas > 1): ?>
      <div class="pagination">
        <span class="pagination-info">
          Mostrando <?= min($offset + 1, $total_filtrado) ?>–<?= min($offset + $por_pagina, $total_filtrado) ?> de <?= $total_filtrado ?> citas
        </span>
        <div class="pagination-btns">
          <?php if ($pagina > 1): ?>
            <a href="?filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&fecha=<?= urlencode($fecha) ?>&pagina=<?= $pagina - 1 ?>" class="pag-btn"><i class="ti ti-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&fecha=<?= urlencode($fecha) ?>&pagina=<?= $i ?>"
               class="pag-btn <?= $i === $pagina ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($pagina < $total_paginas): ?>
            <a href="?filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&fecha=<?= urlencode($fecha) ?>&pagina=<?= $pagina + 1 ?>" class="pag-btn"><i class="ti ti-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal detalle cita -->
<div class="modal-overlay" id="detalleModal">
  <div class="modal modal-detalle">
    <div class="modal-detalle-header">
      <div class="modal-detalle-icon"><i class="ti ti-calendar-event"></i></div>
      <div>
        <div class="modal-detalle-titulo">Detalle de cita</div>
        <div class="modal-detalle-sub" id="detalleEstadoBadge"></div>
      </div>
      <button class="modal-close" id="detalleClose"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-detalle-body">
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-user"></i> Paciente</span>
        <span class="detalle-value" id="detallePaciente"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-stethoscope"></i> Médico</span>
        <span class="detalle-value" id="detalleMedico"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-clipboard-list"></i> Especialidad</span>
        <span class="detalle-value" id="detalleEspecialidad"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-calendar"></i> Fecha</span>
        <span class="detalle-value" id="detalleFecha"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-clock"></i> Hora</span>
        <span class="detalle-value" id="detalleHora"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-notes"></i> Motivo</span>
        <span class="detalle-value" id="detalleMotivo"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-calendar-plus"></i> Registrada</span>
        <span class="detalle-value" id="detalleRegistro"></span>
      </div>
    </div>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/admin/layout.js"></script>
<script src="/CitaAgil1/assets/js/admin/citas.js"></script>
</body>
</html>