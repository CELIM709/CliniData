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

require_once __DIR__ . '/../models/Medico.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada.']);
    exit;
}

$medicoModel = new Medico();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch ($metodo) {

        // --- CONSULTAR MÉDICOS ---
        case 'GET':
            if (isset($_GET['cedula'])) {
                $medico = $medicoModel->obtenerPorCedula($_GET['cedula']);
                if (!$medico) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Médico no encontrado.']);
                    exit;
                }
                echo json_encode(['success' => true, 'data' => $medico]);
            } else {
                $medicos = $medicoModel->obtenerTodos();
                echo json_encode(['success' => true, 'data' => $medicos]);
            }
            break;

        // --- REGISTRAR NUEVO MÉDICO ---
        case 'POST':
            if ($_SESSION['usuario']['rol'] !== 'ADMIN') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Solo el Administrador puede registrar médicos.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            if (empty($input['persona']) || empty($input['empleado']) || empty($input['medico'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Faltan estructurar los datos del médico correctamente.']);
                exit;
            }

            $datosPersona = $input['persona'];
            $datosEmpleado = $input['empleado'];
            $datosMedico = $input['medico'];
            $especialidades = $input['especialidades'] ?? []; // Array con IDs de especialidades: [1, 3]

            $medicoModel->registrarMedico($datosPersona, $datosEmpleado, $datosMedico, $especialidades);

            http_response_code(201);
            echo json_encode(['success' => true, 'mensaje' => 'Médico y especialidades registrados con éxito.']);
            break;

        // --- ACTUALIZAR TARIFA O DATOS DEL MÉDICO ---
        case 'PUT':
            if (!in_array($_SESSION['usuario']['rol'], ['ADMIN', 'MEDICO'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['cedula']) || empty($input['medico'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Se requiere cédula y datos a actualizar.']);
                exit;
            }

            $medicoModel->actualizarParcial($input['cedula'], $input['medico']);
            echo json_encode(['success' => true, 'mensaje' => 'Datos del médico actualizados correctamente.']);
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