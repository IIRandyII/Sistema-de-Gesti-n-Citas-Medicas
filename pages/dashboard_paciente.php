<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('paciente');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mi panel — CitaÁgil</title>
  <link rel="stylesheet" href="../assets/css/auth.css"/>
</head>
<body style="padding:40px; font-family:'Nunito',sans-serif;">

  <h1 style="color:#1a5c38;">
    👋 Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?>
  </h1>
  <p style="color:#6b8a74; margin-top:8px;">Rol: <strong>Paciente</strong></p>
  <br>
  <a href="/CitaAgil1/includes/logout.php" style="color:#c0392b; font-weight:700;">Cerrar sesión</a>

</body>
</html>