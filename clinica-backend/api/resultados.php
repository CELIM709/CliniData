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

require_once __DIR__ . '/../models/Resultado.php';

$resultadoModel = new Resultado();
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

        
        // --- CONSULTAR RESULTADOS (GET) ---
        
        case 'GET':
            // Opción 1: Obtener resultados de un estudio específico (?id_estudio=X)
            if (isset($_GET['id_estudio'])) {
                $idEstudio = filter_var($_GET['id_estudio'], FILTER_VALIDATE_INT);
                if (!$idEstudio) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'ID de estudio inválido.']);
                    exit;
                }

                $resultados = $resultadoModel->obtenerPorEstudio($idEstudio);
                echo json_encode(['success' => true, 'data' => $resultados]);
                break;
            }

            // Opción 2: Obtener todos los resultados de una consulta médica (?id_consulta=Y)
            if (isset($_GET['id_consulta'])) {
                $idConsulta = filter_var($_GET['id_consulta'], FILTER_VALIDATE_INT);
                if (!$idConsulta) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'ID de consulta inválido.']);
                    exit;
                }

                $resultados = $resultadoModel->obtenerPorConsulta($idConsulta);
                echo json_encode(['success' => true, 'data' => $resultados]);
                break;
            }

            // Opción 3: Obtener todos los resultados de un paciente (?paciente=Z)
            if (isset($_GET['paciente'])) {
                $cedulaPaciente = trim($_GET['paciente']);
                if (empty($cedulaPaciente)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Cédula de paciente requerida.']);
                    exit;
                }

                $resultados = $resultadoModel->obtenerPorPaciente($cedulaPaciente);
                echo json_encode(['success' => true, 'data' => $resultados]);
                break;
            }

            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Debe especificar "id_estudio", "id_consulta" o "paciente".']);
            break;

        
        // --- REGISTRAR / SUBIR RESULTADO (POST) ---
        
        case 'POST':
            $rolUsuario = $_SESSION['usuario']['rol'] ?? '';
            if (!in_array($rolUsuario, ['LABORATORISTA', 'ADMIN', 'MEDICO'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes para registrar resultados.']);
                exit;
            }

            // Datos provenientes de FormData (Soporta archivos subidos y texto)
            $idEstudio = $_POST['id_estudio'] ?? null;
            $descripcion = $_POST['descripcion'] ?? '';
            $rutaArchivo = null;

            // Soporte fallback para JSON si no se envía un archivo directo
            if (!$idEstudio) {
                $input = json_decode(file_get_contents('php://input'), true);
                $idEstudio = $input['id_estudio'] ?? null;
                $descripcion = $input['descripcion'] ?? '';
                $rutaArchivo = $input['ruta_archivo'] ?? null;
            }

            if (empty($idEstudio) || empty($descripcion)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Faltan campos obligatorios (id_estudio, descripcion).']);
                exit;
            }

            // Lógica de procesamiento de archivo físico si viene en $_FILES
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $directorioUploads = __DIR__ . '/../uploads/resultados/';

                // Crear el directorio si no existe
                if (!is_dir($directorioUploads)) {
                    mkdir($directorioUploads, 0755, true);
                }

                $extension = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
                $nombreUnico = 'estudio_' . $idEstudio . '_' . time() . '_' . uniqid() . '.' . $extension;
                $destinoFinal = $directorioUploads . $nombreUnico;

                if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destinoFinal)) {
                    $rutaArchivo = 'uploads/resultados/' . $nombreUnico;
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo en el servidor.']);
                    exit;
                }
            }

            if (empty($rutaArchivo)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Debe adjuntar un archivo o especificar una ruta válida.']);
                exit;
            }

            $idGenerado = $resultadoModel->registrarResultado($idEstudio, $descripcion, $rutaArchivo);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'mensaje' => 'Resultado registrado con éxito.',
                'id_resultado' => $idGenerado,
                'ruta_archivo' => $rutaArchivo
            ]);
            break;

        
        // --- ELIMINAR RESULTADO (DELETE) ---
        
        case 'DELETE':
            $rolUsuario = $_SESSION['usuario']['rol'] ?? '';
            if (!in_array($rolUsuario, ['LABORATORISTA', 'ADMIN'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
                exit;
            }

            if (empty($_GET['id_resultado'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Se requiere el id_resultado a eliminar.']);
                exit;
            }

            $idResultado = $_GET['id_resultado'];
            $rutaArchivo = $resultadoModel->eliminar($idResultado);

            // Elimina físicamente el archivo del servidor si existe
            if ($rutaArchivo) {
                $pathFisico = __DIR__ . '/../' . $rutaArchivo;
                if (file_exists($pathFisico)) {
                    @unlink($pathFisico);
                }
            }

            echo json_encode(['success' => true, 'mensaje' => 'Resultado y archivo asociado eliminados.']);
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