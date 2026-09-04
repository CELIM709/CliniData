<?php
require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../models/Empleado.php';

$db = Conexion::conectar();
$empleadoModel = new Empleado();

try {
    // 1. Crear un Horario base de prueba en PostgreSQL (si no existe uno)
    $sqlHorario = "INSERT INTO horario (dias, hora_entrada, hora_salida) 
                   VALUES ('Lunes a Viernes', '08:00:00', '16:00:00') 
                   RETURNING id_horario";
    $stmtHorario = $db->prepare($sqlHorario);
    $stmtHorario->execute();
    $idHorario = $stmtHorario->fetchColumn();

    echo "Horario de prueba creado con ID: {$idHorario}\n\n";

    // 2. Datos para registrar una Recepcionista
    $datosPersona = [
        'cedula'           => 'V-99887766',
        'nombre'           => 'Ana',
        'apellido'         => 'Rojas',
        'fecha_nacimiento' => '1990-03-12',
        'telefono'         => '04165554433',
        'email'            => 'ana.rojas@clinica.com',
        'direccion'        => 'Av. Principal, Edif. Centro'
    ];

    $datosEmpleado = [
        'salario'          => 450.00,
        'fecha_contratado' => '2026-01-15',
        'clave_acceso'     => '12345', // Contraseña en texto plano
        'rol'              => 'RECEPCIONISTA',
        'id_horario'       => $idHorario
    ];

    $datosRol = [
        'estacion_trabajo' => 'Caja Principal - Recepción A',
        'extension_tlf'    => '102'
    ];

    // 3. Registro del empleado
    if ($empleadoModel->registrarEmpleado($datosPersona, $datosEmpleado, $datosRol)) {
        echo "¡Empleado registrado con éxito con hash Bcrypt!\n\n";
    }

    // 4. Prueba A: Login con contraseña CORRECTA
    echo "--- Prueba A: Credenciales correctas ---\n";
    $usuarioLogueado = $empleadoModel->login('V-99887766', '12345');

    if ($usuarioLogueado) {
        echo "Login exitoso. Bienvenido/a {$usuarioLogueado['nombre']} ({$usuarioLogueado['rol']})\n";
        print_r($usuarioLogueado);
    } else {
        echo "Error: Credenciales no aceptadas.\n";
    }

    // 5. Prueba B: Login con contraseña INCORRECTA
    echo "\n--- Prueba B: Contraseña incorrecta ---\n";
    $loginFallido = $empleadoModel->login('V-99887766', 'ClaveFalsa');

    if (!$loginFallido) {
        echo "Seguridad verificada: El acceso fue rechazado correctamente con la clave incorrecta.\n";
    }

} catch (Exception $e) {
    echo "Error durante la prueba: " . $e->getMessage() . "\n";
}