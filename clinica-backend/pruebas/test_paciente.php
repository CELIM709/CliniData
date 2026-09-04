<?php
require_once __DIR__ . '/../models/Paciente.php';

$paciente = new Paciente();

$datosPersona = [
    'cedula'           => 'V-87654321',
    'nombre'           => 'María',
    'apellido'         => 'Gómez',
    'fecha_nacimiento' => '1995-10-20',
    'telefono'         => '04249876543',
    'email'            => 'maria.gomez@email.com',
    'direccion'        => 'Calle 5, Casa 12'
];

$datosPaciente = [
    'genero'      => 'F',
    'tipo_sangre' => 'O+'
];

try {
    if ($paciente->registrarPaciente($datosPersona, $datosPaciente)) {
        echo "¡Paciente y Persona registrados con éxito mediante transacción!\n\n";
        
        $lista = $paciente->obtenerTodos();
        print_r($lista);
    }
} catch (Exception $e) {
        echo "Error en la transacción: " . $e->getMessage() . "\n";
}