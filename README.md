# CliniData
Sistema de Gestión de Citas Medicas

<div align="center">
  <h1>🏥 Sistema de Gestión de Citas Médicas</h1>
  <h3>Proyecto de Base de Datos II</h3>
  <p>Universidad Nacional Experimental de Guayana (UNEG)</p>
</div>

---

## 📖 Contexto del Proyecto

Una clínica privada busca optimizar y modernizar la gestión de sus citas médicas, historiales clínicos, estudios de laboratorio y resultados de exámenes. Actualmente, el manejo fragmentado de la información dificulta la consulta eficiente del historial completo de los pacientes.

### ⚠️ Problemática Actual
- 📝 Las citas se agendan manualmente, ocasionando pérdida de registros.
- 🗂️ Los historiales clínicos se encuentran dispersos.
- 📉 Ausencia de control centralizado para los estudios de laboratorio solicitados.
- ⏱️ Los médicos carecen de acceso rápido a la información integral del paciente.
- 🔍 Los pacientes enfrentan dificultades para consultar sus citas y resultados de exámenes.

---

## 🎯 Objetivos y Alcance del Sistema

### 👥 Usuarios del Sistema
- Pacientes
- Médicos
- Recepcionistas
- Administradores de la clínica
- Personal de laboratorio

### ⚙️ Procesos Principales
1. Registrar pacientes.
2. Agendar citas médicas.
3. Registrar consultas médicas.
4. Solicitar estudios de laboratorio.
5. Cargar resultados de exámenes.
6. Consultar historial clínico.
7. Generar reportes de actividad médica.

### 📦 Objetos Iniciales de la Base de Datos
`Paciente` | `Médico` | `Cita` | `Consulta` | `Estudio` | `Resultado` | `Especialidad`

---

## 🛠️ Especificación Tecnológica y Enfoque de Datos

- **SGBD Asignado:** PostgreSQL (Enfoque Relacional / Híbrido Estricto).
- **Manejo de Datos Especializados:**
  - ⏳ **Datos Temporales:** Manejo de rangos de disponibilidad médica y prevención de solapamiento de citas utilizando `tsrange`.
  - 🖼️ **Multimedia/Binarios:** Almacenamiento y gestión segura de archivos e imágenes correspondientes a exámenes y estudios médicos.

---

## ❓ Preguntas de Negocio (Consultas Clave)
El sistema está diseñado para responder eficientemente a las siguientes interrogantes:
- ¿Qué citas tiene programadas un paciente?
- ¿Qué pacientes ha atendido un médico en una fecha determinada?
- ¿Qué estudios tiene pendientes un paciente?
- ¿Cuáles son los resultados de los exámenes de un paciente?
- ¿Cuántas consultas se realizaron en un mes?
- ¿Qué médicos pertenecen a una especialidad?
- ¿Qué pacientes tienen citas para hoy?
- ¿Cuál es el historial de consultas de un paciente?
- ¿Qué estudios se han solicitado con mayor frecuencia?
- ¿Qué médicos tienen mayor cantidad de citas agendadas?

---

## 👥 Equipo de Trabajo

**👩‍🏫 Profesora:** Ana Sosa  
**📚 Materia:** Base de Datos II  

| Integrante | Cédula de Identidad |
| :--- | :--- |
| **Aarom Luces** | C.I: 28.162.993 |
| **Angie Urrieta** | C.I: 31.538.385 |
| **Lisbelis Yemes** | C.I: 30.437.441 |
| **Celimar Rojas** | C.I: 31.981.398 |
| **David Ezagui** | C.I: 24.963.880 |

---
