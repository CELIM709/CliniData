// Este archivo esta CURSEDO y NO SE DEBE MODIFICAR xd
// Nota: no implementar logica de diferentes usuarios en un solo archivo MONOLITICO D:

document.addEventListener('DOMContentLoaded', () => {
    const API = '../clinica-backend/api/';
    const $ = (id) => document.getElementById(id);
    const val = (id) => ($(id)?.value || '').trim();
    const set = (id, text) => { if ($(id)) $(id).value = text ?? ''; };
    const text = (id, content) => { if ($(id)) $(id).textContent = content ?? ''; };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));
    const appointmentOptions = new Map();

    async function api(endpoint, options = {}) {
        const response = await fetch(API + endpoint, {
            cache: 'no-store', credentials: 'same-origin', ...options,
            headers: options.body instanceof FormData ? options.headers : { 'Content-Type': 'application/json', ...(options.headers || {}) }
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.success === false || data.status === 'error') throw new Error(data.error || data.message || `Error HTTP ${response.status}`);
        return data;
    }

    function message(content, isError = false) {
        window.alert(content);
        console[isError ? 'error' : 'info'](content);
    }

    function id(letter, number) { return `${val(letter)}-${val(number)}`; }
    function timestamp(field) { return val(field).replace('T', ' '); }
    function currentDateTimeLocal() {
        const now = new Date();
        const pad = (value) => String(value).padStart(2, '0');
        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    }
    function configureAppointmentStartLimits() {
        ['reg_rango_cita_inicio', 'edit_rango_cita_inicio'].forEach((field) => {
            $(field)?.setAttribute('min', currentDateTimeLocal());
        });
    }
    function phone(prefix, number) { return val(number) ? `${val(prefix)}-${val(number)}` : ''; }
    function splitPhone(value, prefix, number) {
        if (!value) {
            set(prefix, '0424');
            set(number, '');
            return;
        }

        // Limpiamos espacios o caracteres extra
        const str = String(value).trim();

        // Caso 1: Viene formateado con guion (ej: "0424-1234567")
        if (str.includes('-')) {
            const parts = str.split('-');
            set(prefix, parts[0]);
            set(number, parts.slice(1).join(''));
            return;
        }

        // Caso 2: Viene todo junto con 11 dígitos (ej: "04241234567")
        if (str.length === 11) {
            set(prefix, str.slice(0, 4));  // Toma los primeros 4 dígitos ("0424")
            set(number, str.slice(4));     // Toma el resto ("1234567")
            return;
        }

        // Caso 3: Viene sin el '0' inicial y tiene 10 dígitos (ej: "4241234567")
        if (str.length === 10) {
            set(prefix, '0' + str.slice(0, 3)); // Le añade el 0 ("0424")
            set(number, str.slice(3));
            return;
        }

        // Caso por defecto si el formato es desconocido
        set(prefix, '0424');
        set(number, str);
    }

    function splitCedula(value) {
        if (!value) return { letter: 'V', number: '' };
        const str = String(value).trim();

        // Caso 1: Viene con guion ("V-30437441" o "E-12345678")
        if (str.includes('-')) {
            const parts = str.split('-');
            return { letter: parts[0].toUpperCase(), number: parts[1] || '' };
        }

        // Caso 2: Empieza por V o E sin guion ("V30437441")
        const match = str.match(/^([VEve])\s*(\d+)$/);
        if (match) {
            return { letter: match[1].toUpperCase(), number: match[2] };
        }

        // Caso 3: Solo trae números ("30437441")
        return { letter: 'V', number: str.replace(/\D/g, '') };
    }

    function bind(selector, callback) {
        const form = document.querySelector(selector);
        if (!form) return;
        form.removeAttribute('action');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!form.reportValidity()) return;
            try { await callback(form); } catch (error) { message(error.message || 'No se pudo completar la operación.', true); }
        });
    }

    async function session() {
        try { return (await api('check_session.php')).usuario; }
        catch (error) { if (!location.pathname.endsWith('login.html')) location.href = 'login.html'; return null; }
    }

    async function cerrarSesion(e) {
    if (e) e.preventDefault();

    try {
        // 1. Petición a tu API de logout para destruir $_SESSION
        await fetch(API + 'logout.php', { 
            method: 'POST' // O GET, según esté configurada tu API
        });
    } catch (error) {
        console.error('Error al cerrar sesión en el servidor:', error);
    } finally {
        // 2. Limpiar cualquier estado local guardado
        localStorage.clear();
        sessionStorage.clear();

        // 3. Redirigir reemplazando la entrada del historial
        window.location.replace('login.html');
    }
}

// Vincular evento al botón
document.getElementById('btn-logout')?.addEventListener('click', cerrarSesion);

    document.querySelectorAll('a[href="login.html"]').forEach((link) => link.addEventListener('click', async (event) => {
        event.preventDefault();
        try { await api('logout.php', { method: 'POST' }); } finally { location.href = 'login.html'; }
    }));

    async function receptionSummary() {
        if (!$('resumen_pacientes')) return;
        const stats = (await api('empleados.php?action=estadisticas')).data || {};
        text('resumen_pacientes', stats.total_pacientes || 0); text('resumen_citas_hoy', stats.citas_hoy || 0);
        text('resumen_citas_confirmadas', stats.consultas_mes || 0); text('resumen_consultorios', stats.estudios_pendientes || 0);
    }

    async function loadTodayAppointments() {
        const list = $('citas-del-dia'); if (!list) return;
        const appointments = (await api('citas.php?action=hoy')).data || [];
        list.innerHTML = appointments.length ? appointments.map((item) => `<tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 12px 8px;">${escapeHtml(item.id_cita)}</td><td style="padding: 12px 8px;">${escapeHtml(`${item.paciente_nombre} ${item.paciente_apellido}`)}</td><td style="padding: 12px 8px;">${escapeHtml(`${item.medico_nombre} ${item.medico_apellido}`)}</td><td style="padding: 12px 8px;">${escapeHtml(String(item.fecha_inicio || '').slice(11, 16))} - ${escapeHtml(String(item.fecha_fin || '').slice(11, 16))}</td><td style="padding: 12px 8px;">${escapeHtml(item.consultorio)}</td><td style="padding: 12px 8px;">${escapeHtml(item.estado)}</td><td style="padding: 12px 8px;">${item.estado === 'PENDIENTE'  ? `<button type="button" class="btn btn-primary" data-cita-estado="CONFIRMADA" data-id-cita="${escapeHtml(item.id_cita)}" style="padding: 8px 12px; margin-right: 6px;">Confirmar</button><button type="button" class="btn btn-outline" data-cita-estado="CANCELADA" data-id-cita="${escapeHtml(item.id_cita)}" style="padding: 8px 12px;">Cancelar</button>` : 'Sin acciones'}</td></tr>`).join('') : '<tr><td colspan="7" style="padding: 14px 8px;">No hay citas para hoy.</td></tr>';
    }
 // Seccion de citas y esas cosas:
    // Variable global para guardar las citas en memoria y poder filtrarlas sin recargar
    let listaTodasLasCitas = [];

    async function loadAllAppointments() {
        const list = $('todas-las-citas'); 
        if (!list) return;

        try {
            const response = await api('citas.php?action=todas');
            listaTodasLasCitas = response.data || [];
            renderTablaTodasLasCitas(listaTodasLasCitas);
        } catch (error) {
            list.innerHTML = '<tr><td colspan="8" style="padding: 14px 8px; color: red;">Error al cargar el listado.</td></tr>';
        }
    }

    function renderTablaTodasLasCitas(appointments) {
        const list = $('todas-las-citas');
        if (!list) return;

        if (!appointments.length) {
            list.innerHTML = '<tr><td colspan="8" style="padding: 14px 8px; text-align: center;">No se encontraron citas.</td></tr>';
            return;
        }

        list.innerHTML = appointments.map((item) => {
            const idCita = escapeHtml(item.id_cita);
            const fecha = escapeHtml(String(item.fecha_inicio || item.fecha || '').slice(0, 10));
            const horaInicio = escapeHtml(String(item.fecha_inicio || '').slice(11, 16));
            const horaFin = escapeHtml(String(item.fecha_fin || '').slice(11, 16));
            const paciente = escapeHtml(`${item.paciente_nombre} ${item.paciente_apellido}`);
            const medico = escapeHtml(`${item.medico_nombre} ${item.medico_apellido}`);
            const consultorio = escapeHtml(item.consultorio);
            const estado = escapeHtml(item.estado);

            // Estilos de color según el estado
            const estadoNorm = (item.estado || '').toLowerCase();
            let badgeStyle = 'background: #e2e8f0; color: #475569;'; // Por defecto (gris)

            if (estadoNorm === 'confirmada') badgeStyle = 'background: #dcfce7; color: #166534;';       // Verde
            if (estadoNorm === 'pendiente') badgeStyle = 'background: #fef9c3; color: #854d0e;';        // Amarillo
            if (estadoNorm === 'completada' || estadoNorm === 'atendida') badgeStyle = 'background: #e0f2fe; color: #075985;'; // Azul
            if (estadoNorm === 'cancelada') badgeStyle = 'background: #fee2e2; color: #991b1b;';        // Rojo

            // Construcción de botones según el estado actual
            let accionesHtml = '';

            if (estadoNorm === 'pendiente') {
                accionesHtml += `<button type="button" class="btn btn-outline" data-accion="editar" data-id-cita="${idCita}" style="padding: 6px 10px; margin-right: 4px;">Editar</button>`;
                accionesHtml += `<button type="button" class="btn btn-primary" data-cita-estado="CONFIRMADA" data-id-cita="${idCita}" style="padding: 6px 10px; margin-right: 4px;">Confirmar</button>`;
                accionesHtml += `<button type="button" class="btn btn-danger" data-cita-estado="CANCELADA" data-id-cita="${idCita}" style="padding: 6px 10px;">Cancelar</button>`;
            }
            else if (estadoNorm === 'cancelada') {
                accionesHtml += `<button type="button" class="btn btn-outline" data-accion="editar" data-id-cita="${idCita}" style="padding: 6px 10px; margin-right: 4px;">Editar</button>`;
            }
             else {
                accionesHtml = '<span style="color: #888; font-size: 0.85em;">Sin acciones</span>';
            }

            return `
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 12px 8px;">${idCita}</td>
                    <td style="padding: 12px 8px;">${fecha}</td>
                    <td style="padding: 12px 8px;">${paciente}</td>
                    <td style="padding: 12px 8px;">${medico}</td>
                    <td style="padding: 12px 8px;">${horaInicio} - ${horaFin}</td>
                    <td style="padding: 12px 8px;">${consultorio}</td>
                    <td style="padding: 12px 8px;">
                        <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; ${badgeStyle}">
                            ${estado}
                        </span>
                    </td>
                    <td style="padding: 12px 8px;">${accionesHtml}</td>
                </tr>
            `;
        }).join('');
    }

    // 1. LÓGICA DE FILTRADO (escucha el <select> sin depender de atributos HTML inline)
    function filtrarTodasLasCitas() {
        const estadoSeleccionado = $('filtro_estado_citas')?.value || 'TODOS';
        
        if (estadoSeleccionado === 'TODOS') {
            renderTablaTodasLasCitas(listaTodasLasCitas);
        } else {
            const citasFiltradas = listaTodasLasCitas.filter(cita => cita.estado === estadoSeleccionado);
            renderTablaTodasLasCitas(citasFiltradas);
        }
    }

    // Escuchador de cambio para el select del filtro
    $('filtro_estado_citas')?.addEventListener('change', filtrarTodasLasCitas);


    // 2. ESCUCHADOR DE BOTONES PARA "TODAS LAS CITAS" (Confirmar, Cancelar, Editar)
    $('todas-las-citas')?.addEventListener('click', async (event) => {
        const button = event.target.closest('button');
        if (!button) return;

        const idCita = button.dataset.idCita;
        const nuevoEstado = button.dataset.citaEstado;
        const accion = button.dataset.accion;

        // Acción: Editar cita
        if (accion === 'editar') {
            const seccionEdicion = $('cita-edicion');
            const resumenSection = $('resumen-dashboard');
            
            if (seccionEdicion && $('id_cita')) {
                seccionEdicion.classList.remove('hidden');
                resumenSection.classList.add('hidden');

                // Guardar referencia en memoria
                const cita = listaTodasLasCitas.find(item => String(item.id_cita) === String(idCita));
                if (cita) {
                    appointmentOptions.set(String(idCita), cita);
                }

                // Asignar ID y disparar 'change' para autorellenar todo el formulario de forma limpia
                set('id_cita', idCita);
                $('id_cita').dispatchEvent(new Event('change'));

                seccionEdicion.scrollIntoView({ behavior: 'smooth' });
            }
            return;
        }

        // Acción: Confirmar / Cancelar estado
        if (nuevoEstado) {
            button.disabled = true;
            try {
                await api('citas.php', { 
                    method: 'PUT', 
                    body: JSON.stringify({ id_cita: idCita, nuevo_estado: nuevoEstado }) 
                });
                
                // Actualizar contadores y ambas tablas en pantalla
                await receptionSummary();
                await loadTodayAppointments();
                await loadAllAppointments();
                
                message(`Cita ${nuevoEstado === 'CONFIRMADA' ? 'confirmada' : 'cancelada'} correctamente.`);
            } catch (error) {
                button.disabled = false;
                message(error.message, true);
            }
        }
    });


    // Buscador en tiempo real para Recepción (Paciente + Médico)
    $('buscar_cita_recepcion')?.addEventListener('input', (event) => {
        const term = event.target.value.trim().toLowerCase();

        const filtered = listaTodasLasCitas.filter((item) => {
            // Datos del Paciente
            const cedulaPac = String(item.cedula_paciente || item.paciente_cedula || '').toLowerCase();
            const nombrePac = `${item.paciente_nombre || ''} ${item.paciente_apellido || ''}`.toLowerCase();

            // Datos del Médico
            const cedulaMed = String(item.cedula_medico || item.medico_cedula || '').toLowerCase();
            const nombreMed = `${item.medico_nombre || ''} ${item.medico_apellido || ''}`.toLowerCase();

            const fecha = String(item.fecha_inicio || item.fecha || '').toLowerCase();

            // Retorna true si coincide con CUALQUIERA de los campos
            return cedulaPac.includes(term) || 
                nombrePac.includes(term) || 
                cedulaMed.includes(term) || 
                nombreMed.includes(term) ||
                fecha.includes(term);
        });

        renderTablaTodasLasCitas(filtered); 
    });

    async function loadDoctorAppointments(user) {
        const list = $('mis-citas-lista'); if (!list) return;
        const appointments = (await api(`citas.php?medico=${encodeURIComponent(user.cedula)}`)).data || [];
        list.innerHTML = appointments.length ? appointments.map((item) => `<tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 12px 8px;">${escapeHtml(item.id_cita)}</td><td style="padding: 12px 8px;">${escapeHtml(`${item.paciente_nombre} ${item.paciente_apellido}`)}</td><td style="padding: 12px 8px;">${escapeHtml(String(item.fecha_inicio || '').slice(0, 16))} - ${escapeHtml(String(item.fecha_fin || '').slice(11, 16))}</td><td style="padding: 12px 8px;">${escapeHtml(item.consultorio)}</td><td style="padding: 12px 8px;">${escapeHtml(item.estado)}</td><td style="padding: 12px 8px;">${item.estado === 'CONFIRMADA' ? `<button type="button" class="btn btn-outline" data-usar-cita="${escapeHtml(item.id_cita)}" data-cedula-paciente="${escapeHtml(item.cedula_paciente)}" style="padding: 8px 12px;">Usar cita</button>` : 'Sin acciones'}</td></tr>`).join('') : '<tr><td colspan="6" style="padding: 14px 8px;">No tienes citas registradas.</td></tr>';
    }

    $('mis-citas-lista')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-usar-cita]');
        if (!button) return;
        const [letter, number] = String(button.dataset.cedulaPaciente || '').split('-');
        set('cons_cedula_paciente_letra', letter); set('cons_cedula_paciente_numero', number); set('cons_cedula_paciente', button.dataset.cedulaPaciente); set('cons_id_cita', button.dataset.usarCita);
        set('cons_costo', val('perfil_tarifa').replace(/[^0-9.]/g, ''));
        document.querySelector('#form-registro-consulta')?.scrollIntoView({ behavior: 'smooth' });
        message(`Cita ${button.dataset.usarCita} seleccionada.`);
    });

    $('citas-del-dia')?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-cita-estado]');
        if (!button) return;
        button.disabled = true;
        try {
            await api('citas.php', { method: 'PUT', body: JSON.stringify({ id_cita: button.dataset.idCita, nuevo_estado: button.dataset.citaEstado }) });
            await receptionSummary(); await loadTodayAppointments();
            message(`Cita ${button.dataset.citaEstado === 'CONFIRMADA' ? 'confirmada' : 'cancelada'} correctamente.`);
        } catch (error) {
            button.disabled = false;
            message(error.message, true);
        }
    });

    async function adminSummary() {
        if (!$('total_empleados')) return;
        const employees = (await api('empleados.php')).data || [];
        const count = (role) => employees.filter((item) => String(item.rol).toUpperCase() === role).length;
        text('total_recepcionistas', count('RECEPCIONISTA')); text('total_laboratoristas', count('LABORATORISTA'));
        text('total_medicos', count('MEDICO')); text('total_empleados', employees.length);
    }

    function employee(prefix, role, specific) {
        return {
            persona: { cedula: id(`${prefix}_cedula_letra`, `${prefix}_cedula_numero`), nombre: val(`${prefix}_nombre`), apellido: val(`${prefix}_apellido`), fecha_nacimiento: val(`${prefix}_fecha_nacimiento`), telefono: phone(`${prefix}_telefono_prefijo`, `${prefix}_telefono_numero`), email: val(`${prefix}_email`), direccion: val(`${prefix}_direccion`) },
            empleado: { salario: val(`${prefix}_salario`), fecha_contratado: val(`${prefix}_fecha_contratado`), id_horario: val(`${prefix}_id_horario`), clave_acceso: val(`${prefix}_clave_acceso`), rol: role },
            rol_especifico: specific
        };
    }

    bind('#form-registro-recepcionista', async (form) => {
        await api('empleados.php', { method: 'POST', body: JSON.stringify(employee('rec', 'RECEPCIONISTA', { estacion_trabajo: val('rec_estacion_trabajo'), extension_tlf: phone('rec_extension_tlf_prefijo', 'rec_extension_tlf_numero') })) });
        form.reset(); await adminSummary(); message('Recepcionista registrada correctamente.');
    });
    bind('#form-registro-laboratorista', async (form) => {
        await api('empleados.php', { method: 'POST', body: JSON.stringify(employee('lab', 'LABORATORISTA', { carnet_bioanalista: `M.P.P.S. ${val('lab_carnet_bioanalista_numero')}`, area: val('lab_area') })) });
        form.reset(); await adminSummary(); message('Laboratorista registrado correctamente.');
    });
    
    document.getElementById('form-registro-medico').addEventListener('submit', async function (e) {
        e.preventDefault();

        // 1. Concatenar los campos compuestos
        const cedulaLetra = document.getElementById('med_cedula_letra').value;
        const cedulaNum = document.getElementById('med_cedula_numero').value.trim();
        const cedulaCompleta = `${cedulaLetra}-${cedulaNum}`; // Ej: V-30437441

        const telPrefijo = document.getElementById('med_telefono_prefijo').value;
        const telNum = document.getElementById('med_telefono_numero').value.trim();
        const telefonoCompleto = telNum ? `${telPrefijo}${telNum}` : null; // Ej: 04249500568

        const carnetNum = document.getElementById('med_carnet_medico_numero').value.trim();
        const carnetCompleto = `M.P.P.S. ${carnetNum}`; // Ej: M.P.P.S. 112345

        // 2. Extraer los IDs de las especialidades seleccionadas (<select multiple>)
        const selectEspecialidades = document.getElementById('form_med_especialidades');
        const especialidades = Array.from(selectEspecialidades.selectedOptions)
            .map(option => parseInt(option.value, 10))
            .filter(id => !isNaN(id));

        if (especialidades.length === 0) {
            alert('Por favor, seleccione al menos una especialidad.');
            return;
        }

        // 3. Agrupar la información en la estructura JSON requerida por el PHP
        const payload = {
            persona: {
                cedula: cedulaCompleta,
                nombre: document.getElementById('med_nombre').value.trim(),
                apellido: document.getElementById('med_apellido').value.trim(),
                fecha_nacimiento: document.getElementById('med_fecha_nacimiento').value,
                telefono: telefonoCompleto,
                email: document.getElementById('med_email').value.trim() || null,
                direccion: document.getElementById('med_direccion').value.trim() || null
            },
            empleado: {
                cedula: cedulaCompleta,
                salario: parseFloat(document.getElementById('med_salario').value),
                fecha_contratado: document.getElementById('med_fecha_contratado').value,
                id_horario: parseInt(document.getElementById('med_id_horario').value, 10),
                clave_acceso: document.getElementById('med_clave_acceso').value,
                rol: 'MEDICO'
            },
            medico: {
                cedula: cedulaCompleta,
                carnet_medico: carnetCompleto,
                tarifa: parseFloat(document.getElementById('med_tarifa').value)
            },
            especialidades: especialidades // Arreglo tipo [1, 3]
        };

        // 4. Enviar petición POST vía Fetch
        try {
            const response = await fetch(API + 'medicos.php', { // Ajusta la ruta a tu controlador
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (result.success) {
                alert(result.mensaje || 'Médico registrado con éxito.');
                this.reset(); // Reiniciar el formulario
            } else {
                alert('Error: ' + (result.error || 'No se pudo completar el registro.'));
            }
        } catch (error) {
            console.error('Error al registrar médico:', error);
            alert('Ocurrió un error al intentar registrar el médico.');
        }
    });

    bind('#paciente-registro form', async (form) => {
        await api('pacientes.php', { method: 'POST', body: JSON.stringify({ persona: { cedula: id('reg_cedula_letra', 'reg_cedula_numero'), nombre: val('reg_nombre'), apellido: val('reg_apellido'), fecha_nacimiento: val('reg_fecha_nacimiento'), telefono: phone('reg_telefono_prefijo', 'reg_telefono_numero'), email: val('reg_email'), direccion: val('reg_direccion') }, paciente: { genero: val('reg_genero').slice(0, 1).toUpperCase(), tipo_sangre: val('reg_tipo_sangre') } }) });
        form.reset(); await receptionSummary(); message('Paciente registrado correctamente.');
    });
    bind('#paciente-edicion form', async () => {
        const patientId = id('edit_cedula_letra', 'edit_cedula_numero');
        await api('pacientes.php', { method: 'PUT', body: JSON.stringify({ cedula: patientId, persona: { cedula: patientId, nombre: val('edit_nombre'), apellido: val('edit_apellido'), fecha_nacimiento: val('edit_fecha_nacimiento'), telefono: phone('edit_telefono_prefijo', 'edit_telefono_numero'), email: val('edit_email'), direccion: val('edit_direccion') }, paciente: { genero: val('edit_genero').slice(0, 1).toUpperCase(), tipo_sangre: val('edit_tipo_sangre') } }) });
        await receptionSummary(); message('Paciente actualizado correctamente.');
    });
    $('btn_buscar_paciente')?.addEventListener('click', async () => {
        try {
            const patient = (await api(`pacientes.php?cedula=${encodeURIComponent(id('edit_cedula_letra', 'edit_cedula_numero'))}`)).data;
            ['nombre', 'apellido', 'fecha_nacimiento', 'email', 'direccion'].forEach((field) => set(`edit_${field}`, patient[field]));
            set('edit_genero', patient.genero === 'M' ? 'Masculino' : patient.genero === 'F' ? 'Femenino' : 'Otro'); set('edit_tipo_sangre', patient.tipo_sangre); splitPhone(patient.telefono, 'edit_telefono_prefijo', 'edit_telefono_numero'); message('Datos del paciente cargados.');
        } catch (error) { message(error.message, true); }
    });

    configureAppointmentStartLimits();
    bind('#cita-registro form', async (form) => {
        configureAppointmentStartLimits();
        if (val('reg_rango_cita_inicio') < $('reg_rango_cita_inicio').min) throw new Error('La hora de inicio no puede ser anterior a la hora actual.');
        if (timestamp('reg_rango_cita_fin') <= timestamp('reg_rango_cita_inicio')) throw new Error('La hora final debe ser posterior a la inicial.');
        const result = await api('citas.php', { method: 'POST', body: JSON.stringify({ cedula_paciente: id('reg_cedula_paciente_letra', 'reg_cedula_paciente_numero'), cedula_medico: id('reg_cedula_medico_letra', 'reg_cedula_medico_numero'), consultorio: val('reg_consultorio_numero'), fecha_inicio: timestamp('reg_rango_cita_inicio'), fecha_fin: timestamp('reg_rango_cita_fin') }) });
        form.reset(); await receptionSummary(); await loadTodayAppointments(); text('cita-registrada', `Cita registrada correctamente. ID de cita: ${result.id_cita}`); $('cita-registrada')?.classList.remove('hidden');
    });
    async function loadAppointmentOptions() {
        const list = $('citas-disponibles');
        if (!list) return;
        const data = await api('citas.php?action=editar');
        list.innerHTML = '';
        appointmentOptions.clear();
        (data.data || []).slice(0, 10).forEach((item) => {
            appointmentOptions.set(String(item.id_cita), item);
            const option = new Option(String(item.id_cita), String(item.id_cita));
            option.label = `${item.paciente_nombre || 'Paciente'} - ${item.estado} - ${String(item.fecha_inicio || '').slice(0, 16)}`;
            list.append(option);
        });
    }
    $('id_cita')?.addEventListener('change', () => {
        const idBuscado = val('id_cita');
        
        // Buscar en el Map o fallback a la lista general
        const appointment = appointmentOptions.get(idBuscado) || 
                            listaTodasLasCitas.find(item => String(item.id_cita) === String(idBuscado));

        if (!appointment) return;

        // Obtener cédulas probando posibles nombres de propiedades de la API
        const rawPac = appointment.cedula_paciente || appointment.paciente_cedula || '';
        const rawMed = appointment.cedula_medico || appointment.medico_cedula || '';

        const pac = splitCedula(rawPac);
        const med = splitCedula(rawMed);

        // Asignar Paciente
        set('edit_cedula_paciente_letra', pac.letter);
        set('edit_cedula_paciente_numero', pac.number);
        set('edit_cedula_paciente', rawPac);

        // Asignar Médico
        set('edit_cedula_medico_letra', med.letter);
        set('edit_cedula_medico_numero', med.number);
        set('edit_cedula_medico', rawMed);

        // Fechas y Horarios
        if (appointment.fecha_inicio) {
            set('edit_rango_cita_inicio', String(appointment.fecha_inicio).replace(' ', 'T').slice(0, 16));
        }
        if (appointment.fecha_fin) {
            set('edit_rango_cita_fin', String(appointment.fecha_fin).replace(' ', 'T').slice(0, 16));
        }

        // Consultorio (extrae solo números si viene como "N° 101")
        if (appointment.consultorio) {
            const consNum = String(appointment.consultorio).replace(/\D/g, '');
            set('edit_consultorio_numero', consNum || appointment.consultorio);
        }

        // Estado
        if (appointment.estado) set('edit_estado', appointment.estado);
    });
    bind('#cita-edicion form', async () => {
        configureAppointmentStartLimits();
        if (!/^\d+$/.test(val('id_cita'))) throw new Error('Seleccione una cita válida.');
        if (val('edit_rango_cita_inicio') < $('edit_rango_cita_inicio').min) throw new Error('La hora de inicio no puede ser anterior a la hora actual.');
        if (timestamp('edit_rango_cita_fin') <= timestamp('edit_rango_cita_inicio')) throw new Error('La hora final debe ser posterior a la inicial.');
        await api('citas.php', { method: 'PUT', body: JSON.stringify({ id_cita: val('id_cita'), nuevo_estado: val('edit_estado'), cedula_paciente: id('edit_cedula_paciente_letra', 'edit_cedula_paciente_numero'), cedula_medico: id('edit_cedula_medico_letra', 'edit_cedula_medico_numero'), consultorio: val('edit_consultorio_numero'), fecha_inicio: timestamp('edit_rango_cita_inicio'), fecha_fin: timestamp('edit_rango_cita_fin') }) });
        await receptionSummary(); await loadTodayAppointments(); await loadAllAppointments(); message('Cita actualizada correctamente.');
    });

    // Seccion de Estudios y demas:
    // Variable global para almacenar los estudios obtenidos del servidor
    let listaEstudiosGlobal = [];

    // 2. LÓGICA DE FILTRADO COMBINADO (Estado + Buscador de texto)
    function filtrarEstudios() {
        const estadoSeleccionado = $('filtro_estado_estudios')?.value || 'TODOS';
        const textoBusqueda = ($('buscar_estudio_laboratorio')?.value || '').toLowerCase().trim();

        const estudiosFiltrados = listaEstudiosGlobal.filter(estudio => {
            // A) Evaluamos filtro por Estado
            const estadoEstudio = (estudio.estado || '').toUpperCase();
            const coincideEstado = (estadoSeleccionado === 'TODOS') || (estadoEstudio === estadoSeleccionado);

           // B) Evaluamos filtro por Texto (Paciente: Nombre/Cédula, Médico y Fecha)
            const paciente = `${estudio.paciente_nombre || ''} ${estudio.paciente_apellido || ''} ${estudio.paciente_cedula || estudio.cedula || ''}`.toLowerCase();
            const medico = `${estudio.medico_nombre || ''} ${estudio.medico_apellido || estudio.nombre_medico || ''}`.toLowerCase();
            const fecha = String(estudio.fecha || estudio.fecha_estudio || '').toLowerCase();

            const coincideTexto = !textoBusqueda || 
                paciente.includes(textoBusqueda) || 
                medico.includes(textoBusqueda) || 
                fecha.includes(textoBusqueda);

            // Retorna verdadero solo si cumple ambos criterios
            return coincideEstado && coincideTexto;
        });

        renderizarEstudios(estudiosFiltrados);
    };

    // 3. ESCUCHADORES DE EVENTOS (Reemplaza los atributos inline HTML)
    $('filtro_estado_estudios')?.addEventListener('change', filtrarEstudios);
    $('buscar_estudio_laboratorio')?.addEventListener('input', filtrarEstudios);

    

    /**
     * 1. Petición a la API para obtener la lista de estudios
     */
    async function cargarEstudios() {
        const tbody = document.getElementById('todos-los-estudios');
        tbody.innerHTML = '<tr><td colspan="7" style="padding: 14px 8px; text-align: center;">Cargando listado general...</td></tr>';

        try {
            // Ajusta la URL según la ruta de tu controlador PHP que retorna el JSON
            const response = await fetch(API + 'estudios.php');
            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                // Guardamos la lista original recibida de PHP
                listaEstudiosGlobal = result.data;
                // Dibujamos la tabla por primera vez
                renderizarEstudios(listaEstudiosGlobal);
            } else {
                tbody.innerHTML = '<tr><td colspan="7" style="padding: 14px 8px; color: #dc3545; text-align: center;">No se pudieron obtener los estudios.</td></tr>';
            }
        } catch (error) {
            console.error('Error cargando estudios:', error);
            tbody.innerHTML = '<tr><td colspan="7" style="padding: 14px 8px; color: #dc3545; text-align: center;">Error de conexión al cargar la lista.</td></tr>';
        }
    }

    /**
     * 2. Renderizado del HTML dentro del <tbody>
     */
    function renderizarEstudios(estudios) {
        const tbody = document.getElementById('todos-los-estudios');

        if (!estudios || estudios.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="padding: 14px 8px; text-align: center; color: #6c757d;">No se encontraron estudios.</td></tr>';
            return;
        }

        tbody.innerHTML = estudios.map(estudio => {
            // Adapta estos nombres de propiedades a las columnas reales que devuelve tu SQL/Modelo PHP
            const id = estudio.id_estudio || estudio.id;
            const fecha = escapeHtml(String(estudio.fecha || '').slice(0, 10));
            const paciente = estudio.paciente_cedula || estudio.paciente_nombre || `Nombre Paciente`;
            const medico = estudio.medico_nombre || estudio.nombre_medico || 'N/A';
            const tipo = estudio.tipo || estudio.tipo_estudio || 'N/A';
            const estado = estudio.estado;

            // Estilo visual del tag/badge según el estado
            const badgeEstilo = estado === 'REALIZADO' 
                ? 'background-color: #d4edda; color: #155724;' // Verde (Realizado)
                : (estado === 'CANCELADA' || estado === 'CANCELADO')
                    ? 'background-color: #f8d7da; color: #721c24;' // Rojo (Cancelado)
                    : 'background-color: #fff3cd; color: #856404;'; // Amarillo (Pendiente)

            return `
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 12px 8px; font-weight: bold;">#${id}</td>
                    <td style="padding: 12px 8px;">${fecha}</td>
                    <td style="padding: 12px 8px;">${paciente}</td>
                    <td style="padding: 12px 8px;">${medico}</td>
                    <td style="padding: 12px 8px;">${tipo}</td>
                    <td style="padding: 12px 8px;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 600; ${badgeEstilo}">
                            ${estado}
                        </span>
                    </td>
                    <td style="padding: 12px 8px;">
                        <button class="btn btn-outline" style="padding: 4px 10px; font-size: 0.85em;" onclick="verDetalleEstudio(${id})">
                            👁️ Ver detalle
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    
    // Cargar la lista automáticamente al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        cargarEstudios();
    });

    // Exponemos la función a window únicamente porque el HTML de los botones se genera dinámicamente en el render
window.verDetalleEstudio = function(id) {
    const estudio = listaEstudiosGlobal.find(e => String(e.id_estudio || e.id) === String(id));
    if (!estudio) return;

    // 1. Asignar ID en etiqueta e input oculto
    if ($('det_id_estudio_label')) $('det_id_estudio_label').textContent = id;
    set('id_estudio', id);
    set('res_id_estudio', id);

    // 2. Rellenar campos de consulta y tipo (Lectura)
    set('edit_id_consulta', estudio.id_consulta || '');
    set('edit_tipo', estudio.tipo || estudio.tipo_estudio || '');
    set('edit_estado', estudio.estado || 'PENDIENTE');

    // 3. Formatear la fecha para input datetime-local (YYYY-MM-THH:mm)
    if (estudio.fecha || estudio.fecha_estudio) {
        const rawFecha = estudio.fecha || estudio.fecha_estudio;
        const fechaFormatted = new Date(rawFecha).toISOString().slice(0, 16);
        set('edit_fecha', fechaFormatted);
    }

    // 4. Cédula del laboratorista
    const [letra, numero] = String(estudio.laboratorista || '').split('-');
    if (letra && numero) {
        set('edit_laboratorista_letra', letra);
        set('edit_laboratorista_numero', numero);
        set('edit_laboratorista', `${letra}-${numero}`);
    }

    // 5. Cargar lista de archivos/resultados asociados
    cargarResultadosAdjuntos(id);

    // 6. Cambiar a la pantalla de detalle
    showSection('estudio-detalle');
};

/**
 * Consulta la API para traer los archivos y notas asociados a este estudio
 */
    async function cargarResultadosAdjuntos(idEstudio) {
        const tbody = $('lista-resultados-adjuntos');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="4" style="padding: 10px 8px;">Cargando resultados...</td></tr>';

        try {
            const response = await fetch(`${API}resultados.php?id_estudio=${idEstudio}`);
            const result = await response.json();
            const resultados = result.data || result || [];

            if (!Array.isArray(resultados) || resultados.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="padding: 10px 8px; color: #6c757d;">No hay resultados cargados para este estudio.</td></tr>';
                return;
            }

            tbody.innerHTML = resultados.map(res => {
                const idRes = res.id_resultado || res.id;
                const desc = escapeHtml(res.descripcion || 'Sin descripción');
                const ruta = escapeHtml(res.ruta_archivo || '#');

                return `
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px;">#${idRes}</td>
                        <td style="padding: 8px;">${desc}</td>
                        <td style="padding: 8px;">
                            <a href="${escapeHtml(API + 'ver_resultado.php?id=' + idRes)}" target="_blank" class="btn btn-outline" style="padding: 3px 8px; font-size: 0.85em;">
                                📄 Ver Archivo
                            </a>
                        </td>
                        <td style="padding: 8px;">
                            <button type="button" class="btn btn-outline" style="color: #dc3545; border-color: #dc3545; padding: 3px 8px; font-size: 0.85em;" onclick="eliminarResultado(${idRes}, ${idEstudio})">
                                🗑️ Eliminar
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="4" style="padding: 10px 8px; color: #dc3545;">Error al cargar los resultados adjuntos.</td></tr>';
        }
    }

    // Función global para eliminar un archivo de resultado adjunto
    window.eliminarResultado = async function(idResultado, idEstudio) {
        if (!confirm('¿Estás seguro de que deseas eliminar este resultado?')) return;

        try {
            // Se envía id_resultado como Query Parameter en la URL para que PHP lo reciba en $_GET
            const response = await fetch(`${API}resultados.php?id_resultado=${idResultado}`, {
                method: 'DELETE'
            });
            const result = await response.json();

            if (result.success || response.ok) {
                cargarResultadosAdjuntos(idEstudio);
            } else {
                alert(result.error || result.mensaje || 'No se pudo eliminar el resultado.');
            }
        } catch (error) {
            alert('Error de conexión al eliminar el resultado.');
        }
    };
    
    

    async function loadHistory(patientId) {
        const history = (await api(`historia_medica.php?cedula_paciente=${encodeURIComponent(patientId)}`)).data || {};
        set('hist_antecedentes', history.antecedentes); set('hist_alergias', history.alergias); set('hist_medicacion', history.medicacion_habitual);
    }
    let consultationHistory = [];
    function renderConsultations(items) {
        const list = document.querySelector('#paciente-buscar ul'); if (!list) return;
        list.innerHTML = items.length ? items.map((item) => `<li data-id-consulta="${escapeHtml(item.id_consulta)}" style="padding:14px 18px;border:1px solid var(--border-color);margin-bottom:10px;cursor:pointer">Consulta ${escapeHtml(item.id_consulta)} - Cita ${escapeHtml(item.id_cita || 'sin cita')} - ${escapeHtml(String(item.fecha || '').slice(0, 10))} - ${escapeHtml(item.diagnostico || '')}</li>`).join('') : '<li>No hay consultas en el rango seleccionado.</li>';
        list.querySelectorAll('[data-id-consulta]').forEach((item) => item.addEventListener('click', () => loadDetail(item.dataset.idConsulta)));
    }
    async function loadConsultations(patientId) {
        consultationHistory = (await api(`consultas.php?paciente=${encodeURIComponent(patientId)}`)).data || [];
        renderConsultations(consultationHistory);
    }
    $('btn-consultar-rango')?.addEventListener('click', () => {
        const desde = val('rango_desde'); const hasta = val('rango_hasta');
        if (desde && hasta && desde > hasta) { message('La fecha inicial no puede ser posterior a la fecha final.', true); return; }
        renderConsultations(consultationHistory.filter((item) => { const fecha = String(item.fecha || '').slice(0, 10); return (!desde || fecha >= desde) && (!hasta || fecha <= hasta); }));
    });
    $('btn-ver-todas-consultas')?.addEventListener('click', () => renderConsultations(consultationHistory));
    async function loadDetail(consultationId) {
        const detail = (await api(`consultas.php?id=${consultationId}`)).data || {};
        set('det_diagnostico', detail.diagnostico); set('det_observaciones', detail.observaciones); set('det_costo', detail.costo);
        const [recipes, studies] = await Promise.all([api(`recetas.php?id_consulta=${consultationId}`), api(`estudios.php?id_consulta=${consultationId}`)]);
        set('det_recetas', (recipes.data || []).map((item) => item.nombre_medicamento || item.nombre || '').join(', ')); set('det_estudios', (studies.data || []).map((item) => item.nombre_estudio || item.tipo || '').join(', ')); $('detalle-consulta')?.classList.remove('hidden');
    }
    async function findPatient() {
        const patientId = val('buscar_cedula') || id('buscar_cedula_letra', 'buscar_cedula_numero');
        const patient = (await api(`pacientes.php?cedula=${encodeURIComponent(patientId)}`)).data;
        ['nombre', 'apellido', 'fecha_nacimiento', 'email', 'direccion'].forEach((field) => set(`pac_${field}`, patient[field])); splitPhone(patient.telefono, 'pac_telefono_prefijo', 'pac_telefono_numero'); set('pac_genero', patient.genero === 'M' ? 'Masculino' : patient.genero === 'F' ? 'Femenino' : 'Otro'); set('pac_tipo_sangre', patient.tipo_sangre); set('hist_cedula_paciente', patient.cedula); $('resultado-paciente')?.classList.remove('hidden'); await Promise.all([loadHistory(patient.cedula), loadConsultations(patient.cedula)]);
    }
    document.querySelector('#paciente-buscar .search-bar-container .btn-primary')?.addEventListener('click', () => findPatient().catch((error) => message(error.message, true)));
    window.verDetalleConsulta = (item) => loadDetail(item.dataset.idConsulta).catch((error) => message(error.message, true));
    bind('#form-historia-medica', async () => { await api('historia_medica.php', { method: 'PUT', body: JSON.stringify({ cedula_paciente: val('hist_cedula_paciente'), antecedentes: val('hist_antecedentes'), alergias: val('hist_alergias'), medicacion_habitual: val('hist_medicacion') }) }); message('Historia médica guardada correctamente.'); });
    bind('#form-registro-consulta', async (form) => { const user = await session(); const payload = { cedula_paciente: id('cons_cedula_paciente_letra', 'cons_cedula_paciente_numero'), cedula_medico: user?.cedula, diagnostico: val('cons_diagnostico'), observaciones: val('cons_observaciones'), costo: val('cons_costo') }; if (val('cons_id_cita')) payload.id_cita = val('cons_id_cita'); const result = await api('consultas.php', { method: 'POST', body: JSON.stringify(payload) }); set('rec_id_consulta', result.id_consulta); form.reset(); message('Consulta registrada correctamente.'); });
    bind('#form-registro-receta', async (form) => { await api('recetas.php', { method: 'POST', body: JSON.stringify({ id_consulta: val('rec_id_consulta'), medicamentos: [{ id_medicamento: val('rec_id_medicamento'), dosis: val('rec_dosis'), frecuencia: val('rec_frecuencia'), duracion: val('rec_duracion'), indicaciones: val('rec_indicaciones') }] }) }); form.reset(); message('Receta registrada correctamente.'); });


    // nueva seccion del admin y reportes:
    async function cargarTopEstudios() {
        const lista = document.getElementById('top_estudios_lista');

        try {
            const response = await fetch(API + 'estudios.php?action=top');
            const result = await response.json();

            if (result.success && Array.isArray(result.data) && result.data.length > 0) {
                lista.innerHTML = result.data.map(item => `
                    <li class="top-item" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 0.6rem 0; border-bottom: 1px solid var(--border-color, #eee);">
                        <span class="nombre-estudio" style="font-size: 0.9rem; min-width: 0; word-break: break-word;">
                            <strong>${item.nombre || item.nombre_estudio}</strong>
                        </span>
                        <span class="total-texto" style="color: #6c757d; font-size: 0.85rem; font-weight: 500; white-space: nowrap; flex-shrink: 0;">
                            ${item.total_solicitudes} solicitudes
                        </span>
                    </li>
                `).join('');
            } else {
                lista.innerHTML = '<li>No hay registros de estudios.</li>';
            }
        } catch (error) {
            console.error('Error al cargar top estudios:', error);
            lista.innerHTML = '<li style="color: red;">Error al obtener el reporte.</li>';
        }
    }

    // Ejecutar al cargar la página
    document.addEventListener('DOMContentLoaded', cargarTopEstudios);

    document.addEventListener('DOMContentLoaded', () => {
    // 1. Cargar el selector de especialidades al iniciar
    cargarEspecialidadesSelect();

    // 2. Escuchar el evento change del selector
    const selectEspecialidades = document.getElementById('med_especialidades');
    if (selectEspecialidades) {
        selectEspecialidades.addEventListener('change', (e) => {
            const idEspecialidad = e.target.value;
            cargarMedicosPorEspecialidad(idEspecialidad);
        });
    }
});

// Función para llenar los <select> de especialidades (Filtro y Formulario)
    async function cargarEspecialidadesSelect() {
        const selectFiltro = document.getElementById('med_especialidades');
        const selectForm = document.getElementById('form_med_especialidades');
        
        try {
            const response = await fetch(API + 'especialidades.php');
            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                
                // 1. Llenar el select del filtro de reportes
                if (selectFiltro) {
                    selectFiltro.innerHTML = '<option value="">-- Seleccione una especialidad --</option>';
                    result.data.forEach(esp => {
                        const option = document.createElement('option');
                        option.value = esp.id_especialidad;
                        option.textContent = esp.nombre;
                        selectFiltro.appendChild(option);
                    });
                }

                // 2. Llenar el select del formulario de registro
                if (selectForm) {
                    selectForm.innerHTML = ''; // Elimina las opciones fijas del HTML
                    result.data.forEach(esp => {
                        const option = document.createElement('option');
                        option.value = esp.id_especialidad;
                        option.textContent = esp.nombre;
                        selectForm.appendChild(option);
                    });
                }

            }
        } catch (error) {
            console.error('Error al cargar especialidades:', error);
        }
    }

// Función para obtener y listar los médicos según la especialidad seleccionada
    async function cargarMedicosPorEspecialidad(idEspecialidad) {
        const contenedor = document.getElementById('contenedor_medicos_especialidad');

        if (!idEspecialidad) {
            contenedor.innerHTML = '<p style="color: #666; font-style: italic; font-size: 0.9rem;">Seleccione una especialidad para ver los médicos asociados.</p>';
            return;
        }

        contenedor.innerHTML = '<p style="font-size: 0.9rem;">Cargando médicos...</p>';

        try {
            // Llama al endpoint de médicos enviando el filtro GET id_especialidad
            const response = await fetch(`${API}medicos.php?id_especialidad=${idEspecialidad}`);
            const result = await response.json();

            if (result.success && result.data.length > 0) {
                let html = '<ul style="list-style: none; padding: 0; margin: 0;">';
                
                result.data.forEach(medico => {
                    html += `
                        <li style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 0.6rem 0; border-bottom: 1px solid var(--border-color, #eee);">
                            <span style="font-size: 0.9rem; min-width: 0; word-break: break-word;">
                                <strong>${medico.nombre} ${medico.apellido}</strong>
                            </span>
                            <span style="color: #6c757d; font-size: 0.85rem; font-weight: 500; white-space: nowrap; flex-shrink: 0;">
                                C.I.: ${medico.cedula}
                            </span>
                        </li>
                    `;
                });
                
                html += '</ul>';
                contenedor.innerHTML = html;
            } else {
                contenedor.innerHTML = '<p style="color: #777; font-size: 0.9rem;">No hay médicos registrados en esta especialidad.</p>';
            }
        } catch (error) {
            console.error('Error al cargar médicos por especialidad:', error);
            contenedor.innerHTML = '<p style="color: red; font-size: 0.9rem;">Ocurrió un error al cargar la información.</p>';
        }
    }

    document.getElementById('med_especialidades').addEventListener('change', (event) => {
        const idEspecialidad = event.target.value; // Captura el ID seleccionado
        cargarMedicosPorEspecialidad(idEspecialidad);
    });


    async function cargarTopMedicos() {
        const lista = document.getElementById('top_medicos_lista');
        
        try {
            const response = await fetch(API + 'medicos.php?top_citas');
            const result = await response.json();

            if (result.success && result.data.length > 0) {
                lista.innerHTML = '';
                result.data.forEach(medico => {
                    const li = document.createElement('li');
                    li.style.marginBottom = '0.4rem';
                    li.innerHTML = `<strong>${medico.nombre} ${medico.apellido}</strong> ${medico.cedula} | <span style="color: #666; font-size: 0.85rem;">(${medico.total_citas} citas)</span>`;
                    lista.appendChild(li);
                });
            } else {
                lista.innerHTML = '<li style="color: #666; font-style: italic;">No hay citas registradas.</li>';
            }
        } catch (error) {
            console.error('Error al cargar Top Médicos:', error);
            lista.innerHTML = '<li style="color: red;">Error al cargar datos.</li>';
        }
    }

    // Recuerda invocar la función al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        cargarTopMedicos();
    });

    async function loadMedications() {
        const select = $('rec_id_medicamento');
        if (!select) return;
        const data = await api('medicamentos.php');
        select.innerHTML = '<option value="">Seleccione...</option>';
        (data.data || []).forEach((item) => {
            const details = [item.laboratorio, item.presentacion].filter(Boolean).join(' - ');
            select.add(new Option(details ? `${item.nombre} (${details})` : item.nombre, item.id_medicamento));
        });
    }
    async function loadStudyOptions() {
        const lists = [$('estudios-disponibles'), $('estudios-resultado-disponibles')].filter(Boolean);
        if (!lists.length) return;
        const data = await api('estudios.php');
        lists.forEach((list) => {
            list.innerHTML = '';
            (data.data || []).slice(0, 10).forEach((item) => {
                const option = new Option(String(item.id_estudio), String(item.id_estudio));
                option.label = `${item.nombre_estudio || item.tipo || 'Estudio'} - Consulta ${item.id_consulta}`;
                list.append(option);
            });
        });
    }
    async function loadConsultationOptions() {
        const list = $('consultas-disponibles');
        if (!list) return;
        const data = await api('consultas.php?action=para_estudio');
        list.innerHTML = '';
        (data.data || []).slice(0, 10).forEach((item) => {
            const option = new Option(String(item.id_consulta), String(item.id_consulta));
            option.label = `${item.paciente_nombre || 'Paciente'} - ${item.diagnostico || 'Consulta'} - ${String(item.fecha || '').slice(0, 16)}`;
            list.append(option);
        });
    }

    async function cargarHorariosSelect() {
    // 1. Guardar los IDs en un arreglo y filtrar solo los que existan en el DOM
    const selectIds = ['rec_id_horario', 'lab_id_horario', 'med_id_horario'];
    const selects = selectIds
        .map(id => document.getElementById(id))
        .filter(el => el !== null);

    // Si no se encuentra ninguno de los selects en el HTML, no hace nada
    if (selects.length === 0) return;

    try {
        const response = await fetch(API + 'empleados.php?action=horarios');
        const result = await response.json();

        if (result.success && Array.isArray(result.data)) {
            // 2. Construir el HTML de las opciones una sola vez
            let optionsHTML = '<option value="">-- Seleccione un horario --</option>';

            result.data.forEach(h => {
                const entrada = h.hora_entrada.slice(0, 5);
                const salida = h.hora_salida.slice(0, 5);
                optionsHTML += `<option value="${h.id_horario}">${h.dias} (${entrada} - ${salida})</option>`;
            });

            // 3. Asignar el HTML a todos los selectores encontrados
            selects.forEach(select => {
                select.innerHTML = optionsHTML;
            });
        } else {
            selects.forEach(select => {
                select.innerHTML = '<option value="">No hay horarios disponibles</option>';
            });
        }
    } catch (error) {
        console.error('Error al cargar horarios:', error);
        selects.forEach(select => {
            select.innerHTML = '<option value="">Error al cargar horarios</option>';
        });
    }
}



    async function autocompletarPersonaRegistro() {
        const num = val('reg_cedula_numero');
        // Validar que la cédula tenga entre 7 y 8 dígitos antes de consultar
        if (!/^\d{7,8}$/.test(num)) return;

        const cedulaCompleta = id('reg_cedula_letra', 'reg_cedula_numero');

        try {
            const response = await api(`personas.php?cedula=${encodeURIComponent(cedulaCompleta)}`);
            const persona = response.data;

            if (persona) {
                set('reg_nombre', persona.nombre);
                set('reg_apellido', persona.apellido);
                set('reg_fecha_nacimiento', persona.fecha_nacimiento);
                set('reg_email', persona.email);
                set('reg_direccion', persona.direccion);

                if (persona.telefono) {
                    splitPhone(persona.telefono, 'reg_telefono_prefijo', 'reg_telefono_numero');
                }
            }
        } catch (error) {
            // Si la persona no está registrada previamente en la BD, no hacemos nada
            // para permitir que el usuario ingrese los datos manualmente.
            console.log('Persona nueva o no encontrada:', error.message);
        }
    }

    $('reg_cedula_numero')?.addEventListener('blur', autocompletarPersonaRegistro);
    $('reg_cedula_letra')?.addEventListener('change', autocompletarPersonaRegistro);

    
    function configureLaboratoristaSession(user) {
        const [letter, number] = String(user?.cedula || '').split('-');
        if (!letter || !number || !$('reg_laboratorista_numero')) return;
        set('reg_laboratorista_letra', letter);
        set('reg_laboratorista_numero', number);
        set('reg_laboratorista', `${letter}-${number}`);
        $('reg_laboratorista_letra').disabled = true;
        $('reg_laboratorista_numero').readOnly = true;
    }
    async function loadStudyTypes() { if (!$('reg_tipo')) return; const data = await api('tipos_estudio.php'); $('reg_tipo').innerHTML = '<option value="">Seleccione...</option>'; (data.data || []).forEach((item) => $('reg_tipo').add(new Option(item.nombre_estudio, item.id_tipo_estudio))); }
    bind('#form-registro-estudio', async (form) => { if (!/^\d+$/.test(val('reg_id_consulta'))) throw new Error('Seleccione una consulta válida.'); await api('estudios.php?action=solicitar', { method: 'POST', body: JSON.stringify({ id_consulta: val('reg_id_consulta'), tipos: [val('reg_tipo')] }) }); await cargarEstudios(); form.reset(); const user = await session(); configureLaboratoristaSession(user); message('Estudio solicitado correctamente.'); });
    bind('#form-edicion-estudio', async () => { 
        if (!/^\d+$/.test(val('id_estudio'))) throw new Error('Seleccione un estudio válido.'); 

        await api('estudios.php', { 
            method: 'PUT', 
            body: JSON.stringify({ 
            id_estudio: val('id_estudio'), 
            estado: val('edit_estado'), 
            laboratorista: val('reg_laboratorista_letra') + '-' + val('reg_laboratorista_numero') 
            }) 
        }); 

        //  Llamada a la función que recarga los datos
        await cargarEstudios(); 

        message('Estudio actualizado correctamente.'); 
        });

        bind('#form-registro-resultado', async (form) => { 
            if (!/^\d+$/.test(val('res_id_estudio'))) throw new Error('Seleccione un estudio válido.'); 

            const data = new FormData(form); 
            await api('resultados.php', { method: 'POST', body: data }); 

            form.reset(); 

            //  Llamada a las funciones de recarga según lo que necesites actualizar
            await cargarResultadosAdjuntos(val('res_id_estudio')); // O cargarEstudios(), si la carga de resultado cambia el estado del estudio

            message('Resultado cargado correctamente.'); 
            });

    session().then((user) => { configureLaboratoristaSession(user); return Promise.all([receptionSummary().catch(() => {}), cargarHorariosSelect().catch(() => {}), cargarTopMedicos().catch(() => {}) ,cargarEspecialidadesSelect().catch(() => {}), cargarTopEstudios().catch(() => {}), loadAllAppointments().catch(() => {}), cargarEstudios().catch(() => {}) , loadTodayAppointments().catch(() => {}), adminSummary().catch(() => {}), loadStudyTypes().catch(() => {}), loadMedications().catch(() => {}), loadStudyOptions().catch(() => {}), loadConsultationOptions().catch(() => {}), loadAppointmentOptions().catch(() => {}), user ? Promise.all([loadDoctor(user).catch(() => {}), loadDoctorAppointments(user).catch(() => {})]) : Promise.resolve()]); });
    async function loadDoctor(user) { if (!$('perfil_nombre_texto')) return; const doctor = (await api(`medicos.php?cedula=${encodeURIComponent(user.cedula)}`)).data || {}; set('perfil_nombre_texto', user.nombre); set('perfil_nombre', user.nombre); set('perfil_cedula_numero', user.cedula.split('-')[1]); set('perfil_cedula', user.cedula); set('perfil_carnet_numero', String(doctor.carnet_medico || '').replace(/^M\.P\.P\.S\.\s*/i, '')); set('perfil_carnet', doctor.carnet_medico); set('perfil_tarifa', doctor.tarifa); set('perfil_especialidad', doctor.especialidades); }
});