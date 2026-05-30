<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('paciente');

$current_page = 'inicio';
$page_title   = 'Inicio';

$pdo = getDB();

$paciente_id = $_SESSION['user_id'];

// ── PRÓXIMA CITA ──
$stmt = $pdo->prepare("
    SELECT c.id, c.fecha, c.hora, c.estatus, c.motivo,
           CONCAT(u.nombre, ' ', u.apellido) AS medico,
           e.nombre AS especialidad
    FROM citas c
    JOIN medicos m ON c.medico_id = m.id
    JOIN usuarios u ON m.usuario_id = u.id
    JOIN especialidades e ON m.especialidad_id = e.id
    WHERE c.paciente_id = ? AND c.fecha >= CURDATE() AND c.estatus IN ('pendiente','confirmada')
    ORDER BY c.fecha ASC, c.hora ASC
    LIMIT 1
");
$stmt->execute([$paciente_id]);
$proxima_cita = $stmt->fetch();

// ── NOTIFICACIÓN HOY/MAÑANA ──
$notificacion = null;
if ($proxima_cita) {
    $hoy    = date('Y-m-d');
    $manana = date('Y-m-d', strtotime('+1 day'));
    if ($proxima_cita['fecha'] === $hoy) {
        $notificacion = ['tipo' => 'hoy', 'msg' => 'Tienes una cita hoy a las ' . substr($proxima_cita['hora'], 0, 5)];
    } elseif ($proxima_cita['fecha'] === $manana) {
        $notificacion = ['tipo' => 'manana', 'msg' => 'Tienes una cita mañana a las ' . substr($proxima_cita['hora'], 0, 5)];
    }
}

// ── ÚLTIMAS CITAS ──
$stmt = $pdo->prepare("
    SELECT c.id, c.fecha, c.hora, c.estatus,
           CONCAT(u.nombre, ' ', u.apellido) AS medico,
           e.nombre AS especialidad,
           u.nombre AS med_nombre, u.apellido AS med_apellido
    FROM citas c
    JOIN medicos m ON c.medico_id = m.id
    JOIN usuarios u ON m.usuario_id = u.id
    JOIN especialidades e ON m.especialidad_id = e.id
    WHERE c.paciente_id = ?
    ORDER BY c.fecha DESC, c.hora DESC
    LIMIT 5
");
$stmt->execute([$paciente_id]);
$ultimas_citas = $stmt->fetchAll();

// ── CONTADORES ──
$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE paciente_id = ? AND estatus IN ('pendiente','confirmada') AND fecha >= CURDATE()");
$stmt->execute([$paciente_id]);
$cnt_pendientes = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE paciente_id = ? AND estatus = 'completada'");
$stmt->execute([$paciente_id]);
$cnt_completadas = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/paciente/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/paciente/inicio.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/paciente/layout.php'; ?>

  <div class="content">

    <!-- Notificación -->
    <?php if ($notificacion): ?>
      <div class="notif notif-<?= $notificacion['tipo'] ?>">
        <i class="ti ti-<?= $notificacion['tipo'] === 'hoy' ? 'bell-ringing' : 'bell' ?>"></i>
        <div class="notif-info">
          <span class="notif-titulo"><?= $notificacion['tipo'] === 'hoy' ? '¡Cita hoy!' : 'Recordatorio' ?></span>
          <span class="notif-msg"><?= $notificacion['msg'] ?> con Dr. <?= htmlspecialchars($proxima_cita['medico']) ?> — <?= htmlspecialchars($proxima_cita['especialidad']) ?></span>
        </div>
        <a href="/CitaAgil1/pages/paciente/citas.php" class="notif-link">Ver cita <i class="ti ti-arrow-right"></i></a>
      </div>
    <?php endif; ?>

    <!-- Bienvenida -->
    <div class="welcome">
      <h1>Hola, <?= htmlspecialchars($_SESSION['nombre']) ?> 👋</h1>
      <p>Bienvenido a tu portal de citas médicas.</p>
    </div>

    <div class="inicio-grid">

      <!-- Próxima cita -->
      <div class="proxima-card">
        <div class="proxima-header">
          <span class="proxima-titulo"><i class="ti ti-calendar-event"></i> Próxima cita</span>
          <a href="/CitaAgil1/pages/paciente/citas.php" class="ver-todas">Ver todas <i class="ti ti-arrow-right"></i></a>
        </div>
        <?php if ($proxima_cita): ?>
          <div class="proxima-body">
            <div class="proxima-fecha">
              <div class="fecha-dia"><?= date('d', strtotime($proxima_cita['fecha'])) ?></div>
              <div class="fecha-mes"><?= strtoupper(['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'][date('n', strtotime($proxima_cita['fecha'])) - 1]) ?></div>
            </div>
            <div class="proxima-info">
              <div class="proxima-medico">Dr. <?= htmlspecialchars($proxima_cita['medico']) ?></div>
              <div class="proxima-esp"><?= htmlspecialchars($proxima_cita['especialidad']) ?></div>
              <div class="proxima-hora"><i class="ti ti-clock"></i> <?= substr($proxima_cita['hora'], 0, 5) ?> hrs</div>
            </div>
            <span class="badge <?= $proxima_cita['estatus'] ?>"><?= ucfirst($proxima_cita['estatus']) ?></span>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="ti ti-calendar-off"></i>
            <p>No tienes citas próximas.</p>
            <a href="/CitaAgil1/pages/paciente/buscar.php" class="btn-agendar">
              <i class="ti ti-plus"></i> Agendar cita
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Resumen -->
      <div class="resumen-wrap">
        <div class="resumen-card">
          <div class="resumen-icon amber"><i class="ti ti-clock-hour-4"></i></div>
          <div>
            <div class="resumen-value"><?= $cnt_pendientes ?></div>
            <div class="resumen-label">Citas próximas</div>
          </div>
        </div>
        <div class="resumen-card">
          <div class="resumen-icon teal"><i class="ti ti-circle-check"></i></div>
          <div>
            <div class="resumen-value"><?= $cnt_completadas ?></div>
            <div class="resumen-label">Completadas</div>
          </div>
        </div>
      </div>

      <!-- Accesos rápidos -->
      <div class="accesos-card">
        <div class="accesos-header"><i class="ti ti-bolt"></i> Accesos rápidos</div>
        <div class="accesos-list">
          <a href="/CitaAgil1/pages/paciente/buscar.php" class="acceso-btn">
            <div class="acceso-icon green"><i class="ti ti-search"></i></div>
            <div class="acceso-info">
              <div class="acceso-titulo">Buscar médico</div>
              <div class="acceso-sub">Encuentra y agenda tu cita</div>
            </div>
            <i class="ti ti-chevron-right acceso-arrow"></i>
          </a>
          <a href="/CitaAgil1/pages/paciente/citas.php" class="acceso-btn">
            <div class="acceso-icon blue"><i class="ti ti-calendar"></i></div>
            <div class="acceso-info">
              <div class="acceso-titulo">Mis citas</div>
              <div class="acceso-sub">Ver, cancelar o reprogramar</div>
            </div>
            <i class="ti ti-chevron-right acceso-arrow"></i>
          </a>
          <a href="/CitaAgil1/pages/paciente/historial.php" class="acceso-btn">
            <div class="acceso-icon amber"><i class="ti ti-notes-medical"></i></div>
            <div class="acceso-info">
              <div class="acceso-titulo">Historial médico</div>
              <div class="acceso-sub">Ver notas y consultas pasadas</div>
            </div>
            <i class="ti ti-chevron-right acceso-arrow"></i>
          </a>
        </div>
      </div>

      <!-- Últimas citas -->
      <div class="ultimas-card">
        <div class="ultimas-header">
          <span><i class="ti ti-history"></i> Actividad reciente</span>
        </div>
        <?php if (empty($ultimas_citas)): ?>
          <div class="empty-state"><i class="ti ti-calendar-off"></i><p>Sin actividad aún.</p></div>
        <?php else: ?>
          <div class="ultimas-list">
            <?php foreach ($ultimas_citas as $c): ?>
              <div class="ultima-item">
                <div class="ultima-avatar">
                  <?= strtoupper(substr($c['med_nombre'], 0, 1) . substr($c['med_apellido'], 0, 1)) ?>
                </div>
                <div class="ultima-info">
                  <div class="ultima-medico">Dr. <?= htmlspecialchars($c['medico']) ?></div>
                  <div class="ultima-esp"><?= htmlspecialchars($c['especialidad']) ?> · <?= date('d/m/Y', strtotime($c['fecha'])) ?></div>
                </div>
                <span class="badge <?= $c['estatus'] ?>"><?= ucfirst($c['estatus']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/paciente/layout.js"></script>
<script src="/CitaAgil1/assets/js/paciente/inicio.js"></script>
</body>
</html>