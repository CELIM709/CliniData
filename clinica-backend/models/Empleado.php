<?php
require_once __DIR__ . '/../config/Conexion.php';

class Empleado {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    /**
     * Registrar un Empleado completo con su rol específico
     */
    public function registrarEmpleado($datosPersona, $datosEmpleado, $datosRol = []) {
        try {
            $this->db->beginTransaction();

            $cedula = $datosPersona['cedula'];

            // 1. Verificar o Insertar Persona
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

            // 2. Verificar que no sea ya un empleado
            $sqlCheckEmp = "SELECT cedula FROM empleado WHERE cedula = :cedula";
            $stmtEmp = $this->db->prepare($sqlCheckEmp);
            $stmtEmp->execute([':cedula' => $cedula]);
            if ($stmtEmp->fetch()) {
                throw new Exception("La persona con cédula {$cedula} ya está registrada como empleado.");
            }

            // 3. Encriptar contraseña de acceso
            $claveEncriptada = password_hash($datosEmpleado['clave_acceso'], PASSWORD_BCRYPT);

            // 4. Insertar en tabla base Empleado
            $sqlEmpleado = "INSERT INTO empleado (cedula, salario, fecha_contratado, clave_acceso, rol, id_horario)
                            VALUES (:cedula, :salario, :fecha_contratado, :clave_acceso, :rol, :id_horario)";
            
            $stmtInsEmp = $this->db->prepare($sqlEmpleado);
            $stmtInsEmp->execute([
                ':cedula'           => $cedula,
                ':salario'          => $datosEmpleado['salario'],
                ':fecha_contratado' => $datosEmpleado['fecha_contratado'],
                ':clave_acceso'     => $claveEncriptada,
                ':rol'              => $datosEmpleado['rol'],
                ':id_horario'       => $datosEmpleado['id_horario']
            ]);

            // 5. Insertar en la tabla hija correspondiente según el rol
            switch ($datosEmpleado['rol']) {
                case 'ADMIN':
                    $sqlSub = "INSERT INTO administrador (cedula) VALUES (:cedula)";
                    $stmtSub = $this->db->prepare($sqlSub);
                    $stmtSub->execute([':cedula' => $cedula]);
                    break;

                case 'RECEPCIONISTA':
                    $sqlSub = "INSERT INTO recepcionista (cedula, estacion_trabajo, extension_tlf) 
                               VALUES (:cedula, :estacion_trabajo, :extension_tlf)";
                    $stmtSub = $this->db->prepare($sqlSub);
                    $stmtSub->execute([
                        ':cedula'           => $cedula,
                        ':estacion_trabajo' => $datosRol['estacion_trabajo'] ?? null,
                        ':extension_tlf'    => $datosRol['extension_tlf'] ?? null
                    ]);
                    break;

                case 'LABORATORISTA':
                    $sqlSub = "INSERT INTO laboratorista (cedula, carnet_bioanalista, area) 
                               VALUES (:cedula, :carnet_bioanalista, :area)";
                    $stmtSub = $this->db->prepare($sqlSub);
                    $stmtSub->execute([
                        ':cedula'            => $cedula,
                        ':carnet_bioanalista'=> $datosRol['carnet_bioanalista'],
                        ':area'              => $datosRol['area'] ?? null
                    ]);
                    break;

                case 'MEDICO':
                    $sqlSub = "INSERT INTO medico (cedula, carnet_medico, tarifa) 
                               VALUES (:cedula, :carnet_medico, :tarifa)";
                    $stmtSub = $this->db->prepare($sqlSub);
                    $stmtSub->execute([
                        ':cedula'        => $cedula,
                        ':carnet_medico' => $datosRol['carnet_medico'],
                        ':tarifa'        => $datosRol['tarifa']
                    ]);
                    break;

                default:
                    throw new Exception("Rol no válido especificado.");
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Autenticar Empleado (Para Login)
     */
    public function login($cedula, $clave) {
        $sql = "SELECT e.cedula, e.clave_acceso, e.rol, p.nombre, p.apellido, p.email
                FROM empleado e
                INNER JOIN persona p ON e.cedula = p.cedula
                WHERE e.cedula = :cedula";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula' => $cedula]);
        $empleado = $stmt->fetch();

        // Verificar la contraseña ingresada contra el hash guardado en PostgreSQL
        if ($empleado && password_verify($clave, $empleado['clave_acceso'])) {
            unset($empleado['clave_acceso']); // No retornar el hash en la respuesta por seguridad
            return $empleado;
        }

        return false; // Credenciales incorrectas
    }

    /**
     * Obtener lista de empleados con sus horarios
     */
    public function obtenerTodos() {
        $sql = "SELECT p.cedula, p.nombre, p.apellido, p.email, p.telefono,
                       e.salario, e.fecha_contratado, e.rol,
                       h.dias AS horario_dias, h.hora_entrada, h.hora_salida
                FROM empleado e
                INNER JOIN persona p ON e.cedula = p.cedula
                INNER JOIN horario h ON e.id_horario = h.id_horario";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}