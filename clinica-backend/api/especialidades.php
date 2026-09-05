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

require_once __DIR__ . '/../models/Especialidad.php';

// 1. Verificar autenticación de sesión
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesion no iniciada.']);
    exit;
}

$especialidadModel = new Especialidad();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch ($metodo) {

        
        // --- CONSULTAR ESPECIALIDADES (GET) ---
        
        case 'GET':
            // Permite listar todas las especialidades disponibles
            $especialidades = $especialidadModel->obtenerTodas();
            echo json_encode(['success' => true, 'data' => $especialidades]);
            break;

        
        // --- CREAR O ASIGNAR ESPECIALIDAD (POST) ---
        
        case 'POST':
            // Solo Administradores pueden gestionar el catálogo y asignaciones
            if ($_SESSION['usuario']['rol'] !== 'ADMIN') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado. Solo administradores pueden gestionar especialidades.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $action = $input['action'] ?? 'crear';

            // ACCIÓN A: Crear una nueva especialidad
            if ($action === 'crear') {
                $nombre = trim($input['nombre'] ?? '');
                $descripcion = trim($input['descripcion'] ?? null);

                if (empty($nombre)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'El nombre de la especialidad es obligatorio.']);
                    exit;
                }

                $idGenerado = $especialidadModel->crear($nombre, $descripcion);

                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Especialidad creada con exito.',
                    'id_especialidad' => $idGenerado
                ]);
                break;
            }

            // ACCIÓN B: Asignar especialidad a un médico
            if ($action === 'asignar') {
                $cedulaMedico = trim($input['cedula_medico'] ?? '');
                $idEspecialidad = filter_var($input['id_especialidad'] ?? null, FILTER_VALIDATE_INT);

                if (empty($cedulaMedico) || !$idEspecialidad) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Se requiere "cedula_medico" e "id_especialidad" válidos.'
                    ]);
                    exit;
                }

                $resultado = $especialidadModel->asignarAMedico($cedulaMedico, $idEspecialidad);

                if ($resultado) {
                    echo json_encode(['success' => true, 'mensaje' => 'Especialidad asignada al médico exitosamente.']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'No se pudo realizar la asignación.']);
                }
                break;
            }

            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
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