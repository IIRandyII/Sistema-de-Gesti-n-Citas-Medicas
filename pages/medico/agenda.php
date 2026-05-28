<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('medico');

$current_page = 'agenda';
$page_title   = 'Agenda';

$pdo = getDB();

// ── OBTENER ID Y ESPECIALIDAD DEL MÉDICO ──
$stmt = $pdo->prepare("SELECT m.id, e.nombre AS especialidad FROM medicos m JOIN especialidades e ON e.id = m.especialidad_id WHERE m.usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch();
$medico_id           = $medico['id'] ?? null;
$medico_especialidad = $medico['especialidad'] ?? 'Médico';

// ── FECHA BASE ──
$fecha_base = $_GET['fecha'] ?? date('Y-m-d');
$vista      = $_GET['vista'] ?? 'diaria';

// ── VISTA DIARIA ──
$fecha_dt   = new DateTime($fecha_base);
$fecha_prev = (clone $fecha_dt)->modify('-1 day')->format('Y-m-d');
$fecha_next = (clone $fecha_dt)->modify('+1 day')->format('Y-m-d');
$fecha_label= $fecha_dt->format('d') . ' de ' .
    ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][$fecha_dt->format('n') - 1] .
    ' de ' . $fecha_dt->format('Y');

// ── VISTA SEMANAL ──
$dia_semana  = (int)$fecha_dt->format('N');
$inicio_sem  = (clone $fecha_dt)->modify('-' . ($dia_semana - 1) . ' days');
$fin_sem     = (clone $inicio_sem)->modify('+6 days');
$sem_prev    = (clone $inicio_sem)->modify('-7 days')->format('Y-m-d');
$sem_next    = (clone $inicio_sem)->modify('+7 days')->format('Y-m-d');
$sem_label   = $inicio_sem->format('d M') . ' — ' . $fin_sem->format('d M Y');

// ── CITAS DIARIAS ──
$stmt = $pdo->prepare("
    SELECT c.id, c.hora, c.estatus, c.motivo,
           CONCAT(u.nombre, ' ', u.apellido) AS paciente,
           u.nombre AS pac_nombre, u.apellido AS pac_apellido,
           u.correo AS pac_correo, u.telefono AS pac_telefono
    FROM citas c
    JOIN usuarios u ON c.paciente_id = u.id
    WHERE c.medico_id = ? AND c.fecha = ?
    ORDER BY c.hora ASC
");
$stmt->execute([$medico_id, $fecha_base]);
$citas_dia = $stmt->fetchAll();

// ── CITAS SEMANALES ──
$stmt = $pdo->prepare("
    SELECT c.id, c.fecha, c.hora, c.estatus, c.motivo,
           CONCAT(u.nombre, ' ', u.apellido) AS paciente,
           u.nombre AS pac_nombre, u.apellido AS pac_apellido
    FROM citas c
    JOIN usuarios u ON c.paciente_id = u.id
    WHERE c.medico_id = ? AND c.fecha BETWEEN ? AND ?
    ORDER BY c.fecha ASC, c.hora ASC
");
$stmt->execute([$medico_id, $inicio_sem->format('Y-m-d'), $fin_sem->format('Y-m-d')]);
$citas_semana_raw = $stmt->fetchAll();

// Agrupar por fecha
$citas_semana = [];
foreach ($citas_semana_raw as $c) {
    $citas_semana[$c['fecha']][] = $c;
}

// Generar días de la semana
$dias_semana = [];
for ($i = 0; $i < 7; $i++) {
    $d = (clone $inicio_sem)->modify("+{$i} days");
    $dias_semana[] = $d->format('Y-m-d');
}

$dias_nombres = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/agenda.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/medico/layout.php'; ?>

  <div class="content">

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Agenda</h1>
        <p class="page-sub">Consulta y gestiona tus citas programadas.</p>
      </div>
      <!-- Toggle vista -->
      <div class="vista-toggle">
        <a href="?vista=diaria&fecha=<?= $fecha_base ?>"
           class="vista-btn <?= $vista === 'diaria' ? 'active' : '' ?>">
          <i class="ti ti-calendar-day"></i> Diaria
        </a>
        <a href="?vista=semanal&fecha=<?= $fecha_base ?>"
           class="vista-btn <?= $vista === 'semanal' ? 'active' : '' ?>">
          <i class="ti ti-calendar-week"></i> Semanal
        </a>
      </div>
    </div>

    <?php if ($vista === 'diaria'): ?>
    <!-- ── VISTA DIARIA ── -->
    <div class="agenda-card">
      <div class="agenda-nav">
        <a href="?vista=diaria&fecha=<?= $fecha_prev ?>" class="nav-arrow"><i class="ti ti-chevron-left"></i></a>
        <div class="agenda-fecha-wrap">
          <span class="agenda-fecha"><?= $fecha_label ?></span>
          <?php if ($fecha_base === $hoy): ?>
            <span class="hoy-badge">Hoy</span>
          <?php endif; ?>
        </div>
        <a href="?vista=diaria&fecha=<?= $fecha_next ?>" class="nav-arrow"><i class="ti ti-chevron-right"></i></a>
        <a href="?vista=diaria&fecha=<?= $hoy ?>" class="btn-hoy">Hoy</a>
      </div>

      <?php if (empty($citas_dia)): ?>
        <div class="empty-state">
          <i class="ti ti-calendar-off"></i>
          <p>No tienes citas programadas para este día.</p>
        </div>
      <?php else: ?>
        <div class="citas-diaria">
          <?php foreach ($citas_dia as $c): ?>
            <div class="cita-row">
              <div class="cita-time">
                <span class="cita-hora"><?= substr($c['hora'], 0, 5) ?></span>
              </div>
              <div class="cita-content">
                <div class="cita-header-row">
                  <div class="patient-cell">
                    <div class="mini-avatar">
                      <?= strtoupper(substr($c['pac_nombre'], 0, 1) . substr($c['pac_apellido'], 0, 1)) ?>
                    </div>
                    <div>
                      <div class="cita-paciente"><?= htmlspecialchars($c['paciente']) ?></div>
                      <div class="cita-contacto">
                        <?= $c['pac_correo'] ? htmlspecialchars($c['pac_correo']) : '' ?>
                        <?= $c['pac_telefono'] ? ' · ' . htmlspecialchars($c['pac_telefono']) : '' ?>
                      </div>
                    </div>
                  </div>
                  <span class="badge <?= $c['estatus'] ?>"><?= ucfirst($c['estatus']) ?></span>
                </div>
                <?php if ($c['motivo']): ?>
                  <div class="cita-motivo"><i class="ti ti-notes"></i> <?= htmlspecialchars($c['motivo']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- ── VISTA SEMANAL ── -->
    <div class="agenda-card">
      <div class="agenda-nav">
        <a href="?vista=semanal&fecha=<?= $sem_prev ?>" class="nav-arrow"><i class="ti ti-chevron-left"></i></a>
        <span class="agenda-fecha"><?= $sem_label ?></span>
        <a href="?vista=semanal&fecha=<?= $sem_next ?>" class="nav-arrow"><i class="ti ti-chevron-right"></i></a>
        <a href="?vista=semanal&fecha=<?= $hoy ?>" class="btn-hoy">Esta semana</a>
      </div>

      <div class="semana-grid">
        <?php foreach ($dias_semana as $i => $dia): ?>
          <div class="dia-col <?= $dia === $hoy ? 'hoy' : '' ?>">
            <div class="dia-header">
              <span class="dia-nombre"><?= $dias_nombres[$i] ?></span>
              <span class="dia-num <?= $dia === $hoy ? 'hoy-num' : '' ?>">
                <?= (new DateTime($dia))->format('d') ?>
              </span>
            </div>
            <div class="dia-citas">
              <?php if (!empty($citas_semana[$dia])): ?>
                <?php foreach ($citas_semana[$dia] as $c): ?>
                  <div class="cita-chip <?= $c['estatus'] ?>">
                    <span class="chip-hora"><?= substr($c['hora'], 0, 5) ?></span>
                    <span class="chip-nombre"><?= htmlspecialchars($c['pac_nombre'] . ' ' . substr($c['pac_apellido'], 0, 1) . '.') ?></span>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="dia-vacio">Sin citas</div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/medico/layout.js"></script>
<script src="/CitaAgil1/assets/js/medico/agenda.js"></script>
</body>
</html>