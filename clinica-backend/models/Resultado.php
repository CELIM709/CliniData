<?php
require_once __DIR__ . '/../config/Conexion.php';

class Resultado {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    /**
     * Registrar un nuevo resultado/archivo para un estudio
     */
    public function registrarResultado($id_estudio, $descripcion, $ruta_archivo) {
        try {
            $this->db->beginTransaction();

            $stmtEstado = $this->db->prepare("SELECT estado FROM estudio WHERE id_estudio = :id_estudio FOR UPDATE");
            $stmtEstado->execute([':id_estudio' => $id_estudio]);
            if ($stmtEstado->fetchColumn() !== 'PENDIENTE') {
                throw new Exception('Solo se puede cargar un resultado para un estudio pendiente.');
            }

            $sql = "INSERT INTO resultado (descripcion, ruta_archivo, id_estudio)
                    VALUES (:descripcion, :ruta_archivo, :id_estudio)
                    RETURNING id_resultado";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':descripcion'  => $descripcion,
                ':ruta_archivo' => $ruta_archivo,
                ':id_estudio'   => $id_estudio
            ]);
            $idResultado = $stmt->fetchColumn();

            $stmtEstado = $this->db->prepare("UPDATE estudio SET estado = 'REALIZADO' WHERE id_estudio = :id_estudio");
            $stmtEstado->execute([':id_estudio' => $id_estudio]);
            $this->db->commit();
            return $idResultado;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtener todos los resultados/archivos asociados a un estudio específico
     */
    public function obtenerPorEstudio($id_estudio) {
        $sql = "SELECT id_resultado, descripcion, ruta_archivo, fecha, id_estudio
                FROM resultado
                WHERE id_estudio = :id_estudio
                ORDER BY fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_estudio' => $id_estudio]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener todos los resultados de TODOS los estudios de una misma consulta médica
     */
    public function obtenerPorConsulta($id_consulta) {
        $sql = "SELECT r.id_resultado, r.descripcion, r.ruta_archivo, r.fecha,
                       e.id_estudio, te.nombre_estudio AS tipo_estudio, e.estado AS estado_estudio
                FROM resultado r
                INNER JOIN estudio e ON r.id_estudio = e.id_estudio
                INNER JOIN tipo_estudio te ON e.id_tipo_estudio = te.id_tipo_estudio
                WHERE e.id_consulta = :id_consulta
                ORDER BY r.fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_consulta' => $id_consulta]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener todos los resultados de los exámenes de un paciente por su cédula
     */
    public function obtenerPorPaciente($cedula_paciente) {
        $sql = "SELECT r.id_resultado, r.descripcion, r.ruta_archivo, r.fecha,
                       e.id_estudio, te.nombre_estudio AS tipo_estudio, e.estado AS estado_estudio,
                       c.id_consulta
                FROM resultado r
                INNER JOIN estudio e ON r.id_estudio = e.id_estudio
                INNER JOIN tipo_estudio te ON e.id_tipo_estudio = te.id_tipo_estudio
                INNER JOIN consulta c ON e.id_consulta = c.id_consulta
                WHERE c.cedula_paciente = :cedula_paciente
                ORDER BY r.fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula_paciente' => $cedula_paciente]);
        return $stmt->fetchAll();
    }

    /**
     * Eliminar un resultado y su registro
     */
    public function eliminar($id_resultado) {
        $sql = "DELETE FROM resultado WHERE id_resultado = :id_resultado RETURNING ruta_archivo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_resultado' => $id_resultado]);
        return $stmt->fetchColumn(); // Retorna la ruta para eliminar el archivo del disco si es necesario
    }
}