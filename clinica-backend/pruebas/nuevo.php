<?php

// Prueba para paciente con historia médica
require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/HistoriaMedica.php';

$pacienteModel = new Paciente();
$historiaModel = new HistoriaMedica();

// Datos para la prueba de registro integral
$datosPersona = [
    'cedula'           => 'V-24681357',
    'nombre'           => 'Elena',
    'apellido'         => 'Blanco',
    'fecha_nacimiento' => '1998-05-14',
    'telefono'         => '04149998877',
    'email'            => 'elena.blanco@email.com',
    'direccion'        => 'Urb. El Bosque, Calle 4'
];

$datosPaciente = [
    'genero'      => 'F',
    'tipo_sangre' => 'O+'
];

$datosHistoria = [
    'antecedentes'        => 'Asma bronquial en la infancia',
    'alergias'            => 'Aspirina, Polen',
    'medicacion_habitual' => 'Salbutamol en aerosol si hay crisis'
];

try {
    echo "--- Registrando Paciente con Historia Médica ---\n";

    if ($pacienteModel->registrarPaciente($datosPersona, $datosPaciente, $datosHistoria)) {
        echo "¡Paciente y Persona registrados correctamente!\n\n";

        // Verificación: Consultar la historia médica recién generada
        echo "--- Verificando la Historia Médica Creada ---\n";
        $historia = $historiaModel->obtenerPorPaciente('V-24681357');

        if ($historia) {
            echo "¡Historia médica encontrada automáticamente!\n";
            print_r($historia);
        } else {
            echo "Error: El paciente fue registrado pero no se encontró la historia médica.\n";
        }
    }

} catch (Exception $e) {
    echo "Error durante la prueba: " . $e->getMessage() . "\n";
}