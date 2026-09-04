<?php
// Esto es una prueba, ejecutar primero php -S localhost:8000;
// en api estaran los archivos que reciben el json.
header('Content-Type: application/json');

echo json_encode([
    'estado' => 'Servidor PHP activo',
    'mensaje' => '¡Tu entorno backend está listo!'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

