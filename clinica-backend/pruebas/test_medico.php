<?php
require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/Medico.php';

$db = Conexion::conectar();
$medicoModel = new Medico();

try {
    // 1. Insertar una especialidad de prueba
    $sqlEsp = "INSERT INTO especialidad (nombre, descripcion) 
               VALUES ('Pediatría', 'Atención médica integral a niños y adolescentes') 
               ON CONFLICT (nombre) DO NOTHING 
               RETURNING id_especialidad";
    $stmtEsp = $db->prepare($sqlEsp);
    $stmtEsp->execute();
    $idEspecialidad = $stmtEsp->fetchColumn();

    if (!$idEspecialidad) {
        // Si ya existía, recuperamos su ID
        $stmtFetch = $db->prepare("SELECT id_especialidad FROM especialidad WHERE nombre = 'Pediatría'");
        $stmtFetch->execute();
        $idEspecialidad = $stmtFetch->fetchColumn();
    }

    // 2. Obtener un id_horario existente
    $stmtH = $db->query("SELECT id_horario FROM horario LIMIT 1");
    $idHorario = $stmtH->fetchColumn();

    // 3. Datos del Médico
    $datosPersona = [
        'cedula'           => 'V-55443322',
        'nombre'           => 'Dr. Roberto',
        'apellido'         => 'Mendoza',
        'fecha_nacimiento' => '1982-08-25',
        'telefono'         => '04121112233',
        'email'            => 'roberto.mendoza@clinica.com',
        'direccion'        => 'Av. Las Delicias'
    ];

    $datosEmpleado = [
        'salario'          => 1200.00,
        'fecha_contratado' => '2026-02-01',
        'clave_acceso'     => 'DocPass2026*',
        'id_horario'       => $idHorario
    ];

    $datosMedico = [
        'carnet_medico' => 'MPPS-987654',
        'tarifa'        => 45.00
    ];

    // 4. Registrar Médico con su especialidad
    if ($medicoModel->registrarMedico($datosPersona, $datosEmpleado, $datosMedico, [$idEspecialidad])) {
        echo "¡Médico registrado con éxito y especialidad vinculada!\n\n";

        // 5. Consultar información completa
        $medicoGuardado = $medicoModel->obtenerPorCedula('V-55443322');
        print_r($medicoGuardado);
    }

} catch (Exception $e) {
    echo "Error en la prueba: " . $e->getMessage() . "\n";
}