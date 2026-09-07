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

require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Paciente.php';



$citaModel = new Cita();
$metodo = $_SERVER['REQUEST_METHOD'];

// 1. Identificar si es una consulta pública de un paciente (Método GET con el parámetro 'paciente')
$esConsultaPublicaPaciente = ($metodo === 'GET' && isset($_GET['paciente']));

if ($esConsultaPublicaPaciente) {
    $fechaNacimiento = trim($_GET['fecha_nacimiento'] ?? '');
    $paciente = (new Paciente())->obtenerPorCedula($_GET['paciente']);
    if (!$paciente || $fechaNacimiento === '' || $paciente['fecha_nacimiento'] !== $fechaNacimiento) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Cédula o fecha de nacimiento incorrecta.']);
        exit;
    }
}

// 2. Validar sesión ÚNICAMENTE si NO es una consulta pública de paciente
if (!$esConsultaPublicaPaciente && !isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesion no iniciada.']);
    exit;
}

try {
    switch ($metodo) {

        // --- CONSULTAR CITAS ---
        case 'GET':
            if (isset($_GET['action']) && $_GET['action'] === 'hoy') {
                echo json_encode(['success' => true, 'data' => $citaModel->obtenerDelDia()]);
                break;
            }

            // consultar por medico, o porpaciente, por deferto solo pendientes
            $medico = $_GET['medico'] ?? null;
            $paciente = $_GET['paciente'] ?? null;
            $soloPendientes = isset($_GET['pendientes']) && $_GET['pendientes'] === 'true';

            if ($medico) {
                $citas = $soloPendientes 
                    ? $citaModel->obtenerPendientesPorMedico($medico)
                    : $citaModel->obtenerPorMedico($medico);
                echo json_encode(['success' => true, 'data' => $citas]);
            } elseif ($paciente) {
                $citas = $soloPendientes 
                    ? $citaModel->obtenerPendientesPorPaciente($paciente)
                    : $citaModel->obtenerPorPaciente($paciente);
                echo json_encode(['success' => true, 'data' => $citas]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error'   => 'Debe especificar un parámetro de búsqueda: ?medico=CEDULA o ?paciente=CEDULA'
                ]);
            }
            break;

        // --- AGENDAR NUEVA CITA ---
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $campos = ['fecha_inicio', 'fecha_fin', 'consultorio', 'cedula_medico', 'cedula_paciente'];
            foreach ($campos as $campo) {
                if (empty($input[$campo])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => "El campo '$campo' es obligatorio."]);
                    exit;
                }
            }

            $idCita = $citaModel->agendarCita($input);

            http_response_code(201);
            echo json_encode(['success' => true, 'mensaje' => 'Cita agendada con éxito.', 'id_cita' => $idCita]);
            break;

        // --- CAMBIAR ESTADO O REPROGRAMAR ---
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['id_cita'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Se requiere el id_cita.']);
                exit;
            }

            // Opción 1: Cambiar Estado ('CONFIRMADA', 'CANCELADA', etc.)
            $actualizado = false;
            if (!empty($input['nuevo_estado'])) {
                $citaModel->cambiarEstado($input['id_cita'], $input['nuevo_estado']);
                $actualizado = true;
            }

            // Opción 2: Reprogramar fecha/hora
            if (!empty($input['fecha_inicio']) && !empty($input['fecha_fin'])) {
                $citaModel->reprogramarCita($input['id_cita'], $input['fecha_inicio'], $input['fecha_fin']);
                $actualizado = true;
            }

            if ($actualizado) {
                echo json_encode(['success' => true, 'mensaje' => 'Cita actualizada exitosamente.']);
                exit;
            }

            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Debe enviar nuevo_estado o las fechas para reprogramar.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método HTTP no permitido.']);
            break;
    }

} catch (Exception $e) {
    // Si la excepción proviene del solapamiento tsrange en PostgreSQL, enviamos un HTTP 409 Conflict
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}