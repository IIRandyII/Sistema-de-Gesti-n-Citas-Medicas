<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$current_page = 'dashboard';
$page_title   = 'Panel de administración';

$pdo = getDB();

// Estadísticas
$total_pacientes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'paciente' AND activo = 1")->fetchColumn();
$total_medicos   = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'medico'   AND activo = 1")->fetchColumn();
$citas_hoy       = $pdo->query("SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()")->fetchColumn();
$citas_pendientes= $pdo->query("SELECT COUNT(*) FROM citas WHERE estatus = 'pendiente'")->fetchColumn();

// Últimas 8 citas
$ultimas_citas = $pdo->query("
    SELECT
        c.id, c.fecha, c.hora, c.estatus,
        CONCAT(p.nombre, ' ', p.apellido) AS paciente,
        CONCAT(m.nombre, ' ', m.apellido) AS medico,
        p.nombre AS paciente_nombre,
        p.apellido AS paciente_apellido
    FROM citas c
    JOIN usuarios p ON c.paciente_id = p.id
    JOIN medicos  med ON c.medico_id = med.id
    JOIN usuarios m ON med.usuario_id = m.id
    ORDER BY c.creado_en DESC
    LIMIT 8
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/dashboard.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/admin/layout.php'; ?>

  <div class="content">

    <div class="welcome">
      <h1>👋 Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
      <p>Aquí tienes un resumen del sistema para hoy.</p>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon green"><i class="ti ti-users"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $total_pacientes ?></div>
          <div class="stat-label">Pacientes registrados</div>
          <span class="stat-badge up">Total</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue"><i class="ti ti-stethoscope"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $total_medicos ?></div>
          <div class="stat-label">Médicos activos</div>
          <span class="stat-badge info">Total</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber"><i class="ti ti-calendar-event"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $citas_hoy ?></div>
          <div class="stat-label">Citas hoy</div>
          <span class="stat-badge warn">Hoy</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red"><i class="ti ti-clock-hour-4"></i></div>
        <div class="stat-info">
          <div class="stat-value"><?= $citas_pendientes ?></div>
          <div class="stat-label">Citas pendientes</div>
          <span class="stat-badge warn">Pendientes</span>
        </div>
      </div>
    </div>

    <!-- Bottom Grid -->
    <div class="bottom-grid">

      <!-- Últimas citas -->
      <div class="card">
        <div class="card-header">
          <h3><i class="ti ti-calendar-stats" style="vertical-align:-2px; margin-right:6px; color:var(--green-main)"></i>Últimas citas</h3>
          <a href="/CitaAgil1/pages/admin/citas.php">Ver todas <i class="ti ti-arrow-right"></i></a>
        </div>
        <table>
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Médico</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Estatus</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($ultimas_citas)): ?>
              <tr>
                <td colspan="5">
                  <div class="empty-state">
                    <i class="ti ti-calendar-off"></i>
                    <p>No hay citas registradas aún.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($ultimas_citas as $cita): ?>
                <tr>
                  <td>
                    <div class="patient-cell">
                      <div class="mini-avatar">
                        <?= strtoupper(substr($cita['paciente_nombre'], 0, 1) . substr($cita['paciente_apellido'], 0, 1)) ?>
                      </div>
                      <?= htmlspecialchars($cita['paciente']) ?>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($cita['medico']) ?></td>
                  <td><?= date('d/m/Y', strtotime($cita['fecha'])) ?></td>
                  <td><?= substr($cita['hora'], 0, 5) ?></td>
                  <td><span class="badge <?= $cita['estatus'] ?>"><?= ucfirst($cita['estatus']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Accesos rápidos -->
      <div class="card">
        <div class="card-header">
          <h3><i class="ti ti-bolt" style="vertical-align:-2px; margin-right:6px; color:var(--green-main)"></i>Accesos rápidos</h3>
        </div>
        <div class="actions-list">
          <a href="/CitaAgil1/pages/admin/pacientes.php" class="action-btn">
            <i class="ti ti-user-plus"></i><span>Nuevo paciente</span><i class="ti ti-chevron-right arrow"></i>
          </a>
          <a href="/CitaAgil1/pages/admin/medicos.php" class="action-btn">
            <i class="ti ti-medical-cross"></i><span>Nuevo médico</span><i class="ti ti-chevron-right arrow"></i>
          </a>
          <a href="/CitaAgil1/pages/admin/citas.php" class="action-btn">
            <i class="ti ti-calendar-plus"></i><span>Nueva cita</span><i class="ti ti-chevron-right arrow"></i>
          </a>
          <a href="/CitaAgil1/pages/admin/usuarios.php" class="action-btn">
            <i class="ti ti-users"></i><span>Gestionar usuarios</span><i class="ti ti-chevron-right arrow"></i>
          </a>
          <a href="/CitaAgil1/pages/admin/reportes.php" class="action-btn">
            <i class="ti ti-report"></i><span>Ver reportes</span><i class="ti ti-chevron-right arrow"></i>
          </a>
        </div>
      </div>

    </div>
  </div>
</div><!-- .main -->
</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/admin/layout.js"></script>
<script src="/CitaAgil1/assets/js/admin/dashboard.js"></script>
</body>
</html>