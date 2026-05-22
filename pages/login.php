<?php
// pages/login.php
// TODO: lógica PHP de autenticación aquí
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

    <!-- Formulario -->
    <div class="page-title">Iniciar sesión</div>
    <div class="divider"></div>

    <div class="field">
      <label for="login-email">Correo electrónico</label>
      <input id="login-email" type="email" placeholder="ejemplo@correo.com" autocomplete="email"/>
      <span class="error-msg" id="err-login-email">Ingresa un correo válido.</span>
    </div>

    <div class="field">
      <label for="login-pass">Contraseña</label>
      <input id="login-pass" type="password" placeholder="••••••••" autocomplete="current-password"/>
      <span class="error-msg" id="err-login-pass">Ingresa tu contraseña.</span>
    </div>

    <button class="btn-primary" onclick="handleLogin()">Iniciar sesión</button>

    <div class="footer-link">
      ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
    </div>

  </div>
</div>

<script src="../assets/js/auth.js"></script>
<script src="../assets/js/login.js"></script>
</body>
</html>