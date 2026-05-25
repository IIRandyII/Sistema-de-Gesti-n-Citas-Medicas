<?php
// ============================================================
//  includes/db.php — Conexión a la base de datos con PDO
//  CitaÁgil · Sistema de citas médicas
// ============================================================

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=localhost;dbname=citaagilbd;charset=utf8mb4';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, 'root', 'randy', $options);
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            die('Error de conexión: ' . $e->getMessage());
        }
    }

    return $pdo;
}