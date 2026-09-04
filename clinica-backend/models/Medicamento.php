<?php
require_once __DIR__ . '/../config/Conexion.php';

class Medicamento {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    public function registrar($nombre, $laboratorio, $presentacion) {
        $sql = "INSERT INTO medicamento (nombre, laboratorio, presentacion) 
                VALUES (:nombre, :laboratorio, :presentacion) 
                RETURNING id_medicamento";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'       => $nombre,
            ':laboratorio'  => $laboratorio,
            ':presentacion' => $presentacion
        ]);
        return $stmt->fetchColumn();
    }

    public function buscar($termino) {
        $sql = "SELECT id_medicamento, nombre, laboratorio, presentacion 
                FROM medicamento 
                WHERE nombre ILIKE :termino OR laboratorio ILIKE :termino 
                ORDER BY nombre ASC LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':termino' => "%{$termino}%"]);
        return $stmt->fetchAll();
    }
}