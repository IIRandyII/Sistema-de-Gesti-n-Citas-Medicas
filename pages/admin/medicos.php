<?php
// ============================================================
//  pages/admin/medicos.php
//  CitaÁgil · Sistema de citas médicas
// ============================================================

require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$current_page = 'medicos';
$page_title   = 'Médicos';

$pdo = getDB();

// ── CREAR MÉDICO ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $nombre       = trim($_POST['nombre']       ?? '');
    $apellido     = trim($_POST['apellido']     ?? '');
    $correo       = trim($_POST['correo']       ?? '');
    $telefono     = trim($_POST['telefono']     ?? '');
    $password     = $_POST['password']          ?? '';
    $especialidad = (int)($_POST['especialidad_id'] ?? 0);
    $cedula       = trim($_POST['cedula']       ?? '');
    $error_crear  = '';

    if (!$nombre || !$apellido || !$correo || !$password || !$especialidad) {
        $error_crear = 'Completa todos los campos obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error_crear = 'Correo electrónico no válido.';
    } elseif (strlen($password) < 8) {
        $error_crear = 'La contraseña debe tener mínimo 8 caracteres.';
    } else {
        $existe = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
        $existe->execute([$correo]);
        if ($existe->fetch()) {
            $error_crear = 'Ya existe un usuario con ese correo.';
        } else {
            try {
                $pdo->beginTransaction();
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO usuarios (nombre, apellido, correo, telefono, password_hash, rol) VALUES (?, ?, ?, ?, ?, 'medico')")
                    ->execute([$nombre, $apellido, $correo, $telefono ?: null, $hash]);
                $nuevo_id = $pdo->lastInsertId();
                $pdo->prepare("INSERT INTO medicos (usuario_id, especialidad_id, cedula) VALUES (?, ?, ?)")
                    ->execute([$nuevo_id, $especialidad, $cedula ?: null]);
                $pdo->commit();
                header("Location: medicos.php?created=1");
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error_crear = 'Error al crear el médico. Intenta de nuevo.';
                error_log($e->getMessage());
            }
        }
    }
}

// ── ACTIVAR / DESACTIVAR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $id     = (int)$_POST['toggle_id'];
    $activo = (int)$_POST['toggle_activo'];
    $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id = ? AND rol = 'medico'")
        ->execute([$activo ? 0 : 1, $id]);
    header("Location: medicos.php?q={$buscar}&filtro={$filtro}&especialidad={$filtro_esp}&pagina={$pagina}");
    exit;
}

// ── PARÁMETROS ──
$buscar     = trim($_GET['q']             ?? '');
$filtro     = $_GET['filtro']             ?? 'todos';
$filtro_esp = (int)($_GET['especialidad'] ?? 0);
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 10;
$offset     = ($pagina - 1) * $por_pagina;

// ── ESPECIALIDADES ──
$especialidades = $pdo->query("SELECT * FROM especialidades ORDER BY nombre")->fetchAll();

// ── WHERE DINÁMICO ──
$where  = "WHERE u.rol = 'medico'";
$params = [];

if ($buscar) {
    $where   .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ?)";
    $like     = "%{$buscar}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

if ($filtro === 'activos')   { $where .= " AND u.activo = 1"; }
if ($filtro === 'inactivos') { $where .= " AND u.activo = 0"; }
if ($filtro_esp > 0)         { $where .= " AND m.especialidad_id = ?"; $params[] = $filtro_esp; }

// ── TOTALES ──
$total_todos     = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'medico'")->fetchColumn();
$total_activos   = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'medico' AND activo = 1")->fetchColumn();
$total_inactivos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'medico' AND activo = 0")->fetchColumn();

// ── TOTAL FILTRADO ──
$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios u LEFT JOIN medicos m ON m.usuario_id = u.id {$where}");
$stmt->execute($params);
$total_filtrado = $stmt->fetchColumn();
$total_paginas  = max(1, ceil($total_filtrado / $por_pagina));
$pagina         = min($pagina, $total_paginas);

// ── MÉDICOS ──
$stmt = $pdo->prepare("
    SELECT
        u.id, u.nombre, u.apellido, u.correo, u.telefono, u.activo, u.creado_en,
        m.cedula, m.especialidad_id,
        e.nombre AS especialidad,
        (SELECT COUNT(*) FROM citas c WHERE c.medico_id = m.id AND c.estatus = 'pendiente') AS citas_pendientes,
        (SELECT COUNT(*) FROM citas c WHERE c.medico_id = m.id AND c.fecha = CURDATE()) AS citas_hoy
    FROM usuarios u
    LEFT JOIN medicos m ON m.usuario_id = u.id
    LEFT JOIN especialidades e ON e.id = m.especialidad_id
    {$where}
    ORDER BY u.creado_en DESC
    LIMIT {$por_pagina} OFFSET {$offset}
");
$stmt->execute($params);
$medicos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/medicos.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/admin/layout.php'; ?>

  <div class="content">

    <?php if (isset($_GET['created'])): ?>
      <div class="toast" id="toastCreado">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        Médico creado exitosamente.
      </div>
    <?php endif; ?>

    <?php if (!empty($error_crear)): ?>
      <div class="toast toast-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error_crear) ?>
      </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Médicos</h1>
        <p class="page-sub">Gestiona los médicos registrados en el sistema.</p>
      </div>
      <button class="btn-primary-sm" id="btnNuevoMedico">
        <i class="ti ti-plus"></i> Nuevo médico
      </button>
    </div>

    <!-- Tarjetas resumen -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon green"><i class="ti ti-stethoscope"></i></div>
        <div class="summary-info">
          <div class="summary-value"><?= $total_todos ?></div>
          <div class="summary-label">Total médicos</div>
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

    <!-- Tabla -->
    <div class="table-card">
      <div class="table-toolbar">
        <div class="toolbar-left">
          <div class="filter-tabs">
            <a href="?filtro=todos&q=<?= urlencode($buscar) ?>&especialidad=<?= $filtro_esp ?>"
               class="filter-tab <?= $filtro === 'todos' ? 'active' : '' ?>">
              Todos <span class="filter-count"><?= $total_todos ?></span>
            </a>
            <a href="?filtro=activos&q=<?= urlencode($buscar) ?>&especialidad=<?= $filtro_esp ?>"
               class="filter-tab <?= $filtro === 'activos' ? 'active' : '' ?>">
              Activos <span class="filter-count"><?= $total_activos ?></span>
            </a>
            <a href="?filtro=inactivos&q=<?= urlencode($buscar) ?>&especialidad=<?= $filtro_esp ?>"
               class="filter-tab <?= $filtro === 'inactivos' ? 'active' : '' ?>">
              Inactivos <span class="filter-count"><?= $total_inactivos ?></span>
            </a>
          </div>
          <form method="GET" id="filtroEspForm">
            <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
            <input type="hidden" name="q"      value="<?= htmlspecialchars($buscar) ?>">
            <select name="especialidad" class="esp-select" onchange="document.getElementById('filtroEspForm').submit()">
              <option value="0">Todas las especialidades</option>
              <?php foreach ($especialidades as $esp): ?>
                <option value="<?= $esp['id'] ?>" <?= $filtro_esp === (int)$esp['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($esp['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
        <form method="GET" class="search-form">
          <input type="hidden" name="filtro"       value="<?= htmlspecialchars($filtro) ?>">
          <input type="hidden" name="especialidad" value="<?= $filtro_esp ?>">
          <div class="search-wrap">
            <i class="ti ti-search search-icon"></i>
            <input type="text" name="q" class="search-input"
                   placeholder="Buscar por nombre o correo..."
                   value="<?= htmlspecialchars($buscar) ?>">
            <?php if ($buscar): ?>
              <a href="?filtro=<?= $filtro ?>&especialidad=<?= $filtro_esp ?>" class="search-clear">
                <i class="ti ti-x"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Médico</th>
              <th>Especialidad</th>
              <th>Cédula</th>
              <th>Citas pendientes</th>
              <th>Disponibilidad hoy</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($medicos)): ?>
              <tr>
                <td colspan="7">
                  <div class="empty-state">
                    <i class="ti ti-stethoscope-off"></i>
                    <p><?= $buscar ? 'No se encontraron médicos con esa búsqueda.' : 'No hay médicos registrados aún.' ?></p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($medicos as $m): ?>
                <tr>
                  <td>
                    <div class="patient-cell">
                      <div class="mini-avatar">
                        <?= strtoupper(substr($m['nombre'], 0, 1) . substr($m['apellido'], 0, 1)) ?>
                      </div>
                      <div>
                        <div class="patient-name">Dr. <?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?></div>
                        <div class="patient-email"><?= htmlspecialchars($m['correo']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td><span class="esp-badge"><?= htmlspecialchars($m['especialidad'] ?? '—') ?></span></td>
                  <td class="text-muted"><?= htmlspecialchars($m['cedula'] ?? '—') ?></td>
                  <td>
                    <span class="citas-badge <?= $m['citas_pendientes'] > 0 ? 'amber' : 'green' ?>">
                      <?= $m['citas_pendientes'] ?> pendientes
                    </span>
                  </td>
                  <td>
                    <?php if ($m['citas_hoy'] > 0): ?>
                      <span class="disponibilidad ocupado"><i class="ti ti-calendar-event"></i> <?= $m['citas_hoy'] ?> citas hoy</span>
                    <?php else: ?>
                      <span class="disponibilidad libre"><i class="ti ti-circle-check"></i> Libre</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= $m['activo'] ? 'activo' : 'inactivo' ?>">
                      <?= $m['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                  </td>
                  <td>
                    <div class="actions-cell">
                      <button class="action-icon" title="Ver detalle"
                              onclick="verDetalle(<?= htmlspecialchars(json_encode($m)) ?>)">
                        <i class="ti ti-eye"></i>
                      </button>
                      <form method="POST" style="display:inline">
                        <input type="hidden" name="toggle_id"     value="<?= $m['id'] ?>">
                        <input type="hidden" name="toggle_activo" value="<?= $m['activo'] ?>">
                        <button type="submit" class="action-icon <?= $m['activo'] ? 'danger' : 'success' ?>"
                                title="<?= $m['activo'] ? 'Desactivar' : 'Activar' ?>">
                          <i class="ti ti-<?= $m['activo'] ? 'user-off' : 'user-check' ?>"></i>
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

      <?php if ($total_paginas > 1): ?>
      <div class="pagination">
        <span class="pagination-info">
          Mostrando <?= min($offset + 1, $total_filtrado) ?>–<?= min($offset + $por_pagina, $total_filtrado) ?> de <?= $total_filtrado ?> médicos
        </span>
        <div class="pagination-btns">
          <?php if ($pagina > 1): ?>
            <a href="?filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&especialidad=<?= $filtro_esp ?>&pagina=<?= $pagina - 1 ?>" class="pag-btn"><i class="ti ti-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&especialidad=<?= $filtro_esp ?>&pagina=<?= $i ?>"
               class="pag-btn <?= $i === $pagina ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($pagina < $total_paginas): ?>
            <a href="?filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&especialidad=<?= $filtro_esp ?>&pagina=<?= $pagina + 1 ?>" class="pag-btn"><i class="ti ti-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal detalle médico -->
<div class="modal-overlay" id="detalleModal">
  <div class="modal modal-detalle">
    <div class="modal-detalle-header">
      <div class="modal-detalle-avatar" id="detalleAvatar"></div>
      <div>
        <div class="modal-detalle-nombre" id="detalleNombre"></div>
        <div class="modal-detalle-rol"    id="detalleEspecialidad"></div>
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
        <span class="detalle-label"><i class="ti ti-id-badge"></i> Cédula</span>
        <span class="detalle-value" id="detalleCedula"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-clock"></i> Citas pendientes</span>
        <span class="detalle-value" id="detallePendientes"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-calendar"></i> Citas hoy</span>
        <span class="detalle-value" id="detalleHoy"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-calendar-plus"></i> Registro</span>
        <span class="detalle-value" id="detalleRegistro"></span>
      </div>
      <div class="detalle-row">
        <span class="detalle-label"><i class="ti ti-toggle-right"></i> Estado</span>
        <span class="detalle-value" id="detalleEstado"></span>
      </div>
    </div>
  </div>
</div>

<!-- Modal nuevo médico -->
<div class="modal-overlay" id="nuevoModal">
  <div class="modal modal-form">
    <div class="modal-form-header">
      <div class="modal-form-icon"><i class="ti ti-user-plus"></i></div>
      <div>
        <div class="modal-form-titulo">Nuevo médico</div>
        <div class="modal-form-sub">Completa los datos del nuevo médico.</div>
      </div>
      <button class="modal-close" id="nuevoClose"><i class="ti ti-x"></i></button>
    </div>
    <form method="POST" class="modal-form-body" id="formNuevoMedico">
      <input type="hidden" name="action" value="crear">
      <div class="form-row-2">
        <div class="form-field">
          <label>Nombre <span class="req">*</span></label>
          <input type="text" name="nombre" placeholder="Juan" required>
        </div>
        <div class="form-field">
          <label>Apellido <span class="req">*</span></label>
          <input type="text" name="apellido" placeholder="García" required>
        </div>
      </div>
      <div class="form-field">
        <label>Correo electrónico <span class="req">*</span></label>
        <div class="input-icon-wrap">
          <i class="ti ti-mail"></i>
          <input type="email" name="correo" placeholder="doctor@correo.com" required>
        </div>
      </div>
      <div class="form-row-2">
        <div class="form-field">
          <label>Teléfono</label>
          <input type="tel" name="telefono" placeholder="8112345678" maxlength="10" id="nuevoTelefono">
        </div>
        <div class="form-field">
          <label>Cédula profesional</label>
          <input type="text" name="cedula" placeholder="CED-001">
        </div>
      </div>
      <div class="form-field">
        <label>Especialidad <span class="req">*</span></label>
        <select name="especialidad_id" class="form-select" required>
          <option value="">Selecciona una especialidad</option>
          <?php foreach ($especialidades as $esp): ?>
            <option value="<?= $esp['id'] ?>"><?= htmlspecialchars($esp['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-field">
        <label>Contraseña <span class="req">*</span> <span class="hint">(mínimo 8 caracteres)</span></label>
        <div class="input-icon-wrap has-right">
          <i class="ti ti-lock"></i>
          <input type="password" name="password" id="nuevoPass" placeholder="••••••••" required minlength="8">
          <span class="icon-right-pass" id="toggleNuevoPass">
            <i class="ti ti-eye"></i>
          </span>
        </div>
      </div>
      <div class="modal-form-actions">
        <button type="button" class="btn-cancel" id="nuevoCancelBtn">Cancelar</button>
        <button type="submit" class="btn-submit">
          <i class="ti ti-check"></i> Crear médico
        </button>
      </div>
    </form>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/admin/layout.js"></script>
<script src="/CitaAgil1/assets/js/admin/medicos.js"></script>
</body>
</html>