<?php
require_once __DIR__ . '/../config/Conexion.php';

class Receta {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    /**
     * Registrar múltiples medicamentos en lote para una sola consulta
     * 
     * @param int $id_consulta
     * @param array $medicamentos Lista de arreglos con keys: id_medicamento, dosis, frecuencia, duracion, indicaciones
     */
    public function registrarReceta($id_consulta, array $medicamentos) {
        if (empty($medicamentos)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO receta (id_consulta, id_medicamento, dosis, frecuencia, duracion, indicaciones)
                    VALUES (:id_consulta, :id_medicamento, :dosis, :frecuencia, :duracion, :indicaciones)";
            
            $stmt = $this->db->prepare($sql);

            foreach ($medicamentos as $item) {
                $stmt->execute([
                    ':id_consulta'    => $id_consulta,
                    ':id_medicamento' => $item['id_medicamento'],
                    ':dosis'          => $item['dosis'],          // Ej: "500mg"
                    ':frecuencia'     => $item['frecuencia'],     // Ej: "Cada 8 horas"
                    ':duracion'       => $item['duracion'],       // Ej: "7 días"
                    ':indicaciones'   => $item['indicaciones'] ?? null // Ej: "Tomar después de comer"
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
     * Obtener el detalle de la receta prescrita en una consulta específica
     */
    public function obtenerPorConsulta($id_consulta) {
        $sql = "SELECT r.id_receta, r.dosis, r.frecuencia, r.duracion, r.indicaciones,
                       m.id_medicamento, m.nombre AS medicamento, m.presentacion
                FROM receta r
                INNER JOIN medicamento m ON r.id_medicamento = m.id_medicamento
                WHERE r.id_consulta = :id_consulta
                ORDER BY r.id_receta ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_consulta' => $id_consulta]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener el historial completo de recetas prescritas a un paciente por su cédula
     */
    public function obtenerPorPaciente($cedula_paciente) {
        $sql = "SELECT r.id_receta, r.dosis, r.frecuencia, r.duracion, r.indicaciones,
                       c.id_consulta, c.fecha AS fecha_consulta,
                       m.id_medicamento, m.nombre AS medicamento, m.presentacion,
                       p_med.nombre AS medico_nombre, p_med.apellido AS medico_apellido
                FROM receta r
                INNER JOIN medicamento m ON r.id_medicamento = m.id_medicamento
                INNER JOIN consulta c ON r.id_consulta = c.id_consulta
                INNER JOIN medico med ON c.cedula_medico = med.cedula
                INNER JOIN empleado e ON med.cedula = e.cedula
                INNER JOIN persona p_med ON e.cedula = p_med.cedula
                WHERE c.cedula_paciente = :cedula_paciente
                ORDER BY c.fecha DESC, r.id_receta ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_paciente' => $cedula_paciente]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener el historial de recetas emitidas por un médico según su cédula
     */
    public function obtenerPorMedico($cedula_medico) {
        $sql = "SELECT r.id_receta, r.dosis, r.frecuencia, r.duracion, r.indicaciones,
                       c.id_consulta, c.fecha AS fecha_consulta,
                       m.id_medicamento, m.nombre AS medicamento, m.presentacion,
                       p_pac.nombre AS paciente_nombre, p_pac.apellido AS paciente_apellido, p_pac.cedula AS paciente_cedula
                FROM receta r
                INNER JOIN medicamento m ON r.id_medicamento = m.id_medicamento
                INNER JOIN consulta c ON r.id_consulta = c.id_consulta
                INNER JOIN paciente pac ON c.cedula_paciente = pac.cedula
                INNER JOIN persona p_pac ON pac.cedula = p_pac.cedula
                WHERE c.cedula_medico = :cedula_medico
                ORDER BY c.fecha DESC, r.id_receta ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_medico' => $cedula_medico]);
        return $stmt->fetchAll();
    }

    /**
     * Eliminar un medicamento específico de una receta
     */
    public function eliminarItem($id_receta) {
        $sql = "DELETE FROM receta WHERE id_receta = :id_receta";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_receta' => $id_receta]);
    }

    /**
     * Obtener el récipe completo listo para impresión (Médico, Paciente, Diagnóstico y Medicamentos)
     */
    public function obtenerRecipeCompleto($id_consulta) {
        $sql = "SELECT c.id_consulta, c.fecha, c.diagnostico,
                    p_pac.nombre AS paciente_nombre, p_pac.apellido AS paciente_apellido, p_pac.cedula AS paciente_cedula,
                    p_med.nombre AS medico_nombre, p_med.apellido AS medico_apellido, med.carnet_medico,
                    r.dosis, r.frecuencia, r.duracion, r.indicaciones,
                    m.nombre AS medicamento, m.presentacion
                FROM receta r
                INNER JOIN medicamento m ON r.id_medicamento = m.id_medicamento
                INNER JOIN consulta c ON r.id_consulta = c.id_consulta
                INNER JOIN paciente pac ON c.cedula_paciente = pac.cedula
                INNER JOIN persona p_pac ON pac.cedula = p_pac.cedula
                INNER JOIN medico med ON c.cedula_medico = med.cedula
                INNER JOIN empleado e ON med.cedula = e.cedula
                INNER JOIN persona p_med ON e.cedula = p_med.cedula
                WHERE r.id_consulta = :id_consulta";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_consulta' => $id_consulta]);
        return $stmt->fetchAll();
    }
}