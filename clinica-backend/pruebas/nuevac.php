<?php
// prueba para una consulta con cita detectada automáticamente
require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/Consulta.php';

$db = Conexion::conectar();
$consultaModel = new Consulta();

$cedulaMedico   = 'V-55443322';
$cedulaPaciente = 'V-87654321';

try {
    echo "--- Probando registro automático de consulta ---\n";

    // Enviamos ÚNICAMENTE las cédulas, el diagnóstico y las observaciones.
    // Omitimos 'costo' e 'id_cita' a propósito.
    $datosConsulta = [
        'cedula_medico'   => $cedulaMedico,
        'cedula_paciente' => $cedulaPaciente,
        'diagnostico'     => 'Cuadro gripal y congestión nasal',
        'observaciones'   => 'Indicado reposo por 48 horas e hidratación.'
    ];

    $idConsulta = $consultaModel->registrarConsulta($datosConsulta);
    echo "¡Consulta registrada con éxito! ID asignado: {$idConsulta}\n\n";

    // 1. Verificación de la consulta guardada (Costo e ID Cita)
    echo "--- Datos registrados en la Consulta ---\n";
    $consultaGuardada = $consultaModel->obtenerPorId($idConsulta);
    print_r([
        'ID Consulta'   => $consultaGuardada['id_consulta'],
        'Diagnóstico'   => $consultaGuardada['diagnostico'],
        'Costo Cobrado' => $consultaGuardada['costo'],
        'ID Cita Asociada' => $consultaGuardada['id_cita']
    ]);

    // 2. Verificación del estado actualizado de la cita
    if ($consultaGuardada['id_cita']) {
        echo "\n--- Verificando cambio de estado en la Cita ID {$consultaGuardada['id_cita']} ---\n";
        $stmtCita = $db->prepare("SELECT id_cita, estado, lower(rango_cita) AS fecha_inicio FROM cita WHERE id_cita = :id_cita");
        $stmtCita->execute([':id_cita' => $consultaGuardada['id_cita']]);
        $citaActualizada = $stmtCita->fetch();

        echo "Estado actual de la cita: {$citaActualizada['estado']}\n";
    }

} catch (Exception $e) {
    echo "Error durante la prueba: " . $e->getMessage() . "\n";
}