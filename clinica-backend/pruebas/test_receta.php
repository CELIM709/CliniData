<?php
require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/Receta.php';

$db = Conexion::conectar();
$recetaModel = new Receta();

try {
    // 1. Crear medicamentos de prueba si no existen
    $sqlMed = "INSERT INTO medicamento (nombre, laboratorio, presentacion)
               VALUES ('Amoxicilina', 'Meyer', 'Cápsulas 500mg'),
                      ('Ibuprofeno', 'VeneLab', 'Comprimidos 400mg')
               ON CONFLICT DO NOTHING";
    $db->exec($sqlMed);

    // Obtener IDs de los medicamentos
    $meds = $db->query("SELECT id_medicamento, nombre FROM medicamento LIMIT 2")->fetchAll();

    // 2. Obtener la última consulta registrada
    $idConsulta = $db->query("SELECT id_consulta FROM consulta ORDER BY id_consulta DESC LIMIT 1")->fetchColumn();

    if (!$idConsulta) {
        die("No hay consultas registradas para realizar la prueba.\n");
    }

    // 3. Estructurar la receta médica
    $itemsReceta = [
        [
            'id_medicamento' => $meds[0]['id_medicamento'],
            'dosis'          => '500 mg',
            'frecuencia'     => 'Cada 8 horas',
            'duracion'       => '7 días',
            'indicaciones'   => 'Tomar con abundante agua después de las comidas.'
        ],
        [
            'id_medicamento' => $meds[1]['id_medicamento'],
            'dosis'          => '400 mg',
            'frecuencia'     => 'Cada 12 horas',
            'duracion'       => '3 días',
            'indicaciones'   => 'Solo si presenta dolor o fiebre.'
        ]
    ];

    // 4. Guardar receta
    if ($recetaModel->registrarReceta($idConsulta, $itemsReceta)) {
        echo "¡Receta prescrita con éxito para la Consulta ID: {$idConsulta}!\n\n";

        // 5. Consultar detalle de la receta
        echo "--- Indicaciones Mapeadas ---\n";
        $prescripcion = $recetaModel->obtenerPorConsulta($idConsulta);
        print_r($prescripcion);
    }

} catch (Exception $e) {
    echo "Error en la prueba: " . $e->getMessage() . "\n";
}