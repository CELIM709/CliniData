<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
session_start();
require_once __DIR__ . '/../models/HistoriaMedica.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada.']);
    exit;
}

try {
    $model = new HistoriaMedica();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $cedula = trim($_GET['cedula_paciente'] ?? '');
        if ($cedula === '') { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Cédula de paciente requerida.']); exit; }
        $history = $model->obtenerPorPaciente($cedula);
        if (!$history) { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Historia médica no encontrada.']); exit; }
        echo json_encode(['success' => true, 'data' => $history]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT' || !in_array($_SESSION['usuario']['rol'], ['MEDICO', 'ADMIN'])) { http_response_code(403); echo json_encode(['success' => false, 'error' => 'Permisos insuficientes.']); exit; }
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $cedula = trim($input['cedula_paciente'] ?? '');
    if ($cedula === '') { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Cédula de paciente requerida.']); exit; }
    $fields = array_intersect_key($input, array_flip(['antecedentes', 'alergias', 'medicacion_habitual']));
    if (!$fields || !$model->actualizarParcial($cedula, $fields)) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'No se enviaron datos para actualizar.']); exit; }
    echo json_encode(['success' => true, 'mensaje' => 'Historia médica actualizada correctamente.']);
} catch (Exception $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $error->getMessage()]);
}