<?php
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Consulta.php';

$citaModel = new Cita();
$consultaModel = new Consulta();

$cedulaMedico   = 'V-55443322';
$cedulaPaciente = 'V-87654321';

try {
    // 1. Agendar una cita de prueba
    $idCita = null;
    $citaData = [
        'fecha_inicio'    => '2026-09-15 10:00:00',
        'fecha_fin'       => '2026-09-15 10:30:00',
        'consultorio'     => 'Consultorio 201',
        'cedula_medico'   => $cedulaMedico,
        'cedula_paciente' => $cedulaPaciente
    ];

    if ($citaModel->agendarCita($citaData)) {
        // Obtener el ID de la cita recién creada
        $citas = $citaModel->obtenerPorPaciente($cedulaPaciente);
        $idCita = $citas[0]['id_cita'];
        echo "Cita previa creada con ID: {$idCita}\n";
    }

    // 2. Registrar la Consulta vinculada a esa cita
    $datosConsulta = [
        'diagnostico'     => 'Rinitis alérgica estacional',
        'observaciones'   => 'Se indica antihistamínico por 7 días y control en 1 mes.',
        'costo'           => 50.00,
        'cedula_paciente' => $cedulaPaciente,
        'cedula_medico'   => $cedulaMedico,
        'id_cita'         => $idCita
    ];

    $idConsulta = $consultaModel->registrarConsulta($datosConsulta);
    echo "¡Consulta registrada con éxito! ID asignado: {$idConsulta}\n\n";

    // 3. Verificación de Historial del Paciente
    echo "--- Historial de Consultas del Paciente ---\n";
    $historial = $consultaModel->obtenerPorPaciente($cedulaPaciente);
    print_r($historial);

} catch (Exception $e) {
    echo "Error en la prueba: " . $e->getMessage() . "\n";
}