<?php
require_once __DIR__ . '/../config/Conexion.php';

class Estudio {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    /**
     * Solicitar uno o varios exámenes/estudios dentro de una consulta médica
     * 
     * @param int $id_consulta
     * @param array $tiposEstudios Arreglo de strings con los nombres/tipos de estudios (Ej: ['Perfil 20', 'Rayos X Torax'])
     */
    public function solicitarEstudios($id_consulta, array $tiposEstudios) {
        if (empty($tiposEstudios)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO estudio (tipo, id_consulta) VALUES (:tipo, :id_consulta)";
            $stmt = $this->db->prepare($sql);

            foreach ($tiposEstudios as $tipo) {
                $stmt->execute([
                    ':tipo'        => $tipo,
                    ':id_consulta' => $id_consulta
                ]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Registrar la ejecución de un estudio asociando al laboratorista responsable
     */
    public function completarEstudio($id_estudio, $cedula_laboratorista) {
        $sql = "UPDATE estudio 
                SET estado = 'REALIZADO', 
                    laboratorista = :laboratorista 
                WHERE id_estudio = :id_estudio";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':laboratorista' => $cedula_laboratorista,
            ':id_estudio'     => $id_estudio
        ]);
    }

    /**
     * Actualizar el estado de un estudio (SOLICITADO, REALIZADO, CANCELADO)
     */
    public function cambiarEstado($id_estudio, $estado) {
        $sql = "UPDATE estudio SET estado = :estado WHERE id_estudio = :id_estudio";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado'     => $estado,
            ':id_estudio' => $id_estudio
        ]);
    }

    /**
     * Obtener los estudios solicitados para la bandeja general de laboratorio (Pendientes)
     */
    public function obtenerPendientes() {
        $sql = "SELECT e.id_estudio, e.tipo, e.fecha, e.estado, e.id_consulta,
                       p_pac.cedula AS paciente_cedula, p_pac.nombre AS paciente_nombre, p_pac.apellido AS paciente_apellido,
                       p_med.nombre AS medico_nombre, p_med.apellido AS medico_apellido
                FROM estudio e
                INNER JOIN consulta c ON e.id_consulta = c.id_consulta
                INNER JOIN paciente pac ON c.cedula_paciente = pac.cedula
                INNER JOIN persona p_pac ON pac.cedula = p_pac.cedula
                INNER JOIN medico med ON c.cedula_medico = med.cedula
                INNER JOIN empleado emp_med ON med.cedula = emp_med.cedula
                INNER JOIN persona p_med ON emp_med.cedula = p_med.cedula
                WHERE e.estado = 'SOLICITADO'
                ORDER BY e.fecha ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtener los estudios solicitados en una consulta específica
     */
    public function obtenerPorConsulta($id_consulta) {
        $sql = "SELECT e.id_estudio, e.tipo, e.fecha, e.estado, e.laboratorista,
                       p_lab.nombre AS laboratorista_nombre, p_lab.apellido AS laboratorista_apellido
                FROM estudio e
                LEFT JOIN laboratorista l ON e.laboratorista = l.cedula
                LEFT JOIN empleado emp ON l.cedula = emp.cedula
                LEFT JOIN persona p_lab ON emp.cedula = p_lab.cedula
                WHERE e.id_consulta = :id_consulta
                ORDER BY e.fecha ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_consulta' => $id_consulta]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener el historial completo de exámenes de un paciente
     */
    public function obtenerPorPaciente($cedula_paciente) {
        $sql = "SELECT e.id_estudio, e.tipo, e.fecha, e.estado, e.id_consulta,
                       p_med.nombre AS medico_nombre, p_med.apellido AS medico_apellido,
                       p_lab.nombre AS laboratorista_nombre, p_lab.apellido AS laboratorista_apellido
                FROM estudio e
                INNER JOIN consulta c ON e.id_consulta = c.id_consulta
                INNER JOIN medico med ON c.cedula_medico = med.cedula
                INNER JOIN empleado emp_med ON med.cedula = emp_med.cedula
                INNER JOIN persona p_med ON emp_med.cedula = p_med.cedula
                LEFT JOIN laboratorista l ON e.laboratorista = l.cedula
                LEFT JOIN empleado emp_lab ON l.cedula = emp_lab.cedula
                LEFT JOIN persona p_lab ON emp_lab.cedula = p_lab.cedula
                WHERE c.cedula_paciente = :cedula_paciente
                ORDER BY e.fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_paciente' => $cedula_paciente]);
        return $stmt->fetchAll();
    }
}