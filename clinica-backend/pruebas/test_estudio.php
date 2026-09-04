<?php
require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/Estudio.php';

$db = Conexion::conectar();
$estudioModel = new Estudio();

try {
    // 1. Obtener la última consulta
    $idConsulta = $db->query("SELECT id_consulta FROM consulta ORDER BY id_consulta DESC LIMIT 1")->fetchColumn();

    if (!$idConsulta) {
        die("No hay consultas disponibles para asignarle estudios de prueba.\n");
    }

    // 2. Solicitar exámenes para la consulta
    $examenes = ['Perfil 20 (Hematología/Química)', 'Rayos X de Tórax (PA)'];
    $estudioModel->solicitarEstudios($idConsulta, $examenes);
    echo "¡Estudios solicitados correctamente para la consulta ID {$idConsulta}!\n\n";

    // 3. Ver bandeja de laboratorio (Estudios pendientes)
    echo "--- Bandeja de Estudios Pendientes en Laboratorio ---\n";
    $pendientes = $estudioModel->obtenerPendientes();
    print_r($pendientes);

    // 4. Procesar el primer estudio pendiente si existe laboratorista
    if (!empty($pendientes)) {
        $idEstudioProcesar = $pendientes[0]['id_estudio'];
        
        // Obtener una cédula de laboratorista para simular la toma/procesamiento
        $cedulaLab = $db->query("SELECT cedula FROM laboratorista LIMIT 1")->fetchColumn();

        if ($cedulaLab) {
            $estudioModel->completarEstudio($idEstudioProcesar, $cedulaLab);
            echo "\nEstudio ID {$idEstudioProcesar} marcado como REALIZADO por laboratorista {$cedulaLab}.\n";
        }
    }

} catch (Exception $e) {
    echo "Error en la prueba: " . $e->getMessage() . "\n";
}