<?php
require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/Persona.php';

class Paciente {
    private $db;
    private $personaModel;

    public function __construct() {
        $this->db = Conexion::conectar();
        $this->personaModel = new Persona();
    }

    // En models/Paciente.php

    public function registrarPaciente($datosPersona, $datosPaciente, $datosHistoria = []) {
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

            // 2. Verificar que no sea Paciente
            $sqlCheckPaciente = "SELECT cedula FROM paciente WHERE cedula = :cedula";
            $stmtPaciente = $this->db->prepare($sqlCheckPaciente);
            $stmtPaciente->execute([':cedula' => $cedula]);

            if ($stmtPaciente->fetch()) {
                throw new Exception("La persona con cédula {$cedula} ya está registrada como paciente.");
            }

            // 3. Insertar Paciente
            $sqlPaciente = "INSERT INTO paciente (cedula, genero, tipo_sangre)
                            VALUES (:cedula, :genero, :tipo_sangre)";
            
            $stmtInsPaciente = $this->db->prepare($sqlPaciente);
            $stmtInsPaciente->execute([
                ':cedula'      => $cedula,
                ':genero'      => $datosPaciente['genero'],
                ':tipo_sangre' => $datosPaciente['tipo_sangre']
            ]);

            // 4. APERTURA AUTOMÁTICA DE HISTORIA MÉDICA
            $sqlHistoria = "INSERT INTO historia_medica (cedula_paciente, antecedentes, alergias, medicacion_habitual)
                            VALUES (:cedula, :antecedentes, :alergias, :medicacion_habitual)";

            $stmtInsHistoria = $this->db->prepare($sqlHistoria);
            $stmtInsHistoria->execute([
                ':cedula'              => $cedula,
                ':antecedentes'        => $datosHistoria['antecedentes'] ?? 'Ninguno reportado',
                ':alergias'            => $datosHistoria['alergias'] ?? 'Ninguna conocida',
                ':medicacion_habitual' => $datosHistoria['medicacion_habitual'] ?? 'Ninguna'
            ]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function obtenerTodos() {
        $sql = "SELECT p.cedula, p.nombre, p.apellido, p.fecha_nacimiento, p.telefono, p.email, p.direccion,
                       pac.genero, pac.tipo_sangre
                FROM paciente pac
                INNER JOIN persona p ON pac.cedula = p.cedula";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerPorCedula($cedula) {
        $sql = "SELECT p.cedula, p.nombre, p.apellido, p.fecha_nacimiento, p.telefono, p.email, p.direccion,
                    pac.genero, pac.tipo_sangre,
                    hm.antecedentes, hm.alergias, hm.medicacion_habitual
                FROM paciente pac
                INNER JOIN persona p ON pac.cedula = p.cedula
                LEFT JOIN historia_medica hm ON pac.cedula = hm.cedula_paciente
                WHERE pac.cedula = :cedula";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula' => $cedula]);
        
        return $stmt->fetch();
    }

    public function actualizarPaciente($cedula, $datosPersona = [], $datosPaciente = []) {
        try {
            $this->db->beginTransaction();

            // Si se enviaron datos personales (nombre, teléfono, email, etc.)
            if (!empty($datosPersona)) {
                $this->personaModel->actualizarParcial($cedula, $datosPersona);
            }

            // Si se enviaron datos específicos de paciente (género, tipo_sangre)
            if (!empty($datosPaciente)) {
                $this->actualizarParcial($cedula, $datosPaciente);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizarParcial($cedula, $datosPaciente) {
        $campos = [];
        $valores = [':cedula' => $cedula];

        // Mapea solo las claves pasadas en el arreglo
        foreach ($datosPaciente as $columna => $valor) {
            $campos[] = "{$columna} = :{$columna}";
            $valores[":{$columna}"] = $valor;
        }

        if (empty($campos)) {
            return false; // No se enviaron campos para actualizar
        }

        $sql = "UPDATE paciente SET " . implode(', ', $campos) . " WHERE cedula = :cedula";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($valores);
    }

    // Remueve únicamente el rol de paciente, conservando el registro en 'persona'
    public function eliminarRolPaciente($cedula) {
        $sql = "DELETE FROM paciente WHERE cedula = :cedula";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':cedula' => $cedula]);
    }
}