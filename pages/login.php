<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya hay sesión activa, redirigir según rol
if (!empty($_SESSION['rol'])) {
    header('Location: ' . redirectByRole($_SESSION['rol']));
    exit;
}

require_once __DIR__ . '/../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = trim($_POST['correo']   ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$correo || !$password) {
        $error = 'Completa todos los campos.';
    } else {
        $result = login($correo, $password);
        if ($result['ok']) {
            header('Location: ' . redirectByRole($result['rol']));
            exit;
        } else {
            $error = $result['msg'];
        }
    }
}

function redirectByRole(string $rol): string {
    return match($rol) {
        'admin'   => '/CitaAgil1/pages/admin/dashboard.php',
        'medico'  => '/CitaAgil1/pages/medico/dashboard.php',
        'paciente' => '/CitaAgil1/pages/paciente/inicio.php',
        default   => '/CitaAgil1/pages/login.php',
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Iniciar sesión — CitaÁgil</title>
  <link rel="stylesheet" href="../assets/css/auth.css"/>
</head>
<body>

<div class="wrapper">
  <div class="card">

    <!-- Logo -->
    <div class="logo-wrap">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#2e7d4f" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2"/>
          <line x1="16" y1="2"  x2="16" y2="6"/>
          <line x1="8"  y1="2"  x2="8"  y2="6"/>
          <line x1="3"  y1="10" x2="21" y2="10"/>
          <polyline points="9 16 11 18 15 14"/>
        </svg>
      </div>
      <div class="logo-name">Cita<span>Ágil</span></div>
      <div class="logo-sub">Sistema de citas médicas</div>
    </div>

    <div class="page-title">Iniciar sesión</div>

    <?php if (isset($_GET['registered'])): ?>
      <div class="toast" id="toast-registered">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        ¡Cuenta creada exitosamente!
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="toast toast-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>

      <div class="field">
        <label for="login-email">Correo electrónico</label>
        <div class="input-wrap">
          <span class="icon-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/>
            </svg>
          </span>
          <input id="login-email" name="correo" type="email"
                 placeholder="ejemplo@correo.com" autocomplete="email"
                 value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"/>
        </div>
        <span class="error-msg" id="err-login-email">Ingresa un correo válido.</span>
      </div>

      <div class="field">
        <label for="login-pass">Contraseña</label>
        <div class="input-wrap">
          <span class="icon-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input id="login-pass" name="password" type="password"
                 placeholder="••••••••" autocomplete="current-password" class="has-right"/>
          <span class="icon-right" data-target="login-pass">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </span>
        </div>
        <span class="error-msg" id="err-login-pass">Ingresa tu contraseña.</span>
      </div>

      <button type="submit" class="btn-primary">
        Iniciar sesión
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="5" y1="12" x2="19" y2="12"/><polyline points="13 6 19 12 13 18"/>
        </svg>
      </button>

    </form>

    <div class="footer-link">
      ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
    </div>

  </div>
</div>

<script src="../assets/js/auth.js"></script>
<script src="../assets/js/login.js"></script>

<script>
  <?php if (isset($_GET['registered'])): ?>
  // Limpia el parámetro de la URL sin recargar
  history.replaceState(null, '', 'login.php');
  <?php endif; ?>
</script>
</body>
</html>