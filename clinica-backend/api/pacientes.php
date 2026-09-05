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

require_once __DIR__ . '/../models/Paciente.php';



$pacienteModel = new Paciente();
$metodo = $_SERVER['REQUEST_METHOD'];

// 1. Identificar si es una consulta pública de un paciente (Método GET con el parámetro 'paciente')
$esConsultaPublicaPaciente = ($metodo === 'GET' && isset($_GET['paciente']));

// 2. Validar sesión ÚNICAMENTE si NO es una consulta pública de paciente
if (!$esConsultaPublicaPaciente && !isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesion no iniciada.']);
    exit;
}

try {
    switch ($metodo) {

        // --- CONSULTAR PACIENTES ---
        case 'GET':
            if (isset($_GET['cedula'])) {
                $resultado = $pacienteModel->obtenerPorCedula($_GET['cedula']);
                if (!$resultado) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Paciente no encontrado.']);
                    exit;
                }
            } else {
                $resultado = $pacienteModel->obtenerTodos();
            }
            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        // --- REGISTRAR NUEVO PACIENTE ---
        case 'POST':
            if (!in_array($_SESSION['usuario']['rol'], ['RECEPCIONISTA', 'ADMIN'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            if (empty($input['persona']) || empty($input['paciente'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios del paciente.']);
                exit;
            }

            $pacienteModel->registrarPaciente(
                $input['persona'], 
                $input['paciente'], 
                $input['historia'] ?? []
            );

            http_response_code(201);
            echo json_encode(['success' => true, 'mensaje' => 'Paciente registrado exitosamente.']);
            break;

        // --- ACTUALIZAR DATOS DE PACIENTE ---
        case 'PUT':
            if (!in_array($_SESSION['usuario']['rol'], ['RECEPCIONISTA', 'ADMIN'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            // Validar cédula
            if (empty($input['cedula'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Se requiere la cédula del paciente.']);
                exit;
            }

            // Validar que al menos envíe datos de persona o de paciente para actualizar
            if (empty($input['persona']) && empty($input['paciente'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Debe incluir al menos datos de persona o paciente para actualizar.']);
                exit;
            }

            // Ejecutar la actualización coordinada
            $pacienteModel->actualizarPaciente(
                $input['cedula'],
                $input['persona'] ?? [],
                $input['paciente'] ?? []
            );

            echo json_encode(['success' => true, 'mensaje' => 'Paciente actualizado exitosamente.']);
            break;

        // --- ELIMINAR ROL PACIENTE ---
        case 'DELETE':
            if ($_SESSION['usuario']['rol'] !== 'ADMIN') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Solo el administrador puede eliminar pacientes.']);
                exit;
            }

            if (!isset($_GET['cedula'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Se requiere la cédula del paciente.']);
                exit;
            }

            $pacienteModel->eliminarRolPaciente($_GET['cedula']);
            echo json_encode(['success' => true, 'mensaje' => 'Rol de paciente removido exitosamente.']);
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