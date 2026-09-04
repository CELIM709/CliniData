<?php
require_once __DIR__ . '/../config/Conexion.php';

class HistoriaMedica {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    /**
     * Obtener la historia médica completa de un paciente usando su cédula (PK)
     */
    public function obtenerPorPaciente($cedula_paciente) {
        $sql = "SELECT h.cedula_paciente, h.fecha_creacion, h.antecedentes, 
                       h.alergias, h.medicacion_habitual,
                       p.nombre, p.apellido, p.fecha_nacimiento, p.telefono, p.email,
                       pac.genero, pac.tipo_sangre
                FROM historia_medica h
                INNER JOIN paciente pac ON h.cedula_paciente = pac.cedula
                INNER JOIN persona p ON pac.cedula = p.cedula
                WHERE h.cedula_paciente = :cedula_paciente";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_paciente' => $cedula_paciente]);
        return $stmt->fetch();
    }

    /**
     * Actualizar dinámicamente campos de la historia médica
     */
    public function actualizarParcial($cedula_paciente, $datos) {
        $campos = [];
        $valores = [':cedula_paciente' => $cedula_paciente];

        foreach ($datos as $columna => $valor) {
            $campos[] = "{$columna} = :{$columna}";
            $valores[":{$columna}"] = $valor;
        }

        if (empty($campos)) {
            return false;
        }

        $sql = "UPDATE historia_medica SET " . implode(', ', $campos) . " WHERE cedula_paciente = :cedula_paciente";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($valores);
    }
}