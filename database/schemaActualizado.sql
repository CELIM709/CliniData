CREATE EXTENSION IF NOT EXISTS btree_gist;
-- 1. Tabla Persona
CREATE TABLE persona (
    cedula VARCHAR(20) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,   -- ejemplo: '1990-09-01'
    telefono VARCHAR(20),
    email VARCHAR(150) UNIQUE,
    direccion VARCHAR(255)
);

-- 2. Tabla Paciente (Hereda / Especialización de Persona)
CREATE TABLE paciente (
    cedula VARCHAR(20) PRIMARY KEY,
    genero CHAR(1) NOT NULL CHECK (genero IN ('M', 'F')),
    tipo_sangre VARCHAR(10) NOT NULL,
    CONSTRAINT fk_paciente_persona 
        FOREIGN KEY (cedula) REFERENCES persona(cedula) ON DELETE CASCADE
);

-- 3. Tabla Horario
CREATE TABLE horario (
    id_horario INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dias VARCHAR(100) NOT NULL,  -- ejemplo: 'Lunes a Viernes', 'Sabado y Domingo'
    hora_entrada TIME NOT NULL,
    hora_salida TIME NOT NULL
);

-- 4. Tabla Empleado (Hereda de Persona y relaciona Horario)
CREATE TABLE empleado (
    cedula VARCHAR(20) PRIMARY KEY,
    salario NUMERIC(10, 2) NOT NULL,
    fecha_contratado DATE NOT NULL,
    clave_acceso VARCHAR(255) NOT NULL,
    rol VARCHAR(50) NOT NULL CHECK (rol IN ('MEDICO', 'RECEPCIONISTA', 'LABORATORISTA', 'ADMIN')),
    id_horario INT NOT NULL,
    CONSTRAINT fk_empleado_persona 
        FOREIGN KEY (cedula) REFERENCES persona(cedula) ON DELETE CASCADE,
    CONSTRAINT fk_empleado_horario 
        FOREIGN KEY (id_horario) REFERENCES horario(id_horario) ON UPDATE CASCADE
);

-- 5. Subclases de Empleado

CREATE TABLE administrador (
    cedula VARCHAR(20) PRIMARY KEY,
    CONSTRAINT fk_admin_empleado 
        FOREIGN KEY (cedula) REFERENCES empleado(cedula) ON DELETE CASCADE
);

CREATE TABLE recepcionista (
    cedula VARCHAR(20) PRIMARY KEY,
    estacion_trabajo VARCHAR(50),
    extension_tlf VARCHAR(20),
    CONSTRAINT fk_recepcionista_empleado 
        FOREIGN KEY (cedula) REFERENCES empleado(cedula) ON DELETE CASCADE
);

CREATE TABLE laboratorista (
    cedula VARCHAR(20) PRIMARY KEY,
    carnet_bioanalista VARCHAR(50) NOT NULL,
    area VARCHAR(100),
    CONSTRAINT fk_laboratorista_empleado 
        FOREIGN KEY (cedula) REFERENCES empleado(cedula) ON DELETE CASCADE
);

CREATE TABLE medico (
    cedula VARCHAR(20) PRIMARY KEY,
    carnet_medico VARCHAR(50) NOT NULL UNIQUE,
    tarifa NUMERIC(10, 2) NOT NULL,
    CONSTRAINT fk_medico_empleado 
        FOREIGN KEY (cedula) REFERENCES empleado(cedula) ON DELETE CASCADE
);

-- 6. Especialidad y relación M:N con Medico
CREATE TABLE especialidad (
    id_especialidad INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
);

CREATE TABLE medico_especialidad (
    cedula_medico VARCHAR(20) NOT NULL,
    id_especialidad INT NOT NULL,
    PRIMARY KEY (cedula_medico, id_especialidad),
    CONSTRAINT fk_medesp_medico 
        FOREIGN KEY (cedula_medico) REFERENCES medico(cedula) ON DELETE CASCADE,
    CONSTRAINT fk_medesp_especialidad 
        FOREIGN KEY (id_especialidad) REFERENCES especialidad(id_especialidad) ON DELETE CASCADE
);

-- 7. Historia Médica
CREATE TABLE historia_medica (
    cedula_paciente VARCHAR(20) PRIMARY KEY,  -- PK y FK  (una historia medica por paciente)
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    antecedentes TEXT,
    alergias TEXT,
    medicacion_habitual TEXT,
    CONSTRAINT fk_historia_paciente 
        FOREIGN KEY (cedula_paciente) REFERENCES paciente(cedula) ON DELETE CASCADE
);

-- 8. Cita
CREATE TABLE cita (
    id_cita INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    rango_cita TSRANGE NOT NULL,
    consultorio VARCHAR(50) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE' CHECK (estado IN ('PENDIENTE', 'CONFIRMADA', 'CANCELADA')),
    cedula_medico VARCHAR(20) NOT NULL,
    cedula_paciente VARCHAR(20) NOT NULL,
    CONSTRAINT fk_cita_medico 
        FOREIGN KEY (cedula_medico) REFERENCES medico(cedula),
    CONSTRAINT fk_cita_paciente 
        FOREIGN KEY (cedula_paciente) REFERENCES paciente(cedula),

		-- Restricción para evitar que se solapen las citas de un mismo médico
    CONSTRAINT no_solapar_citas_medico
        EXCLUDE USING gist (
            cedula_medico WITH =,
            rango_cita WITH &&
        ) WHERE (estado != 'CANCELADA'),

    CONSTRAINT no_solapar_citas_paciente
        EXCLUDE USING gist (
            cedula_paciente WITH =,
            rango_cita WITH &&
        )
        WHERE (estado != 'CANCELADA')
);

-- 9. Consulta
CREATE TABLE consulta (
    id_consulta INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    fecha TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diagnostico TEXT NOT NULL,
    observaciones TEXT,
    costo NUMERIC(10, 2) NOT NULL,
    cedula_paciente VARCHAR(20) NOT NULL,
    cedula_medico VARCHAR(20) NOT NULL,
    id_cita INT UNIQUE,   -- Aseegura una sola cita por consulta.
    CONSTRAINT fk_consulta_paciente 
        FOREIGN KEY (cedula_paciente) REFERENCES paciente(cedula),  -- HistoriaMedica ya no esta ligada a consulta? bueno yo lo dejo asi.
    CONSTRAINT fk_consulta_medico 
        FOREIGN KEY (cedula_medico) REFERENCES medico(cedula),
    CONSTRAINT fk_consulta_cita 
        FOREIGN KEY (id_cita) REFERENCES cita(id_cita) ON DELETE SET NULL
);

-- 10. Crear la tabla catálogo para los exámenes de laboratorio
CREATE TABLE tipo_estudio (
    id_tipo_estudio INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_estudio VARCHAR(100) UNIQUE NOT NULL,
    descripcion TEXT
);

-- 11. Estudio
CREATE TABLE estudio (
    id_estudio INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_tipo_estudio INT NOT NULL,
    fecha TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20) NOT NULL  DEFAULT 'SOLICITADO' CHECK (estado IN ('SOLICITADO', 'REALIZADO', 'CANCELADO')),
    id_consulta INT NOT NULL,
    laboratorista VARCHAR(20),
    CONSTRAINT fk_estudio_tipo 
        FOREIGN KEY (id_tipo_estudio) REFERENCES tipo_estudio(id_tipo_estudio),
    CONSTRAINT fk_estudio_consulta 
        FOREIGN KEY (id_consulta) REFERENCES consulta(id_consulta) ON DELETE CASCADE,
    CONSTRAINT fk_estudio_laboratorista 
        FOREIGN KEY (laboratorista) REFERENCES laboratorista(cedula) ON DELETE SET NULL
);

-- 12. Resultado
CREATE TABLE resultado (
    id_resultado INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    descripcion TEXT,
    ruta_archivo VARCHAR(255) NOT NULL,  -- Opcion 1: guardar la ruta del arcchivo, Opcion 2 usar tipo BYTEA pero seria mas pesada la BD
    fecha TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_estudio INT NOT NULL,
    CONSTRAINT fk_resultado_estudio 
        FOREIGN KEY (id_estudio) REFERENCES estudio(id_estudio) ON DELETE CASCADE
);

-- 13. Medicamento
CREATE TABLE medicamento (
    id_medicamento INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    laboratorio VARCHAR(100) NOT NULL,
    presentacion VARCHAR(100) NOT NULL,

    -- Evita duplicados, preferiblemente dejarlo.
    CONSTRAINT no_duplicados UNIQUE (nombre, laboratorio, presentacion)
);

-- 14. Receta
CREATE TABLE receta (
    id_receta INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dosis VARCHAR(100) NOT NULL,
    frecuencia VARCHAR(100) NOT NULL,
    duracion VARCHAR(100) NOT NULL,
    indicaciones TEXT,
    id_consulta INT NOT NULL,
    id_medicamento INT NOT NULL,
    CONSTRAINT fk_receta_consulta 
        FOREIGN KEY (id_consulta) REFERENCES consulta(id_consulta) ON DELETE CASCADE,
    CONSTRAINT fk_receta_medicamento 
        FOREIGN KEY (id_medicamento) REFERENCES medicamento(id_medicamento)
);
