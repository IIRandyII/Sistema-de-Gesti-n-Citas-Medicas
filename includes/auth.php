<?php
// ============================================================
//  includes/auth.php — Lógica de autenticación
//  CitaÁgil · Sistema de citas médicas
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

function login(string $correo, string $password): array {
    $pdo = getDB();

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE correo = ? AND activo = 1 LIMIT 1');
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'msg' => 'Correo o contraseña incorrectos.'];
    }

    // Guardar sesión
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['nombre']   = $user['nombre'];
    $_SESSION['apellido'] = $user['apellido'];
    $_SESSION['correo']   = $user['correo'];
    $_SESSION['rol']      = $user['rol'];

    return ['ok' => true, 'rol' => $user['rol']];
}

function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: /CitaAgil1/pages/login.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['rol'], $roles, true)) {
        header('Location: /CitaAgil1/pages/login.php');
        exit;
    }
}

function logout(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_destroy();
    header('Location: /CitaAgil1/pages/login.php');
    exit;
}