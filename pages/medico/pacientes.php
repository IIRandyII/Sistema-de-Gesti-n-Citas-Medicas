<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('medico');

$current_page = 'pacientes';
$page_title   = 'Mis pacientes';

$pdo = getDB();

// ── OBTENER ID MÉDICO ──
$stmt = $pdo->prepare("SELECT m.id, e.nombre AS especialidad FROM medicos m JOIN especialidades e ON e.id = m.especialidad_id WHERE m.usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch();
$medico_id           = $medico['id'] ?? null;
$medico_especialidad = $medico['especialidad'] ?? 'Médico';

// ── AGREGAR NOTA ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'nota') {
    $cita_id    = (int)($_POST['cita_id']    ?? 0);
    $paciente_id= (int)($_POST['paciente_id']?? 0);
    $nota       = trim($_POST['nota']        ?? '');

    if ($nota && $cita_id && $paciente_id) {
        $pdo->prepare("INSERT INTO notas_consulta (cita_id, medico_id, paciente_id, nota) VALUES (?, ?, ?, ?)")
            ->execute([$cita_id, $medico_id, $paciente_id, $nota]);
    }
    header("Location: pacientes.php?pac={$paciente_id}&nota=1");
    exit;
}

// ── BÚSQUEDA ──
$buscar = trim($_GET['q'] ?? '');
$pac_id = (int)($_GET['pac'] ?? 0);

// ── LISTAR PACIENTES ──
$where  = "WHERE c.medico_id = ?";
$params = [$medico_id];

if ($buscar) {
    $where   .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ?)";
    $like     = "%{$buscar}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$stmt = $pdo->prepare("
    SELECT DISTINCT
        u.id, u.nombre, u.apellido, u.correo, u.telefono,
        COUNT(c.id) AS total_citas,
        MAX(c.fecha) AS ultima_cita,
        SUM(c.estatus = 'completada') AS completadas
    FROM citas c
    JOIN usuarios u ON c.paciente_id = u.id
    {$where}
    GROUP BY u.id, u.nombre, u.apellido, u.correo, u.telefono
    ORDER BY ultima_cita DESC
");
$stmt->execute($params);
$pacientes = $stmt->fetchAll();

// ── DETALLE PACIENTE ──
$paciente_detalle = null;
$historial        = [];
$notas            = [];

if ($pac_id) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$pac_id]);
    $paciente_detalle = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT c.id, c.fecha, c.hora, c.estatus, c.motivo,
               (SELECT COUNT(*) FROM notas_consulta n WHERE n.cita_id = c.id) AS tiene_nota
        FROM citas c
        WHERE c.medico_id = ? AND c.paciente_id = ?
        ORDER BY c.fecha DESC, c.hora DESC
    ");
    $stmt->execute([$medico_id, $pac_id]);
    $historial = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT n.*, c.fecha, c.hora
        FROM notas_consulta n
        JOIN citas c ON n.cita_id = c.id
        WHERE n.medico_id = ? AND n.paciente_id = ?
        ORDER BY n.creado_en DESC
    ");
    $stmt->execute([$medico_id, $pac_id]);
    $notas = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/pacientes.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/medico/layout.php'; ?>

  <div class="content">

    <?php if (isset($_GET['nota'])): ?>
      <div class="toast">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        Nota agregada correctamente.
      </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Mis pacientes</h1>
        <p class="page-sub">Historial de pacientes atendidos y notas de consulta.</p>
      </div>
    </div>

    <div class="pacientes-layout <?= $pac_id ? 'with-detail' : '' ?>">

      <!-- Lista de pacientes -->
      <div class="pacientes-list-wrap">
        <div class="list-toolbar">
          <form method="GET" class="search-form">
            <?php if ($pac_id): ?>
              <input type="hidden" name="pac" value="<?= $pac_id ?>">
            <?php endif; ?>
            <div class="search-wrap">
              <i class="ti ti-search search-icon"></i>
              <input type="text" name="q" class="search-input"
                     placeholder="Buscar paciente..."
                     value="<?= htmlspecialchars($buscar) ?>">
              <?php if ($buscar): ?>
                <a href="pacientes.php<?= $pac_id ? "?pac={$pac_id}" : '' ?>" class="search-clear">
                  <i class="ti ti-x"></i>
                </a>
              <?php endif; ?>
            </div>
          </form>
          <span class="list-count"><?= count($pacientes) ?> paciente<?= count($pacientes) !== 1 ? 's' : '' ?></span>
        </div>

        <div class="pacientes-list">
          <?php if (empty($pacientes)): ?>
            <div class="empty-state">
              <i class="ti ti-users-off"></i>
              <p><?= $buscar ? 'No se encontraron pacientes.' : 'Aún no tienes pacientes atendidos.' ?></p>
            </div>
          <?php else: ?>
            <?php foreach ($pacientes as $p): ?>
              <a href="?pac=<?= $p['id'] ?><?= $buscar ? '&q=' . urlencode($buscar) : '' ?>"
                 class="paciente-item <?= $pac_id === (int)$p['id'] ? 'active' : '' ?>">
                <div class="pac-avatar">
                  <?= strtoupper(substr($p['nombre'], 0, 1) . substr($p['apellido'], 0, 1)) ?>
                </div>
                <div class="pac-info">
                  <div class="pac-nombre"><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></div>
                  <div class="pac-meta">
                    <?= $p['total_citas'] ?> cita<?= $p['total_citas'] !== 1 ? 's' : '' ?> ·
                    Última: <?= $p['ultima_cita'] ? date('d/m/Y', strtotime($p['ultima_cita'])) : '—' ?>
                  </div>
                </div>
                <i class="ti ti-chevron-right pac-arrow"></i>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Detalle paciente -->
      <?php if ($pac_id && $paciente_detalle): ?>
      <div class="paciente-detail">

        <!-- Header -->
        <div class="detail-header">
          <div class="detail-avatar">
            <?= strtoupper(substr($paciente_detalle['nombre'], 0, 1) . substr($paciente_detalle['apellido'], 0, 1)) ?>
          </div>
          <div class="detail-info">
            <div class="detail-nombre"><?= htmlspecialchars($paciente_detalle['nombre'] . ' ' . $paciente_detalle['apellido']) ?></div>
            <div class="detail-meta">
              <?= htmlspecialchars($paciente_detalle['correo']) ?>
              <?= $paciente_detalle['telefono'] ? ' · ' . htmlspecialchars($paciente_detalle['telefono']) : '' ?>
            </div>
          </div>
          <a href="pacientes.php<?= $buscar ? '?q=' . urlencode($buscar) : '' ?>" class="detail-close">
            <i class="ti ti-x"></i>
          </a>
        </div>

        <!-- Tabs -->
        <div class="detail-tabs">
          <button class="detail-tab active" data-tab="historial">
            <i class="ti ti-calendar"></i> Historial
          </button>
          <button class="detail-tab" data-tab="notas">
            <i class="ti ti-notes"></i> Notas <span class="tab-count"><?= count($notas) ?></span>
          </button>
        </div>

        <!-- Tab: Historial -->
        <div class="tab-content active" id="tab-historial">
          <?php if (empty($historial)): ?>
            <div class="empty-state"><i class="ti ti-calendar-off"></i><p>Sin historial de citas.</p></div>
          <?php else: ?>
            <?php foreach ($historial as $c): ?>
              <div class="historial-item">
                <div class="hist-fecha">
                  <span class="hist-dia"><?= date('d', strtotime($c['fecha'])) ?></span>
                  <span class="hist-mes"><?= strtoupper(date('M', strtotime($c['fecha']))) ?></span>
                </div>
                <div class="hist-info">
                  <div class="hist-hora"><?= substr($c['hora'], 0, 5) ?> hrs</div>
                  <div class="hist-motivo"><?= htmlspecialchars($c['motivo'] ?? 'Sin motivo especificado') ?></div>
                </div>
                <span class="badge <?= $c['estatus'] ?>"><?= ucfirst($c['estatus']) ?></span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Tab: Notas -->
        <div class="tab-content" id="tab-notas">
          <!-- Agregar nota -->
          <div class="nota-form-wrap">
            <form method="POST" class="nota-form">
              <input type="hidden" name="action"      value="nota">
              <input type="hidden" name="paciente_id" value="<?= $pac_id ?>">
              <div class="nota-select-wrap">
                <select name="cita_id" class="nota-select" required>
                  <option value="">Selecciona una cita</option>
                  <?php foreach ($historial as $c): ?>
                    <option value="<?= $c['id'] ?>">
                      <?= date('d/m/Y', strtotime($c['fecha'])) ?> — <?= ucfirst($c['estatus']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <textarea name="nota" class="nota-textarea" placeholder="Escribe una nota de consulta..." required rows="3"></textarea>
              <button type="submit" class="btn-nota">
                <i class="ti ti-plus"></i> Agregar nota
              </button>
            </form>
          </div>

          <!-- Lista de notas -->
          <?php if (empty($notas)): ?>
            <div class="empty-state"><i class="ti ti-notes-off"></i><p>No hay notas para este paciente.</p></div>
          <?php else: ?>
            <?php foreach ($notas as $n): ?>
              <div class="nota-item">
                <div class="nota-meta">
                  <span class="nota-fecha">
                    <i class="ti ti-calendar"></i>
                    Cita del <?= date('d/m/Y', strtotime($n['fecha'])) ?> a las <?= substr($n['hora'], 0, 5) ?>
                  </span>
                  <span class="nota-time"><?= date('d/m/Y H:i', strtotime($n['creado_en'])) ?></span>
                </div>
                <div class="nota-texto"><?= nl2br(htmlspecialchars($n['nota'])) ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
      <?php elseif (!$pac_id): ?>
      <div class="detail-placeholder">
        <i class="ti ti-user-search"></i>
        <p>Selecciona un paciente para ver su historial y notas.</p>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/medico/layout.js"></script>
<script src="/CitaAgil1/assets/js/medico/pacientes.js"></script>
</body>
</html>