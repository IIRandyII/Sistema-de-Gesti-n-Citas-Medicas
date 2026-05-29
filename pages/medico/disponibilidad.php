<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('medico');

$current_page = 'disponibilidad';
$page_title   = 'Mi disponibilidad';

$pdo = getDB();

// ── OBTENER ID MÉDICO ──
$stmt = $pdo->prepare("SELECT m.id, e.nombre AS especialidad FROM medicos m JOIN especialidades e ON e.id = m.especialidad_id WHERE m.usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch();
$medico_id           = $medico['id'] ?? null;
$medico_especialidad = $medico['especialidad'] ?? 'Médico';

// ── GUARDAR HORARIO ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'horario') {
    $dias = $_POST['dias'] ?? [];

    // Eliminar disponibilidad actual
    $pdo->prepare("DELETE FROM disponibilidad_medico WHERE medico_id = ?")->execute([$medico_id]);

    // Insertar nueva disponibilidad
    foreach ($dias as $dia => $data) {
        if (!empty($data['activo'])) {
            $pdo->prepare("INSERT INTO disponibilidad_medico (medico_id, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)")
                ->execute([$medico_id, (int)$dia, $data['inicio'], $data['fin']]);
        }
    }
    header("Location: disponibilidad.php?horario=1");
    exit;
}

// ── AGREGAR EXCEPCIÓN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'excepcion') {
    $fecha  = $_POST['fecha']  ?? '';
    $motivo = trim($_POST['motivo'] ?? '');
    if ($fecha) {
        $existe = $pdo->prepare("SELECT id FROM excepciones_disponibilidad WHERE medico_id = ? AND fecha = ?");
        $existe->execute([$medico_id, $fecha]);
        if (!$existe->fetch()) {
            $pdo->prepare("INSERT INTO excepciones_disponibilidad (medico_id, fecha, motivo) VALUES (?, ?, ?)")
                ->execute([$medico_id, $fecha, $motivo ?: null]);
        }
    }
    header("Location: disponibilidad.php?excepcion=1");
    exit;
}

// ── ELIMINAR EXCEPCIÓN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'del_excepcion') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM excepciones_disponibilidad WHERE id = ? AND medico_id = ?")->execute([$id, $medico_id]);
    header("Location: disponibilidad.php?deleted=1");
    exit;
}

// ── CARGAR DISPONIBILIDAD ──
$stmt = $pdo->prepare("SELECT * FROM disponibilidad_medico WHERE medico_id = ? ORDER BY dia_semana");
$stmt->execute([$medico_id]);
$disponibilidad_raw = $stmt->fetchAll();

$disponibilidad = [];
foreach ($disponibilidad_raw as $d) {
    $disponibilidad[$d['dia_semana']] = $d;
}

// ── CARGAR EXCEPCIONES ──
$stmt = $pdo->prepare("SELECT * FROM excepciones_disponibilidad WHERE medico_id = ? AND fecha >= CURDATE() ORDER BY fecha ASC");
$stmt->execute([$medico_id]);
$excepciones = $stmt->fetchAll();

// ── CONFIGURACIÓN DEL SISTEMA ──
$config = [];
foreach ($pdo->query("SELECT clave, valor FROM configuracion") as $row) {
    $config[$row['clave']] = $row['valor'];
}

$dias_nombres = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/disponibilidad.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/medico/layout.php'; ?>

  <div class="content">

    <?php if (isset($_GET['horario'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Horario actualizado correctamente.</div>
    <?php elseif (isset($_GET['excepcion'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Excepción agregada correctamente.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Excepción eliminada.</div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Mi disponibilidad</h1>
        <p class="page-sub">Configura tus días y horarios de atención.</p>
      </div>
    </div>

    <div class="disp-grid">

      <!-- ── HORARIO ── -->
      <div class="disp-section">
        <div class="section-header">
          <div class="section-icon green"><i class="ti ti-clock"></i></div>
          <div>
            <div class="section-titulo">Horario de atención</div>
            <div class="section-sub">Define tus días y horas disponibles.</div>
          </div>
        </div>
        <form method="POST" class="disp-form">
          <input type="hidden" name="action" value="horario">
          <div class="dias-list">
            <?php foreach ($dias_nombres as $num => $nombre): ?>
              <?php $d = $disponibilidad[$num] ?? null; ?>
              <div class="dia-row">
                <label class="dia-toggle">
                  <input type="checkbox" name="dias[<?= $num ?>][activo]" value="1"
                         id="dia_<?= $num ?>"
                         <?= $d ? 'checked' : '' ?>
                         onchange="toggleDia(<?= $num ?>, this.checked)">
                  <span class="toggle-slider"></span>
                </label>
                <span class="dia-label <?= $d ? 'activo' : '' ?>" id="label_<?= $num ?>"><?= $nombre ?></span>
                <div class="dia-horario" id="horario_<?= $num ?>" style="<?= !$d ? 'opacity:.4;pointer-events:none' : '' ?>">
                  <input type="time" name="dias[<?= $num ?>][inicio]"
                         value="<?= $d ? $d['hora_inicio'] : ($config['horario_inicio'] ?? '08:00') ?>"
                         class="time-input">
                  <span class="time-sep">—</span>
                  <input type="time" name="dias[<?= $num ?>][fin]"
                         value="<?= $d ? $d['hora_fin'] : ($config['horario_fin'] ?? '18:00') ?>"
                         class="time-input">
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-guardar">
              <i class="ti ti-check"></i> Guardar horario
            </button>
          </div>
        </form>
      </div>

      <!-- ── EXCEPCIONES ── -->
      <div class="disp-section">
        <div class="section-header">
          <div class="section-icon amber"><i class="ti ti-calendar-off"></i></div>
          <div>
            <div class="section-titulo">Días no disponibles</div>
            <div class="section-sub">Fechas específicas en que no atenderás.</div>
          </div>
        </div>

        <!-- Agregar excepción -->
        <form method="POST" class="excep-form">
          <input type="hidden" name="action" value="excepcion">
          <div class="excep-inputs">
            <div class="form-field">
              <label>Fecha</label>
              <div class="input-icon-wrap">
                <i class="ti ti-calendar"></i>
                <input type="date" name="fecha" min="<?= date('Y-m-d') ?>" required>
              </div>
            </div>
            <div class="form-field">
              <label>Motivo <span class="hint">(opcional)</span></label>
              <input type="text" name="motivo" placeholder="Ej: Vacaciones, Congreso...">
            </div>
          </div>
          <button type="submit" class="btn-excepcion">
            <i class="ti ti-plus"></i> Agregar fecha
          </button>
        </form>

        <!-- Lista de excepciones -->
        <div class="excep-list">
          <?php if (empty($excepciones)): ?>
            <div class="empty-state">
              <i class="ti ti-calendar-check"></i>
              <p>No tienes fechas bloqueadas próximas.</p>
            </div>
          <?php else: ?>
            <?php foreach ($excepciones as $e): ?>
              <div class="excep-item">
                <div class="excep-fecha">
                  <span class="excep-dia"><?= date('d', strtotime($e['fecha'])) ?></span>
                  <span class="excep-mes"><?= strtoupper(date('M', strtotime($e['fecha']))) ?></span>
                </div>
                <div class="excep-info">
                  <div class="excep-label"><?= date('l', strtotime($e['fecha'])) ?></div>
                  <div class="excep-motivo"><?= htmlspecialchars($e['motivo'] ?? 'Sin motivo especificado') ?></div>
                </div>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="del_excepcion">
                  <input type="hidden" name="id"     value="<?= $e['id'] ?>">
                  <button type="submit" class="excep-del" title="Eliminar">
                    <i class="ti ti-trash"></i>
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/medico/layout.js"></script>
<script src="/CitaAgil1/assets/js/medico/disponibilidad.js"></script>
</body>
</html>