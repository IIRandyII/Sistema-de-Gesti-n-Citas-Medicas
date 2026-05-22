<?php
// ============================================================
//  includes/auth_check.php — Verificación de sesión activa
//  CitaÁgil · Sistema de citas médicas
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirige al login si no hay sesión activa.
 * Usar al inicio de páginas protegidas.
 */
function requireLogin(): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: /pages/login.php');
        exit;
    }
}

/**
 * Redirige al dashboard si ya hay sesión activa.
 * Usar en login.php y register.php para no volver a entrar.
 */
function redirectIfLoggedIn(): void {
    if (!empty($_SESSION['usuario_id'])) {
        $rol = $_SESSION['rol'] ?? 'paciente';

        switch ($rol) {
            case 'admin':
                header('Location: /pages/admin/dashboard.php');
                break;
            case 'medico':
                header('Location: /pages/medico/dashboard.php');
                break;
            default:
                header('Location: /pages/paciente/dashboard.php');
                break;
        }
        exit;
    }
}

/**
 * Devuelve el rol del usuario en sesión.
 * @return string|null
 */
function getRolActual(): ?string {
    return $_SESSION['rol'] ?? null;
}

/**
 * Verifica que el usuario tenga el rol requerido.
 * Redirige al login si no cumple.
 * @param string|array $roles  Rol o lista de roles permitidos
 */
function requireRol(string|array $roles): void {
    requireLogin();
    $roles = (array) $roles;
    if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
        header('Location: /pages/login.php');
        exit;
    }
}