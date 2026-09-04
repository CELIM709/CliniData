<?php
require_once __DIR__ . '/../config/Conexion.php';

class Consulta {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    /**
     * Registrar una consulta médica con cálculo automático de tarifa y búsqueda de cita
     */
    public function registrarConsulta($datos) {
        try {
            $this->db->beginTransaction();

            $cedulaMedico   = $datos['cedula_medico'];
            $cedulaPaciente = $datos['cedula_paciente'];

            // 1. COSTO AUTOMÁTICO: Si no se pasa un costo explícito, obtener la tarifa del médico
            $costo = $datos['costo'] ?? null;
            if ($costo === null) {
                $sqlTarifa = "SELECT tarifa FROM medico WHERE cedula = :cedula_medico";
                $stmtTarifa = $this->db->prepare($sqlTarifa);
                $stmtTarifa->execute([':cedula_medico' => $cedulaMedico]);
                $costo = $stmtTarifa->fetchColumn();

                if ($costo === false) {
                    throw new Exception("El médico con cédula {$cedulaMedico} no existe.");
                }
            }

            // 2. CITA AUTOMÁTICA: Si no se especifica 'id_cita', buscar la cita más próxima del paciente con el médico
            $idCita = $datos['id_cita'] ?? null;
            if ($idCita === null) {
                $sqlBuscarCita = "SELECT id_cita 
                                  FROM cita 
                                  WHERE cedula_paciente = :cedula_paciente 
                                    AND cedula_medico = :cedula_medico 
                                    AND estado IN ('PENDIENTE', 'CONFIRMADA')
                                  ORDER BY lower(rango_cita) ASC 
                                  LIMIT 1";
                
                $stmtBuscarCita = $this->db->prepare($sqlBuscarCita);
                $stmtBuscarCita->execute([
                    ':cedula_paciente' => $cedulaPaciente,
                    ':cedula_medico'   => $cedulaMedico
                ]);
                
                $idCita = $stmtBuscarCita->fetchColumn() ?: null;
            }

            // 3. Registrar la consulta
            $sql = "INSERT INTO consulta (diagnostico, observaciones, costo, cedula_paciente, cedula_medico, id_cita)
                    VALUES (:diagnostico, :observaciones, :costo, :cedula_paciente, :cedula_medico, :id_cita)
                    RETURNING id_consulta";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':diagnostico'      => $datos['diagnostico'],
                ':observaciones'    => $datos['observaciones'] ?? null,
                ':costo'            => $costo,
                ':cedula_paciente'  => $cedulaPaciente,
                ':cedula_medico'    => $cedulaMedico,
                ':id_cita'          => $idCita
            ]);

            $idConsulta = $stmt->fetchColumn();

            // 4. Si hay una cita asociada (manual o detectada automáticamente), confirmarla
            if ($idCita !== null) {
                $sqlCita = "UPDATE cita SET estado = 'CONFIRMADA' WHERE id_cita = :id_cita";
                $stmtCita = $this->db->prepare($sqlCita);
                $stmtCita->execute([':id_cita' => $idCita]);
            }

            $this->db->commit();
            return $idConsulta;

        } catch (PDOException $e) {
            $this->db->rollBack();
            if ($e->getCode() === '23505') {
                throw new Exception("La cita especificada ya tiene una consulta médica asociada.");
            }
            throw $e;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtener el historial completo de consultas de un paciente
     */
    public function obtenerPorPaciente($cedula_paciente) {
        $sql = "SELECT c.id_consulta, c.fecha, c.diagnostico, c.observaciones, c.costo, c.id_cita,
                       p_med.nombre AS medico_nombre, 
                       p_med.apellido AS medico_apellido,
                       m.carnet_medico
                FROM consulta c
                INNER JOIN medico m ON c.cedula_medico = m.cedula
                INNER JOIN empleado e ON m.cedula = e.cedula
                INNER JOIN persona p_med ON e.cedula = p_med.cedula
                WHERE c.cedula_paciente = :cedula_paciente
                ORDER BY c.fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_paciente' => $cedula_paciente]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener el detalle de una consulta por su ID
     */
    public function obtenerPorId($id_consulta) {
        $sql = "SELECT c.id_consulta, c.fecha, c.diagnostico, c.observaciones, c.costo, c.id_cita,
                       c.cedula_paciente, c.cedula_medico,
                       p_pac.nombre AS paciente_nombre, p_pac.apellido AS paciente_apellido,
                       p_med.nombre AS medico_nombre, p_med.apellido AS medico_apellido
                FROM consulta c
                INNER JOIN paciente pac ON c.cedula_paciente = pac.cedula
                INNER JOIN persona p_pac ON pac.cedula = p_pac.cedula
                INNER JOIN medico m ON c.cedula_medico = m.cedula
                INNER JOIN empleado e ON m.cedula = e.cedula
                INNER JOIN persona p_med ON e.cedula = p_med.cedula
                WHERE c.id_consulta = :id_consulta";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_consulta' => $id_consulta]);
        return $stmt->fetch();
    }

    /**
     * Obtener el historial completo de consultas atendidas por un médico
     */
    public function obtenerPorMedico($cedula_medico) {
        $sql = "SELECT c.id_consulta, c.fecha, c.diagnostico, c.observaciones, c.costo, c.id_cita,
                       p_pac.cedula AS paciente_cedula,
                       p_pac.nombre AS paciente_nombre, 
                       p_pac.apellido AS paciente_apellido,
                       p_pac.telefono AS paciente_telefono
                FROM consulta c
                INNER JOIN paciente pac ON c.cedula_paciente = pac.cedula
                INNER JOIN persona p_pac ON pac.cedula = p_pac.cedula
                WHERE c.cedula_medico = :cedula_medico
                ORDER BY c.fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_medico' => $cedula_medico]);
        return $stmt->fetchAll();
    }
}