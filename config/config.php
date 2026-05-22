<?php
// ============================================================
//  config/config.php — Configuración global de CitaÁgil
//  CitaÁgil · Sistema de citas médicas
// ============================================================

// ── BASE DE DATOS ──
define('DB_HOST',    'localhost');
define('DB_NAME',    'citaagilbd');
define('DB_USER',    'root');       
define('DB_PASS',    'randy');           
define('DB_CHARSET', 'utf8mb4');

// ── APLICACIÓN ──
define('APP_NAME',    'CitaÁgil');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/CitaAgil1'); 

// ── RUTAS ──
define('ROOT_PATH',  dirname(__DIR__));             // Raíz del proyecto
define('PAGES_PATH', ROOT_PATH . '/pages');
define('INC_PATH',   ROOT_PATH . '/includes');

// ── SESIÓN ──
define('SESSION_NAME',     'citaagil_session');
define('SESSION_LIFETIME', 3600); // 1 hora en segundos

// ── ENTORNO ──
// Cambia a 'production' antes de subir al servidor
define('APP_ENV', 'development');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}