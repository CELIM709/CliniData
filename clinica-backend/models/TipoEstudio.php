<?php
require_once __DIR__ . '/../config/Conexion.php';

class TipoEstudio {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    public function obtenerTodos() {
        $sql = "SELECT id_tipo_estudio, nombre_estudio, descripcion 
                FROM tipo_estudio 
                ORDER BY nombre_estudio ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function registrar($nombre_estudio, $descripcion = null) {
        $sql = "INSERT INTO tipo_estudio (nombre_estudio, descripcion) 
                VALUES (:nombre_estudio, :descripcion) 
                RETURNING id_tipo_estudio";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre_estudio' => $nombre_estudio,
            ':descripcion'    => $descripcion
        ]);
        return $stmt->fetchColumn();
    }

    public function buscar($termino) {
        $sql = "SELECT id_tipo_estudio, nombre_estudio, descripcion 
                FROM tipo_estudio 
                WHERE nombre_estudio ILIKE :termino OR descripcion ILIKE :termino 
                ORDER BY nombre_estudio ASC LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':termino' => "%{$termino}%"]);
        return $stmt->fetchAll();
    }
}