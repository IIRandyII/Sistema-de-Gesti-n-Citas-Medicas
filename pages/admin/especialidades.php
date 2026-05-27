<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$current_page = 'especialidades';
$page_title   = 'Especialidades';

$pdo = getDB();

// ── CREAR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear') {
    $nombre = trim($_POST['nombre'] ?? '');
    if (!$nombre) {
        $error = 'El nombre es requerido.';
    } else {
        $existe = $pdo->prepare("SELECT id FROM especialidades WHERE nombre = ? LIMIT 1");
        $existe->execute([$nombre]);
        if ($existe->fetch()) {
            $error = 'Ya existe una especialidad con ese nombre.';
        } else {
            $pdo->prepare("INSERT INTO especialidades (nombre) VALUES (?)")->execute([$nombre]);
            header("Location: especialidades.php?created=1"); exit;
        }
    }
}

// ── EDITAR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar') {
    $id     = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    if (!$nombre || !$id) {
        $error = 'Datos inválidos.';
    } else {
        $existe = $pdo->prepare("SELECT id FROM especialidades WHERE nombre = ? AND id != ? LIMIT 1");
        $existe->execute([$nombre, $id]);
        if ($existe->fetch()) {
            $error = 'Ya existe una especialidad con ese nombre.';
        } else {
            $pdo->prepare("UPDATE especialidades SET nombre = ? WHERE id = ?")->execute([$nombre, $id]);
            header("Location: especialidades.php?updated=1"); exit;
        }
    }
}

// ── ELIMINAR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    $tiene_medicos = $pdo->prepare("SELECT COUNT(*) FROM medicos WHERE especialidad_id = ?");
    $tiene_medicos->execute([$id]);
    if ($tiene_medicos->fetchColumn() > 0) {
        $error = 'No puedes eliminar una especialidad con médicos asignados.';
    } else {
        $pdo->prepare("DELETE FROM especialidades WHERE id = ?")->execute([$id]);
        header("Location: especialidades.php?deleted=1"); exit;
    }
}

// ── BÚSQUEDA ──
$buscar = trim($_GET['q'] ?? '');
$where  = '';
$params = [];

if ($buscar) {
    $where    = "WHERE e.nombre LIKE ?";
    $params[] = "%{$buscar}%";
}

// ── TOTAL ──
$total = $pdo->query("SELECT COUNT(*) FROM especialidades")->fetchColumn();

// ── LISTAR ──
$stmt = $pdo->prepare("
    SELECT e.id, e.nombre,
           COUNT(m.id) AS total_medicos
    FROM especialidades e
    LEFT JOIN medicos m ON m.especialidad_id = e.id
    {$where}
    GROUP BY e.id, e.nombre
    ORDER BY e.nombre
");
$stmt->execute($params);
$especialidades = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/especialidades.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/admin/layout.php'; ?>

  <div class="content">

    <?php if (isset($_GET['created'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Especialidad creada exitosamente.</div>
    <?php elseif (isset($_GET['updated'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Especialidad actualizada.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Especialidad eliminada.</div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="toast toast-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Especialidades</h1>
        <p class="page-sub">Gestiona las especialidades médicas del sistema.</p>
      </div>
      <button class="btn-primary-sm" id="btnNueva">
        <i class="ti ti-plus"></i> Nueva especialidad
      </button>
    </div>

    <!-- Resumen -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon green"><i class="ti ti-clipboard-list"></i></div>
        <div class="summary-info">
          <div class="summary-value"><?= $total ?></div>
          <div class="summary-label">Total especialidades</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon blue"><i class="ti ti-stethoscope"></i></div>
        <div class="summary-info">
          <div class="summary-value"><?= $pdo->query("SELECT COUNT(*) FROM medicos")->fetchColumn() ?></div>
          <div class="summary-label">Médicos registrados</div>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="table-card">
      <div class="table-toolbar">
        <span class="toolbar-title">
          <i class="ti ti-clipboard-list"></i> Lista de especialidades
        </span>
        <form method="GET" class="search-form">
          <div class="search-wrap">
            <i class="ti ti-search search-icon"></i>
            <input type="text" name="q" class="search-input"
                   placeholder="Buscar especialidad..."
                   value="<?= htmlspecialchars($buscar) ?>">
            <?php if ($buscar): ?>
              <a href="especialidades.php" class="search-clear"><i class="ti ti-x"></i></a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Médicos asignados</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($especialidades)): ?>
              <tr>
                <td colspan="4">
                  <div class="empty-state">
                    <i class="ti ti-clipboard-off"></i>
                    <p><?= $buscar ? 'No se encontraron especialidades.' : 'No hay especialidades registradas.' ?></p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($especialidades as $i => $esp): ?>
                <tr>
                  <td class="text-muted"><?= $i + 1 ?></td>
                  <td>
                    <div class="esp-name-cell">
                      <div class="esp-icon"><i class="ti ti-clipboard-list"></i></div>
                      <span class="esp-nombre"><?= htmlspecialchars($esp['nombre']) ?></span>
                    </div>
                  </td>
                  <td>
                    <span class="medicos-badge <?= $esp['total_medicos'] > 0 ? 'has' : 'empty' ?>">
                      <i class="ti ti-stethoscope"></i>
                      <?= $esp['total_medicos'] ?> <?= $esp['total_medicos'] === 1 ? 'médico' : 'médicos' ?>
                    </span>
                  </td>
                  <td>
                    <div class="actions-cell">
                      <button class="action-icon" title="Editar"
                              onclick="abrirEditar(<?= $esp['id'] ?>, '<?= htmlspecialchars(addslashes($esp['nombre'])) ?>')">
                        <i class="ti ti-edit"></i>
                      </button>
                      <button class="action-icon danger" title="Eliminar"
                              onclick="abrirEliminar(<?= $esp['id'] ?>, '<?= htmlspecialchars(addslashes($esp['nombre'])) ?>', <?= $esp['total_medicos'] ?>)">
                        <i class="ti ti-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal crear -->
<div class="modal-overlay" id="crearModal">
  <div class="modal modal-form">
    <div class="modal-form-header">
      <div class="modal-form-icon"><i class="ti ti-clipboard-plus"></i></div>
      <div>
        <div class="modal-form-titulo">Nueva especialidad</div>
        <div class="modal-form-sub">Ingresa el nombre de la especialidad.</div>
      </div>
      <button class="modal-close" id="crearClose"><i class="ti ti-x"></i></button>
    </div>
    <form method="POST" class="modal-form-body">
      <input type="hidden" name="action" value="crear">
      <div class="form-field">
        <label>Nombre <span class="req">*</span></label>
        <input type="text" name="nombre" placeholder="Ej: Neurología" required autofocus>
      </div>
      <div class="modal-form-actions">
        <button type="button" class="btn-cancel" id="crearCancel">Cancelar</button>
        <button type="submit" class="btn-submit"><i class="ti ti-check"></i> Crear especialidad</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal editar -->
<div class="modal-overlay" id="editarModal">
  <div class="modal modal-form">
    <div class="modal-form-header">
      <div class="modal-form-icon" style="background:var(--amber-light);color:var(--amber-main)"><i class="ti ti-edit"></i></div>
      <div>
        <div class="modal-form-titulo">Editar especialidad</div>
        <div class="modal-form-sub">Modifica el nombre de la especialidad.</div>
      </div>
      <button class="modal-close" id="editarClose"><i class="ti ti-x"></i></button>
    </div>
    <form method="POST" class="modal-form-body">
      <input type="hidden" name="action" value="editar">
      <input type="hidden" name="id"     id="editarId">
      <div class="form-field">
        <label>Nombre <span class="req">*</span></label>
        <input type="text" name="nombre" id="editarNombre" placeholder="Ej: Neurología" required>
      </div>
      <div class="modal-form-actions">
        <button type="button" class="btn-cancel" id="editarCancel">Cancelar</button>
        <button type="submit" class="btn-submit btn-amber"><i class="ti ti-check"></i> Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal eliminar -->
<div class="modal-overlay" id="eliminarModal">
  <div class="modal modal-confirm">
    <div class="modal-icon modal-icon-red"><i class="ti ti-trash"></i></div>
    <h3 class="modal-title">¿Eliminar especialidad?</h3>
    <p class="modal-desc" id="eliminarDesc"></p>
    <form method="POST" class="modal-actions">
      <input type="hidden" name="action" value="eliminar">
      <input type="hidden" name="id"     id="eliminarId">
      <button type="button" class="modal-btn modal-cancel-btn" id="eliminarCancel">Cancelar</button>
      <button type="submit" class="modal-btn modal-confirm-btn">Sí, eliminar</button>
    </form>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/admin/layout.js"></script>
<script src="/CitaAgil1/assets/js/admin/especialidades.js"></script>
</body>
</html>