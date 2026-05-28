<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$current_page = 'configuracion';
$page_title   = 'Configuración';

$pdo = getDB();

$error   = '';
$success = '';

// ── CARGAR CONFIGURACIÓN ──
$config = [];
foreach ($pdo->query("SELECT clave, valor FROM configuracion") as $row) {
    $config[$row['clave']] = $row['valor'];
}

$dias_laborables = explode(',', $config['dias_laborables'] ?? '1,2,3,4,5');

// ── GUARDAR PERFIL ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'perfil') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo   = trim($_POST['correo']   ?? '');

    if (!$nombre || !$apellido || !$correo) {
        $error = 'Completa todos los campos del perfil.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico no válido.';
    } else {
        $existe = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ? LIMIT 1");
        $existe->execute([$correo, $_SESSION['user_id']]);
        if ($existe->fetch()) {
            $error = 'Ese correo ya está en uso por otro usuario.';
        } else {
            $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, correo = ? WHERE id = ?")
                ->execute([$nombre, $apellido, $correo, $_SESSION['user_id']]);
            $_SESSION['nombre']  = $nombre;
            $_SESSION['apellido']= $apellido;
            $_SESSION['correo']  = $correo;
            header("Location: configuracion.php?perfil=1"); exit;
        }
    }
}

// ── CAMBIAR CONTRASEÑA ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $actual   = $_POST['password_actual']  ?? '';
    $nueva    = $_POST['password_nueva']   ?? '';
    $confirma = $_POST['password_confirma']?? '';

    $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($actual, $user['password_hash'])) {
        $error = 'La contraseña actual es incorrecta.';
    } elseif (strlen($nueva) < 8) {
        $error = 'La nueva contraseña debe tener mínimo 8 caracteres.';
    } elseif ($nueva !== $confirma) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hash = password_hash($nueva, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?")
            ->execute([$hash, $_SESSION['user_id']]);
        header("Location: configuracion.php?password=1"); exit;
    }
}

// ── GUARDAR CONFIGURACIÓN SISTEMA ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sistema') {
    $horario_inicio = $_POST['horario_inicio'] ?? '08:00';
    $horario_fin    = $_POST['horario_fin']    ?? '18:00';
    $duracion       = (int)($_POST['duracion_cita'] ?? 30);
    $dias           = $_POST['dias'] ?? [];
    $dias_str       = implode(',', array_map('intval', $dias));

    $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'horario_inicio'")->execute([$horario_inicio]);
    $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'horario_fin'")->execute([$horario_fin]);
    $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'duracion_cita'")->execute([$duracion]);
    $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'dias_laborables'")->execute([$dias_str]);

    header("Location: configuracion.php?sistema=1"); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/admin/configuracion.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/admin/layout.php'; ?>

  <div class="content">

    <?php if (isset($_GET['perfil'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Perfil actualizado correctamente.</div>
    <?php elseif (isset($_GET['password'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Contraseña actualizada correctamente.</div>
    <?php elseif (isset($_GET['sistema'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Configuración guardada correctamente.</div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="toast toast-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Configuración</h1>
        <p class="page-sub">Administra tu perfil y los ajustes del sistema.</p>
      </div>
    </div>

    <div class="config-grid">

      <!-- ── PERFIL ── -->
      <div class="config-section">
        <div class="section-header">
          <div class="section-icon green"><i class="ti ti-user-circle"></i></div>
          <div>
            <div class="section-titulo">Perfil del administrador</div>
            <div class="section-sub">Actualiza tu información personal.</div>
          </div>
        </div>
        <form method="POST" class="config-form">
          <input type="hidden" name="action" value="perfil">
          <div class="form-row-2">
            <div class="form-field">
              <label>Nombre <span class="req">*</span></label>
              <input type="text" name="nombre" value="<?= htmlspecialchars($_SESSION['nombre']) ?>" required>
            </div>
            <div class="form-field">
              <label>Apellido <span class="req">*</span></label>
              <input type="text" name="apellido" value="<?= htmlspecialchars($_SESSION['apellido']) ?>" required>
            </div>
          </div>
          <div class="form-field">
            <label>Correo electrónico <span class="req">*</span></label>
            <div class="input-icon-wrap">
              <i class="ti ti-mail"></i>
              <input type="email" name="correo" value="<?= htmlspecialchars($_SESSION['correo']) ?>" required>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-guardar">
              <i class="ti ti-check"></i> Guardar perfil
            </button>
          </div>
        </form>
      </div>

      <!-- ── CONTRASEÑA ── -->
      <div class="config-section">
        <div class="section-header">
          <div class="section-icon blue"><i class="ti ti-lock"></i></div>
          <div>
            <div class="section-titulo">Cambiar contraseña</div>
            <div class="section-sub">Mínimo 8 caracteres.</div>
          </div>
        </div>
        <form method="POST" class="config-form">
          <input type="hidden" name="action" value="password">
          <div class="form-field">
            <label>Contraseña actual <span class="req">*</span></label>
            <div class="input-icon-wrap has-right">
              <i class="ti ti-lock"></i>
              <input type="password" name="password_actual" id="passActual" placeholder="••••••••" required>
              <span class="toggle-pass" onclick="togglePass('passActual', this)"><i class="ti ti-eye"></i></span>
            </div>
          </div>
          <div class="form-field">
            <label>Nueva contraseña <span class="req">*</span></label>
            <div class="input-icon-wrap has-right">
              <i class="ti ti-lock"></i>
              <input type="password" name="password_nueva" id="passNueva" placeholder="••••••••" required minlength="8">
              <span class="toggle-pass" onclick="togglePass('passNueva', this)"><i class="ti ti-eye"></i></span>
            </div>
          </div>
          <div class="form-field">
            <label>Confirmar nueva contraseña <span class="req">*</span></label>
            <div class="input-icon-wrap has-right">
              <i class="ti ti-lock"></i>
              <input type="password" name="password_confirma" id="passConfirma" placeholder="••••••••" required minlength="8">
              <span class="toggle-pass" onclick="togglePass('passConfirma', this)"><i class="ti ti-eye"></i></span>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-guardar btn-blue">
              <i class="ti ti-lock-check"></i> Cambiar contraseña
            </button>
          </div>
        </form>
      </div>

      <!-- ── SISTEMA ── -->
      <div class="config-section config-full">
        <div class="section-header">
          <div class="section-icon amber"><i class="ti ti-settings"></i></div>
          <div>
            <div class="section-titulo">Configuración del sistema</div>
            <div class="section-sub">Horario de atención y duración de citas.</div>
          </div>
        </div>
        <form method="POST" class="config-form">
          <input type="hidden" name="action" value="sistema">
          <div class="form-row-3">
            <div class="form-field">
              <label>Hora de inicio</label>
              <div class="input-icon-wrap">
                <i class="ti ti-clock"></i>
                <input type="time" name="horario_inicio" value="<?= $config['horario_inicio'] ?? '08:00' ?>">
              </div>
            </div>
            <div class="form-field">
              <label>Hora de fin</label>
              <div class="input-icon-wrap">
                <i class="ti ti-clock"></i>
                <input type="time" name="horario_fin" value="<?= $config['horario_fin'] ?? '18:00' ?>">
              </div>
            </div>
            <div class="form-field">
              <label>Duración por cita</label>
              <select name="duracion_cita" class="form-select">
                <?php foreach ([30, 45, 60] as $min): ?>
                  <option value="<?= $min ?>" <?= ($config['duracion_cita'] ?? '30') == $min ? 'selected' : '' ?>>
                    <?= $min ?> minutos
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-field">
            <label>Días laborables</label>
            <div class="dias-wrap">
              <?php
              $dias_nombres = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 7=>'Domingo'];
              foreach ($dias_nombres as $num => $nombre): ?>
                <label class="dia-check <?= in_array($num, $dias_laborables) ? 'active' : '' ?>">
                  <input type="checkbox" name="dias[]" value="<?= $num ?>"
                         <?= in_array($num, $dias_laborables) ? 'checked' : '' ?>
                         onchange="this.closest('label').classList.toggle('active', this.checked)">
                  <?= $nombre ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-guardar btn-amber">
              <i class="ti ti-check"></i> Guardar configuración
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/admin/layout.js"></script>
<script src="/CitaAgil1/assets/js/admin/configuracion.js"></script>
</body>
</html>