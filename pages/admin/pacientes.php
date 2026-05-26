<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$current_page = 'pacientes';
$page_title   = 'Pacientes';

$pdo = getDB();

// ── PARÁMETROS ──
$buscar  = trim($_GET['q']      ?? '');
$filtro  = $_GET['filtro']      ?? 'todos';
$pagina  = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 10;
$offset     = ($pagina - 1) * $por_pagina;

// ── ACTIVAR / DESACTIVAR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $id     = (int)$_POST['toggle_id'];
    $activo = (int)$_POST['toggle_activo'];
    $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id = ? AND rol = 'paciente'")
        ->execute([$activo ? 0 : 1, $id]);
    $nuevo = $activo ? 0 : 1;
    header("Location: pacientes.php?q={$buscar}&filtro={$filtro}&pagina={$pagina}&toggled={$nuevo}");
    exit;
}

// ── WHERE DINÁMICO ──
$where  = "WHERE rol = 'paciente'";
$params = [];

if ($buscar) {
    $where   .= " AND (nombre LIKE ? OR apellido LIKE ? OR correo LIKE ?)";
    $like     = "%{$buscar}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

if ($filtro === 'activos')   { $where .= " AND activo = 1"; }
if ($filtro === 'inactivos') { $where .= " AND activo = 0"; }

// ── TOTALES ──
$total_todos    = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'paciente'")->fetchColumn();
$total_activos  = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'paciente' AND activo = 1")->fetchColumn();
$total_inactivos= $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'paciente' AND activo = 0")->fetchColumn();

// ── TOTAL FILTRADO ──
$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios {$where}");
$stmt->execute($params);
$total_filtrado = $stmt->fetchColumn();
$total_paginas  = max(1, ceil($total_filtrado / $por_pagina));
$pagina         = min($pagina, $total_paginas);

// ── PACIENTES ──
$stmt = $pdo->prepare("
    SELECT id, nombre, apellido, correo, telefono, activo, creado_en,
           (SELECT COUNT(*) FROM citas WHERE paciente_id = usuarios.id) AS total_citas
    FROM usuarios {$where}
    ORDER BY creado_en DESC
    LIMIT {$por_pagina} OFFSET {$offset}
");
$stmt->execute($params);
$pacientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/pacientes.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/admin/layout.php'; ?>

  <div class="content">

    <!-- Encabezado -->
    <div class="page-header">
      <div>
        <h1 class="page-title-text">Pacientes</h1>
        <p class="page-sub">Gestiona los pacientes registrados en el sistema.</p>
      </div>
    </div>

    <!-- Tarjetas resumen -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon green"><i class="ti ti-users"></i></div>
        <div class="summary-info">
          <div class="summary-value"><?= $total_todos ?></div>
          <div class="summary-label">Total pacientes</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon blue"><i class="ti ti-user-check"></i></div>
        <div class="summary-info">
          <div class="summary-value"><?= $total_activos ?></div>
          <div class="summary-label">Activos</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon red"><i class="ti ti-user-off"></i></div>
        <div class="summary-info">
          <div class="summary-value"><?= $total_inactivos ?></div>
          <div class="summary-label">Inactivos</div>
        </div>
      </div>
    </div>

    <!-- Filtros y buscador -->
    <div class="table-card">
      <div class="table-toolbar">
        <div class="filter-tabs">
          <a href="?filtro=todos&q=<?= urlencode($buscar) ?>"
             class="filter-tab <?= $filtro === 'todos' ? 'active' : '' ?>">
            Todos <span class="filter-count"><?= $total_todos ?></span>
          </a>
          <a href="?filtro=activos&q=<?= urlencode($buscar) ?>"
             class="filter-tab <?= $filtro === 'activos' ? 'active' : '' ?>">
            Activos <span class="filter-count"><?= $total_activos ?></span>
          </a>
          <a href="?filtro=inactivos&q=<?= urlencode($buscar) ?>"
             class="filter-tab <?= $filtro === 'inactivos' ? 'active' : '' ?>">
            Inactivos <span class="filter-count"><?= $total_inactivos ?></span>
          </a>
        </div>
        <form method="GET" class="search-form">
          <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
          <div class="search-wrap">
            <i class="ti ti-search search-icon"></i>
            <input type="text" name="q" class="search-input"
                   placeholder="Buscar por nombre o correo..."
                   value="<?= htmlspecialchars($buscar) ?>">
            <?php if ($buscar): ?>
              <a href="?filtro=<?= $filtro ?>" class="search-clear"><i class="ti ti-x"></i></a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Tabla -->
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Correo</th>
              <th>Teléfono</th>
              <th>Citas</th>
              <th>Registro</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pacientes)): ?>
              <tr>
                <td colspan="7">
                  <div class="empty-state">
                    <i class="ti ti-users-off"></i>
                    <p><?= $buscar ? 'No se encontraron pacientes con esa búsqueda.' : 'No hay pacientes registrados aún.' ?></p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($pacientes as $p): ?>
                <tr>
                  <td>
                    <div class="patient-cell">
                      <div class="mini-avatar">
                        <?= strtoupper(substr($p['nombre'], 0, 1) . substr($p['apellido'], 0, 1)) ?>
                      </div>
                      <div>
                        <div class="patient-name"><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="text-muted"><?= htmlspecialchars($p['correo']) ?></td>
                  <td class="text-muted"><?= $p['telefono'] ? htmlspecialchars($p['telefono']) : '—' ?></td>
                  <td>
                    <span class="citas-badge"><?= $p['total_citas'] ?> citas</span>
                  </td>
                  <td class="text-muted"><?= date('d/m/Y', strtotime($p['creado_en'])) ?></td>
                  <td>
                    <span class="badge <?= $p['activo'] ? 'activo' : 'inactivo' ?>">
                      <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                  </td>
                  <td>
                    <div class="actions-cell">
                      <button class="action-icon" title="Ver detalle"
                              onclick="verDetalle(<?= htmlspecialchars(json_encode($p)) ?>)">
                        <i class="ti ti-eye"></i>
                      </button>
                      <form method="POST" style="display:inline">
                        <input type="hidden" name="toggle_id"     value="<?= $p['id'] ?>">
                        <input type="hidden" name="toggle_activo" value="<?= $p['activo'] ?>">
                        <button type="submit" class="action-icon <?= $p['activo'] ? 'danger' : 'success' ?>"
                                title="<?= $p['activo'] ? 'Desactivar' : 'Activar' ?>">
                          <i class="ti ti-<?= $p['activo'] ? 'user-off' : 'user-check' ?>"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <?php if ($total_paginas > 1): ?>
      <div class="pagination">
        <span class="pagination-info">
          Mostrando <?= min($offset + 1, $total_filtrado) ?>–<?= min($offset + $por_pagina, $total_filtrado) ?> de <?= $total_filtrado ?> pacientes
        </span>
        <div class="pagination-btns">
          <?php if ($pagina > 1): ?>
            <a href="?filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&pagina=<?= $pagina - 1 ?>"
               class="pag-btn"><i class="ti ti-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&pagina=<?= $i ?>"
               class="pag-btn <?= $i === $pagina ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($pagina < $total_paginas): ?>
            <a href="?filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&pagina=<?= $pagina + 1 ?>"
               class="pag-btn"><i class="ti ti-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Modal detalle paciente -->
<div class="modal-overlay" id="detalleModal">
  <div class="modal modal-detalle">
    <div class="modal-detalle-header">
      <div class="modal-detalle-avatar" id="detalleAvatar"></div>
      <div>
        <div class="modal-detalle-nombre" id="detalleNombre"></div>
        <div class="modal-detalle-rol">Paciente</div>
      </div>
      <button class="modal-close" id="detalleClose"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-detalle-body">
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-mail"></i> Correo</span>
        <span class="detalle-value" id="detalleCorreo"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-phone"></i> Teléfono</span>
        <span class="detalle-value" id="detalleTelefono"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-calendar"></i> Registro</span>
        <span class="detalle-value" id="detalleRegistro"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-notes-medical"></i> Citas totales</span>
        <span class="detalle-value" id="detalleCitas"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-toggle-right"></i> Estado</span>
        <span class="detalle-value" id="detalleEstado"></span>
      </div>
    </div>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/admin/layout.js"></script>
<script src="/CitaAgil1/assets/js/admin/pacientes.js"></script>
</body>
</html>