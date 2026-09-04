<?php
require_once __DIR__ . '/../models/Persona.php';

$personaModel = new Persona();

$nuevaPersona = [
    'cedula'           => 'V-12345678',
    'nombre'           => 'Carlos',
    'apellido'         => 'Pérez',
    'fecha_nacimiento' => '1992-05-15',
    'telefono'         => '04141234567',
    'email'            => 'carlos.perez@email.com',
    'direccion'        => 'Av. Bolivar, Edif. 3, Apt 4B'
];

try {
    if ($personaModel->crear($nuevaPersona)) {
        echo "¡Persona registrada exitosamente!\n\n";
        
        // Verificación leyendo el registro guardado
        $personaGuardada = $personaModel->obtenerPorCedula('V-12345678');
        print_r($personaGuardada);
    }
} catch (PDOException $e) {
    echo "Error en la prueba: " . $e->getMessage() . "\n";
}