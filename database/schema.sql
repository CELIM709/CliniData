CREATE TABLE Persona (
    cedula VARCHAR(20) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(150) UNIQUE,
    direccion VARCHAR(255),
);

CREATE TABLE Paciente (
    cedula VARCHAR(20) PRIMARY KEY,
    fecha_nacimiento DATE NOT NULL,  -- ejemplo: '1990-09-01'
    genero CHAR(1) CHECK (genero IN ('M', 'F')),
    tipo_sangre VARCHAR(5),

    CONSTRAINT fk_paciente_persona
        FOREIGN KEY (cedula)
        REFERENCES Persona(cedula) ON DELETE CASCADE
);

CREATE TABLE Horario (
    id_horario INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dias VARCHAR(100) NOT NULL,  -- ejemplo: 'Lunes a Viernes', 'Sabado y Domingo'
    hora_entrada TIME NOT NULL,
    hora_salida TIME NOT NULL
);

CREATE TABLE Empleado (
    cedula VARCHAR(20) PRIMARY KEY,
    salario NUMERIC(10,2) NOT NULL,
    fecha_contratado DATE NOT NULL,
    clave_acceso VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL CHECK (rol IN ('MEDICO', 'RECEPCIONISTA', 'LABORATORISTA', 'ADMIN')),
    id_horario INTEGER NOT NULL,

    CONSTRAINT fk_empleado_persona
        FOREIGN KEY (cedula)
        REFERENCES Persona(cedula) ON DELETE CASCADE,

    CONSTRAINT fk_empleado_horario
        FOREIGN KEY (id_horario)
        REFERENCES Horario(id_horario) ON UPDATE CASCADE
);

CREATE TABLE Especialidad (
    id_especialidad INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
);

CREATE TABLE Medico (
    cedula VARCHAR(20) PRIMARY KEY,
    carnet_medico VARCHAR(50) NOT NULL UNIQUE,
    tarifa NUMERIC(10, 2) NOT NULL,

    CONSTRAINT fk_medico_empleado
        FOREIGN KEY (cedula)
        REFERENCES Empleado(cedula) ON DELETE CASCADE
);

CREATE TABLE Medico_Especialidad (
    cedula_medico VARCHAR(20),
    id_especialidad INTEGER,

    PRIMARY KEY (cedula_medico, id_especialidad),

    FOREIGN KEY (cedula_medico)
        REFERENCES Medico(cedula),

    FOREIGN KEY (id_especialidad)
        REFERENCES Especialidad(id_especialidad)
);

CREATE TABLE Laboratorista (
    cedula VARCHAR(20) PRIMARY KEY,
    carnet_bioanalista VARCHAR(50) NOT NULL UNIQUE,
    area VARCHAR(100) NOT NULL, -- Conviene solo texto

    CONSTRAINT fk_laboratorista_empleado
        FOREIGN KEY (cedula)
        REFERENCES Empleado(cedula) ON DELETE CASCADE
);

CREATE TABLE recepcionista (
    cedula VARCHAR(20) PRIMARY KEY,
    estacion_trabajo VARCHAR(50) NOT NULL,
    extension_tlf VARCHAR(10) NOT NULL,

    CONSTRAINT fk_recepcionista_empleado
        FOREIGN KEY (cedula)
        REFERENCES Empleado(cedula) ON DELETE CASCADE
);

CREATE TABLE Administrador (
    cedula VARCHAR(20) PRIMARY KEY,
    
    CONSTRAINT fk_administrador_empleado
        FOREIGN KEY (cedula)
        REFERENCES Empleado(cedula) ON DELETE CASCADE
);

CREATE TABLE Historia_medica (
    cedula_paciente VARCHAR(20) PRIMARY KEY, -- PK y FK  (una historia medica por paciente)
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    antecedentes TEXT,
    alergias TEXT,
    medicacion_habitual TEXT,

    CONSTRAINT fk_historia_paciente
        FOREIGN KEY (cedula_paciente)
        REFERENCES Paciente(cedula) ON DELETE CASCADE
);


-- Extension necesaria para verificar solapamiento
CREATE EXTENSION IF NOT EXISTS btree_gist;

CREATE TABLE Cita (
    id_cita INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    rango_cita TSTZRANGE NOT NULL, 
    consultorio VARCHAR(50) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE' CHECK (estado IN ('PENDIENTE', 'CONFIRMADA', 'CANCELADA')),
    cedula_medico VARCHAR(20) NOT NULL,
    cedula_paciente VARCHAR(20) NOT NULL,

    CONSTRAINT fk_cita_medico
        FOREIGN KEY (cedula_medico)
        REFERENCES Medico(cedula),

    CONSTRAINT fk_cita_paciente
        FOREIGN KEY (cedula_paciente)
        REFERENCES Paciente(cedula),

    -- Restriccion de solapamiento de citas
    CONSTRAINT no_solapar_citas_medico
        EXCLUDE USING gist (
            cedula_medico WITH =,
            rango_cita WITH &&
        ) WHERE (estado != 'CANCELADA')
);

CREATE TABLE Consulta (
    id_consulta INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    fecha TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diagnostico TEXT NOT NULL,
    observaciones TEXT,
    costo NUMERIC(10, 2) NOT NULL,

    cedula_paciente VARCHAR(20) NOT NULL, 
    cedula_medico VARCHAR(20) NOT NULL,
    id_cita INT UNIQUE, 

    CONSTRAINT fk_consulta_historia
        FOREIGN KEY (cedula_paciente)
        REFERENCES Historia_medica(cedula_paciente),

    CONSTRAINT fk_consulta_medico
        FOREIGN KEY (cedula_medico)
        REFERENCES Medico(cedula),

    CONSTRAINT fk_consulta_cita
        FOREIGN KEY (id_cita)
        REFERENCES Cita(id_cita) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE Estudio (
    id_estudio INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo VARCHAR(100) NOT NULL, 
    fecha TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20) NOT NULL DEFAULT 'SOLICITADO' CHECK (estado IN ('SOLICITADO', 'REALIZADO', 'CANCELADO')),

    id_consulta INT NOT NULL,
    laboratorista VARCHAR(20), -- opcional

    CONSTRAINT fk_estudio_consulta
        FOREIGN KEY (id_consulta)
        REFERENCES Consulta(id_consulta) ON DELETE CASCADE,

    CONSTRAINT fk_estudio_laboratorista
        FOREIGN KEY (laboratorista)
        REFERENCES Laboratorista(cedula) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE Resultado (
    id_resultado INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    descripcion TEXT,
    ruta_archivo VARCHAR(255) NOT NULL, -- Opcion 1: guardar la ruta del arcchivo, Opcion 2 usar tipo BYTEA pero seria mas pesada la BD
    fecha TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    id_estudio INT NOT NULL,

    CONSTRAINT fk_resultado_estudio
        FOREIGN KEY (id_estudio)
        REFERENCES Estudio(id_estudio) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Medicamento (
    id_medicamento INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    laboratorio VARCHAR(100) NOT NULL, 
    presentacion VARCHAR(100) NOT NULL, 

    CONSTRAINT no_duplicados UNIQUE (nombre, laboratorio, presentacion)
);

CREATE TABLE receta (
    id_receta INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dosis VARCHAR(100) NOT NULL,        
    frecuencia VARCHAR(100) NOT NULL,   
    duracion VARCHAR(100) NOT NULL,     
    indicaciones TEXT,   -- opcional

    id_consulta INT NOT NULL,
    id_medicamento INT NOT NULL,

    CONSTRAINT fk_receta_consulta
        FOREIGN KEY (id_consulta)
        REFERENCES Consulta(id_consulta) ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_receta_medicamento
        FOREIGN KEY (id_medicamento)
        REFERENCES Medicamento(id_medicamento)
);
