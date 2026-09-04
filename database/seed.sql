-- 1. Insertar personas (Base para empleados y pacientes)
INSERT INTO persona (cedula, nombre, apellido, fecha_nacimiento, telefono, email, direccion) VALUES
('V-10111222', 'Carlos', 'Mendoza', '1980-05-15', '0414-1112233', 'carlos.mendoza@email.com', 'Av. Las Américas, Puerto Ordaz'),
('V-12345678', 'Ana', 'Martínez', '1985-11-20', '0412-5554433', 'ana.martinez@email.com', 'Alta Vista, Calle Cuchivero'),
('V-87654321', 'Luis', 'Ríos', '1992-03-10', '0416-9998877', 'luis.rios@email.com', 'Unare II, Bloque 5'),
('V-15975310', 'María', 'García', '1988-08-05', '0424-7776655', 'maria.garcia@email.com', 'Villa Asia, Manzana 12'),
('V-20304050', 'Jorge', 'Pérez', '1995-01-30', '0414-3332211', 'jorge.perez@email.com', 'Core 8, Sector 1'),
('V-25801947', 'Elena', 'Gómez', '2001-07-12', '0412-8881122', 'elena.gomez@email.com', 'San Félix, El Roble'),
('V-30123456', 'Pedro', 'Sánchez', '1999-12-01', '0416-2223344', 'pedro.sanchez@email.com', 'Castillito, Sector Comercial');

-- 2. Insertar Pacientes
INSERT INTO paciente (cedula, genero, tipo_sangre) VALUES
('V-87654321', 'M', 'O+'),
('V-20304050', 'M', 'A+'),
('V-25801947', 'F', 'O-'),
('V-30123456', 'M', 'B+');

-- 3. Insertar Horarios
INSERT INTO horario (dias, hora_entrada, hora_salida) VALUES
('Lunes a Viernes', '07:00:00', '15:00:00'),
('Lunes a Viernes', '12:00:00', '18:00:00'),
('Sábados y Domingos', '08:00:00', '16:00:00');

-- 4. Insertar Empleados
-- Nota: este hash lo genere con php, ver archivo generarHash.php
INSERT INTO empleado (cedula, salario, fecha_contratado, clave_acceso, rol, id_horario) VALUES
('V-10111222', 1200.00, '2020-01-15', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'MEDICO', 1),
('V-12345678', 1150.00, '2021-06-01', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'MEDICO', 1),
('V-15975310', 600.00,  '2022-03-10', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'RECEPCIONISTA', 2),
('V-20304050', 700.00,  '2023-02-01', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'LABORATORISTA', 1),
('V-30123456', 900.00,  '2019-11-20', '$2y$12$iVsRNQgnH3lWmSqs0xvrBedvWfoj0TOjxJC.lnqC59khHJKmJYCBy', 'ADMIN', 1);

-- 5. Subclases de Empleados
INSERT INTO administrador (cedula) VALUES
('V-30123456');

INSERT INTO recepcionista (cedula, estacion_trabajo, extension_tlf) VALUES
('V-15975310', 'Módulo Principal Entrada', '101');

INSERT INTO laboratorista (cedula, carnet_bioanalista, area) VALUES
('V-20304050', 'BIO-98765', 'Hematología y Bioquímica');

INSERT INTO medico (cedula, carnet_medico, tarifa) VALUES
('V-10111222', 'MPPS-45123', 40.00),
('V-12345678', 'MPPS-67890', 50.00);

-- 6. Especialidades y asignación a Médicos
INSERT INTO especialidad (nombre, descripcion) VALUES
('Medicina General', 'Atención médica primaria e integral'),
('Pediatría', 'Atención médica de niños y adolescentes'),
('Cardiología', 'Diagnóstico y tratamiento de enfermedades del corazón');

INSERT INTO medico_especialidad (cedula_medico, id_especialidad) VALUES
('V-10111222', 1),
('V-12345678', 1),
('V-12345678', 3);

-- 7. Historias Médicas
INSERT INTO historia_medica (cedula_paciente, fecha_creacion, antecedentes, alergias, medicacion_habitual) VALUES
('V-87654321', '2024-01-10 09:00:00-04', 'Hipertensión arterial familiar', 'Penicilina', 'Losartán 50mg diario'),
('V-25801947', '2024-02-15 10:30:00-04', 'Ninguno de importancia', 'Ninguna', 'Ninguno'),
('V-30123456', '2024-03-01 14:00:00-04', 'Asma bronquial en la infancia', 'Aspirina, Polvo', 'Salbutamol en aerosol si hay crisis');

-- 8. Citas (Haciendo uso de rangos tsrange)
INSERT INTO cita (rango_cita, consultorio, estado, cedula_medico, cedula_paciente) VALUES
('[2026-09-10 08:00:00, 2026-09-10 08:30:00)', 'Consultorio 1', 'CONFIRMADA', 'V-10111222', 'V-87654321'),
('[2026-09-10 08:30:00, 2026-09-10 09:00:00)', 'Consultorio 1', 'PENDIENTE',  'V-10111222', 'V-25801947'),
('[2026-09-10 09:00:00, 2026-09-10 09:30:00)', 'Consultorio 2', 'CONFIRMADA', 'V-12345678', 'V-30123456');

-- 9. Consultas asociadas a las Citas
INSERT INTO consulta (fecha, diagnostico, observaciones, costo, cedula_paciente, cedula_medico, id_cita) VALUES
('2026-09-10 08:25:00-04', 'Síndrome Febril Agudo / Cuadro gripal', 'Reposo por 3 días e hidratación abundante.', 40.00, 'V-87654321', 'V-10111222', 1),
('2026-09-10 09:20:00-04', 'Control Cardiovascular de Rutina', 'Presión arterial dentro de los límites normales (120/80).', 50.00, 'V-30123456', 'V-12345678', 3);

-- 10. Estudios de laboratorio indicados en la Consulta
INSERT INTO estudio (tipo, fecha, estado, id_consulta, laboratorista) VALUES
('Hematología Completa', '2026-09-10 08:35:00-04', 'REALIZADO', 1, 'V-20304050'),
('Perfil Lipídico', '2026-09-10 09:35:00-04', 'SOLICITADO', 2, 'V-20304050');

-- 11. Resultados de Estudios
INSERT INTO resultado (descripcion, ruta_archivo, fecha, id_estudio) VALUES
('Hemoglobina: 14.5 g/dL, Leucocitos: 7,500/mm3, Plaquetas: 250,000/mm3. Valores dentro de rangos normales.', '/uploads/resultados/estudio_1_hematologia.pdf', '2026-09-10 11:00:00-04', 1);

-- 12. Medicamentos disponibles en catálogo
INSERT INTO medicamento (nombre, laboratorio, presentacion) VALUES
('Acetaminofén 500mg', 'Genfar', 'Comprimidos / Caja x 10'),
('Ibuprofeno 400mg', 'Calox', 'Tabletas / Caja x 20'),
('Losartán Potásico 50mg', 'Leti', 'Comprimidos / Caja x 30'),
('Amoxicilina 500mg', 'Meyer', 'Cápsulas / Caja x 12');

-- 13. Recetas/Prescripciones médicas asociadas a la Consulta
INSERT INTO receta (dosis, frecuencia, duracion, indicaciones, id_consulta, id_medicamento) VALUES
('1 tableta (500mg)', 'Cada 8 horas', '5 días', 'Tomar después de las comidas con abundante agua.', 1, 1),
('1 comprimido (50mg)', 'Cada 24 horas', 'Continuo', 'Tomar por las mañanas preferiblemente.', 2, 3);