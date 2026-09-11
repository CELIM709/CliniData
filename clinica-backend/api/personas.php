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

require_once __DIR__ . '/../models/Persona.php';

$personaModel = new Persona();
$metodo = $_SERVER['REQUEST_METHOD'];

// 1. Identificar si es una consulta pública (por ejemplo, buscar datos básicos por cédula)
$esConsultaPublicaPersona = ($metodo === 'GET' && isset($_GET['cedula']));

// 2. Validar sesión ÚNICAMENTE si NO es una consulta pública
if (!$esConsultaPublicaPersona && !isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesion no iniciada.']);
    exit;
}

try {
    switch ($metodo) {

        // --- CONSULTAR PERSONAS ---
        case 'GET':
            // ejemplo: http://localhost:8080/api/personas?cedula=CEDULA
            if (isset($_GET['cedula'])) {
                $resultado = $personaModel->obtenerPorCedula($_GET['cedula']);
                if (!$resultado) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Persona no encontrada.']);
                    exit;
                }
            } else {
                $resultado = $personaModel->obtenerTodas();
            }
            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        // --- REGISTRAR NUEVA PERSONA ---
        case 'POST':
            if (!in_array($_SESSION['usuario']['rol'], ['RECEPCIONISTA', 'ADMIN'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            if (empty($input['cedula']) || empty($input['nombre']) || empty($input['apellido']) || empty($input['fecha_nacimiento'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios de la persona.']);
                exit;
            }

            if ($personaModel->existe($input['cedula'])) {
                http_response_code(409);
                echo json_encode(['success' => false, 'error' => 'La cédula ya se encuentra registrada.']);
                exit;
            }

            $personaModel->crear($input);

            http_response_code(201);
            echo json_encode(['success' => true, 'mensaje' => 'Persona registrada exitosamente.']);
            break;

        // --- ACTUALIZAR DATOS DE PERSONA ---
        case 'PUT':
            if (!in_array($_SESSION['usuario']['rol'], ['RECEPCIONISTA', 'ADMIN'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['cedula'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Se requiere la cédula de la persona.']);
                exit;
            }

            $personaModel->actualizar($input['cedula'], $input);

            echo json_encode(['success' => true, 'mensaje' => 'Persona actualizada exitosamente.']);
            break;

        // --- ELIMINAR PERSONA ---
        case 'DELETE':
            if ($_SESSION['usuario']['rol'] !== 'ADMIN') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Solo el administrador puede eliminar personas.']);
                exit;
            }

            if (!isset($_GET['cedula'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Se requiere la cédula de la persona.']);
                exit;
            }

            $personaModel->eliminar($_GET['cedula']);
            echo json_encode(['success' => true, 'mensaje' => 'Persona eliminada exitosamente.']);
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