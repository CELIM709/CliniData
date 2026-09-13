document.addEventListener('DOMContentLoaded', () => {
    const API = '../clinica-backend/api/';
    const $ = (id) => document.getElementById(id);
    const val = (id) => ($(id)?.value || '').trim();
    const set = (id, text) => { if ($(id)) $(id).value = text ?? ''; };
    const text = (id, content) => { if ($(id)) $(id).textContent = content ?? ''; };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));

    // Estado global de la consulta en pantalla
    let currentConsultationId = null;
    let allDoctorConsultations = []; // Almacena el historial de consultas del médico para filtrado

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

    function splitPhone(value, prefix, number) {
        if (!value) {
            set(prefix, '0424');
            set(number, '');
            return;
        }
        const str = String(value).trim();
        if (str.includes('-')) {
            const parts = str.split('-');
            set(prefix, parts[0]);
            set(number, parts.slice(1).join(''));
            return;
        }
        if (str.length === 11) {
            set(prefix, str.slice(0, 4));
            set(number, str.slice(4));
            return;
        }
        if (str.length === 10) {
            set(prefix, '0' + str.slice(0, 3));
            set(number, str.slice(3));
            return;
        }
        set(prefix, '0424');
        set(number, str);
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

    // ==========================================
    // CONTROL DE MODO DE CONSULTA (CREAR vs VER)
    // ==========================================

    function setConsultaMode(mode) {
        const saveBtn = document.querySelector('#form-registro-consulta button[type="submit"]');
        const formInputs = document.querySelectorAll('#form-registro-consulta input, #form-registro-consulta textarea');

        if (mode === 'VIEW') {
            if (saveBtn) saveBtn.style.display = 'none';
            formInputs.forEach(input => input.setAttribute('readonly', 'true'));
        } else if (mode === 'CREATE') {
            if (saveBtn) saveBtn.style.display = 'inline-block';
            formInputs.forEach(input => input.removeAttribute('readonly'));
        }
    }

    // ==========================================
    // ESPACIO DE TRABAJO DE CONSULTA ACTIVA
    // ==========================================

    window.ocultarConsultaActiva = function() {
        const interfaz = $('interfaz-consulta-activa');
        if (interfaz) interfaz.classList.add('hidden');
        limpiarEspacioConsulta();
    };

    async function loadRecetas(idConsulta) {
        const container = $('lista-recetas-consulta');
        if (!container) return;

        try {
            const response = await api(`recetas.php?id_consulta=${idConsulta}`);
            const recetas = response.data || response || [];

            if (!Array.isArray(recetas) || !recetas.length) {
                container.innerHTML = '<p style="color: gray; margin: 0;">No hay recetas registradas para esta consulta.</p>';
                return;
            }

            container.innerHTML = recetas.map(r => `
                <div style="background: var(--bg-card, #f8f9fa); border: 1px solid var(--border-color, #eee); border-radius: 6px; padding: 10px; margin-bottom: 8px;">
                    <strong>${escapeHtml(r.medicamento)}</strong> (${r.presentacion || ''})<br>
                    <small><strong>Dosis:</strong> ${escapeHtml(r.dosis)} | <strong>Frec:</strong> ${escapeHtml(r.frecuencia)} | <strong>Duración:</strong> ${escapeHtml(r.duracion)}</small>
                    ${r.indicaciones ? `<br><small style="color:#555;"><em>Indicaciones: ${escapeHtml(r.indicaciones)}</em></small>` : ''}
                </div>
            `).join('');
        } catch (e) {
            container.innerHTML = '<p style="color: red; margin: 0;">Error al cargar recetas.</p>';
        }
    }

    async function loadExamenes(idConsulta) {
        const container = $('lista-examenes-consulta');
        if (!container) return;

        try {
            const response = await api(`estudios.php?id_consulta=${idConsulta}`);
            const examenes = response.data || [];

            if (!examenes.length) {
                container.innerHTML = '<p style="color: gray; margin: 0;">No hay exámenes solicitados para esta consulta.</p>';
                return;
            }

            // Obtener los resultados de cada estudio en paralelo
            const examenesConResultados = await Promise.all(
                examenes.map(async (e) => {
                    try {
                        const res = await api(`resultados.php?id_estudio=${e.id_estudio}`);
                        return { ...e, resultados: res.data || [] };
                    } catch {
                        return { ...e, resultados: [] };
                    }
                })
            );

            // Renderizar tarjetas de exámenes con sus adjuntos/resultados
            container.innerHTML = examenesConResultados.map(e => {
                const tieneResultados = e.resultados && e.resultados.length > 0;

                const listaResultadosHtml = tieneResultados
                    ? `<div style="margin-top: 6px;">
                        <small style="font-weight: bold; color: #333;">Resultados / Adjuntos:</small>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px;">
                            ${e.resultados.map((r, index) => {
                                const url = r.ruta_archivo || r.archivo_resultado || r.archivo;
                                const etiqueta = r.descripcion || r.nombre_estudio || `Adjunto #${index + 1}`;
                                return `
                                    <a href="${escapeHtml(url)}" target="_blank" class="btn btn-outline" style="font-size:11px; padding:3px 8px; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                        📄 ${escapeHtml(etiqueta)}
                                    </a>`;
                            }).join('')}
                        </div>
                    </div>`
                    : '<span style="color:#e67e22; font-size:12px; display:block; margin-top:5px;">Resultados pendientes</span>';

                return `
                    <div style="background: var(--bg-card, #f8f9fa); border: 1px solid var(--border-color, #eee); border-radius: 6px; padding: 10px; margin-bottom: 8px;">
                        <strong>${escapeHtml(e.tipo_examen || e.nombre_estudio || 'Estudio')}</strong><br>
                        ${e.estado ? `<small style="color:#555;"><em>Estado: ${escapeHtml(e.estado)}</em></small><br>` : ''}
                        ${listaResultadosHtml}
                    </div>
                `;
            }).join('');

        } catch (err) {
            container.innerHTML = '<p style="color: red; margin: 0;">Error al cargar exámenes.</p>';
        }
    }

    async function loadDetail(consultationId) {
        try {
            const response = await api(`consultas.php?id=${consultationId}`);
            const consulta = response.data || response;

            if (!consulta) throw new Error('No se encontró la consulta.');

            currentConsultationId = String(consultationId);

            const [letra, numero] = String(consulta.cedula_paciente || '').split('-');
            set('cons_cedula_paciente_letra', letra || 'V');
            set('cons_cedula_paciente_numero', numero || consulta.cedula_paciente || '');
            set('cons_cedula_paciente', consulta.cedula_paciente);
            set('cons_id_cita', consulta.id_cita || '');
            set('cons_costo', consulta.costo || '0.00');
            set('cons_diagnostico', consulta.diagnostico || '');
            set('cons_observaciones', consulta.observaciones || '');

            set('rec_id_consulta', currentConsultationId);
            set('exam_id_consulta', currentConsultationId);
            set('reg_id_consulta', currentConsultationId);

            const titulo = $('titulo-consulta-activa');
            if (titulo) titulo.textContent = `Espacio de Consulta Activa (ID: ${currentConsultationId})`;

            setConsultaMode('VIEW');

            await Promise.all([
                loadMedications(),
                loadRecetas(currentConsultationId),
                loadExamenes(currentConsultationId)
            ]);

            const interfaz = $('interfaz-consulta-activa');
            if (interfaz) {
                interfaz.classList.remove('hidden');
                interfaz.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            message(error.message || 'Error al obtener detalles de la consulta', true);
        }
    }

    window.verDetalleConsulta = (item) => loadDetail(item.dataset.idConsulta).catch((error) => message(error.message, true));

    // ==========================================
    // CITAS Y CONSULTAS DEL MÉDICO
    // ==========================================

    async function loadDoctorAppointments(user) {
        const list = $('mis-citas-lista'); 
        if (!list) return;
        
        const appointments = (await api(`citas.php?medico=${encodeURIComponent(user.cedula)}`)).data || [];
        
        list.innerHTML = appointments.length ? appointments.map((item) => `
            <tr style="border-bottom: 1px solid var(--border-color);">
                <td style="padding: 12px 8px;">${escapeHtml(item.id_cita)}</td>
                <td style="padding: 12px 8px;">${escapeHtml(item.cedula_paciente)}</td>
                <td style="padding: 12px 8px;">${escapeHtml(`${item.paciente_nombre} ${item.paciente_apellido}`)}</td>
                <td style="padding: 12px 8px;">${escapeHtml(String(item.fecha_inicio || '').slice(0, 16))} - ${escapeHtml(String(item.fecha_fin || '').slice(11, 16))}</td>
                <td style="padding: 12px 8px;">${escapeHtml(item.consultorio)}</td>
                <td style="padding: 12px 8px;">${escapeHtml(item.estado)}</td>
                <td style="padding: 12px 8px;">
                    ${item.estado === 'CONFIRMADA' ? `<button type="button" class="btn btn-outline" data-usar-cita="${escapeHtml(item.id_cita)}" data-cedula-paciente="${escapeHtml(item.cedula_paciente)}" style="padding: 8px 12px;">Usar cita</button>` : 'Sin acciones'}
                </td>
            </tr>
        `).join('') : '<tr><td colspan="7" style="padding: 14px 8px;">No tienes citas registradas.</td></tr>';
    }

    function renderDoctorConsultations(consultations) {
        const list = $('lista-mis-consultas');
        if (!list) return;

        if (!consultations.length) {
            list.innerHTML = '<tr><td colspan="6" style="padding: 14px 8px;">No se encontraron consultas registradas.</td></tr>';
            return;
        }

        list.innerHTML = consultations.map((item) => `
            <tr style="border-bottom: 1px solid var(--border-color);">
                <td style="padding: 12px 8px;">${escapeHtml(item.id_consulta)}</td>
                <td style="padding: 12px 8px;">${escapeHtml(item.paciente_nombre || '')} ${escapeHtml(item.paciente_apellido || '')}</td>
                <td style="padding: 12px 8px;">${escapeHtml(item.paciente_cedula)}</td>
                <td style="padding: 12px 8px;">${escapeHtml(item.diagnostico || 'Sin diagnóstico')}</td>
                <td style="padding: 12px 8px;">$${escapeHtml(item.costo || '0.00')}</td>
                <td style="padding: 12px 8px; text-align: right;">
                    <button type="button" class="btn btn-outline" data-ver-consulta="${escapeHtml(item.id_consulta)}" style="padding: 6px 12px;">Ver Detalle</button>
                </td>
            </tr>
        `).join('');
    }

    async function loadDoctorConsultations(user) {
        const list = $('lista-mis-consultas');
        if (!list) return;

        try {
            const response = await api(`consultas.php?medico=${encodeURIComponent(user.cedula)}`);
            allDoctorConsultations = response.data || [];
            renderDoctorConsultations(allDoctorConsultations);
        } catch (error) {
            list.innerHTML = `<tr><td colspan="6" style="padding: 14px 8px; color: var(--danger-color, red);">Error al cargar historial: ${escapeHtml(error.message)}</td></tr>`;
        }
    }

    // Buscador de consultas por cédula o nombre en tiempo real
    $('buscar_consulta_cedula')?.addEventListener('input', (event) => {
        const term = event.target.value.trim().toLowerCase();

        const filtered = allDoctorConsultations.filter((item) => {
            const cedula = String(item.paciente_cedula || '').toLowerCase();
            const nombreCompleto = `${item.paciente_nombre || ''} ${item.paciente_apellido || ''}`.toLowerCase();
            return cedula.includes(term) || nombreCompleto.includes(term);
        });

        renderDoctorConsultations(filtered);
    });

    $('lista-mis-consultas')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-ver-consulta]');
        if (!button) return;
        loadDetail(button.dataset.verConsulta).catch((error) => message(error.message, true));
    });

    $('mis-citas-lista')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-usar-cita]');
        if (!button) return;

        limpiarEspacioConsulta();

        const [letter, number] = String(button.dataset.cedulaPaciente || '').split('-');
        set('cons_cedula_paciente_letra', letter || 'V');
        set('cons_cedula_paciente_numero', number || button.dataset.cedulaPaciente || '');
        set('cons_cedula_paciente', button.dataset.cedulaPaciente);
        set('cons_id_cita', button.dataset.usarCita);
        set('cons_costo', val('perfil_tarifa').replace(/[^0-9.]/g, ''));

        text('titulo-consulta-activa', `Nueva Consulta (Cita #${button.dataset.usarCita})`);
        setConsultaMode('CREATE');

        text('lista-recetas-consulta', 'Guarde la consulta primero para poder emitir recetas.');
        text('lista-examenes-consulta', 'Guarde la consulta primero para poder solicitar exámenes.');

        // Transición de pantallas
        $('mis-citas')?.classList.add('hidden');
        $('mis-consultas')?.classList.remove('hidden');

        const interfaz = $('interfaz-consulta-activa');
        if (interfaz) {
            interfaz.classList.remove('hidden');
            interfaz.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        setTimeout(() => {
            $('cons_diagnostico')?.focus();
        }, 300);
    });

    $('btn_buscar_paciente')?.addEventListener('click', async () => {
        try {
            const patient = (await api(`pacientes.php?cedula=${encodeURIComponent(id('edit_cedula_letra', 'edit_cedula_numero'))}`)).data;
            ['nombre', 'apellido', 'fecha_nacimiento', 'email', 'direccion'].forEach((field) => set(`edit_${field}`, patient[field]));
            set('edit_genero', patient.genero === 'M' ? 'Masculino' : patient.genero === 'F' ? 'Femenino' : 'Otro'); 
            set('edit_tipo_sangre', patient.tipo_sangre); 
            splitPhone(patient.telefono, 'edit_telefono_prefijo', 'edit_telefono_numero'); 
            message('Datos del paciente cargados.');
        } catch (error) { message(error.message, true); }
    });

    async function loadHistory(patientId) {
        const history = (await api(`historia_medica.php?cedula_paciente=${encodeURIComponent(patientId)}`)).data || {};
        set('hist_antecedentes', history.antecedentes); 
        set('hist_alergias', history.alergias); 
        set('hist_medicacion', history.medicacion_habitual);
    }
    
    let consultationHistory = [];
    function renderConsultations(items) {
        const list = document.querySelector('#paciente-buscar ul'); 
        if (!list) return;
        list.innerHTML = items.length ? items.map((item) => `<li data-id-consulta="${escapeHtml(item.id_consulta)}" style="padding:14px 18px;border:1px solid var(--border-color);margin-bottom:10px;cursor:pointer">Consulta ${escapeHtml(item.id_consulta)} - Cita ${escapeHtml(item.id_cita || 'sin cita')} - ${escapeHtml(String(item.fecha || '').slice(0, 10))} - ${escapeHtml(item.diagnostico || '')}</li>`).join('') : '<li>No hay consultas en el rango seleccionado.</li>';
        list.querySelectorAll('[data-id-consulta]').forEach((item) => item.addEventListener('click', () => loadDetail(item.dataset.idConsulta)));
    }

    async function loadConsultations(patientId) {
        consultationHistory = (await api(`consultas.php?paciente=${encodeURIComponent(patientId)}`)).data || [];
        renderConsultations(consultationHistory);
    }

    async function findPatient() {
        const patientId = val('buscar_cedula') || id('buscar_cedula_letra', 'buscar_cedula_numero');
        const patient = (await api(`pacientes.php?cedula=${encodeURIComponent(patientId)}`)).data;
        ['nombre', 'apellido', 'fecha_nacimiento', 'email', 'direccion'].forEach((field) => set(`pac_${field}`, patient[field])); 
        splitPhone(patient.telefono, 'pac_telefono_prefijo', 'pac_telefono_numero'); 
        set('pac_genero', patient.genero === 'M' ? 'Masculino' : patient.genero === 'F' ? 'Femenino' : 'Otro'); 
        set('pac_tipo_sangre', patient.tipo_sangre); 
        set('hist_cedula_paciente', patient.cedula); 
        $('resultado-paciente')?.classList.remove('hidden'); 
        await Promise.all([loadHistory(patient.cedula), loadConsultations(patient.cedula)]);
    }

    document.querySelector('#paciente-buscar .search-bar-container .btn-primary')?.addEventListener('click', () => findPatient().catch((error) => message(error.message, true)));

    // ==========================================
    // FORMULARIOS DE REGISTRO
    // ==========================================

    bind('#form-historia-medica', async () => { 
        await api('historia_medica.php', { method: 'PUT', body: JSON.stringify({ cedula_paciente: val('hist_cedula_paciente'), antecedentes: val('hist_antecedentes'), alergias: val('hist_alergias'), medicacion_habitual: val('hist_medicacion') }) }); 
        message('Historia médica guardada correctamente.'); 
    });

    bind('#form-registro-consulta', async (form) => { 
        const user = await session(); 
        const payload = { 
            cedula_paciente: id('cons_cedula_paciente_letra', 'cons_cedula_paciente_numero'), 
            cedula_medico: user?.cedula, 
            diagnostico: val('cons_diagnostico'), 
            observaciones: val('cons_observaciones'), 
            costo: val('cons_costo') 
        }; 
        if (val('cons_id_cita')) payload.id_cita = val('cons_id_cita'); 

        const result = await api('consultas.php', { method: 'POST', body: JSON.stringify(payload) }); 
        
        const extractedId = result.id_consulta || result.id || result.data?.id_consulta || result.data?.id;
        if (!extractedId) {
            throw new Error('No se pudo obtener el ID de la consulta guardada.');
        }

        currentConsultationId = String(extractedId);
        set('rec_id_consulta', currentConsultationId); 
        set('exam_id_consulta', currentConsultationId);
        set('reg_id_consulta', currentConsultationId);

        const titulo = $('titulo-consulta-activa');
        if (titulo) titulo.textContent = `Espacio de Consulta Activa (ID: ${currentConsultationId})`;

        setConsultaMode('VIEW');

        if (user) {
            await Promise.all([
                loadDoctorConsultations(user),
                loadDoctorAppointments(user) 
            ]);
        }

        await Promise.all([
            loadRecetas(currentConsultationId),
            loadExamenes(currentConsultationId)
        ]);

        message(`Consulta #${currentConsultationId} registrada correctamente. Ahora puede agregar recetas o exámenes.`); 
    });

    bind('#form-registro-receta', async (form) => {
        const idConsulta = val('rec_id_consulta') || currentConsultationId;
        if (!idConsulta) throw new Error('Debe seleccionar o guardar una consulta activa antes de agregar una receta.');

        await api('recetas.php', {
            method: 'POST',
            body: JSON.stringify({
                id_consulta: idConsulta,
                medicamentos: [{
                    id_medicamento: val('rec_id_medicamento'),
                    dosis: val('rec_dosis'),
                    frecuencia: val('rec_frecuencia'),
                    duracion: val('rec_duracion'),
                    indicaciones: val('rec_indicaciones')
                }]
            })
        });

        ['rec_dosis', 'rec_frecuencia', 'rec_duracion', 'rec_indicaciones'].forEach(field => set(field, ''));
        set('rec_id_consulta', idConsulta);

        await loadRecetas(idConsulta);
        message('Receta registrada correctamente.');
    });

    bind('#form-registro-examen', async (form) => {
        const idConsulta = val('exam_id_consulta') || currentConsultationId;
        const tipoExamen = val('exam_tipo');

        if (!idConsulta) throw new Error('Debe guardar la consulta o seleccionar una consulta activa antes de solicitar un examen.');
        if (!tipoExamen) throw new Error('Debe seleccionar el tipo de examen.');

        const payload = {
            action: 'solicitar',
            id_consulta: idConsulta,
            tipos: [tipoExamen],
            indicaciones: val('exam_indicaciones')
        };

        await api('estudios.php', { method: 'POST', body: JSON.stringify(payload) });
        set('exam_tipo', '');
        set('exam_indicaciones', '');
        set('exam_id_consulta', idConsulta);

        await loadExamenes(idConsulta);
        message('Solicitud de examen registrada correctamente.');
    });

    // ==========================================
    // CATÁLOGOS Y OPCIONES
    // ==========================================

    async function loadMedications() {
        const selects = [$('rec_id_medicamento'), $('rec_medicamento'), $('id_medicamento')].filter(Boolean);
        if (!selects.length) return;
        const data = await api('medicamentos.php');
        const list = data.data || data || [];
        
        selects.forEach(select => {
            select.innerHTML = '<option value="">Seleccione...</option>';
            (Array.isArray(list) ? list : []).forEach((item) => {
                const details = [item.laboratorio, item.presentacion].filter(Boolean).join(' - ');
                const itemId = item.id_medicamento || item.id;
                select.add(new Option(details ? `${item.nombre} (${details})` : item.nombre, itemId));
            });
        });
    }

    async function loadStudyTypes() { 
        const selects = [$('reg_tipo'), $('exam_tipo'), $('tipo_examen')].filter(Boolean);
        if (!selects.length) return; 
        const data = await api('tipos_estudio.php'); 
        const items = data.data || data || [];
        selects.forEach(select => {
            select.innerHTML = '<option value="">Seleccione un examen...</option>'; 
            (Array.isArray(items) ? items : []).forEach((item) => {
                const valId = item.id_tipo_estudio || item.id || item.nombre_estudio;
                select.add(new Option(item.nombre_estudio, valId));
            });
        });
    }

    async function loadDoctor(user) { 
        if (!$('perfil_nombre_texto')) return; 
        const doctor = (await api(`medicos.php?cedula=${encodeURIComponent(user.cedula)}`)).data || {}; 
        set('perfil_nombre_texto', user.nombre); 
        set('perfil_nombre', user.nombre); 
        set('perfil_cedula_numero', user.cedula.split('-')[1]); 
        set('perfil_cedula', user.cedula); 
        set('perfil_carnet_numero', String(doctor.carnet_medico || '').replace(/^M\.P\.P\.S\.\s*/i, '')); 
        set('perfil_carnet', doctor.carnet_medico); 
        set('perfil_tarifa', doctor.tarifa); 
        set('perfil_especialidad', doctor.especialidades); 
    }

    window.crearNuevaConsultaSinCita = function() {
        limpiarEspacioConsulta();
        
        const tarifaMedica = val('perfil_tarifa').replace(/[^0-9.]/g, '');
        set('cons_costo', tarifaMedica || '0.00');

        text('titulo-consulta-activa', 'Creación de Nueva Consulta (Sin Cita)');
        text('lista-recetas-consulta', 'Guarde la consulta primero para poder emitir recetas.');
        text('lista-examenes-consulta', 'Guarde la consulta primero para poder solicitar exámenes.');

        setConsultaMode('CREATE');

        const interfaz = $('interfaz-consulta-activa');
        if (interfaz) {
            interfaz.classList.remove('hidden');
            interfaz.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        setTimeout(() => {
            $('cons_cedula_paciente_numero')?.focus();
        }, 300);
    };

    function limpiarEspacioConsulta() {
        currentConsultationId = null;

        set('cons_cedula_paciente_letra', 'V');
        set('cons_cedula_paciente_numero', '');
        set('cons_cedula_paciente', '');
        set('cons_id_cita', '');
        set('cons_diagnostico', '');
        set('cons_observaciones', '');

        const tarifaDoctor = val('perfil_tarifa').replace(/[^0-9.]/g, '');
        set('cons_costo', tarifaDoctor || '0.00');

        set('rec_id_consulta', '');
        set('exam_id_consulta', '');
        set('reg_id_consulta', '');

        text('lista-recetas-consulta', 'Guarde la consulta primero para poder emitir recetas.');
        text('lista-examenes-consulta', 'Guarde la consulta primero para poder solicitar exámenes.');
    }

    // Inicialización del sistema y carga de catálogos
    session().then((user) => {
        return Promise.all([
            loadStudyTypes().catch(() => {}), 
            loadMedications().catch(() => {}),   
            user ? Promise.all([
                loadDoctor(user).catch(() => {}), 
                loadDoctorAppointments(user).catch(() => {}), 
                loadDoctorConsultations(user).catch(() => {})
            ]) : Promise.resolve()
        ]); 
    });
});