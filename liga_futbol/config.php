<?php
// ===========================================
// CONFIGURACIÓN DE BASE DE DATOS Y SESIÓN
// ===========================================

define('DB_HOST', 's-----.infinityfree.com');
define('DB_USER', 'if0_-------');
define('DB_PASS', ' ');
define('DB_NAME', 'if0_--------_liga_futbol');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Crear conexión
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Sanitizar texto
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}