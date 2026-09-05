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

require_once __DIR__ . '/../models/Receta.php';

$recetaModel = new Receta();
$metodo = $_SERVER['REQUEST_METHOD'];

// 1. Identificar si es una consulta pública de un paciente (Método GET con el parámetro 'paciente' o 'cedula_paciente')
$esConsultaPublicaPaciente = ($metodo === 'GET');

// 2. Validar sesión ÚNICAMENTE si NO es una consulta pública de paciente
if (!$esConsultaPublicaPaciente && !isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesion no iniciada.']);
    exit;
}

try {
    switch ($metodo) {

        // --- CONSULTAR RECETAS / RÉCIPE ---
        case 'GET':
            // Opción 1: Obtener historial de recetas por médico (?cedula_medico=X o ?medico=X)
            if (isset($_GET['cedula_medico']) || isset($_GET['medico'])) {
                $cedulaMedico = trim($_GET['cedula_medico'] ?? $_GET['medico']);
                if (empty($cedulaMedico)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Cédula de médico requerida.']);
                    exit;
                }

                $resultado = $recetaModel->obtenerPorMedico($cedulaMedico);
                echo json_encode(['success' => true, 'data' => $resultado]);
                break;
            }

            // Opción 2: Obtener historial de recetas por paciente (?cedula_paciente=X o ?paciente=X)
            if (isset($_GET['cedula_paciente']) || isset($_GET['paciente'])) {
                $cedulaPaciente = trim($_GET['cedula_paciente'] ?? $_GET['paciente']);
                if (empty($cedulaPaciente)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Cédula de paciente requerida.']);
                    exit;
                }

                $resultado = $recetaModel->obtenerPorPaciente($cedulaPaciente);
                echo json_encode(['success' => true, 'data' => $resultado]);
                break;
            }

            // Opción 3: Obtener recetas de una consulta específica (?id_consulta=Y)
            if (isset($_GET['id_consulta'])) {
                $idConsulta = filter_var($_GET['id_consulta'], FILTER_VALIDATE_INT);
                if (!$idConsulta) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'ID de consulta inválido.']);
                    exit;
                }

                if (isset($_GET['impresion']) && $_GET['impresion'] === 'true') {
                    $resultado = $recetaModel->obtenerRecipeCompleto($idConsulta);
                } else {
                    $resultado = $recetaModel->obtenerPorConsulta($idConsulta);
                }

                echo json_encode(['success' => true, 'data' => $resultado]);
                break;
            }

            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Debe especificar "id_consulta", "cedula_paciente" o "cedula_medico".']);
            break;

        // --- REGISTRAR MEDICAMENTOS EN LOTE A UNA CONSULTA ---
        case 'POST':
            if (!in_array($_SESSION['usuario']['rol'], ['ADMIN', 'MEDICO'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Solo los médicos pueden prescribir recetas.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            if (empty($input['id_consulta']) || empty($input['medicamentos']) || !is_array($input['medicamentos'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Debe incluir el id_consulta y la lista de medicamentos.']);
                exit;
            }

            $recetaModel->registrarReceta($input['id_consulta'], $input['medicamentos']);

            http_response_code(201);
            echo json_encode(['success' => true, 'mensaje' => 'Receta médica registrada exitosamente.']);
            break;

        // --- ELIMINAR UN MEDICAMENTO DE LA RECETA ---
        case 'DELETE':
            if (!in_array($_SESSION['usuario']['rol'], ['ADMIN', 'MEDICO'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes.']);
                exit;
            }

            if (empty($_GET['id_receta'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Se requiere el id_receta a eliminar.']);
                exit;
            }

            $recetaModel->eliminarItem($_GET['id_receta']);
            echo json_encode(['success' => true, 'mensaje' => 'Medicamento removido de la receta.']);
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