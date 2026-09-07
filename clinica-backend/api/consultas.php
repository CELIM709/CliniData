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

require_once __DIR__ . '/../models/Consulta.php';



$consultaModel = new Consulta();
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

        // --- CONSULTAR HISTORIAL O DETALLE DE CONSULTA ---
        case 'GET':
            if (isset($_GET['id'])) {
                $consulta = $consultaModel->obtenerPorId($_GET['id']);
                if (!$consulta) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Consulta no encontrada.']);
                    exit;
                }
                echo json_encode(['success' => true, 'data' => $consulta]);

            } elseif (isset($_GET['paciente'])) {
                $consultas = $consultaModel->obtenerPorPaciente($_GET['paciente']);
                echo json_encode(['success' => true, 'data' => $consultas]);

            } elseif (isset($_GET['medico'])) {
                $consultas = $consultaModel->obtenerPorMedico($_GET['medico']);
                echo json_encode(['success' => true, 'data' => $consultas]);

            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error'   => 'Debe especificar un parámetro de consulta: ?id=ID, ?paciente=CEDULA o ?medico=CEDULA'
                ]);
            }
            break;

        // --- REGISTRAR CONSULTA MÉDICA ---
        case 'POST':
            // Solo Administradores y Médicos pueden registrar consultas
            if (!in_array($_SESSION['usuario']['rol'], ['ADMIN', 'MEDICO'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes para registrar consultas.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            // Validar campos estrictamente requeridos
            $campos = ['diagnostico', 'cedula_paciente', 'cedula_medico'];
            foreach ($campos as $campo) {
                if (empty($input[$campo])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => "El campo '$campo' es obligatorio."]);
                    exit;
                }
            }

            $idConsulta = $consultaModel->registrarConsulta($input);

            http_response_code(201);
            echo json_encode([
                'success'     => true,
                'mensaje'     => 'Consulta médica registrada con éxito.',
                'id_consulta' => $idConsulta
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método HTTP no permitido.']);
            break;
    }

} catch (Exception $e) {
    // Retorna HTTP 400 si salta la excepción '23505' (Cita ya vinculada) o tarifa no encontrada
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}