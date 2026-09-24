-- =============================================================================
-- SEED DATA - SISTEMA DE GESTIÓN DE CITAS Y CONSULTAS MÉDICAS
-- =============================================================================

BEGIN;

-- -----------------------------------------------------------------------------
-- 0. LIMPIEZA DE TABLAS Y REINICIO DE IDENTIDADES (IDs)
-- -----------------------------------------------------------------------------

TRUNCATE TABLE 
    receta,
    medicamento,
    resultado,
    estudio,
    tipo_estudio,
    consulta,
    cita,
    historia_medica,
    medico_especialidad,
    especialidad,
    medico,
    laboratorista,
    recepcionista,
    administrador,
    empleado,
    horario,
    paciente,
    persona
RESTART IDENTITY CASCADE;

-- -----------------------------------------------------------------------------
-- 1. CATÁLOGOS BASE (Horarios, Especialidades, Tipo Estudio, Medicamentos)
-- -----------------------------------------------------------------------------

-- Horarios (ID 1 al 4)
INSERT INTO horario (dias, hora_entrada, hora_salida) VALUES
('Lunes a Viernes', '08:00:00', '16:00:00'),
('Lunes a Viernes', '12:00:00', '20:00:00'),
('Sabados y Domingos', '08:00:00', '14:00:00'),
('Lunes a Sabado', '07:00:00', '13:00:00');

-- Especialidades (ID 1 al 7)
INSERT INTO especialidad (nombre, descripcion) VALUES
('Medicina General', 'Atención médica primaria e integral del paciente.'),
('Pediatría', 'Atención médica de bebés, niños y adolescentes.'),
('Cardiología', 'Diagnóstico y tratamiento de enfermedades del corazón y sistema circulatorio.'),
('Traumatología', 'Tratamiento de lesiones traumáticas en huesos y articulaciones.'),
('Ginecología y Obstetricia', 'Salud del sistema reproductor femenino y atención del embarazo.'),
('Dermatología', 'Diagnóstico y tratamiento de enfermedades de la piel.'),
('Neurología', 'Tratamiento de trastornos del sistema nervioso central y periférico.');

-- Catálogo de Exámenes de Laboratorio (ID 1 al 6)
INSERT INTO tipo_estudio (nombre_estudio, descripcion) VALUES
('Perfil 20 (Hematología Completa)', 'Análisis de sangre integral para conteo globular y química sanguínea.'),
('Examen General de Orina (EGO)', 'Evaluación física, química y microscópica de la orina.'),
('Coproanálisis', 'Estudio físico y parasitológico de muestras fecales.'),
('Perfil Lipídico', 'Medición de colesterol total, HDL, LDL y triglicéridos.'),
('Glicemia en Ayunas', 'Medición de niveles de glucosa en sangre.'),
('Rayos X de Tórax', 'Estudio de radiodiagnóstico para evaluación pulmonar y cardíaca.');

-- Catálogo Amplio de Medicamentos (ID 1 al 9)
INSERT INTO medicamento (nombre, laboratorio, presentacion) VALUES
('Amoxicilina 500mg', 'Laboratorios Leti', 'Cápsulas - Caja x 12'),
('Ibuprofeno 400mg', 'Calox International', 'Tabletas - Caja x 20'),
('Paracetamol 500mg', 'Genfar', 'Tabletas - Caja x 10'),
('Omeprazol 20mg', 'Meyer', 'Cápsulas - Caja x 14'),
('Losartán Potásico 50mg', 'Nolver', 'Comprimidos - Caja x 30'),
('Metformina 850mg', 'Vargas', 'Tabletas - Caja x 30'),
('Loratadina 10mg', 'Genfar', 'Tabletas - Caja x 10'),
('Azitromicina 500mg', 'Laboratorios Leti', 'Tabletas - Caja x 3'),
('Diclofenac Sódico 50mg', 'Calox International', 'Tabletas - Caja x 20');

-- -----------------------------------------------------------------------------
-- 2. PERSONAS (Usuarios del Sistema y Pacientes)
-- -----------------------------------------------------------------------------

INSERT INTO persona (cedula, nombre, apellido, fecha_nacimiento, telefono, email, direccion) VALUES
-- Empleados Específicos de Prueba
('V-00000000', 'Carlos', 'Administrador', '1985-05-15', '0414-0000000', 'admin@clinica.com', 'Av. Principal Central, Edif. Admin'),
('V-11111111', 'Roberto', 'Gómez', '1978-10-20', '0412-1111111', 'medico.gomez@clinica.com', 'Urb. Los Olivos, Calle 3'),
('V-22222222', 'Ana', 'López', '1992-03-12', '0416-2222222', 'recepcion@clinica.com', 'Av. Las Américas, Res. La Floresta'),
('V-33333333', 'Luis', 'Martínez', '1988-08-25', '0424-3333333', 'lab.martinez@clinica.com', 'Sector Centro, Calle Bolívar'),

-- Médicos Adicionales por Especialidad
('V-44444444', 'Elena', 'Rostova', '1982-12-05', '0414-4444444', 'dra.rostova@clinica.com', 'Urb. Altamira, Av. San Juan'),
('V-55555555', 'Andrés', 'Mendoza', '1980-04-18', '0412-5555555', 'dr.mendoza@clinica.com', 'Urb. Terrazas del Caroní'),
('V-66666666', 'Sofia', 'Benítez', '1986-09-30', '0416-6666666', 'dra.benitez@clinica.com', 'Res. Las Villas, Apto 4B'),
('V-77777777', 'Ricardo', 'Silva', '1975-11-12', '0424-7777777', 'dr.silva@clinica.com', 'Av. Atlántico, Quinta San José'),

-- Pacientes Específicos de Prueba
('V-00000001', 'Juan', 'Pérez', '2000-01-01', '0412-9990001', 'juan.perez@test.com', 'Urb. Villa Rosa, Casa #10'),
('V-00000002', 'María', 'Rodríguez', '1995-05-05', '0414-9990002', 'maria.rodriguez@test.com', 'Sector Altavista, Torre B');

-- -----------------------------------------------------------------------------
-- 3. PACIENTES E HISTORIAS MÉDICAS
-- -----------------------------------------------------------------------------

INSERT INTO paciente (cedula, genero, tipo_sangre) VALUES
('V-00000001', 'M', 'O+'),
('V-00000002', 'F', 'A+');

INSERT INTO historia_medica (cedula_paciente, antecedentes, alergias, medicacion_habitual) VALUES
('V-00000001', 'Hipertensión arterial controlada.', 'Penicilina', 'Losartán 50mg diario'),
('V-00000002', 'Sin antecedentes de importancia.', 'Ninguna conocida', 'Ninguna');

-- -----------------------------------------------------------------------------
-- 4. EMPLEADOS Y ROLES (Todos con el Hash Requerido)
-- Hash: $2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy
-- -----------------------------------------------------------------------------

INSERT INTO empleado (cedula, salario, fecha_contratado, clave_acceso, rol, id_horario) VALUES
('V-00000000', 1800.00, '2020-01-15', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'ADMIN', 1),
('V-11111111', 2500.00, '2021-03-01', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'MEDICO', 1),
('V-22222222', 850.00,  '2022-06-10', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'RECEPCIONISTA', 1),
('V-33333333', 1100.00, '2021-09-01', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'LABORATORISTA', 2),
('V-44444444', 2700.00, '2020-11-15', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'MEDICO', 2),
('V-55555555', 2400.00, '2021-01-20', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'MEDICO', 1),
('V-66666666', 2800.00, '2019-05-10', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'MEDICO', 2),
('V-77777777', 2300.00, '2022-02-01', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'MEDICO', 1);

-- Subclases de Empleados
INSERT INTO administrador (cedula) VALUES ('V-00000000');

INSERT INTO recepcionista (cedula, estacion_trabajo, extension_tlf) VALUES
('V-22222222', 'Módulo Recepción A', '101');

INSERT INTO laboratorista (cedula, carnet_bioanalista, area) VALUES
('V-33333333', 'BIO-987654', 'Hematología y Bioquímica');

INSERT INTO medico (cedula, carnet_medico, tarifa) VALUES
('V-11111111', 'MP-102030', 40.00),
('V-44444444', 'MP-506070', 60.00),
('V-55555555', 'MP-809010', 50.00),
('V-66666666', 'MP-112233', 55.00),
('V-77777777', 'MP-445566', 45.00);

-- Especialidades por Médico
INSERT INTO medico_especialidad (cedula_medico, id_especialidad) VALUES
('V-11111111', 1), -- Dr. Roberto Gómez   -> Medicina General (ID 1)
('V-44444444', 3), -- Dra. Elena Rostova   -> Cardiología (ID 3)
('V-55555555', 2), -- Dr. Andrés Mendoza   -> Pediatría (ID 2)
('V-66666666', 4), -- Dra. Sofia Benítez   -> Traumatología (ID 4)
('V-77777777', 6); -- Dr. Ricardo Silva    -> Dermatología (ID 6)

-- -----------------------------------------------------------------------------
-- 5. CITAS Y CONSULTAS DE MUESTRA
-- -----------------------------------------------------------------------------

-- Cita 1: Paciente Juan Pérez con Dr. Roberto Gómez (Medicina General)
INSERT INTO cita (rango_cita, consultorio, estado, cedula_medico, cedula_paciente) VALUES
(tsrange('2026-10-05 09:00:00', '2026-10-05 09:30:00'), '101', 'CONFIRMADA', 'V-11111111', 'V-00000001');

-- Cita 2: Paciente María Rodríguez con Dra. Elena Rostova (Cardiología - Pendiente)
INSERT INTO cita (rango_cita, consultorio, estado, cedula_medico, cedula_paciente) VALUES
(tsrange('2026-10-06 14:00:00', '2026-10-06 14:30:00'), '204', 'PENDIENTE', 'V-44444444', 'V-00000002');

-- Cita 3: Paciente María Rodríguez con Dr. Roberto Gómez (Medicina General)
INSERT INTO cita (rango_cita, consultorio, estado, cedula_medico, cedula_paciente) VALUES
(tsrange('2026-09-20 10:00:00', '2026-09-20 10:30:00'), '101', 'CONFIRMADA', 'V-11111111', 'V-00000002');

-- Cita 4: Paciente Juan Pérez con Dr. Andrés Mendoza (Pediatría)
INSERT INTO cita (rango_cita, consultorio, estado, cedula_medico, cedula_paciente) VALUES
(tsrange('2026-09-25 10:00:00', '2026-09-25 10:30:00'), '102', 'CONFIRMADA', 'V-55555555', 'V-00000001');

-- Cita 5: Paciente María Rodríguez con Dra. Sofia Benítez (Traumatología)
INSERT INTO cita (rango_cita, consultorio, estado, cedula_medico, cedula_paciente) VALUES
(tsrange('2026-10-01 11:00:00', '2026-10-01 11:30:00'), '201', 'CONFIRMADA', 'V-66666666', 'V-00000002');

-- Cita 6: Paciente Juan Pérez con Dr. Ricardo Silva (Dermatología - Pendiente futura)
INSERT INTO cita (rango_cita, consultorio, estado, cedula_medico, cedula_paciente) VALUES
(tsrange('2026-10-12 16:00:00', '2026-10-12 16:30:00'), '103', 'PENDIENTE', 'V-77777777', 'V-00000001');


-- CONSULTAS LIGADAS A CITAS
-- (Los Triggers cambian automáticamente el estado de las citas asociadas a 'COMPLETADA')

-- Consulta 1: Ligada a Cita ID 1 (Dr. Gómez)
INSERT INTO consulta (fecha, diagnostico, observaciones, costo, cedula_paciente, cedula_medico, id_cita) VALUES
('2026-10-05 09:15:00', 'Síndrome gripal e inflamación faríngea aguda.', 'Se indica reposo por 3 días e hidratación constante.', 40.00, 'V-00000001', 'V-11111111', 1);

-- Consulta 2: Ligada a Cita ID 3 (Dr. Gómez)
INSERT INTO consulta (fecha, diagnostico, observaciones, costo, cedula_paciente, cedula_medico, id_cita) VALUES
('2026-09-20 10:20:00', 'Chequeo de rutina. Cefalea leve ocasional por estrés.', 'Se solicitan exámenes de laboratorio preventivos.', 40.00, 'V-00000002', 'V-11111111', 3);

-- Consulta 3: Ligada a Cita ID 4 (Dr. Mendoza - Pediatría)
INSERT INTO consulta (fecha, diagnostico, observaciones, costo, cedula_paciente, cedula_medico, id_cita) VALUES
('2026-09-25 10:15:00', 'Evaluación física general. Desarrollo según percentiles normales.', 'Paciente en excelente condición física.', 50.00, 'V-00000001', 'V-55555555', 4);

-- Consulta 4: Ligada a Cita ID 5 (Dra. Benítez - Traumatología)
INSERT INTO consulta (fecha, diagnostico, observaciones, costo, cedula_paciente, cedula_medico, id_cita) VALUES
('2026-10-01 11:20:00', 'Esguince de tobillo derecho Grado I por torcedura deportiva.', 'Inmovilización leve con vendaje elástico e hieloterapia.', 55.00, 'V-00000002', 'V-66666666', 5);

-- -----------------------------------------------------------------------------
-- 6. ORDENES DE ESTUDIO Y RECETAS
-- -----------------------------------------------------------------------------

-- Órdenes de estudio generadas
INSERT INTO estudio (id_tipo_estudio, fecha, estado, id_consulta, laboratorista) VALUES
(1, '2026-09-20 10:25:00', 'PENDIENTE', 2, 'V-33333333'), -- Perfil 20 para Consulta 2
(6, '2026-10-01 11:25:00', 'PENDIENTE', 4, 'V-33333333'); -- Rx de Tórax/Extremidades para Consulta 4

-- Recetas prescritas
INSERT INTO receta (dosis, frecuencia, duracion, indicaciones, id_consulta, id_medicamento) VALUES
('1 cápsula cada 8 horas', 'Cada 8 horas', '7 días', 'Tomar después de los alimentos principales.', 1, 1), -- Amoxicilina (Consulta 1)
('1 tableta en caso de dolor o fiebre', 'Cada 8 horas', '3 días', 'No exceder 3 tabletas al día.', 1, 3),      -- Paracetamol (Consulta 1)
('1 tableta de 50mg cada 12 horas', 'Cada 12 horas', '5 días', 'Tomar junto con antiácido o comida.', 4, 9), -- Diclofenac (Consulta 4)
('1 tableta de 400mg si hay inflamación', 'Cada 8 horas', '3 días', 'Para control del dolor agudo.', 4, 2);  -- Ibuprofeno (Consulta 4)

COMMIT;