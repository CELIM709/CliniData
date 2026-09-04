<?php
require_once __DIR__ . '/../config/Conexion.php';

class Medico {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    /**
     * Registrar un Médico completo en Persona -> Empleado -> Medico + Especialidades
     */
    public function registrarMedico($datosPersona, $datosEmpleado, $datosMedico, $idsEspecialidades = []) {
        try {
            $this->db->beginTransaction();

            $cedula = $datosPersona['cedula'];

            // 1. Verificar o Insertar en 'persona'
            $sqlCheckPersona = "SELECT cedula FROM persona WHERE cedula = :cedula";
            $stmtPersona = $this->db->prepare($sqlCheckPersona);
            $stmtPersona->execute([':cedula' => $cedula]);

            if (!$stmtPersona->fetch()) {
                $sqlPersona = "INSERT INTO persona (cedula, nombre, apellido, fecha_nacimiento, telefono, email, direccion)
                               VALUES (:cedula, :nombre, :apellido, :fecha_nacimiento, :telefono, :email, :direccion)";
                $stmtInsPersona = $this->db->prepare($sqlPersona);
                $stmtInsPersona->execute([
                    ':cedula'           => $datosPersona['cedula'],
                    ':nombre'           => $datosPersona['nombre'],
                    ':apellido'         => $datosPersona['apellido'],
                    ':fecha_nacimiento' => $datosPersona['fecha_nacimiento'],
                    ':telefono'         => $datosPersona['telefono'] ?? null,
                    ':email'            => $datosPersona['email'] ?? null,
                    ':direccion'        => $datosPersona['direccion'] ?? null
                ]);
            }

            // 2. Verificar que no sea Empleado
            $sqlCheckEmp = "SELECT cedula FROM empleado WHERE cedula = :cedula";
            $stmtEmp = $this->db->prepare($sqlCheckEmp);
            $stmtEmp->execute([':cedula' => $cedula]);
            if ($stmtEmp->fetch()) {
                throw new Exception("La persona con cédula {$cedula} ya está registrada como empleado.");
            }

            // 3. Encriptar contraseña e Insertar en 'empleado' (Rol forzado a MEDICO)
            $claveEncriptada = password_hash($datosEmpleado['clave_acceso'], PASSWORD_BCRYPT);
            $sqlEmpleado = "INSERT INTO empleado (cedula, salario, fecha_contratado, clave_acceso, rol, id_horario)
                            VALUES (:cedula, :salario, :fecha_contratado, :clave_acceso, 'MEDICO', :id_horario)";
            
            $stmtInsEmp = $this->db->prepare($sqlEmpleado);
            $stmtInsEmp->execute([
                ':cedula'           => $cedula,
                ':salario'          => $datosEmpleado['salario'],
                ':fecha_contratado' => $datosEmpleado['fecha_contratado'],
                ':clave_acceso'     => $claveEncriptada,
                ':id_horario'       => $datosEmpleado['id_horario']
            ]);

            // 4. Insertar en la tabla 'medico'
            $sqlMedico = "INSERT INTO medico (cedula, carnet_medico, tarifa)
                          VALUES (:cedula, :carnet_medico, :tarifa)";
            $stmtInsMed = $this->db->prepare($sqlMedico);
            $stmtInsMed->execute([
                ':cedula'        => $cedula,
                ':carnet_medico' => $datosMedico['carnet_medico'],
                ':tarifa'        => $datosMedico['tarifa']
            ]);

            // 5. Vincular especialidades en 'medico_especialidad' (Tabla M:N)
            if (!empty($idsEspecialidades)) {
                $sqlEsp = "INSERT INTO medico_especialidad (cedula_medico, id_especialidad) VALUES (:cedula, :id_especialidad)";
                $stmtInsEsp = $this->db->prepare($sqlEsp);

                foreach ($idsEspecialidades as $idEspecialidad) {
                    $stmtInsEsp->execute([
                        ':cedula'          => $cedula,
                        ':id_especialidad' => $idEspecialidad
                    ]);
                }
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtener lista de todos los médicos con sus datos, horario y especialidades agrupadas
     */
    public function obtenerTodos() {
        // PostgreSQL: STRING_AGG junta todas las especialidades en una sola cadena de texto separada por comas
        $sql = "SELECT p.cedula, p.nombre, p.apellido, p.telefono, p.email,
                       e.salario, e.fecha_contratado,
                       m.carnet_medico, m.tarifa,
                       h.dias AS horario_dias, h.hora_entrada, h.hora_salida,
                       STRING_AGG(esp.nombre, ', ') AS especialidades
                FROM medico m
                INNER JOIN empleado e ON m.cedula = e.cedula
                INNER JOIN persona p ON e.cedula = p.cedula
                INNER JOIN horario h ON e.id_horario = h.id_horario
                LEFT JOIN medico_especialidad me ON m.cedula = me.cedula_medico
                LEFT JOIN especialidad esp ON me.id_especialidad = esp.id_especialidad
                GROUP BY p.cedula, p.nombre, p.apellido, p.telefono, p.email,
                         e.salario, e.fecha_contratado, m.carnet_medico, m.tarifa,
                         h.dias, h.hora_entrada, h.hora_salida";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener un único médico por su cédula
     */
    public function obtenerPorCedula($cedula) {
        $sql = "SELECT p.cedula, p.nombre, p.apellido, p.telefono, p.email, p.direccion,
                       e.salario, e.fecha_contratado,
                       m.carnet_medico, m.tarifa,
                       h.dias AS horario_dias, h.hora_entrada, h.hora_salida,
                       STRING_AGG(esp.nombre, ', ') AS especialidades
                FROM medico m
                INNER JOIN empleado e ON m.cedula = e.cedula
                INNER JOIN persona p ON e.cedula = p.cedula
                INNER JOIN horario h ON e.id_horario = h.id_horario
                LEFT JOIN medico_especialidad me ON m.cedula = me.cedula_medico
                LEFT JOIN especialidad esp ON me.id_especialidad = esp.id_especialidad
                WHERE m.cedula = :cedula
                GROUP BY p.cedula, p.nombre, p.apellido, p.telefono, p.email, p.direccion,
                         e.salario, e.fecha_contratado, m.carnet_medico, m.tarifa,
                         h.dias, h.hora_entrada, h.hora_salida";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula' => $cedula]);
        return $stmt->fetch();
    }

    /**
     * Actualizar tarifa o datos específicos del médico
     */
    public function actualizarParcial($cedula, $datosMedico) {
        $campos = [];
        $valores = [':cedula' => $cedula];

        foreach ($datosMedico as $columna => $valor) {
            $campos[] = "{$columna} = :{$columna}";
            $valores[":{$columna}"] = $valor;
        }

        if (empty($campos)) {
            return false;
        }

        $sql = "UPDATE medico SET " . implode(', ', $campos) . " WHERE cedula = :cedula";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($valores);
    }
}