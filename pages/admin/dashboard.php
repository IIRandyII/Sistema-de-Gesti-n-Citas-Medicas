<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <title>Dashboard Admin — CitaÁgil</title>
  <link rel="stylesheet" href="../../assets/css/auth.css"/>
</head>
<body style="padding:40px; font-family:'Nunito',sans-serif;">
  <h1 style="color:#1a5c38;">👋 Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
  <p style="color:#6b8a74; margin-top:8px;">Rol: <strong><?= $_SESSION['rol'] ?></strong></p>
  <br>
  <a href="/CitaAgil1/includes/logout.php" style="color:#c0392b; font-weight:700;">Cerrar sesión</a>
</body>
</html>