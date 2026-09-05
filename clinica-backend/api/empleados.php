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

require_once __DIR__ . '/../models/Empleado.php';

// 1. Verificar autenticación básica
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesion no iniciada.']);
    exit;
}

$empleadoModel = new Empleado();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch ($metodo) {

        // --- CONSULTAR EMPLEADOS ---
        case 'GET':

            // Opción 1: Obtener Estadísticas del Dashboard (?action=estadisticas o ?estadisticas=true)
            if (isset($_GET['action']) && $_GET['action'] === 'estadisticas' || isset($_GET['estadisticas'])) {
                // Permitido para cualquier usuario autenticado en el sistema
                $estadisticas = $empleadoModel->obtenerEstadisticasGenerales();
                echo json_encode(['success' => true, 'data' => $estadisticas]);
                break;
            }

            // Solo Administradores pueden listar empleados
            if ($_SESSION['usuario']['rol'] !== 'ADMIN') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
                exit;
            }

            $empleados = $empleadoModel->obtenerTodos();
            echo json_encode(['success' => true, 'data' => $empleados]);
            break;

        // --- REGISTRAR NUEVO EMPLEADO ---
        case 'POST':
            if ($_SESSION['usuario']['rol'] !== 'ADMIN') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Solo el Administrador puede crear empleados.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            // Validar bloques de datos mínimos
            if (empty($input['persona']) || empty($input['empleado'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios de la persona o empleado.']);
                exit;
            }

            $datosPersona = $input['persona'];
            $datosEmpleado = $input['empleado'];
            $datosRol = $input['rol_especifico'] ?? [];

            $empleadoModel->registrarEmpleado($datosPersona, $datosEmpleado, $datosRol);

            http_response_code(201);
            echo json_encode(['success' => true, 'mensaje' => 'Empleado registrado con éxito.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método HTTP no permitido.']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}