<?php
require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/TipoEstudio.php';
require_once __DIR__ . '/../models/Estudio.php';

$db = Conexion::conectar();
$tipoEstudioModel = new TipoEstudio();
$estudioModel = new Estudio();

try {
    // 1. Crear / registrar 3 tipos de estudio de prueba
    echo "--- Registrando Tipos de Estudio ---\n";
    
    $tiposPrueba = [
        ['nombre' => 'Hemograma Completo', 'descripcion' => 'Evaluación de glóbulos rojos, blancos y plaquetas.'],
        ['nombre' => 'Rayos X de Tórax (PA)', 'descripcion' => 'Radiografía simple de área torácica.'],
        ['nombre' => 'Perfil Lipídico', 'descripcion' => 'Análisis de colesterol total, HDL, LDL y triglicéridos.']
    ];

    $idsTipos = [];

    foreach ($tiposPrueba as $tipo) {
        $idCreado = $tipoEstudioModel->registrar($tipo['nombre'], $tipo['descripcion']);
        $idsTipos[] = $idCreado;
        echo "Tipo de estudio registrado: '{$tipo['nombre']}' (ID: {$idCreado})\n";
    }
    echo "\n";

    // 2. Obtener la última consulta registrada
    $idConsulta = $db->query("SELECT id_consulta FROM consulta ORDER BY id_consulta DESC LIMIT 1")->fetchColumn();

    if (!$idConsulta) {
        die("No hay consultas disponibles para asignarle estudios de prueba.\n");
    }

    // 3. Solicitar los primeros 2 tipos de estudio creados usando sus IDs numéricos
    $estudiosASolicitar = [$idsTipos[0], $idsTipos[1]];
    
    $estudioModel->solicitarEstudios($idConsulta, $estudiosASolicitar);
    echo "¡Estudios (IDs: " . implode(', ', $estudiosASolicitar) . ") solicitados correctamente para la consulta ID {$idConsulta}!\n\n";

    // 4. Ver bandeja de laboratorio (Estudios pendientes)
    echo "--- Bandeja de Estudios Pendientes en Laboratorio ---\n";
    $pendientes = $estudioModel->obtenerPendientes();
    print_r($pendientes);

    // 5. Procesar el primer estudio pendiente si existe laboratorista
    if (!empty($pendientes)) {
        $idEstudioProcesar = $pendientes[0]['id_estudio'];
        
        // Obtener una cédula de laboratorista para simular el procesamiento
        $cedulaLab = $db->query("SELECT cedula FROM laboratorista LIMIT 1")->fetchColumn();

        if ($cedulaLab) {
            $estudioModel->completarEstudio($idEstudioProcesar, $cedulaLab);
            echo "\nEstudio ID {$idEstudioProcesar} marcado como REALIZADO por laboratorista {$cedulaLab}.\n";
        } else {
            echo "\nNota: No se encontró ningún laboratorista en la base de datos para simular el completado.\n";
        }
    }

} catch (Exception $e) {
    echo "Error en la prueba: " . $e->getMessage() . "\n";
}