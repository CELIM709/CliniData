CREATE TABLE Persona (
    cedula VARCHAR(20) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,  -- ejemplo: '1990-09-01'
    telefono VARCHAR(20),
    email VARCHAR(150) UNIQUE,
    genero CHAR(1) CHECK (genero IN ('M', 'F')),
    tipo_sangre VARCHAR(5),
    direccion VARCHAR(255)
);

CREATE TABLE Paciente (
    cedula VARCHAR(20) PRIMARY KEY,

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


-- NOTA: Quite las subclases por las quejas de las herencias. pero es necesario o mas facil la vida, dejar entidad Empleado y Paciente. Solo los empleados se diferenciarán por roles. 
-- Asi evitamos crear multiroles, tamblas intermedias, identificaciones herradas...
CREATE TABLE Empleado (
    cedula VARCHAR(20) PRIMARY KEY,
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
        ) WHERE (estado != 'CANCELADA'),

    CONSTRAINT no_solapar_citas_paciente
        EXCLUDE USING gist (
            cedula_paciente WITH =,
            rango_cita WITH &&
        )
        WHERE (estado != 'CANCELADA')
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
        REFERENCES Empleado(cedula) ON DELETE SET NULL ON UPDATE CASCADE
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

-- No soy un experto en bases de datos, pero la profesora Maria Herrera en BD1 siempre nos aconsejaba normalizar datos con identidad propia.
-- Incluso ella nos dio un ejemplo que parecía innecesario, en un taller mecánico, ella creo el catalogo de Marcas.  Y si... si... EL TALLER MECANICO NO VENDE CARROS!
-- EXISTEN INFINIDAD DE MARCAS, PERO AUN ASI, se agrega un catalogo de marcas para evitar malas consultas. por ejemplo:
-- todos los Select carros Hyundai. ah pero y si un carro tiene Hiundai ? no va a aparecer.
-- En nuestro caso, Select todas las consultas que contengan Acetaminofen, pero y si una receta dice Asetaminofen ?
-- Esa no va a aparecer en la consulta. Ademas un medicamento es una entidad del dominio, tiene atributos tangibles.
-- Muchas de las cosas que digo, vienen de TEC PRGOG III, estructura de datos, BD 1, y no creo que esos profesores esten errados.
-- Sinceramente, desconfío más de una profesora que nunca ha dado la asignatura antes! 
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
    indicaciones TEXT,                  -- opcional

    -- Relaciones esenciales
    id_consulta INT NOT NULL,
    id_medicamento INT NOT NULL,

    CONSTRAINT fk_receta_consulta
        FOREIGN KEY (id_consulta)
        REFERENCES Consulta(id_consulta) ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_receta_medicamento
        FOREIGN KEY (id_medicamento)
        REFERENCES Medicamento(id_medicamento)
);
