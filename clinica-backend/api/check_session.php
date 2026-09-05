<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Si el navegador hace una petición previa de verificación (OPTIONS), respondemos 200 OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
session_start();

// Si existe la sesión, enviamos los datos del usuario conectado
if (isset($_SESSION['usuario'])) {
    http_response_code(200);
    echo json_encode([
        'autenticado' => true,
        'usuario'     => $_SESSION['usuario'] // cédula, nombre, rol, etc.
    ]);
} else {
    // Si no hay sesión o ya expiró
    http_response_code(401);
    echo json_encode([
        'autenticado' => false,
        'error'       => 'No hay una sesión activa.'
    ]);
}