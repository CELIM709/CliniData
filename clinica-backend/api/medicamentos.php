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

require_once __DIR__ . '/../models/Medicamento.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada.']);
    exit;
}

$medicamentoModel = new Medicamento();
$metodo = $_SERVER['REQUEST_METHOD'];



try {
    switch ($metodo) {

        // --- CONSULTAR O BUSCAR MEDICAMENTOS ---
        case 'GET':
            $busqueda = $_GET['q'] ?? $_GET['termino'] ?? null;

            if ($busqueda) {
                // Si envían un término de búsqueda (útil para auto-completar)
                $resultado = $medicamentoModel->buscar($busqueda);
            } else {
                // Si no hay parámetro, trae todo el catálogo
                $resultado = $medicamentoModel->obtenerTodos();
            }

            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        // --- REGISTRAR NUEVO MEDICAMENTO ---
        case 'POST':
            if (!in_array($_SESSION['usuario']['rol'], ['ADMIN', 'MEDICO'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes para registrar medicamentos.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            if (empty($input['nombre']) || empty($input['laboratorio']) || empty($input['presentacion'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Nombre, laboratorio y presentación son obligatorios.']);
                exit;
            }

            $idGenerado = $medicamentoModel->registrar(
                $input['nombre'],
                $input['laboratorio'],
                $input['presentacion']
            );

            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'mensaje' => 'Medicamento registrado exitosamente.',
                'id_medicamento' => $idGenerado
            ]);
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