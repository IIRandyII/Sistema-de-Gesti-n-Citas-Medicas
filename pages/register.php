<?php
// ============================================================
//  pages/register.php
//  CitaÁgil · Sistema de citas médicas
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo   = trim($_POST['correo']   ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$nombre || !$apellido || !$correo || strlen($password) < 8) {
        $error = 'Completa todos los campos correctamente.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico no válido.';
    } elseif ($telefono && !preg_match('/^\d{10}$/', $telefono)) {
        $error = 'El teléfono debe tener exactamente 10 dígitos.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ? LIMIT 1');
            $stmt->execute([$correo]);

            if ($stmt->fetch()) {
                $error = 'Ya existe una cuenta con ese correo.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare('INSERT INTO usuarios (nombre, apellido, correo, telefono, password_hash, rol) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute([$nombre, $apellido, $correo, $telefono ?: null, $hash, 'paciente']);

                header('Location: login.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Error al crear la cuenta. Intenta de nuevo.';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Crear cuenta — CitaÁgil</title>
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

    <div class="page-title">Crear cuenta</div>

    <?php if ($error): ?>
      <div class="toast toast-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>

      <div class="form-row">
        <div class="field">
          <label for="reg-nombre">Nombre <span class="req">*</span></label>
          <input id="reg-nombre" name="nombre" type="text" placeholder="Juan"
                 value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"/>
          <span class="error-msg" id="err-reg-nombre">Campo requerido.</span>
        </div>
        <div class="field">
          <label for="reg-apellido">Apellido <span class="req">*</span></label>
          <input id="reg-apellido" name="apellido" type="text" placeholder="García"
                 value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>"/>
          <span class="error-msg" id="err-reg-apellido">Campo requerido.</span>
        </div>
      </div>

      <div class="field">
        <label for="reg-email">Correo electrónico <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="icon-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/>
            </svg>
          </span>
          <input id="reg-email" name="correo" type="email" placeholder="ejemplo@correo.com"
                 value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"/>
        </div>
        <span class="error-msg" id="err-reg-email">Ingresa un correo válido.</span>
      </div>

      <div class="field">
        <label for="reg-tel">Teléfono <span class="hint">(10 dígitos)</span></label>
        <input id="reg-tel" name="telefono" type="tel" placeholder="8112345678"
               maxlength="10"
               value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"/>
        <span class="error-msg" id="err-reg-tel">Debe tener exactamente 10 dígitos.</span>
      </div>

      <div class="field" style="margin-bottom:0">
        <label for="reg-pass">
          Contraseña <span class="req">*</span>
          <span class="hint">(mínimo 8 caracteres)</span>
        </label>
        <div class="input-wrap">
          <span class="icon-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input id="reg-pass" name="password" type="password" placeholder="••••••••" class="has-right"/>
          <span class="icon-right" data-target="reg-pass">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </span>
        </div>
        <span class="error-msg" id="err-reg-pass">Mínimo 8 caracteres.</span>
      </div>

      <button type="submit" class="btn-primary">
        Crear cuenta
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="5" y1="12" x2="19" y2="12"/><polyline points="13 6 19 12 13 18"/>
        </svg>
      </button>

    </form>

    <div class="footer-link">
      ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </div>

  </div>
</div>

<script src="../assets/js/auth.js"></script>
<script src="../assets/js/register.js"></script>
</body>
</html>