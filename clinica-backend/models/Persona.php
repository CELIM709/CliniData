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
        $sql = "INSERT INTO persona (cedula, nombre, apellido, fecha_nacimiento, telefono, email, direccion)
                VALUES (:cedula, :nombre, :apellido, :fecha_nacimiento, :telefono, :email, :direccion)";
        
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':cedula'           => $datos['cedula'],
            ':nombre'           => $datos['nombre'],
            ':apellido'         => $datos['apellido'],
            ':fecha_nacimiento' => $datos['fecha_nacimiento'],
            ':telefono'         => $datos['telefono'] ?? null,
            ':email'            => $datos['email'] ?? null,
            ':direccion'        => $datos['direccion'] ?? null
        ]);
    }

    // Actualizar datos personales básicos
    public function actualizar($cedula, $datos) {
        $sql = "UPDATE persona 
                SET nombre = :nombre, 
                    apellido = :apellido, 
                    fecha_nacimiento = :fecha_nacimiento, 
                    telefono = :telefono, 
                    email = :email, 
                    direccion = :direccion 
                WHERE cedula = :cedula";
                
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':cedula'           => $cedula,
            ':nombre'           => $datos['nombre'],
            ':apellido'         => $datos['apellido'],
            ':fecha_nacimiento' => $datos['fecha_nacimiento'],
            ':telefono'         => $datos['telefono'] ?? null,
            ':email'            => $datos['email'] ?? null,
            ':direccion'        => $datos['direccion'] ?? null
        ]);
    }

    // Eliminar a la persona del sistema completo (limpia roles asociados en cascada)
    public function eliminar($cedula) {
        $sql = "DELETE FROM persona WHERE cedula = :cedula";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':cedula' => $cedula]);
    }

    public function actualizarParcial($cedula, $datos) {
        $campos = [];
        $valores = [':cedula' => $cedula];

        // Construye dinámicamente solo los campos a modificar
        foreach ($datos as $columna => $valor) {
            $campos[] = "{$columna} = :{$columna}";
            $valores[":{$columna}"] = $valor;
        }

        if (empty($campos)) {
            return false; // No hay nada que actualizar
        }

        $sql = "UPDATE persona SET " . implode(', ', $campos) . " WHERE cedula = :cedula";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($valores);
    }
}