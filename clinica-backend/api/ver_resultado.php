<?php

require_once '../config/Conexion.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Falta el ID del resultado.');
}

$idResultado = $_GET['id'];

try {

    // Buscar la ruta del archivo en la base de datos
    $sql = "SELECT ruta_archivo
            FROM resultado
            WHERE id_resultado = :id";

    $stmt = $db->prepare($sql);

    $stmt->execute([
        ':id' => $idResultado
    ]);

    $ruta = $stmt->fetchColumn();

    if ($ruta === false || empty($ruta)) {
        http_response_code(404);
        exit('No se encontró el archivo.');
    }

    // Desde /api subimos hasta la raíz de CliniData
    $archivo = __DIR__ . '/../../' . $ruta;

    if (!file_exists($archivo)) {
        http_response_code(404);
        exit('El archivo no existe en el servidor.');
    }

    // Obtener extensión
    $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

    // Tipos de archivo que queremos mostrar directamente
    $tipos = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif'
    ];

    $tipo = $tipos[$extension] ?? 'application/octet-stream';

    // Archivos que el navegador puede mostrar
    $archivosVisibles = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'gif'
    ];

    if (in_array($extension, $archivosVisibles)) {

        // Mostrar en el navegador
        header('Content-Disposition: inline');

    } else {

        // Descargar
        header('Content-Disposition: attachment; filename="' . basename($archivo) . '"');

    }

    header('Content-Type: ' . $tipo);
    header('Content-Length: ' . filesize($archivo));

    readfile($archivo);

} catch (PDOException $e) {

    http_response_code(500);
    exit('Error al obtener el archivo.');

}