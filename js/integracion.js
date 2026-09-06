document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // MÓDULO: CARGAR RESUMEN (DASHBOARD)
    // ==========================================
    async function cargarResumen() {
        try {
            // Agregamos { cache: 'no-store' } para evitar que el navegador recicle datos viejos
            const respuesta = await fetch('../clinica-backend/api/empleados.php?action=estadisticas', {
                method: 'GET',
                cache: 'no-store'
            });
            
            if (respuesta.ok) {
                const datos = await respuesta.json();
                
                if (datos.success) {
                    document.getElementById('resumen_pacientes').textContent = datos.data.total_pacientes || 0;
                    document.getElementById('resumen_citas_hoy').textContent = datos.data.citas_hoy || 0;
                    // Cambiamos a los nombres exactos que envía el backend
                    document.getElementById('resumen_citas_confirmadas').textContent = datos.data.consultas_mes || 0;
                    document.getElementById('resumen_consultorios').textContent = datos.data.estudios_pendientes || 0;
                }
            }
        } catch (error) {
            console.error("Error al cargar el resumen del dashboard:", error);
        }
    }

    cargarResumen();
    
    // ==========================================
    // 1. MÓDULO: REGISTRO DE PACIENTE
    // ==========================================
    const formRegistroPaciente = document.querySelector('#paciente-registro form');

    if (formRegistroPaciente) {
        formRegistroPaciente.addEventListener('submit', async (evento) => {
            evento.preventDefault(); // Evita que la página se recargue

            const letraCed = document.getElementById('reg_cedula_letra').value;
            const numCed = document.getElementById('reg_cedula_numero').value;
            const cedulaSegura = `${letraCed}-${numCed}`;

            const prefijoTel = document.getElementById('reg_telefono_prefijo').value;
            const numTel = document.getElementById('reg_telefono_numero').value;
            const telefonoSeguro = numTel ? `${prefijoTel}-${numTel}` : '';

           // 2. Construimos el JSON usando estrictamente los IDs que empiezan con 'reg_'
            const payload = {
                persona: {
                    cedula: cedulaSegura,
                    nombre: document.getElementById('reg_nombre').value,
                    apellido: document.getElementById('reg_apellido').value,
                    fecha_nacimiento: document.getElementById('reg_fecha_nacimiento').value,
                    telefono: telefonoSeguro, // Usamos la variable segura que acabamos de armar
                    email: document.getElementById('reg_email').value,
                    direccion: document.getElementById('reg_direccion').value
                },
                paciente: {
                    genero: document.getElementById('reg_genero').value.substring(0, 1).toUpperCase(),
                    tipo_sangre: document.getElementById('reg_tipo_sangre').value
                }
            };

            try {
                // Ejecutar la petición asíncrona a la API
                const respuesta = await fetch('../clinica-backend/api/pacientes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const datos = await respuesta.json();

                // Manejo de la respuesta
                if (respuesta.ok && datos.success) {
                    alert('¡' + datos.mensaje + '!');
                    formRegistroPaciente.reset(); // Limpiar el formulario
                } else {
                    alert('Error del servidor: ' + datos.error);
                }
            } catch (error) {
                console.error("Error de red o conexión:", error);
                alert("Ocurrió un error al intentar conectar con el servidor.");
            }
        });
    }

    // ==========================================
    // 2. MÓDULO: REGISTRO DE CITA
    // ==========================================
    const formCita = document.querySelector('#cita-registro form');

    if (formCita) {
        formCita.addEventListener('submit', async (evento) => {
            evento.preventDefault(); 

            // Armar el JSON con los campos exactos requeridos por citas.php
            const payload = {
                cedula_paciente: document.getElementById('reg_cedula_paciente').value,
                cedula_medico: document.getElementById('reg_cedula_medico').value,
                consultorio: document.getElementById('reg_consultorio').value,
                // Reemplazamos la "T" del input datetime por un espacio para PostgreSQL
                fecha_inicio: document.getElementById('reg_rango_cita_inicio').value.replace('T', ' '),
                fecha_fin: document.getElementById('reg_rango_cita_fin').value.replace('T', ' ')
            };

            try {
                const respuesta = await fetch('../clinica-backend/api/citas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const datos = await respuesta.json();

                // Manejo de respuesta, incluyendo colisiones de horario (409 Conflict)
                if (respuesta.ok && datos.success) {
                    alert('¡Éxito! ' + datos.mensaje);
                    formCita.reset(); 
                } else {
                    alert('Error al agendar la cita: ' + datos.error);
                }
            } catch (error) {
                console.error("Error en la petición de citas:", error);
                alert("Ocurrió un error al intentar conectar con el servidor.");
            }
        });
    }

    // ==========================================
    // 3. MÓDULO: EDITAR PACIENTE
    // ==========================================
    const formEditarPaciente = document.querySelector('#paciente-edicion form');

    if (formEditarPaciente) {
        formEditarPaciente.addEventListener('submit', async (evento) => {
            evento.preventDefault(); 

            // 1. Armamos los datos leyendo directamente de las cajas visibles para evitar fallos del campo oculto
            const letraCed = document.getElementById('edit_cedula_letra').value;
            const numCed = document.getElementById('edit_cedula_numero').value;
            const cedulaSegura = `${letraCed}-${numCed}`;

            const prefijoTel = document.getElementById('edit_telefono_prefijo').value;
            const numTel = document.getElementById('edit_telefono_numero').value;
            const telefonoSeguro = numTel ? `${prefijoTel}-${numTel}` : '';

            // 2. Construimos el JSON tal como lo exige el case 'PUT' de pacientes.php
            const payload = {
                cedula: cedulaSegura, // Identificador principal que pide el PHP
                persona: {
                    cedula: cedulaSegura,
                    nombre: document.getElementById('edit_nombre').value,
                    apellido: document.getElementById('edit_apellido').value,
                    fecha_nacimiento: document.getElementById('edit_fecha_nacimiento').value,
                    telefono: telefonoSeguro,
                    email: document.getElementById('edit_email').value,
                    direccion: document.getElementById('edit_direccion').value
                },
                paciente: {
                    genero: document.getElementById('edit_genero').value.substring(0, 1).toUpperCase(),
                    tipo_sangre: document.getElementById('edit_tipo_sangre').value
                }
            };

            try {
                const respuesta = await fetch('../clinica-backend/api/pacientes.php', {
                    method: 'PUT', 
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const datos = await respuesta.json();

                if (respuesta.ok && datos.success) {
                    alert('¡Actualización exitosa! ' + datos.mensaje);
                    cargarResumen(); // Refresca los números del panel
                } else {
                    alert('Error al actualizar: ' + datos.error);
                }
            } catch (error) {
                console.error("Error en la petición de edición:", error);
                alert("Ocurrió un error al intentar conectar con el servidor.");
            }
        });
    }

    // ==========================================
    // 4. MÓDULO: EDITAR CITA
    // ==========================================
    const formEditarCita = document.querySelector('#cita-edicion form');

    if (formEditarCita) {
        formEditarCita.addEventListener('submit', async (evento) => {
            evento.preventDefault(); 

            // Extraemos el ID de la cita desde el input oculto en el HTML
            const idCita = formEditarCita.querySelector('input[name="id_cita"]').value;

            // Armamos el JSON con el ID, el nuevo estado y las fechas ajustadas sin la "T"
            const payload = {
                id_cita: idCita,
                nuevo_estado: document.getElementById('edit_estado').value,
                fecha_inicio: document.getElementById('edit_rango_cita_inicio').value.replace('T', ' '),
                fecha_fin: document.getElementById('edit_rango_cita_fin').value.replace('T', ' ')
            };

            try {
                const respuesta = await fetch('../clinica-backend/api/citas.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const datos = await respuesta.json();

                // Si se detecta un solapamiento (409 Conflict), entrará al else y mostrará el error
                if (respuesta.ok && datos.success) {
                    alert('¡Actualización de cita exitosa! ' + datos.mensaje);
                    cargarResumen(); // Refrescamos los números del dashboard
                } else {
                    alert('Error al actualizar la cita: ' + datos.error);
                }
            } catch (error) {
                console.error("Error en la petición de edición de cita:", error);
                alert("Ocurrió un error al intentar conectar con el servidor.");
            }
        });
    }

    // ==========================================
    // 5. MÓDULO: BUSCAR PACIENTE PARA EDITAR
    // ==========================================
    const btnBuscarPaciente = document.getElementById('btn_buscar_paciente');

    if (btnBuscarPaciente) {
        btnBuscarPaciente.addEventListener('click', async () => {
            const letra = document.getElementById('edit_cedula_letra').value;
            const numero = document.getElementById('edit_cedula_numero').value;

            if (!numero) {
                alert("Por favor, ingrese el número de cédula que desea buscar.");
                return;
            }

            const cedulaBusqueda = `${letra}-${numero}`;

            try {
                // Hacemos una petición GET al backend pasando la cédula por la URL
                const respuesta = await fetch(`../clinica-backend/api/pacientes.php?cedula=${cedulaBusqueda}`);
                const datos = await respuesta.json();

                if (respuesta.ok && datos.success) {
                    const paciente = datos.data; 
                    
                    // Rellenamos los campos del formulario con los datos reales
                    document.getElementById('edit_nombre').value = paciente.nombre || '';
                    document.getElementById('edit_apellido').value = paciente.apellido || '';
                    document.getElementById('edit_fecha_nacimiento').value = paciente.fecha_nacimiento || '';
                    document.getElementById('edit_email').value = paciente.email || '';
                    document.getElementById('edit_direccion').value = paciente.direccion || '';
                    document.getElementById('edit_genero').value = (paciente.genero === 'M') ? 'Masculino' : ((paciente.genero === 'F') ? 'Femenino' : 'Otro');
                    document.getElementById('edit_tipo_sangre').value = paciente.tipo_sangre || 'O+';
                    
                    // Separar el teléfono si viene con guion (ej: 0414-1234567)
                    if (paciente.telefono && paciente.telefono.includes('-')) {
                        const partesTel = paciente.telefono.split('-');
                        document.getElementById('edit_telefono_prefijo').value = partesTel[0];
                        document.getElementById('edit_telefono_numero').value = partesTel[1];
                    } else {
                        document.getElementById('edit_telefono_numero').value = paciente.telefono || '';
                    }

                    alert("Datos del paciente cargados correctamente.");
                } else {
                    alert('No se encontró ningún paciente con esa cédula.');
                    // Limpiar campos si no existe
                    document.getElementById('paciente-edicion').querySelector('form').reset();
                    document.getElementById('edit_cedula_numero').value = numero; // Mantener el número que buscó
                }
            } catch (error) {
                console.error("Error al buscar paciente:", error);
                alert("Ocurrió un error al intentar consultar el servidor.");
            }
        });
    }
});