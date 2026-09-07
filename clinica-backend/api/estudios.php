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

require_once __DIR__ . '/../models/Estudio.php';

$estudioModel = new Estudio();
$metodo = $_SERVER['REQUEST_METHOD'];

// 1. Identificar si es una consulta pública
$esConsultaPublicaPaciente = ($metodo === 'GET');

// 2. Validar sesión ÚNICAMENTE si NO es una consulta pública de paciente
if (!$esConsultaPublicaPaciente && !isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesion no iniciada.']);
    exit;
}

try {
    switch ($metodo) {

        
        // --- CONSULTAR ESTUDIOS (GET) ---
        
        case 'GET':
            // Opción 1: Obtener estudios de una consulta específica (?id_consulta=X)
            if (isset($_GET['id_consulta'])) {
                $idConsulta = filter_var($_GET['id_consulta'], FILTER_VALIDATE_INT);
                if (!$idConsulta) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'ID de consulta inválido.']);
                    exit;
                }
                $estudios = $estudioModel->obtenerPorConsulta($idConsulta);
                echo json_encode(['success' => true, 'data' => $estudios]);
                break;
            }

            // Opción 2: Obtener historial de estudios de un paciente (?paciente=X)
            if (isset($_GET['paciente'])) {
                $cedulaPaciente = trim($_GET['paciente']);
                if (empty($cedulaPaciente)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Cédula de paciente requerida.']);
                    exit;
                }
                $estudios = $estudioModel->obtenerPorPaciente($cedulaPaciente);
                echo json_encode(['success' => true, 'data' => $estudios]);
                break;
            }

            // Opción 3: Obtener estudios filtrados por tipo de estudio (?id_tipo_estudio=X o ?id_tipo=X)
            if (isset($_GET['id_tipo_estudio']) || isset($_GET['id_tipo'])) {
                $idTipoEstudio = filter_var($_GET['id_tipo_estudio'] ?? $_GET['id_tipo'], FILTER_VALIDATE_INT);
                if (!$idTipoEstudio) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'ID de tipo de estudio inválido.']);
                    exit;
                }
                $estudios = $estudioModel->obtenerPorTipo($idTipoEstudio);
                echo json_encode(['success' => true, 'data' => $estudios]);
                break;
            }

            // Opción 4 (Por defecto / ?action=pendientes): Bandeja general de laboratorio (Pendientes)
            $estudios = $estudioModel->obtenerPendientes();
            echo json_encode(['success' => true, 'data' => $estudios]);
            break;

        
        // --- SOLICITAR / COMPLETAR / MODIFICAR (POST) ---
        
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $action = $input['action'] ?? $_GET['action'] ?? 'solicitar';

            // ACCIÓN A: Solicitar exámenes desde una consulta médica
            if ($action === 'solicitar') {
                $rolUsuario = $_SESSION['usuario']['rol'] ?? '';
                if (!in_array($rolUsuario, ['MEDICO', 'ADMIN'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Solo médicos o administradores pueden solicitar exámenes.']);
                    exit;
                }

                // Permite recibir los IDs tanto en 'tipos' como en 'tipos_estudios'
                $tiposEstudios = $input['tipos'] ?? $input['tipos_estudios'] ?? null;

                if (empty($input['id_consulta']) || empty($tiposEstudios) || !is_array($tiposEstudios)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Se requiere "id_consulta" y un arreglo "tipos" (o "tipos_estudios") con los IDs de los tipos de exámenes.'
                    ]);
                    exit;
                }

                $resultado = $estudioModel->solicitarEstudios($input['id_consulta'], $tiposEstudios);

                if ($resultado) {
                    http_response_code(201);
                    echo json_encode(['success' => true, 'mensaje' => 'Estudios solicitados exitosamente.']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'No se pudieron registrar los estudios.']);
                }
                break;
            }

            // ACCIÓN B: Completar un estudio (procesado por laboratorista)
            if ($action === 'completar') {
                $rolUsuario = $_SESSION['usuario']['rol'] ?? '';
                if (!in_array($rolUsuario, ['LABORATORISTA', 'ADMIN'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Solo laboratoristas o administradores pueden procesar estudios.']);
                    exit;
                }

                $idEstudio = $input['id_estudio'] ?? null;
                // Toma la cédula del laboratorista del parámetro o de la sesión activa
                $cedulaLaboratorista = $input['cedula_laboratorista'] ?? $_SESSION['usuario']['cedula'] ?? null;

                if (!$idEstudio || !$cedulaLaboratorista) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Falta "id_estudio" o "cedula_laboratorista".']);
                    exit;
                }

                $resultado = $estudioModel->completarEstudio($idEstudio, $cedulaLaboratorista);

                if ($resultado) {
                    echo json_encode(['success' => true, 'mensaje' => 'Estudio marcado como REALIZADO.']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'No se pudo actualizar el estudio.']);
                }
                break;
            }

            // ACCIÓN C: Cambiar estado arbitrario (SOLICITADO, REALIZADO, CANCELADO)
            if ($action === 'cambiar_estado') {
                $idEstudio = $input['id_estudio'] ?? null;
                $estado = strtoupper($input['estado'] ?? '');

                $estadosValidos = ['SOLICITADO', 'REALIZADO', 'CANCELADO'];
                if (!$idEstudio || !in_array($estado, $estadosValidos)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Estado no válido. Debe ser SOLICITADO, REALIZADO o CANCELADO.'
                    ]);
                    exit;
                }

                $resultado = $estudioModel->cambiarEstado($idEstudio, $estado);

                if ($resultado) {
                    echo json_encode(['success' => true, 'mensaje' => 'Estado actualizado a ' . $estado]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Error al cambiar estado del estudio.']);
                }
                break;
            }

            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acción no válida en POST.']);
            break;

        
        // --- ACTUALIZACIONES DIRECTAS (PUT) ---
        
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);

            // Completar estudio mediante PUT
            if (isset($input['action']) && $input['action'] === 'completar') {
                $idEstudio = $input['id_estudio'] ?? null;
                $cedulaLaboratorista = $input['cedula_laboratorista'] ?? $_SESSION['usuario']['cedula'] ?? null;

                if (!$idEstudio || !$cedulaLaboratorista) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Faltan parámetros id_estudio o cedula_laboratorista.']);
                    exit;
                }

                $resultado = $estudioModel->completarEstudio($idEstudio, $cedulaLaboratorista);
                echo json_encode(['success' => $resultado, 'mensaje' => $resultado ? 'Estudio completado.' : 'Error al actualizar.']);
                break;
            }

            // Cambiar estado mediante PUT
            if (isset($input['id_estudio']) && isset($input['estado'])) {
                $resultado = $estudioModel->cambiarEstado($input['id_estudio'], strtoupper($input['estado']));
                echo json_encode(['success' => $resultado, 'mensaje' => $resultado ? 'Estado actualizado.' : 'Error al cambiar estado.']);
                break;
            }

            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parámetros insuficientes para la petición PUT.']);
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