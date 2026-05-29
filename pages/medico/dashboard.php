<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('medico');

$current_page = 'dashboard';
$page_title   = 'Panel médico';

$pdo = getDB();

// ── OBTENER ID DEL MÉDICO ──
$stmt = $pdo->prepare("SELECT m.id, e.nombre AS especialidad FROM medicos m JOIN especialidades e ON e.id = m.especialidad_id WHERE m.usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch();
$medico_id          = $medico['id'] ?? null;
$medico_especialidad= $medico['especialidad'] ?? 'Médico';

// ── ESTADÍSTICAS ──
$citas_hoy        = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND fecha = CURDATE()");
$citas_hoy->execute([$medico_id]);
$total_hoy = $citas_hoy->fetchColumn();

$citas_pendientes = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND estatus = 'pendiente'");
$citas_pendientes->execute([$medico_id]);
$total_pendientes = $citas_pendientes->fetchColumn();

$citas_completadas = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND estatus = 'completada'");
$citas_completadas->execute([$medico_id]);
$total_completadas = $citas_completadas->fetchColumn();

$total_pacientes = $pdo->prepare("SELECT COUNT(DISTINCT paciente_id) FROM citas WHERE medico_id = ?");
$total_pacientes->execute([$medico_id]);
$total_pacs = $total_pacientes->fetchColumn();

// ── CITAS DE HOY ──
$stmt = $pdo->prepare("
    SELECT c.id, c.hora, c.estatus, c.motivo,
           CONCAT(u.nombre, ' ', u.apellido) AS paciente,
           u.nombre AS pac_nombre, u.apellido AS pac_apellido
    FROM citas c
    JOIN usuarios u ON c.paciente_id = u.id
    WHERE c.medico_id = ? AND c.fecha = CURDATE()
    ORDER BY c.hora ASC
    LIMIT 8
");
$stmt->execute([$medico_id]);
$citas_de_hoy = $stmt->fetchAll();

// ── ÚLTIMAS CITAS ──
$stmt = $pdo->prepare("
    SELECT c.id, c.fecha, c.hora, c.estatus,
           CONCAT(u.nombre, ' ', u.apellido) AS paciente,
           u.nombre AS pac_nombre, u.apellido AS pac_apellido
    FROM citas c
    JOIN usuarios u ON c.paciente_id = u.id
    WHERE c.medico_id = ?
    ORDER BY c.fecha DESC, c.hora DESC
    LIMIT 6
");
$stmt->execute([$medico_id]);
$ultimas_citas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/dashboard.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/medico/layout.php'; ?>

  <div class="content">

    <div class="welcome">
      <h1>Bienvenido, Dr. <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
      <p><?= htmlspecialchars($medico_especialidad) ?> · <?= date('l, d \d\e F \d\e Y') ?></p>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="ti ti-calendar-event"></i></div>
        <div>
          <div class="stat-value"><?= $total_hoy ?></div>
          <div class="stat-label">Citas hoy</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber"><i class="ti ti-clock-hour-4"></i></div>
        <div>
          <div class="stat-value"><?= $total_pendientes ?></div>
          <div class="stat-label">Pendientes</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon teal"><i class="ti ti-circle-check"></i></div>
        <div>
          <div class="stat-value"><?= $total_completadas ?></div>
          <div class="stat-label">Completadas</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="ti ti-users"></i></div>
        <div>
          <div class="stat-value"><?= $total_pacs ?></div>
          <div class="stat-label">Mis pacientes</div>
        </div>
      </div>
    </div>

    <div class="bottom-grid">

      <!-- Citas de hoy -->
      <div class="card">
        <div class="card-header">
          <h3><i class="ti ti-calendar-today" style="vertical-align:-2px; margin-right:6px; color:var(--blue-main)"></i>Citas de hoy</h3>
          <a href="/CitaAgil1/pages/medico/agenda.php">Ver agenda <i class="ti ti-arrow-right"></i></a>
        </div>
        <?php if (empty($citas_de_hoy)): ?>
          <div class="empty-state">
            <i class="ti ti-calendar-off"></i>
            <p>No tienes citas programadas para hoy.</p>
          </div>
        <?php else: ?>
          <div class="citas-list">
            <?php foreach ($citas_de_hoy as $c): ?>
              <div class="cita-item">
                <span class="cita-hora"><?= substr($c['hora'], 0, 5) ?></span>
                <div class="cita-info">
                  <div class="cita-paciente"><?= htmlspecialchars($c['paciente']) ?></div>
                  <div class="cita-motivo"><?= htmlspecialchars($c['motivo'] ?? 'Sin motivo especificado') ?></div>
                </div>
                <span class="badge <?= $c['estatus'] ?>"><?= ucfirst($c['estatus']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Accesos rápidos -->
      <div class="card">
        <div class="card-header">
          <h3><i class="ti ti-bolt" style="vertical-align:-2px; margin-right:6px; color:var(--blue-main)"></i>Accesos rápidos</h3>
        </div>
        <div class="actions-list">
          <a href="/CitaAgil1/pages/medico/agenda.php" class="action-btn">
            <i class="ti ti-calendar-week"></i><span>Ver agenda</span><i class="ti ti-chevron-right arrow"></i>
          </a>
          <a href="/CitaAgil1/pages/medico/pacientes.php" class="action-btn">
            <i class="ti ti-users"></i><span>Mis pacientes</span><i class="ti ti-chevron-right arrow"></i>
          </a>
          <a href="/CitaAgil1/pages/medico/disponibilidad.php" class="action-btn">
            <i class="ti ti-clock"></i><span>Mi disponibilidad</span><i class="ti ti-chevron-right arrow"></i>
          </a>
          <a href="/CitaAgil1/pages/medico/estadisticas.php" class="action-btn">
            <i class="ti ti-chart-bar"></i><span>Mis estadísticas</span><i class="ti ti-chevron-right arrow"></i>
          </a>
        </div>
      </div>

    </div>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/medico/layout.js"></script>
<script src="/CitaAgil1/assets/js/medico/dashboard.js"></script>
</body>
</html>