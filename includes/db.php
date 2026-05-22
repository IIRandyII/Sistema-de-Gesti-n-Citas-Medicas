<?php
// ============================================================
//  includes/db.php — Conexión a la base de datos con PDO
//  CitaÁgil · Sistema de citas médicas
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lanza excepciones en errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Resultados como array asociativo
            PDO::ATTR_EMULATE_PREPARES   => false,                   // Prepared statements reales
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            if (APP_ENV === 'development') {
                die('Error de conexión: ' . $e->getMessage());
            } else {
                die(json_encode(['error' => 'No se pudo conectar a la base de datos.']));
            }
        }
    }

    return $pdo;
}