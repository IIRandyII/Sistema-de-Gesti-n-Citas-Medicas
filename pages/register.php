<?php
// pages/register.php
// TODO: lógica PHP de registro aquí
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

    <!-- Formulario -->
    <div class="page-title">Crear cuenta</div>

    <div class="form-row">
      <div class="field">
        <label for="reg-nombre">Nombre <span class="req">*</span></label>
        <input id="reg-nombre" type="text" placeholder="Juan"/>
        <span class="error-msg" id="err-reg-nombre">Campo requerido.</span>
      </div>
      <div class="field">
        <label for="reg-apellido">Apellido <span class="req">*</span></label>
        <input id="reg-apellido" type="text" placeholder="García"/>
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
        <input id="reg-email" type="email" placeholder="ejemplo@correo.com"/>
      </div>
      <span class="error-msg" id="err-reg-email">Ingresa un correo válido.</span>
    </div>

    <div class="field">
      <label for="reg-tel">Teléfono</label>
      <input id="reg-tel" type="tel" placeholder="81 1234 5678"/>
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
        <input id="reg-pass" type="password" placeholder="••••••••" class="has-right"/>
        <span class="icon-right" data-target="reg-pass">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
          </svg>
        </span>
      </div>
      <span class="error-msg" id="err-reg-pass">Mínimo 8 caracteres.</span>
    </div>

    <button class="btn-primary" onclick="handleRegister()">
      Crear cuenta
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="13 6 19 12 13 18"/>
      </svg>
    </button>

    <div class="footer-link">
      ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </div>

  </div>
</div>

<script src="../assets/js/auth.js"></script>
<script src="../assets/js/register.js"></script>
</body>
</html>