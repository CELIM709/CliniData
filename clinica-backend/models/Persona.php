<?php
require_once __DIR__ . '/../config/Conexion.php';
// Persona o PersonaModel en un futuro
class Persona {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    // Obtener todas las personas
    public function obtenerTodas() {
        $sql = "SELECT * FROM persona";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Buscar una persona por cédula
    public function obtenerPorCedula($cedula) {
        $sql = "SELECT * FROM persona WHERE cedula = :cedula";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula' => $cedula]);
        return $stmt->fetch();
    }

    // Registrar una nueva persona
    public function crear($datos) {
        $sql = "INSERT INTO persona (cedula, nombre, apellido, fecha_nacimiento, telefono, email, genero, tipo_sangre, direccion)
                VALUES (:cedula, :nombre, :apellido, :fecha_nacimiento, :telefono, :email, :genero, :tipo_sangre, :direccion)";
        
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':cedula'           => $datos['cedula'],
            ':nombre'           => $datos['nombre'],
            ':apellido'         => $datos['apellido'],
            ':fecha_nacimiento' => $datos['fecha_nacimiento'],
            ':telefono'         => $datos['telefono'] ?? null,
            ':email'            => $datos['email'] ?? null,
            ':genero'           => $datos['genero'] ?? null,
            ':tipo_sangre'      => $datos['tipo_sangre'] ?? null,
            ':direccion'        => $datos['direccion'] ?? null
        ]);
    }
}