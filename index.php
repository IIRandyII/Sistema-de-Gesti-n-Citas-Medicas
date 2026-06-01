<?php
// ============================================================
//  index.php — Punto de entrada principal
//  CitaÁgil · Sistema de citas médicas
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['rol'])) {
    switch ($_SESSION['rol']) {
        case 'admin':    header('Location: /CitaAgil1/pages/admin/dashboard.php'); break;
        case 'medico':   header('Location: /CitaAgil1/pages/medico/dashboard.php'); break;
        case 'paciente': header('Location: /CitaAgil1/pages/paciente/inicio.php');  break;
        default:         header('Location: /CitaAgil1/pages/login.php');            break;
    }
} else {
    header('Location: /CitaAgil1/pages/login.php');
}

exit;