document.addEventListener('DOMContentLoaded', () => {
    const API = '../clinica-backend/api/';
    const $ = (id) => document.getElementById(id);
    const val = (id) => ($(id)?.value || '').trim();
    const set = (id, text) => { if ($(id)) $(id).value = text ?? ''; };
    const text = (id, content) => { if ($(id)) $(id).textContent = content ?? ''; };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));

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
    function phone(prefix, number) { return val(number) ? `${val(prefix)}-${val(number)}` : ''; }
    function splitPhone(value, prefix, number) {
        const parts = String(value || '').split('-');
        set(prefix, parts.length > 1 ? parts[0] : '0424');
        set(number, parts.length > 1 ? parts.slice(1).join('-') : parts[0]);
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
        list.innerHTML = appointments.length ? appointments.map((item) => `<tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 12px 8px;">${escapeHtml(item.id_cita)}</td><td style="padding: 12px 8px;">${escapeHtml(`${item.paciente_nombre} ${item.paciente_apellido}`)}</td><td style="padding: 12px 8px;">${escapeHtml(`${item.medico_nombre} ${item.medico_apellido}`)}</td><td style="padding: 12px 8px;">${escapeHtml(String(item.fecha_inicio || '').slice(11, 16))} - ${escapeHtml(String(item.fecha_fin || '').slice(11, 16))}</td><td style="padding: 12px 8px;">${escapeHtml(item.consultorio)}</td><td style="padding: 12px 8px;">${escapeHtml(item.estado)}</td><td style="padding: 12px 8px;">${item.estado === 'PENDIENTE' ? `<button type="button" class="btn btn-primary" data-cita-estado="CONFIRMADA" data-id-cita="${escapeHtml(item.id_cita)}" style="padding: 8px 12px; margin-right: 6px;">Confirmar</button><button type="button" class="btn btn-outline" data-cita-estado="CANCELADA" data-id-cita="${escapeHtml(item.id_cita)}" style="padding: 8px 12px;">Cancelar</button>` : 'Sin acciones'}</td></tr>`).join('') : '<tr><td colspan="7" style="padding: 14px 8px;">No hay citas para hoy.</td></tr>';
    }

    async function loadDoctorAppointments(user) {
        const list = $('mis-citas-lista'); if (!list) return;
        const appointments = (await api(`citas.php?medico=${encodeURIComponent(user.cedula)}`)).data || [];
        list.innerHTML = appointments.length ? appointments.map((item) => `<tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 12px 8px;">${escapeHtml(item.id_cita)}</td><td style="padding: 12px 8px;">${escapeHtml(`${item.paciente_nombre} ${item.paciente_apellido}`)}</td><td style="padding: 12px 8px;">${escapeHtml(String(item.fecha_inicio || '').slice(0, 16))} - ${escapeHtml(String(item.fecha_fin || '').slice(11, 16))}</td><td style="padding: 12px 8px;">${escapeHtml(item.consultorio)}</td><td style="padding: 12px 8px;">${escapeHtml(item.estado)}</td><td style="padding: 12px 8px;"><button type="button" class="btn btn-outline" data-usar-cita="${escapeHtml(item.id_cita)}" data-cedula-paciente="${escapeHtml(item.cedula_paciente)}" style="padding: 8px 12px;">Usar cita</button></td></tr>`).join('') : '<tr><td colspan="6" style="padding: 14px 8px;">No tienes citas registradas.</td></tr>';
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
    bind('#form-registro-medico', async (form) => {
        const specialties = Array.from($('med_especialidades')?.selectedOptions || []).map((option) => option.value);
        await api('empleados.php', { method: 'POST', body: JSON.stringify(employee('med', 'MEDICO', { carnet_medico: `M.P.P.S. ${val('med_carnet_medico_numero')}`, tarifa: val('med_tarifa'), especialidades: specialties })) });
        form.reset(); await adminSummary(); message('Médico registrado correctamente.');
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

    bind('#cita-registro form', async (form) => {
        if (timestamp('reg_rango_cita_fin') <= timestamp('reg_rango_cita_inicio')) throw new Error('La hora final debe ser posterior a la inicial.');
        const result = await api('citas.php', { method: 'POST', body: JSON.stringify({ cedula_paciente: id('reg_cedula_paciente_letra', 'reg_cedula_paciente_numero'), cedula_medico: id('reg_cedula_medico_letra', 'reg_cedula_medico_numero'), consultorio: val('reg_consultorio_numero'), fecha_inicio: timestamp('reg_rango_cita_inicio'), fecha_fin: timestamp('reg_rango_cita_fin') }) });
        form.reset(); await receptionSummary(); await loadTodayAppointments(); text('cita-registrada', `Cita registrada correctamente. ID de cita: ${result.id_cita}`); $('cita-registrada')?.classList.remove('hidden');
    });
    bind('#cita-edicion form', async () => {
        if (timestamp('edit_rango_cita_fin') <= timestamp('edit_rango_cita_inicio')) throw new Error('La hora final debe ser posterior a la inicial.');
        await api('citas.php', { method: 'PUT', body: JSON.stringify({ id_cita: val('id_cita'), nuevo_estado: val('edit_estado'), fecha_inicio: timestamp('edit_rango_cita_inicio'), fecha_fin: timestamp('edit_rango_cita_fin') }) });
        await receptionSummary(); await loadTodayAppointments(); message('Cita actualizada correctamente.');
    });

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

    async function loadStudyTypes() { if (!$('reg_tipo')) return; const data = await api('tipos_estudio.php'); $('reg_tipo').innerHTML = '<option value="">Seleccione...</option>'; (data.data || []).forEach((item) => $('reg_tipo').add(new Option(item.nombre_estudio, item.id_tipo_estudio))); }
    bind('#form-registro-estudio', async (form) => { await api('estudios.php?action=solicitar', { method: 'POST', body: JSON.stringify({ id_consulta: val('reg_id_consulta'), tipos: [val('reg_tipo')] }) }); form.reset(); message('Estudio solicitado correctamente.'); });
    bind('#form-edicion-estudio', async () => { await api('estudios.php', { method: 'PUT', body: JSON.stringify({ id_estudio: val('id_estudio'), estado: val('edit_estado') }) }); message('Estudio actualizado correctamente.'); });
    bind('#form-registro-resultado', async (form) => { const data = new FormData(form); await api('resultados.php', { method: 'POST', body: data }); form.reset(); message('Resultado cargado correctamente.'); });

    session().then((user) => Promise.all([receptionSummary().catch(() => {}), loadTodayAppointments().catch(() => {}), adminSummary().catch(() => {}), loadStudyTypes().catch(() => {}), user ? Promise.all([loadDoctor(user).catch(() => {}), loadDoctorAppointments(user).catch(() => {})]) : Promise.resolve()]));
    async function loadDoctor(user) { if (!$('perfil_nombre_texto')) return; const doctor = (await api(`medicos.php?cedula=${encodeURIComponent(user.cedula)}`)).data || {}; set('perfil_nombre_texto', user.nombre); set('perfil_nombre', user.nombre); set('perfil_cedula_numero', user.cedula.split('-')[1]); set('perfil_cedula', user.cedula); set('perfil_carnet_numero', String(doctor.carnet_medico || '').replace(/^M\.P\.P\.S\.\s*/i, '')); set('perfil_carnet', doctor.carnet_medico); set('perfil_tarifa', doctor.tarifa); set('perfil_especialidad', doctor.especialidades); }
});