<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciamos o recuperamos la sesión de PHP
session_start();

require_once __DIR__ . '/../models/Empleado.php';

// 1. Verificar que la petición sea de tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'error'   => 'Metodo no permitido. El inicio de sesin requiere POST.'
    ]);
    exit;
}

// 2. Leer los datos enviados (soporta tanto JSON como enviados por Formulario POST)
$inputData = json_decode(file_get_contents('php://input'), true);

$identificador = $inputData['identificador'] ?? $_POST['identificador'] ?? $_POST['cedula'] ?? null;
$clave         = $inputData['clave'] ?? $_POST['clave'] ?? null;

// 3. Validar que no vengan campos vacíos
if (empty($identificador) || empty($clave)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error'   => 'Debe proporcionar la cedula o correo y la contraseña.'
    ]);
    exit;
}

// 4. Autenticar con el modelo Empleado
try {
    $empleadoModel = new Empleado();
    $usuario = $empleadoModel->login($identificador, $clave);

    if ($usuario) {
        // Guardamos los datos esenciales en la memoria del servidor ($_SESSION)
        $_SESSION['usuario'] = [
            'cedula' => $usuario['cedula'],
            'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
            'email'  => $usuario['email'],
            'rol'    => $usuario['rol']
        ];

        // Respuesta exitosa al frontend
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'mensaje' => 'Inicio de sesión exitoso.',
            'usuario' => $_SESSION['usuario']
        ]);
    } else {
        // Credenciales inválidas
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'error'   => 'Cédula/correo o contraseña incorrectos.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error'   => 'Error interno en el servidor: ' . $e->getMessage()
    ]);
}