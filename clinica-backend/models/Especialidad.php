<?php
require_once __DIR__ . '/../config/Conexion.php';

class Especialidad {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    public function crear($nombre, $descripcion = null) {
        $sql = "INSERT INTO especialidad (nombre, descripcion) VALUES (:nombre, :descripcion) RETURNING id_especialidad";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion]);
        return $stmt->fetchColumn();
    }

    public function obtenerTodas() {
        $sql = "SELECT id_especialidad, nombre, descripcion FROM especialidad ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function asignarAMedico($cedula_medico, $id_especialidad) {
        $sql = "INSERT INTO medico_especialidad (cedula_medico, id_especialidad) VALUES (:cedula, :id_especialidad) ON CONFLICT DO NOTHING";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':cedula' => $cedula_medico, ':id_especialidad' => $id_especialidad]);
    }
}