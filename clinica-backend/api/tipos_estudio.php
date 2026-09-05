<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Manejo de peticiones preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

require_once __DIR__ . '/../models/TipoEstudio.php';

// 1. Verificar autenticación de sesión
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesion no iniciada.']);
    exit;
}

$tipoEstudioModel = new TipoEstudio();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Si se pasa un parámetro de búsqueda ?q=termino
            if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
                $termino = trim($_GET['q']);
                $resultados = $tipoEstudioModel->buscar($termino);
            } else {
                $resultados = $tipoEstudioModel->obtenerTodos();
            }

            echo json_encode([
                'status' => 'success',
                'data' => $resultados
            ]);
            break;

        case 'POST':

            if (!in_array($_SESSION['usuario']['rol'], ['ADMIN', 'MEDICO', 'LABORATORISTA'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes para registrar tipos de estudios.']);
                exit;
            }
            // Leer los datos del cuerpo de la petición (JSON o Form Data)
            $input = json_decode(file_get_contents('php://input'), true);

            $nombre_estudio = $input['nombre_estudio'] ?? $_POST['nombre_estudio'] ?? null;
            $descripcion = $input['descripcion'] ?? $_POST['descripcion'] ?? null;

            // Validación básica de campos requeridos
            if (empty($nombre_estudio) || empty(trim($nombre_estudio))) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El campo "nombre_estudio" es obligatorio.'
                ]);
                exit();
            }

            $id_creado = $tipoEstudioModel->registrar(trim($nombre_estudio), $descripcion ? trim($descripcion) : null);

            http_response_code(201);
            echo json_encode([
                'status' => 'success',
                'message' => 'Tipo de estudio registrado exitosamente.',
                'id_tipo_estudio' => $id_creado
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Método HTTP no permitido.'
            ]);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}