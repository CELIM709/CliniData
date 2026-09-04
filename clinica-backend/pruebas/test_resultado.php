<?php
require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/Resultado.php';

$db = Conexion::conectar();
$resultadoModel = new Resultado();

try {
    // 1. Obtener un estudio existente
    $idEstudio = $db->query("SELECT id_estudio FROM estudio ORDER BY id_estudio DESC LIMIT 1")->fetchColumn();

    if (!$idEstudio) {
        die("No hay estudios registrados para realizar la prueba.\n");
    }

    // 2. Simular la subida de un resultado en PDF/Imagen
    $descripcion = "Hemograma completo dentro de parámetros normales. Plaquetas 250.000/mm³.";
    $rutaSimulada = "uploads/resultados/2026/09/estudio_" . $idEstudio . "_analisis.pdf";

    $idResultado = $resultadoModel->registrarResultado($idEstudio, $descripcion, $rutaSimulada);
    echo "¡Resultado adjuntado con éxito! ID: {$idResultado}\n\n";

    // 3. Consultar resultados del estudio
    echo "--- Resultados del Estudio ID {$idEstudio} ---\n";
    $resultados = $resultadoModel->obtenerPorEstudio($idEstudio);
    print_r($resultados);

} catch (Exception $e) {
    echo "Error en la prueba: " . $e->getMessage() . "\n";
}