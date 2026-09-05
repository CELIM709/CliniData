# CliniData — Documentación de API

Backend del sistema de gestión de clínica desarrollado con **PHP + PostgreSQL**.

Las APIs reciben solicitudes HTTP y utilizan **JSON** para la comunicación con el frontend. Los modelos PHP se encargan de ejecutar las operaciones sobre PostgreSQL.

---

## 1. Estructura del backend

```text
clinica-backend/
├── api/
│   ├── check_session.php
│   ├── citas.php
│   ├── consultas.php
│   ├── empleados.php
│   ├── especialidades.php
│   ├── estudios.php
│   ├── login.php
│   ├── logout.php
│   ├── medicamentos.php
│   ├── medicos.php
│   ├── pacientes.php
│   ├── recetas.php
│   └── resultados.php
│
├── models/
│   ├── Cita.php
│   ├── Consulta.php
│   ├── Empleado.php
│   ├── Especialidad.php
│   ├── Estudio.php
│   ├── HistoriaMedica.php
│   ├── Medicamento.php
│   ├── Medico.php
│   ├── Paciente.php
│   ├── Persona.php
│   ├── Receta.php
│   └── Resultado.php
│
└── config/
    └── Conexion.php
```

---

# 2. Ejecución

Desde la carpeta `clinica-backend`:

```bash
php -S localhost:8000
```

Las APIs estarán disponibles mediante:

```text
http://localhost:8000/api/
```

Por ejemplo:

```text
http://localhost:8000/api/pacientes.php
```

---

# 3. Comunicación con el frontend

El frontend se comunica con las APIs mediante solicitudes HTTP.

El flujo general es:

```text
Frontend
   │
   │ HTTP + JSON
   ▼
API PHP
   │
   │ llamada al modelo
   ▼
Modelo PHP
   │
   │ SQL
   ▼
PostgreSQL
   │
   ▼
Modelo PHP
   │
   ▼
API PHP
   │
   │ JSON
   ▼
Frontend
```

El frontend no necesita conocer las consultas SQL internas. Solo necesita conocer:

- URL del endpoint.
- Método HTTP.
- Parámetros o JSON de entrada.
- Estructura de la respuesta.
- Códigos HTTP utilizados.

---

# 4. Formato general de respuestas

Las respuestas exitosas utilizan normalmente:

```json
{
    "success": true,
    "data": []
}
```

o:

```json
{
    "success": true,
    "mensaje": "Operación realizada correctamente."
}
```

Las respuestas de error utilizan:

```json
{
    "success": false,
    "error": "Mensaje descriptivo del error."
}
```

## Códigos HTTP

| Código | Descripción |
|---|---|
| `200` | Solicitud procesada correctamente |
| `201` | Registro creado correctamente |
| `400` | Datos o parámetros inválidos |
| `401` | Usuario no autenticado |
| `403` | Usuario autenticado pero sin permisos |
| `404` | Recurso no encontrado |
| `405` | Método HTTP no permitido |
| `409` | Conflicto de datos, por ejemplo solapamiento de citas |
| `500` | Error interno del servidor |

---

# 5. Autenticación

## 5.1 Iniciar sesión

### POST

```text
/api/login.php
```

El endpoint permite iniciar sesión utilizando cédula o correo.

### JSON de entrada

```json
{
    "identificador": "12345678",
    "clave": "12345"
}
```

También se admite:

```json
{
    "cedula": "12345678",
    "clave": "12345"
}
```

### Respuesta exitosa

```json
{
    "success": true,
    "mensaje": "Inicio de sesión exitoso.",
    "usuario": {
        "cedula": "12345678",
        "nombre": "Juan Perez",
        "email": "juan@example.com",
        "rol": "RECEPCIONISTA"
    }
}
```

### Credenciales incorrectas

HTTP `401`

```json
{
    "success": false,
    "error": "Cédula/correo o contraseña incorrectos."
}
```

### Ejemplo JavaScript

```javascript
fetch("http://localhost:8000/api/login.php", {
    method: "POST",
    headers: {
        "Content-Type": "application/json"
    },
    body: JSON.stringify({
        identificador: "12345678",
        clave: "12345"
    })
})
.then(response => response.json())
.then(data => {
    console.log(data);
});
```

---

## 5.2 Comprobar sesión

### GET

```text
/api/check_session.php
```

### Usuario autenticado

```json
{
    "autenticado": true,
    "usuario": {
        "cedula": "12345678",
        "nombre": "Juan Perez",
        "email": "juan@example.com",
        "rol": "RECEPCIONISTA"
    }
}
```

### Sin sesión

HTTP `401`

```json
{
    "autenticado": false,
    "error": "No hay una sesión activa."
}
```

---

## 5.3 Cerrar sesión

### `/api/logout.php`

El endpoint destruye la sesión actual.

### Respuesta

```json
{
    "success": true,
    "mensaje": "Sesion cerrada correctamente."
}
```

---

# 6. Pacientes

Endpoint:

```text
/api/pacientes.php
```

## 6.1 Obtener todos los pacientes

### GET

```text
/api/pacientes.php
```

### Respuesta

```json
{
    "success": true,
    "data": [
        {
            "cedula": "12345678",
            "nombre": "Juan",
            "apellido": "Perez",
            "fecha_nacimiento": "1990-05-10",
            "telefono": "04140000000",
            "email": "juan@example.com",
            "direccion": "Calle 1",
            "genero": "M",
            "tipo_sangre": "O+"
        }
    ]
}
```

---

## 6.2 Obtener paciente por cédula

### GET

```text
/api/pacientes.php?cedula=12345678
```

### Respuesta

```json
{
    "success": true,
    "data": {
        "cedula": "12345678",
        "nombre": "Juan",
        "apellido": "Perez",
        "fecha_nacimiento": "1990-05-10",
        "telefono": "04140000000",
        "email": "juan@example.com",
        "direccion": "Calle 1",
        "genero": "M",
        "tipo_sangre": "O+",
        "id_historia": 1,
        "antecedentes": "Ninguno reportado",
        "alergias": "Ninguna conocida",
        "medicacion_habitual": "Ninguna"
    }
}
```

Si no existe:

```json
{
    "success": false,
    "error": "Paciente no encontrado."
}
```

HTTP `404`.

---

## 6.3 Registrar paciente

### POST

```text
/api/pacientes.php
```

Permisos:

```text
RECEPCIONISTA
ADMIN
```

### JSON

```json
{
    "persona": {
        "cedula": "12345678",
        "nombre": "Juan",
        "apellido": "Perez",
        "fecha_nacimiento": "1990-05-10",
        "telefono": "04140000000",
        "email": "juan@example.com",
        "direccion": "Calle 1"
    },
    "paciente": {
        "genero": "M",
        "tipo_sangre": "O+"
    },
    "historia": {
        "antecedentes": "Ninguno reportado",
        "alergias": "Ninguna conocida",
        "medicacion_habitual": "Ninguna"
    }
}
```

`historia` puede omitirse.

### Respuesta

HTTP `201`

```json
{
    "success": true,
    "mensaje": "Paciente registrado exitosamente."
}
```

---

## 6.4 Actualizar paciente

### PUT

```text
/api/pacientes.php
```

Permisos:

```text
RECEPCIONISTA
ADMIN
```

### JSON

```json
{
    "cedula": "12345678",
    "persona": {
        "telefono": "04141111111",
        "email": "nuevo@example.com"
    },
    "paciente": {
        "tipo_sangre": "A+"
    }
}
```

Puede enviarse `persona`, `paciente` o ambos.

### Respuesta

```json
{
    "success": true,
    "mensaje": "Paciente actualizado exitosamente."
}
```

---

## 6.5 Eliminar rol de paciente

### DELETE

```text
/api/pacientes.php?cedula=12345678
```

Permisos:

```text
ADMIN
```

Esta operación elimina el registro de `paciente` y conserva el registro general de `persona`.

### Respuesta

```json
{
    "success": true,
    "mensaje": "Rol de paciente removido exitosamente."
}
```

---

# 7. Citas

Endpoint:

```text
/api/citas.php
```

## 7.1 Obtener citas de un médico

### GET

```text
/api/citas.php?medico=87654321
```

### Solo pendientes

```text
/api/citas.php?medico=87654321&pendientes=true
```

### Respuesta

```json
{
    "success": true,
    "data": [
        {
            "id_cita": 1,
            "fecha_inicio": "2026-09-10 10:00:00",
            "fecha_fin": "2026-09-10 10:30:00",
            "consultorio": "3",
            "estado": "PENDIENTE",
            "paciente_nombre": "Juan",
            "paciente_apellido": "Perez",
            "paciente_telefono": "04140000000"
        }
    ]
}
```

---

## 7.2 Obtener citas de un paciente

### GET

```text
/api/citas.php?paciente=12345678
```

### Solo pendientes

```text
/api/citas.php?paciente=12345678&pendientes=true
```

### Respuesta

```json
{
    "success": true,
    "data": [
        {
            "id_cita": 1,
            "fecha_inicio": "2026-09-10 10:00:00",
            "fecha_fin": "2026-09-10 10:30:00",
            "consultorio": "3",
            "estado": "PENDIENTE",
            "medico_nombre": "Ana",
            "medico_apellido": "Gomez",
            "medico_telefono": "04142222222"
        }
    ]
}
```

---

## 7.3 Agendar cita

### POST

```text
/api/citas.php
```

### JSON

```json
{
    "fecha_inicio": "2026-09-10 10:00:00",
    "fecha_fin": "2026-09-10 10:30:00",
    "consultorio": "3",
    "cedula_medico": "87654321",
    "cedula_paciente": "12345678",
    "estado": "PENDIENTE"
}
```

`estado` puede omitirse; el modelo utiliza `PENDIENTE` como valor predeterminado.

### Respuesta

HTTP `201`

```json
{
    "success": true,
    "mensaje": "Cita agendada con éxito."
}
```

### Conflicto de horario

HTTP `409`

```json
{
    "success": false,
    "error": "Conflicto de agenda: El médico o el paciente ya tienen una cita programada dentro de ese rango de tiempo."
}
```

---

## 7.4 Cambiar estado de una cita

### PUT

```text
/api/citas.php
```

### JSON

```json
{
    "id_cita": 1,
    "nuevo_estado": "CONFIRMADA"
}
```

### Respuesta

```json
{
    "success": true,
    "mensaje": "Estado de la cita actualizado."
}
```

---

## 7.5 Reprogramar una cita

### PUT

```text
/api/citas.php
```

### JSON

```json
{
    "id_cita": 1,
    "fecha_inicio": "2026-09-11 11:00:00",
    "fecha_fin": "2026-09-11 11:30:00"
}
```

### Respuesta

```json
{
    "success": true,
    "mensaje": "Cita reprogramada exitosamente."
}
```

---

# 8. Consultas médicas

Endpoint:

```text
/api/consultas.php
```

## 8.1 Obtener consulta por ID

### GET

```text
/api/consultas.php?id=1
```

---

## 8.2 Obtener consultas de un paciente

### GET

```text
/api/consultas.php?paciente=12345678
```

---

## 8.3 Obtener consultas de un médico

### GET

```text
/api/consultas.php?medico=87654321
```

---

## 8.4 Registrar consulta

### POST

```text
/api/consultas.php
```

Permisos:

```text
ADMIN
MEDICO
```

### JSON mínimo

```json
{
    "diagnostico": "Gripe",
    "cedula_paciente": "12345678",
    "cedula_medico": "87654321"
}
```

### JSON completo

```json
{
    "diagnostico": "Gripe",
    "observaciones": "Reposo por 3 días",
    "cedula_paciente": "12345678",
    "cedula_medico": "87654321"
}
```

El costo puede omitirse. En ese caso se obtiene automáticamente la tarifa del médico.

También puede enviarse `id_cita` para asociar explícitamente una cita.

### Respuesta

HTTP `201`

```json
{
    "success": true,
    "mensaje": "Consulta médica registrada con éxito.",
    "id_consulta": 10
}
```

---

# 9. Médicos

Endpoint:

```text
/api/medicos.php
```

## 9.1 Obtener todos los médicos

### GET

```text
/api/medicos.php
```

### Respuesta

```json
{
    "success": true,
    "data": [
        {
            "cedula": "87654321",
            "nombre": "Ana",
            "apellido": "Gomez",
            "telefono": "04142222222",
            "email": "ana@example.com",
            "salario": "1000.00",
            "fecha_contratado": "2026-01-01",
            "carnet_medico": "MED-001",
            "tarifa": "50.00",
            "horario_dias": "Lunes a Viernes",
            "hora_entrada": "08:00:00",
            "hora_salida": "16:00:00",
            "especialidades": "Cardiología, Medicina interna"
        }
    ]
}
```

---

## 9.2 Obtener médico por cédula

### GET

```text
/api/medicos.php?cedula=87654321
```

Si no existe:

HTTP `404`.

```json
{
    "success": false,
    "error": "Médico no encontrado."
}
```

---

## 9.3 Registrar médico

### POST

```text
/api/medicos.php
```

Permisos:

```text
ADMIN
```

### JSON

```json
{
    "persona": {
        "cedula": "87654321",
        "nombre": "Ana",
        "apellido": "Gomez",
        "fecha_nacimiento": "1985-03-20",
        "telefono": "04142222222",
        "email": "ana@example.com",
        "direccion": "Calle 2"
    },
    "empleado": {
        "salario": "1000.00",
        "fecha_contratado": "2026-01-01",
        "clave_acceso": "12345",
        "id_horario": 1
    },
    "medico": {
        "carnet_medico": "MED-001",
        "tarifa": "50.00"
    },
    "especialidades": [1, 3]
}
```

### Respuesta

HTTP `201`

```json
{
    "success": true,
    "mensaje": "Médico y especialidades registrados con éxito."
}
```

---

## 9.4 Actualizar médico

### PUT

```text
/api/medicos.php
```

Permisos:

```text
ADMIN
MEDICO
```

### JSON

```json
{
    "cedula": "87654321",
    "medico": {
        "tarifa": "60.00"
    }
}
```

### Respuesta

```json
{
    "success": true,
    "mensaje": "Datos del médico actualizados correctamente."
}
```

---

# 10. Empleados

Endpoint:

```text
/api/empleados.php
```

## 10.1 Obtener empleados

### GET

```text
/api/empleados.php
```

Permisos:

```text
ADMIN
```

---

## 10.2 Obtener estadísticas generales

### GET

```text
/api/empleados.php?action=estadisticas
```

También:

```text
/api/empleados.php?estadisticas=true
```

Disponible para cualquier usuario autenticado.

### Respuesta

```json
{
    "success": true,
    "data": {
        "total_pacientes": 25,
        "citas_hoy": 8,
        "consultas_mes": 43,
        "estudios_pendientes": 6
    }
}
```

---

## 10.3 Registrar empleado

### POST

```text
/api/empleados.php
```

Permisos:

```text
ADMIN
```

### JSON

```json
{
    "persona": {
        "cedula": "11111111",
        "nombre": "Carlos",
        "apellido": "Lopez",
        "fecha_nacimiento": "1990-01-01",
        "telefono": "04143333333",
        "email": "carlos@example.com",
        "direccion": "Calle 3"
    },
    "empleado": {
        "salario": "800.00",
        "fecha_contratado": "2026-02-01",
        "clave_acceso": "12345",
        "rol": "RECEPCIONISTA",
        "id_horario": 1
    },
    "rol_especifico": {
        "estacion_trabajo": "Recepción principal",
        "extension_tlf": "101"
    }
}
```

Roles disponibles:

```text
ADMIN
RECEPCIONISTA
LABORATORISTA
MEDICO
```

### Respuesta

HTTP `201`

```json
{
    "success": true,
    "mensaje": "Empleado registrado con éxito."
}
```

La contraseña se almacena mediante un hash utilizando `password_hash()`.

---

# 11. Especialidades

Endpoint:

```text
/api/especialidades.php
```

## 11.1 Listar especialidades

### GET

```text
/api/especialidades.php
```

### Respuesta

```json
{
    "success": true,
    "data": [
        {
            "id_especialidad": 1,
            "nombre": "Cardiología",
            "descripcion": "Especialidad médica."
        }
    ]
}
```

---

## 11.2 Crear especialidad

### POST

```text
/api/especialidades.php
```

Permisos:

```text
ADMIN
```

### JSON

```json
{
    "action": "crear",
    "nombre": "Cardiología",
    "descripcion": "Especialidad médica."
}
```

### Respuesta

HTTP `201`

```json
{
    "success": true,
    "mensaje": "Especialidad creada con exito.",
    "id_especialidad": 1
}
```

---

## 11.3 Asignar especialidad a un médico

### POST

```text
/api/especialidades.php
```

### JSON

```json
{
    "action": "asignar",
    "cedula_medico": "87654321",
    "id_especialidad": 1
}
```

### Respuesta

```json
{
    "success": true,
    "mensaje": "Especialidad asignada al médico exitosamente."
}
```

---

# 12. Medicamentos

Endpoint:

```text
/api/medicamentos.php
```

## 12.1 Obtener todos

### GET

```text
/api/medicamentos.php
```

### Respuesta

```json
{
    "success": true,
    "data": [
        {
            "id_medicamento": 1,
            "nombre": "Paracetamol",
            "laboratorio": "Laboratorio X",
            "presentacion": "500 mg tabletas"
        }
    ]
}
```

---

## 12.2 Buscar medicamentos

### GET

```text
/api/medicamentos.php?q=paracetamol
```

También:

```text
/api/medicamentos.php?termino=paracetamol
```

### Respuesta

```json
{
    "success": true,
    "data": [
        {
            "id_medicamento": 1,
            "nombre": "Paracetamol",
            "laboratorio": "Laboratorio X",
            "presentacion": "500 mg tabletas"
        }
    ]
}
```

---

## 12.3 Registrar medicamento

### POST

```text
/api/medicamentos.php
```

Permisos:

```text
ADMIN
MEDICO
```

### JSON

```json
{
    "nombre": "Paracetamol",
    "laboratorio": "Laboratorio X",
    "presentacion": "500 mg tabletas"
}
```

### Respuesta

HTTP `201`

```json
{
    "success": true,
    "mensaje": "Medicamento registrado exitosamente.",
    "id_medicamento": 1
}
```

---

# 13. Estudios

Endpoint:

```text
/api/estudios.php
```

## 13.1 Obtener estudios de una consulta

### GET

```text
/api/estudios.php?id_consulta=10
```

---

## 13.2 Obtener estudios de un paciente

### GET

```text
/api/estudios.php?cedula_paciente=12345678
```

---

## 13.3 Obtener estudios pendientes

### GET

```text
/api/estudios.php
```

Devuelve los estudios cuyo estado es `SOLICITADO`.

---

## 13.4 Solicitar estudios

### POST

```text
/api/estudios.php
```

Permisos:

```text
MEDICO
ADMIN
```

### JSON

```json
{
    "action": "solicitar",
    "id_consulta": 10,
    "id_tipo_estudio": 1,
}
```

### Respuesta

HTTP `201`

```json
{
    "success": true,
    "mensaje": "Estudios solicitados exitosamente."
}
```

---

## 13.5 Completar estudio

### POST

```text
/api/estudios.php
```

Permisos:

```text
LABORATORISTA
ADMIN
```

### JSON

```json
{
    "action": "completar",
    "id_estudio": 5,
    "cedula_laboratorista": "22222222"
}
```

La cédula del laboratorista puede omitirse si existe una sesión activa.

### Respuesta

```json
{
    "success": true,
    "mensaje": "Estudio marcado como REALIZADO."
}
```

---

## 13.6 Cambiar estado

### POST

```text
/api/estudios.php
```

### JSON

```json
{
    "action": "cambiar_estado",
    "id_estudio": 5,
    "estado": "CANCELADO"
}
```

Estados válidos:

```text
SOLICITADO
REALIZADO
CANCELADO
```

### Respuesta

```json
{
    "success": true,
    "mensaje": "Estado actualizado a CANCELADO"
}
```

---

## 13.7 Operaciones mediante PUT

### PUT

```text
/api/estudios.php
```

### Completar

```json
{
    "action": "completar",
    "id_estudio": 5,
    "cedula_laboratorista": "22222222"
}
```

### Cambiar estado

```json
{
    "id_estudio": 5,
    "estado": "REALIZADO"
}
```

---

# 14. Recetas

Endpoint:

```text
/api/recetas.php
```

## 14.1 Obtener medicamentos de una consulta

### GET

```text
/api/recetas.php?id_consulta=10
```

### Respuesta

```json
{
    "success": true,
    "data": [
        {
            "id_receta": 1,
            "dosis": "500mg",
            "frecuencia": "Cada 8 horas",
            "duracion": "7 días",
            "indicaciones": "Tomar después de comer",
            "id_medicamento": 1,
            "medicamento": "Paracetamol",
            "presentacion": "500 mg tabletas"
        }
    ]
}
```

---

## 14.2 Obtener récipe completo

### GET

```text
/api/recetas.php?id_consulta=10&impresion=true
```

Devuelve información de la consulta, paciente, médico y medicamentos prescritos.

---

## 14.3 Registrar receta

### POST

```text
/api/recetas.php
```

Permisos:

```text
ADMIN
MEDICO
```

### JSON

```json
{
    "id_consulta": 10,
    "medicamentos": [
        {
            "id_medicamento": 1,
            "dosis": "500mg",
            "frecuencia": "Cada 8 horas",
            "duracion": "7 días",
            "indicaciones": "Tomar después de comer"
        },
        {
            "id_medicamento": 2,
            "dosis": "10mg",
            "frecuencia": "Cada 12 horas",
            "duracion": "5 días",
            "indicaciones": "No conducir."
        }
    ]
}
```

### Respuesta

HTTP `201`

```json
{
    "success": true,
    "mensaje": "Receta médica registrada exitosamente."
}
```

---

## 14.4 Eliminar medicamento de una receta

### DELETE

```text
/api/recetas.php?id_receta=1
```

Permisos:

```text
ADMIN
MEDICO
```

### Respuesta

```json
{
    "success": true,
    "mensaje": "Medicamento removido de la receta."
}
```

---

# 15. Resultados de estudios

Endpoint:

```text
/api/resultados.php
```

## 15.1 Obtener resultados de un estudio

### GET

```text
/api/resultados.php?id_estudio=5
```

### Respuesta

```json
{
    "success": true,
    "data": [
        {
            "id_resultado": 1,
            "descripcion": "Resultado del análisis",
            "ruta_archivo": "uploads/resultados/estudio_5_....pdf",
            "fecha": "2026-09-10 12:00:00",
            "id_estudio": 5
        }
    ]
}
```

---

## 15.2 Obtener resultados de una consulta

### GET

```text
/api/resultados.php?id_consulta=10
```

Devuelve los resultados pertenecientes a todos los estudios de la consulta.

---

## 15.3 Subir resultado

### POST

```text
/api/resultados.php
```

Permisos:

```text
LABORATORISTA
ADMIN
MEDICO
```

Este endpoint utiliza `multipart/form-data` para permitir la subida de archivos.

### Campos de FormData

```text
id_estudio = 5
descripcion = Resultado del análisis
archivo = resultado.pdf
```

El campo del archivo debe llamarse:

```text
archivo
```

### Ejemplo JavaScript

```javascript
const formData = new FormData();

formData.append("id_estudio", "5");
formData.append("descripcion", "Resultado del análisis");
formData.append("archivo", archivoSeleccionado);

fetch("http://localhost:8000/api/resultados.php", {
    method: "POST",
    body: formData
})
.then(response => response.json())
.then(data => {
    console.log(data);
});
```

### Respuesta

HTTP `201`

```json
{
    "success": true,
    "mensaje": "Resultado registrado con éxito.",
    "id_resultado": 1,
    "ruta_archivo": "uploads/resultados/estudio_5_....pdf"
}
```

---

## 15.4 Eliminar resultado

### DELETE

```text
/api/resultados.php?id_resultado=1
```

Permisos:

```text
LABORATORISTA
ADMIN
```

### Respuesta

```json
{
    "success": true,
    "mensaje": "Resultado y archivo asociado eliminados."
}
```

---

# 16. Ejemplos de integración con JavaScript

## GET

```javascript
fetch("http://localhost:8000/api/pacientes.php")
    .then(response => response.json())
    .then(data => {
        console.log(data.data);
    });
```

## POST

```javascript
fetch("http://localhost:8000/api/medicamentos.php", {
    method: "POST",
    headers: {
        "Content-Type": "application/json"
    },
    body: JSON.stringify({
        nombre: "Paracetamol",
        laboratorio: "Laboratorio X",
        presentacion: "500 mg tabletas"
    })
})
.then(response => response.json())
.then(data => {
    console.log(data);
});
```

## PUT

```javascript
fetch("http://localhost:8000/api/pacientes.php", {
    method: "PUT",
    headers: {
        "Content-Type": "application/json"
    },
    body: JSON.stringify({
        cedula: "12345678",
        persona: {
            telefono: "04141111111"
        }
    })
})
.then(response => response.json())
.then(data => {
    console.log(data);
});
```

## DELETE

```javascript
fetch("http://localhost:8000/api/pacientes.php?cedula=12345678", {
    method: "DELETE"
})
.then(response => response.json())
.then(data => {
    console.log(data);
});
```

---



# 18. Flujo de un registro

Por ejemplo, para registrar un paciente:

```text
Formulario del frontend
        │
        │ JSON
        ▼
POST /api/pacientes.php
        │
        ▼
Paciente.php
        │
        │ INSERT
        ▼
PostgreSQL
        │
        ▼
Respuesta JSON
        │
        ▼
Frontend
```

Ejemplo de respuesta:

```json
{
    "success": true,
    "mensaje": "Paciente registrado exitosamente."
}
```

De esta manera el frontend puede realizar las operaciones del sistema sin acceder directamente a PostgreSQL.