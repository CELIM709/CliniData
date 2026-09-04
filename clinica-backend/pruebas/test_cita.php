<?php
require_once __DIR__ . '/../models/Cita.php';

$citaModel = new Cita();

// Usamos las cédulas cargadas en las pruebas anteriores
$cedulaMedico   = 'V-55443322'; 
$cedulaPaciente = 'V-87654321'; 

$cita1 = [
    'fecha_inicio'    => '2026-09-10 09:00:00',
    'fecha_fin'       => '2026-09-10 09:30:00',
    'consultorio'     => 'Consultorio 102',
    'cedula_medico'   => $cedulaMedico,
    'cedula_paciente' => $cedulaPaciente
];

// Cita 2: Se solapa 15 minutos con la Cita 1
$citaSolapada = [
    'fecha_inicio'    => '2026-09-10 09:15:00',
    'fecha_fin'       => '2026-09-10 09:45:00',
    'consultorio'     => 'Consultorio 102',
    'cedula_medico'   => $cedulaMedico,
    'cedula_paciente' => $cedulaPaciente
];

try {
    echo "--- Intentando agendar Cita 1 ---\n";
    if ($citaModel->agendarCita($cita1)) {
        echo "¡Cita 1 agendada con éxito!\n\n";
    }

    echo "--- Intentando agendar Cita 2 (Solapada) ---\n";
    $citaModel->agendarCita($citaSolapada);

} catch (Exception $e) {
    echo "Resultado esperado del motor: " . $e->getMessage() . "\n\n";
}

// Mostrar la agenda cargada del médico
echo "--- Agenda del Médico ---\n";
$agenda = $citaModel->obtenerPorMedico($cedulaMedico);
$agendaP = $citaModel->obtenerPorPaciente($cedulaPaciente);
print_r($agenda);
echo "--- Agenda del Paciente ---\n";
print_r($agendaP);