<?php
require_once __DIR__ . '/../config/Conexion.php';

class Cita {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    /**
     * Agendar una cita utilizando tsrange nativo de PostgreSQL
     */
    public function agendarCita($datos) {
        try {
            // tsrange construye el rango [inicio, fin)
            $sql = "INSERT INTO cita (rango_cita, consultorio, cedula_medico, cedula_paciente, estado)
                    VALUES (
                        tsrange(:fecha_inicio::timestamp, :fecha_fin::timestamp, '[)'), 
                        :consultorio, 
                        :cedula_medico, 
                        :cedula_paciente, 
                        :estado
                    )";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':fecha_inicio'    => $datos['fecha_inicio'], // Formato: 'YYYY-MM-DD HH:MM:SS'
                ':fecha_fin'       => $datos['fecha_fin'],    // Formato: 'YYYY-MM-DD HH:MM:SS'
                ':consultorio'     => $datos['consultorio'],
                ':cedula_medico'   => $datos['cedula_medico'],
                ':cedula_paciente' => $datos['cedula_paciente'],
                ':estado'          => $datos['estado'] ?? 'PENDIENTE'
            ]);

        } catch (PDOException $e) {
            // Código 23P01 = exclusion_violation en PostgreSQL (solapamiento detectado por la restricción EXCLUDE)
            if ($e->getCode() === '23P01') {
                throw new Exception("Conflicto de agenda: El médico o el paciente ya tienen una cita programada dentro de ese rango de tiempo.");
            }
            throw $e;
        }
    }

    /**
     * Obtener citas de un médico extrayendo inicio y fin del tsrange
     */
    public function obtenerPorMedico($cedula_medico) {
        $sql = "SELECT c.id_cita,
                       lower(c.rango_cita) AS fecha_inicio,
                       upper(c.rango_cita) AS fecha_fin,
                       c.consultorio, 
                       c.estado,
                       p.nombre AS paciente_nombre, 
                       p.apellido AS paciente_apellido, 
                       p.telefono AS paciente_telefono
                FROM cita c
                INNER JOIN paciente pac ON c.cedula_paciente = pac.cedula
                INNER JOIN persona p ON pac.cedula = p.cedula
                WHERE c.cedula_medico = :cedula_medico
                ORDER BY fecha_inicio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_medico' => $cedula_medico]);
        return $stmt->fetchAll();
    }

    /**
     * Cambiar estado de la cita ('CONFIRMADA', 'CANCELADA', etc.)
     */
    public function cambiarEstado($id_cita, $nuevo_estado) {
        $sql = "UPDATE cita SET estado = :estado WHERE id_cita = :id_cita";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado'  => $nuevo_estado,
            ':id_cita' => $id_cita
        ]);
    }

    /**
     * Reprogramar el rango de fecha/hora de una cita existente
     */
    public function reprogramarCita($id_cita, $nueva_fecha_inicio, $nueva_fecha_fin) {
        try {
            $sql = "UPDATE cita 
                    SET rango_cita = tsrange(:fecha_inicio::timestamp, :fecha_fin::timestamp, '[)')
                    WHERE id_cita = :id_cita";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':fecha_inicio' => $nueva_fecha_inicio,
                ':fecha_fin'    => $nueva_fecha_fin,
                ':id_cita'      => $id_cita
            ]);

        } catch (PDOException $e) {
            // Manejo del choque de horarios al reprogramar
            if ($e->getCode() === '23P01') {
                throw new Exception("Conflicto de agenda: No se puede mover la cita porque existe un solapamiento en el nuevo horario.");
            }
            throw $e;
        }
    }

    /**
     * Obtener el historial de citas de un paciente
     */
    public function obtenerPorPaciente($cedula_paciente) {
        $sql = "SELECT c.id_cita,
                       lower(c.rango_cita) AS fecha_inicio,
                       upper(c.rango_cita) AS fecha_fin,
                       c.consultorio, 
                       c.estado,
                       p.nombre AS medico_nombre, 
                       p.apellido AS medico_apellido,
                       p.telefono AS medico_telefono
                FROM cita c
                INNER JOIN medico m ON c.cedula_medico = m.cedula
                INNER JOIN empleado e ON m.cedula = e.cedula
                INNER JOIN persona p ON e.cedula = p.cedula
                WHERE c.cedula_paciente = :cedula_paciente
                ORDER BY fecha_inicio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_paciente' => $cedula_paciente]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener solo las citas PENDIENTES de un médico
     */
    public function obtenerPendientesPorMedico($cedula_medico) {
        $sql = "SELECT c.id_cita,
                       lower(c.rango_cita) AS fecha_inicio,
                       upper(c.rango_cita) AS fecha_fin,
                       c.consultorio, 
                       c.estado,
                       p.nombre AS paciente_nombre, 
                       p.apellido AS paciente_apellido, 
                       p.telefono AS paciente_telefono
                FROM cita c
                INNER JOIN paciente pac ON c.cedula_paciente = pac.cedula
                INNER JOIN persona p ON pac.cedula = p.cedula
                WHERE c.cedula_medico = :cedula_medico
                  AND c.estado = 'PENDIENTE'
                ORDER BY fecha_inicio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_medico' => $cedula_medico]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener solo las citas PENDIENTES de un paciente
     */
    public function obtenerPendientesPorPaciente($cedula_paciente) {
        $sql = "SELECT c.id_cita,
                       lower(c.rango_cita) AS fecha_inicio,
                       upper(c.rango_cita) AS fecha_fin,
                       c.consultorio, 
                       c.estado,
                       p.nombre AS medico_nombre, 
                       p.apellido AS medico_apellido,
                       p.telefono AS medico_telefono
                FROM cita c
                INNER JOIN medico m ON c.cedula_medico = m.cedula
                INNER JOIN empleado e ON m.cedula = e.cedula
                INNER JOIN persona p ON e.cedula = p.cedula
                WHERE c.cedula_paciente = :cedula_paciente
                  AND c.estado = 'PENDIENTE'
                ORDER BY fecha_inicio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_paciente' => $cedula_paciente]);
        return $stmt->fetchAll();
    }
}