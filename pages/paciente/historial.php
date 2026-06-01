<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('paciente');

$current_page = 'historial';
$page_title   = 'Historial médico';

$pdo = getDB();
$paciente_id = $_SESSION['user_id'];

// ── PARÁMETROS ──
$buscar = trim($_GET['q'] ?? '');
$cita_id= (int)($_GET['cita'] ?? 0);

// ── CITAS COMPLETADAS ──
$where  = "WHERE c.paciente_id = ? AND c.estatus = 'completada'";
$params = [$paciente_id];

if ($buscar) {
    $where   .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR e.nombre LIKE ?)";
    $like     = "%{$buscar}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$stmt = $pdo->prepare("
    SELECT c.id, c.fecha, c.hora, c.motivo,
           CONCAT(u.nombre, ' ', u.apellido) AS medico,
           u.nombre AS med_nombre, u.apellido AS med_apellido,
           e.nombre AS especialidad,
           (SELECT COUNT(*) FROM notas_consulta n WHERE n.cita_id = c.id) AS total_notas
    FROM citas c
    JOIN medicos m ON c.medico_id = m.id
    JOIN usuarios u ON m.usuario_id = u.id
    JOIN especialidades e ON m.especialidad_id = e.id
    {$where}
    ORDER BY c.fecha DESC, c.hora DESC
");
$stmt->execute($params);
$historial = $stmt->fetchAll();

// ── DETALLE CITA ──
$cita_detalle = null;
$notas        = [];

if ($cita_id) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.fecha, c.hora, c.motivo,
               CONCAT(u.nombre, ' ', u.apellido) AS medico,
               u.nombre AS med_nombre, u.apellido AS med_apellido,
               e.nombre AS especialidad
        FROM citas c
        JOIN medicos m ON c.medico_id = m.id
        JOIN usuarios u ON m.usuario_id = u.id
        JOIN especialidades e ON m.especialidad_id = e.id
        WHERE c.id = ? AND c.paciente_id = ? AND c.estatus = 'completada'
        LIMIT 1
    ");
    $stmt->execute([$cita_id, $paciente_id]);
    $cita_detalle = $stmt->fetch();

    if ($cita_detalle) {
        $stmt = $pdo->prepare("
            SELECT n.nota, n.creado_en,
                   CONCAT(u.nombre, ' ', u.apellido) AS medico
            FROM notas_consulta n
            JOIN usuarios u ON u.id = (SELECT usuario_id FROM medicos WHERE id = n.medico_id)
            WHERE n.cita_id = ?
            ORDER BY n.creado_en ASC
        ");
        $stmt->execute([$cita_id]);
        $notas = $stmt->fetchAll();
    }
}

$meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/paciente/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/paciente/historial.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/paciente/layout.php'; ?>

  <div class="content">

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Historial médico</h1>
        <p class="page-sub">Consultas completadas y notas de tu médico.</p>
      </div>
    </div>

    <div class="historial-layout <?= $cita_id ? 'with-detail' : '' ?>">

      <!-- Lista de consultas -->
      <div class="historial-list-wrap">
        <div class="list-toolbar">
          <form method="GET" class="search-form">
            <?php if ($cita_id): ?>
              <input type="hidden" name="cita" value="<?= $cita_id ?>">
            <?php endif; ?>
            <div class="search-wrap">
              <i class="ti ti-search search-icon"></i>
              <input type="text" name="q" class="search-input"
                     placeholder="Buscar médico o especialidad..."
                     value="<?= htmlspecialchars($buscar) ?>">
              <?php if ($buscar): ?>
                <a href="historial.php<?= $cita_id ? "?cita={$cita_id}" : '' ?>" class="search-clear">
                  <i class="ti ti-x"></i>
                </a>
              <?php endif; ?>
            </div>
          </form>
          <span class="list-count"><?= count($historial) ?> consulta<?= count($historial) !== 1 ? 's' : '' ?></span>
        </div>

        <div class="historial-list">
          <?php if (empty($historial)): ?>
            <div class="empty-state">
              <i class="ti ti-notes-medical"></i>
              <p><?= $buscar ? 'No se encontraron consultas.' : 'No tienes consultas completadas aún.' ?></p>
            </div>
          <?php else: ?>
            <?php foreach ($historial as $h): ?>
              <a href="?cita=<?= $h['id'] ?><?= $buscar ? '&q=' . urlencode($buscar) : '' ?>"
                 class="historial-item <?= $cita_id === (int)$h['id'] ? 'active' : '' ?>">
                <div class="hist-fecha-badge">
                  <span class="hist-dia"><?= date('d', strtotime($h['fecha'])) ?></span>
                  <span class="hist-mes"><?= strtoupper(date('M', strtotime($h['fecha']))) ?></span>
                </div>
                <div class="hist-info">
                  <div class="hist-medico">Dr. <?= htmlspecialchars($h['medico']) ?></div>
                  <div class="hist-esp"><?= htmlspecialchars($h['especialidad']) ?></div>
                  <?php if ($h['total_notas'] > 0): ?>
                    <div class="hist-notas-badge">
                      <i class="ti ti-notes"></i> <?= $h['total_notas'] ?> nota<?= $h['total_notas'] !== 1 ? 's' : '' ?>
                    </div>
                  <?php endif; ?>
                </div>
                <i class="ti ti-chevron-right hist-arrow"></i>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Detalle consulta -->
      <?php if ($cita_id && $cita_detalle): ?>
      <div class="consulta-detail">

        <div class="detail-header">
          <div class="detail-icon"><i class="ti ti-notes-medical"></i></div>
          <div class="detail-info">
            <div class="detail-titulo">Dr. <?= htmlspecialchars($cita_detalle['medico']) ?></div>
            <div class="detail-meta"><?= htmlspecialchars($cita_detalle['especialidad']) ?></div>
          </div>
          <a href="historial.php<?= $buscar ? '?q=' . urlencode($buscar) : '' ?>" class="detail-close">
            <i class="ti ti-x"></i>
          </a>
        </div>

        <!-- Info cita -->
        <div class="cita-info-wrap">
          <div class="cita-info-row">
            <span class="ci-label"><i class="ti ti-calendar"></i> Fecha</span>
            <span class="ci-value">
              <?= date('d', strtotime($cita_detalle['fecha'])) ?> de
              <?= $meses[date('n', strtotime($cita_detalle['fecha'])) - 1] ?> de
              <?= date('Y', strtotime($cita_detalle['fecha'])) ?>
            </span>
          </div>
          <div class="cita-info-row">
            <span class="ci-label"><i class="ti ti-clock"></i> Hora</span>
            <span class="ci-value"><?= substr($cita_detalle['hora'], 0, 5) ?> hrs</span>
          </div>
          <?php if ($cita_detalle['motivo']): ?>
          <div class="cita-info-row">
            <span class="ci-label"><i class="ti ti-notes"></i> Motivo</span>
            <span class="ci-value"><?= htmlspecialchars($cita_detalle['motivo']) ?></span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Notas del médico -->
        <div class="notas-section">
          <div class="notas-titulo">
            <i class="ti ti-clipboard-text"></i> Notas del médico
          </div>
          <?php if (empty($notas)): ?>
            <div class="empty-state" style="padding:24px">
              <i class="ti ti-notes-off"></i>
              <p>El médico no dejó notas en esta consulta.</p>
            </div>
          <?php else: ?>
            <div class="notas-list">
              <?php foreach ($notas as $n): ?>
                <div class="nota-item">
                  <div class="nota-header">
                    <span class="nota-medico"><i class="ti ti-user"></i> Dr. <?= htmlspecialchars($n['medico']) ?></span>
                    <span class="nota-fecha"><?= date('d/m/Y H:i', strtotime($n['creado_en'])) ?></span>
                  </div>
                  <div class="nota-texto"><?= nl2br(htmlspecialchars($n['nota'])) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>
      <?php elseif (!$cita_id): ?>
      <div class="detail-placeholder">
        <i class="ti ti-notes-medical"></i>
        <p>Selecciona una consulta para ver el detalle y las notas.</p>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/paciente/layout.js"></script>
<script src="/CitaAgil1/assets/js/paciente/historial.js"></script>
</body>
</html>