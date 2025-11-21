// === FUNCIONES PARA PROPUESTAS DE INTERCAMBIO ===

// Función para renderizar mensaje de propuesta de intercambio
function renderPropuestaIntercambio(messageData, isOwnMessage, originalMessageData) {
    try {
        // messageData puede ser: 1) el objeto completo con .message (string),
        // 2) el objeto JSON ya parseado (con campos tipo, lugar, etc.).
        let data = null;

        if (messageData === null || messageData === undefined) {
            return renderMensajeAntiguoIntercambio(originalMessageData && (originalMessageData.message || originalMessageData.mensaje) ? (originalMessageData.message || originalMessageData.mensaje) : '');
        }

        // Si viene como objeto parseado (sin campo .message)
        if (typeof messageData === 'object' && !messageData.hasOwnProperty('message')) {
            data = messageData;
        } else if (typeof messageData.message === 'string') {
            // Intentar parsear string JSON
            const raw = messageData.message;
            if (raw && raw.trim().startsWith('{')) {
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    // Mensaje antiguo en texto
                    return renderMensajeAntiguoIntercambio(raw);
                }
            } else {
                return renderMensajeAntiguoIntercambio(raw);
            }
        } else if (typeof messageData.message === 'object') {
            data = messageData.message;
        }

        // Si no tenemos datos, intentar usar originalMessageData
        if (!data && originalMessageData && (originalMessageData.message || originalMessageData.mensaje)) {
            try { data = JSON.parse(originalMessageData.message || originalMessageData.mensaje); } catch(e){ data = null; }
        }

        if (!data) return renderMensajeAntiguoIntercambio(originalMessageData && (originalMessageData.message || originalMessageData.mensaje) ? (originalMessageData.message || originalMessageData.mensaje) : '');

        const productoSolicitado = data.producto_solicitado || data.producto_solicitado || {};
        const productoOfrecido = data.producto_ofrecido || data.producto_ofrecido || {};
        const mensajeAdicional = data.mensaje_adicional || data.notas || '';
        const propuestaId = data.propuesta_id || messageData.propuesta_id || (originalMessageData && originalMessageData.propuesta_id);
        const estado = data.estado || 'pendiente';

        console.log('🔍 Renderizando propuesta:', {
            propuestaId,
            estado,
            isOwnMessage,
            messageData,
            data
        });

        const imagenSolicitado = productoSolicitado.imagen || productoSolicitado.producto_solicitado_imagen || '/img/placeholder-producto.svg';
        const imagenOfrecido = productoOfrecido.imagen || productoOfrecido.producto_ofrecido_imagen || '/img/placeholder-producto.svg';
        
        // Botones de acción (solo si no es mensaje propio y está pendiente)
        let botonesHTML = '';
        if (!isOwnMessage && estado === 'pendiente' && propuestaId) {
            botonesHTML = `
                <div class="propuesta-acciones">
                    <button class="btn-propuesta btn-aceptar" onclick="abrirModalCoordinacion(${propuestaId})">
                        <i class="fas fa-check"></i> Aceptar
                    </button>
                    <button class="btn-propuesta btn-contraoferta" onclick="abrirContraoferta(${propuestaId})">
                        <i class="fas fa-retweet"></i> Contraoferta
                    </button>
                    <button class="btn-propuesta btn-rechazar" onclick="gestionarPropuesta(${propuestaId}, 'rechazar')">
                        <i class="fas fa-times"></i> Rechazar
                    </button>
                </div>
            `;
        } else if (isOwnMessage && estado === 'pendiente' && propuestaId) {
            // Si es mensaje propio, solo mostrar opción de cancelar
            botonesHTML = `
                <div class="propuesta-acciones">
                    <button class="btn-propuesta btn-cancelar" onclick="gestionarPropuesta(${propuestaId}, 'cancelar')">
                        <i class="fas fa-ban"></i> Cancelar propuesta
                    </button>
                </div>
            `;
        } else if (!propuestaId && estado === 'pendiente') {
            // Propuesta antigua sin ID - mostrar nota informativa
            botonesHTML = `
                <div class="propuesta-nota">
                    <i class="fas fa-info-circle"></i>
                    <small>Propuesta antigua - Se requiere crear una nueva propuesta para interactuar</small>
                </div>
            `;
        } else if (estado === 'aceptada') {
            botonesHTML = `
                <div class="propuesta-estado estado-aceptada">
                    <i class="fas fa-check-circle"></i> Propuesta aceptada - En coordinación
                </div>
            `;
        } else if (estado === 'rechazada') {
            botonesHTML = `
                <div class="propuesta-estado estado-rechazada">
                    <i class="fas fa-times-circle"></i> Propuesta rechazada
                </div>
            `;
        } else if (estado === 'completada') {
            botonesHTML = `
                <div class="propuesta-estado estado-completada">
                    <i class="fas fa-check-double"></i> Intercambio completado
                </div>
            `;
        }
        
        return `
            <div class="propuesta-intercambio" data-propuesta-id="${propuestaId || ''}">
                <div class="propuesta-header">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Propuesta de Intercambio</span>
                </div>
                
                <div class="propuesta-body">
                    <!-- Producto ofrecido -->
                    <div class="producto-card producto-ofrecido">
                        <div class="producto-label">
                            ${isOwnMessage ? '📦 Ofrezco' : '📦 Te ofrezco'}
                        </div>
                        <div class="producto-imagen">
                            <img src="${imagenOfrecido}" alt="${productoOfrecido.nombre || 'Producto'}" 
                                 onerror="this.src='/img/placeholder-producto.svg'">
                        </div>
                        <div class="producto-nombre">${productoOfrecido.nombre || 'Producto'}</div>
                    </div>
                    
                    <!-- Icono de intercambio -->
                    <div class="intercambio-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    
                    <!-- Producto solicitado -->
                    <div class="producto-card producto-solicitado">
                        <div class="producto-label">
                            ${isOwnMessage ? '🎯 Quiero' : '🎯 Quiere'}
                        </div>
                        <div class="producto-imagen">
                            <img src="${imagenSolicitado}" alt="${productoSolicitado.nombre || 'Producto'}" 
                                 onerror="this.src='/img/placeholder-producto.svg'">
                        </div>
                        <div class="producto-nombre">${productoSolicitado.nombre || 'Producto'}</div>
                    </div>
                </div>
                
                ${mensajeAdicional ? `
                    <div class="propuesta-mensaje">
                        <i class="fas fa-comment-dots"></i>
                        <span>${escapeHtml(mensajeAdicional)}</span>
                    </div>
                ` : ''}
                
                ${botonesHTML}
            </div>
        `;
    } catch (error) {
        console.error('Error al renderizar propuesta de intercambio:', error);
        return renderMensajeAntiguoIntercambio(messageData.message);
    }
}

// Función para renderizar mensajes antiguos de intercambio (formato texto)
function renderMensajeAntiguoIntercambio(mensajeTexto) {
    return `
        <div class="propuesta-intercambio propuesta-antigua">
            <div class="propuesta-header">
                <i class="fas fa-exchange-alt"></i>
                <span>Propuesta de Intercambio</span>
            </div>
            <div class="propuesta-contenido-antiguo">
                ${escapeHtml(mensajeTexto).replace(/\n/g, '<br>')}
            </div>
            <div class="propuesta-nota">
                <i class="fas fa-info-circle"></i>
                <small>Propuesta en formato anterior</small>
            </div>
        </div>
    `;
}

// Función para cargar propuestas pendientes
async function loadPropuestasPendientes(userId) {
    try {
        const response = await fetch(`/api/obtener-propuestas-pendientes.php?user_id=${userId}`);
        const data = await response.json();
        
        if (data.success && data.propuesta) {
            // Verificar si hay coordinación activa
            const coordResponse = await fetch(`/api/obtener-coordinacion.php?propuesta_id=${data.propuesta.id}`);
            const coordData = await coordResponse.json();
            
            if (coordData.status === 'success' && coordData.coordinacion) {
                // Mostrar panel de coordinación
                mostrarPanelCoordinacion(data.propuesta.id);
            } else {
                // Mostrar banner normal de propuesta
                mostrarBannerPropuesta(data.propuesta);
            }
        } else {
            ocultarBannerPropuesta();
        }
    } catch (error) {
        console.error('Error al cargar propuestas pendientes:', error);
    }
}

// Función para mostrar banner de propuesta pendiente
function mostrarBannerPropuesta(propuesta) {
    // Buscar si ya existe el banner
    let banner = document.getElementById('propuesta-banner');
    
    if (!banner) {
        banner = document.createElement('div');
        banner.id = 'propuesta-banner';
        banner.className = 'propuesta-banner';
        
        // Insertar después del chat-header
        const chatHeader = document.querySelector('.chat-header');
        if (chatHeader) {
            chatHeader.insertAdjacentElement('afterend', banner);
        }
    }
    
    const esReceptor = propuesta.es_receptor;
    const productoSolicitado = {
        nombre: propuesta.producto_solicitado_nombre,
        imagen: propuesta.producto_solicitado_imagen
    };
    const productoOfrecido = {
        nombre: propuesta.producto_ofrecido_nombre,
        imagen: propuesta.producto_ofrecido_imagen
    };
    
    banner.innerHTML = `
        <div class="propuesta-banner-content">
            <div class="propuesta-banner-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="propuesta-banner-info">
                <div class="propuesta-banner-title">
                    ${esReceptor ? '📬 Tienes una propuesta de intercambio pendiente' : '⏳ Propuesta de intercambio en espera'}
                </div>
                <div class="propuesta-banner-productos">
                    <span class="producto-mini">
                        <img src="${productoOfrecido.imagen}" alt="${productoOfrecido.nombre}">
                        ${productoOfrecido.nombre}
                    </span>
                    <i class="fas fa-exchange-alt"></i>
                    <span class="producto-mini">
                        <img src="${productoSolicitado.imagen}" alt="${productoSolicitado.nombre}">
                        ${productoSolicitado.nombre}
                    </span>
                </div>
            </div>
            ${esReceptor ? `
                <div class="propuesta-banner-actions">
                    <button class="btn-propuesta-accion btn-aceptar" onclick="gestionarPropuesta(${propuesta.id}, 'aceptar')">
                        <i class="fas fa-check"></i> Aceptar
                    </button>
                    <button class="btn-propuesta-accion btn-contraoferta" onclick="abrirContraoferta(${propuesta.id})">
                        <i class="fas fa-retweet"></i> Contraoferta
                    </button>
                    <button class="btn-propuesta-accion btn-rechazar" onclick="gestionarPropuesta(${propuesta.id}, 'rechazar')">
                        <i class="fas fa-times"></i> Rechazar
                    </button>
                </div>
            ` : `
                <div class="propuesta-banner-actions">
                    <button class="btn-propuesta-accion btn-cancelar" onclick="gestionarPropuesta(${propuesta.id}, 'cancelar')">
                        <i class="fas fa-ban"></i> Cancelar
                    </button>
                </div>
            `}
        </div>
    `;
    
    banner.style.display = 'block';
}

// Función para ocultar banner de propuesta
function ocultarBannerPropuesta() {
    const banner = document.getElementById('propuesta-banner');
    if (banner) {
        banner.style.display = 'none';
    }
}

// Función para gestionar propuesta (aceptar, rechazar, cancelar)
async function gestionarPropuesta(propuestaId, accion) {
    console.log('🎯 gestionarPropuesta llamada:', { propuestaId, accion });
    
    try {
        // Validar que tenemos un ID de propuesta
        if (!propuestaId || propuestaId === 'null' || propuestaId === 'undefined') {
            console.error('❌ ID de propuesta inválido:', propuestaId);
            showNotification('Error: No se pudo identificar la propuesta', 'error');
            return;
        }
        
        // Si es aceptar, abrir modal de coordinación en lugar de confirmar directamente
        if (accion === 'aceptar') {
            console.log('✅ Abriendo modal de coordinación para propuesta:', propuestaId);
            abrirModalCoordinacion(propuestaId);
            return;
        }
        
        const confirmaciones = {
            'rechazar': '¿Rechazar esta propuesta de intercambio?',
            'cancelar': '¿Cancelar tu propuesta de intercambio?'
        };
        
        if (!confirm(confirmaciones[accion])) {
            return;
        }
        
        const response = await fetch('/api/gestionar-propuesta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                propuesta_id: propuestaId,
                accion: accion
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            ocultarBannerPropuesta();
            // Recargar mensajes para mostrar la actualización
            if (window.currentChatUserId) {
                loadMessages(window.currentChatUserId);
            }
        } else {
            showNotification(data.message || 'Error al procesar la propuesta', 'error');
        }
    } catch (error) {
        console.error('Error al gestionar propuesta:', error);
        showNotification('Error al procesar la propuesta', 'error');
    }
}

// Función para abrir modal de contraoferta
function abrirContraoferta(propuestaId) {
    // Abrir modal para seleccionar uno de mis productos como contraoferta
    (async () => {
        try {
            const resp = await fetch('/api/get-mis-productos-disponibles.php');
            const data = await resp.json();
            if (!data.success) {
                showNotification(data.message || 'No se pudieron obtener tus productos', 'error');
                return;
            }

            const productos = data.productos || [];
            if (productos.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No tienes productos disponibles',
                    text: 'Publica un producto antes de hacer una contraoferta.'
                });
                return;
            }

            // Crear modal
            const modal = document.createElement('div');
            modal.className = 'modal-coordinacion';
            modal.id = 'modal-contraoferta';

            let productosHtml = '';
            productos.forEach(p => {
                productosHtml += `
                    <label class="contraoferta-producto-item">
                        <input type="radio" name="producto_contra" value="${p.id}">
                        <img src="${p.imagen || 'img/placeholder-producto.jpg'}" alt="${escapeHtml(p.nombre)}">
                        <div class="contraoferta-info">
                            <strong>${escapeHtml(p.nombre)}</strong>
                            <div class="contraoferta-meta">${escapeHtml(p.categoria || '')} - ${escapeHtml(p.estado || '')}</div>
                        </div>
                    </label>
                `;
            });

            modal.innerHTML = `
                <div class="modal-coordinacion-content">
                    <div class="modal-coordinacion-header">
                        <h3><i class="fas fa-retweet"></i> Contraoferta</h3>
                        <button class="btn-cerrar-modal" onclick="document.getElementById('modal-contraoferta').remove();">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-coordinacion-body">
                        <p>Selecciona uno de tus productos para ofrecer como contraoferta:</p>
                        <div class="contraoferta-list">
                            ${productosHtml}
                        </div>
                        <div style="margin-top:12px; text-align:right;">
                            <button class="btn-propuesta btn-cancelar" onclick="document.getElementById('modal-contraoferta').remove();">Cancelar</button>
                            <button class="btn-propuesta btn-contraoferta" id="btn-enviar-contraoferta">Enviar contraoferta</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            document.getElementById('btn-enviar-contraoferta').addEventListener('click', async () => {
                const selected = modal.querySelector('input[name="producto_contra"]:checked');
                if (!selected) {
                    showNotification('Selecciona un producto para la contraoferta', 'error');
                    return;
                }
                const productoId = selected.value;

                try {
                    const res = await fetch('/api/gestionar-propuesta.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ propuesta_id: propuestaId, accion: 'contraoferta', producto_contraoferta_id: productoId })
                    });
                    const resultado = await res.json();
                    if (resultado.success) {
                        showNotification(resultado.message || 'Contraoferta enviada', 'success');
                        modal.remove();
                        if (window.currentChatUserId) loadMessages(window.currentChatUserId);
                    } else {
                        showNotification(resultado.message || 'Error al enviar contraoferta', 'error');
                    }
                } catch (err) {
                    console.error('Error enviando contraoferta:', err);
                    showNotification('Error de conexión', 'error');
                }
            });

        } catch (error) {
            console.error('Error al abrir contraoferta:', error);
            showNotification('Error al cargar tus productos', 'error');
        }
    })();
}

// ========== FUNCIONES DE COORDINACIÓN DE INTERCAMBIO ==========

// Función para abrir modal de coordinación
async function abrirModalCoordinacion(propuestaId) {
    console.log('📋 Abriendo modal de coordinación, propuestaId:', propuestaId);
    
    try {
        // Validar ID
        if (!propuestaId) {
            console.error('❌ propuestaId es undefined o null');
            showNotification('Error: ID de propuesta no válido', 'error');
            return;
        }
        
        // Obtener detalles de la propuesta y coordinación si existe
        const url = `/api/obtener-coordinacion.php?propuesta_id=${propuestaId}`;
        console.log('🌐 Fetching:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        console.log('📥 Respuesta del API:', data);
        
        if (data.status !== 'success') {
            console.error('❌ Error en respuesta:', data);
            showNotification(data.message || 'Error al cargar la propuesta', 'error');
            return;
        }
        
        const propuesta = data.propuesta;
        
        // Crear modal
        const modal = document.createElement('div');
        modal.className = 'modal-coordinacion';
        modal.id = 'modal-coordinacion';
        
        modal.innerHTML = `
            <div class="modal-coordinacion-content">
                <div class="modal-coordinacion-header">
                    <h3><i class="fas fa-handshake"></i> Coordinar Intercambio</h3>
                    <button class="btn-cerrar-modal" onclick="cerrarModalCoordinacion()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-coordinacion-body">
                    <div class="productos-intercambio-resumen">
                        <div class="producto-resumen">
                            <img src="${propuesta.producto_ofrecido_imagen}" alt="${propuesta.producto_ofrecido_nombre}">
                            <p>${propuesta.producto_ofrecido_nombre}</p>
                        </div>
                        <div class="icono-intercambio">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="producto-resumen">
                            <img src="${propuesta.producto_solicitado_imagen}" alt="${propuesta.producto_solicitado_nombre}">
                            <p>${propuesta.producto_solicitado_nombre}</p>
                        </div>
                    </div>
                    
                    <div class="coordinacion-form">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-map-marker-alt"></i> Lugar de encuentro
                            </label>
                            <!-- Select de ubicaciones poblado desde los puntos de encuentro del producto -->
                            <select id="ubicacion-select" class="form-control">
                                <option value="">Selecciona una ubicación sugerida o escribe una nueva...</option>
                            </select>
                            <input type="text" id="lugar-encuentro" placeholder="O escribe un lugar, ej: Plaza Principal, Starbucks..." class="form-control" style="margin-top:8px;">
                            <small class="form-hint">Puedes elegir una ubicación sugerida por las publicaciones o escribir una propia.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-calendar-alt"></i> Fecha y hora propuesta
                            </label>
                            <input type="datetime-local" id="fecha-encuentro" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-sticky-note"></i> Notas adicionales (opcional)
                            </label>
                            <textarea id="notas-encuentro" placeholder="Ej: Llevo el producto en caja original..." 
                                      class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="coordinacion-info">
                            <i class="fas fa-info-circle"></i>
                            <p>Una vez que propongas estos datos, el otro usuario deberá confirmarlos. 
                            Puedes ajustar los detalles mediante el chat hasta que ambos estén de acuerdo.</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-coordinacion-footer">
                    <button class="btn-modal-secundario" onclick="cerrarModalCoordinacion()">
                        Cancelar
                    </button>
                    <button class="btn-modal-primario" onclick="enviarPropuestaCoordinacion(${propuestaId})">
                        <i class="fas fa-paper-plane"></i> Enviar Propuesta
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Establecer fecha mínima (hoy)
        const fechaInput = document.getElementById('fecha-encuentro');
        const ahora = new Date();
        ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
        fechaInput.min = ahora.toISOString().slice(0, 16);
        
        // Mostrar modal con animación
        setTimeout(() => modal.classList.add('show'), 10);
        // Poblar select de ubicaciones con puntos de encuentro de ambos productos (si existen)
        (async () => {
            try {
                const select = document.getElementById('ubicacion-select');
                if (!select) return;

                const ofrecidoId = propuesta.producto_ofrecido_id || propuesta.producto_ofrecido || null;
                const solicitadoId = propuesta.producto_solicitado_id || propuesta.producto_solicitado || null;

                const puntosTotales = [];

                const fetchPuntos = async (productoId, etiqueta) => {
                    if (!productoId) return [];
                    const r = await fetch(`/api/puntos-encuentro.php?producto_id=${productoId}`);
                    const j = await r.json();
                    if (j && j.success && j.data && Array.isArray(j.data.puntos_encuentro)) {
                        return j.data.puntos_encuentro.map(p => ({...p, _productoEtiqueta: etiqueta}));
                    }
                    return [];
                };

                const [puntosOf, puntosSol] = await Promise.all([
                    fetchPuntos(ofrecidoId, 'Ofrecido'),
                    fetchPuntos(solicitadoId, 'Solicitado')
                ]);

                puntosTotales.push(...puntosOf, ...puntosSol);

                // Añadir opciones agrupadas
                if (puntosTotales.length > 0) {
                    // Limpiar opciones excepto la primera
                    select.innerHTML = '<option value="">Selecciona una ubicación sugerida o escribe una nueva...</option>';
                    puntosTotales.forEach(p => {
                        const label = `${p._productoEtiqueta}: ${p.nombre} — ${p.direccion}`;
                        const value = encodeURIComponent(JSON.stringify({ punto_id: p.id, direccion: p.direccion, lat: p.latitud, lng: p.longitud, nombre: p.nombre }));
                        const opt = document.createElement('option');
                        opt.value = value;
                        opt.textContent = label;
                        select.appendChild(opt);
                    });

                    // Cuando se seleccione una opción, llenar el campo de texto con la dirección
                    select.addEventListener('change', () => {
                        const v = select.value;
                        if (!v) return;
                        try {
                            const parsed = JSON.parse(decodeURIComponent(v));
                            document.getElementById('lugar-encuentro').value = parsed.direccion || parsed.nombre || '';
                        } catch (e) {
                            console.warn('Error parsing option value', e);
                        }
                    });
                }
            } catch (e) {
                console.error('Error cargando puntos de encuentro:', e);
            }
        })();
        
    } catch (error) {
        console.error('Error al abrir modal de coordinación:', error);
        showNotification('Error al abrir modal de coordinación', 'error');
    }
}

// Función para cerrar modal de coordinación
function cerrarModalCoordinacion() {
    const modal = document.getElementById('modal-coordinacion');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

// Función para enviar propuesta de coordinación
async function enviarPropuestaCoordinacion(propuestaId) {
    const lugar = document.getElementById('lugar-encuentro').value.trim();
    const fecha = document.getElementById('fecha-encuentro').value;
    const notas = document.getElementById('notas-encuentro').value.trim();
    
    if (!lugar) {
        showNotification('Por favor, indica un lugar de encuentro', 'error');
        return;
    }
    
    if (!fecha) {
        showNotification('Por favor, selecciona una fecha y hora', 'error');
        return;
    }
    
    try {
        // Extraer latitud y longitud si se seleccionó una ubicación del select
        let lat = null;
        let lng = null;
        const selectUbicacion = document.getElementById('ubicacion-select');
        if (selectUbicacion && selectUbicacion.value) {
            try {
                const ubicacionData = JSON.parse(decodeURIComponent(selectUbicacion.value));
                lat = ubicacionData.lat;
                lng = ubicacionData.lng;
            } catch (e) {
                console.warn('No se pudo extraer coordenadas', e);
            }
        }
        
        // Llamar a crear-seguimiento para aceptar y crear seguimiento
        const response = await fetch('/api/crear-seguimiento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                propuesta_id: propuestaId,
                lugar: lugar,
                fecha: fecha,
                lat: lat,
                lng: lng
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            cerrarModalCoordinacion();
            showNotification('✅ Intercambio aceptado. Ahora pueden coordinar los detalles.', 'success');
            
            // Abrir panel de seguimiento del intercambio
            window.location.href = '/mis-intercambios.php';
        } else {
            console.error('❌ Error del servidor:', data);
            showNotification(data.error || data.message || 'Error al aceptar intercambio', 'error');
        }
    } catch (error) {
        console.error('❌ Error al enviar coordinación:', error);
        showNotification('Error al aceptar intercambio', 'error');
    }
}

// Función para mostrar panel de coordinación en el banner
async function mostrarPanelCoordinacion(propuestaId) {
    try {
        const response = await fetch(`/api/obtener-coordinacion.php?propuesta_id=${propuestaId}`);
        const data = await response.json();
        
        if (data.status !== 'success' || !data.coordinacion) {
            return;
        }
        
        const coord = data.coordinacion;
        const banner = document.getElementById('propuesta-banner');
        
        if (!banner) return;
        
        const esReceptor = coord.es_receptor;
        const yaConfirme = esReceptor ? coord.confirmado_por_receptor : coord.confirmado_por_solicitante;
        const otroConfirmo = esReceptor ? coord.confirmado_por_solicitante : coord.confirmado_por_receptor;
        
        banner.innerHTML = `
            <div class="propuesta-banner-content coordinacion-activa">
                <div class="propuesta-banner-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="coordinacion-detalles">
                    <h4><i class="fas fa-handshake"></i> Coordinación de Intercambio</h4>
                    
                    <div class="coordinacion-info-grid">
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <strong>Lugar:</strong> ${coord.lugar_propuesto}
                        </div>
                        <div class="info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <strong>Fecha:</strong> ${formatearFecha(coord.fecha_hora_propuesta)}
                        </div>
                        ${coord.notas ? `
                            <div class="info-item info-full">
                                <i class="fas fa-sticky-note"></i>
                                <strong>Notas:</strong> ${coord.notas}
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="confirmacion-estados">
                        <div class="estado-confirmacion ${coord.confirmado_por_solicitante ? 'confirmado' : ''}">
                            <i class="fas ${coord.confirmado_por_solicitante ? 'fa-check-circle' : 'fa-clock'}"></i>
                            ${coord.solicitante_nombre} ${coord.confirmado_por_solicitante ? 'confirmó' : 'pendiente'}
                        </div>
                        <div class="estado-confirmacion ${coord.confirmado_por_receptor ? 'confirmado' : ''}">
                            <i class="fas ${coord.confirmado_por_receptor ? 'fa-check-circle' : 'fa-clock'}"></i>
                            ${coord.receptor_nombre} ${coord.confirmado_por_receptor ? 'confirmó' : 'pendiente'}
                        </div>
                    </div>
                </div>
                <div class="coordinacion-actions">
                    ${!yaConfirme ? `
                        <button class="btn-coordinacion btn-confirmar" onclick="confirmarCoordinacion(${propuestaId})">
                            <i class="fas fa-check"></i> Confirmar
                        </button>
                        <button class="btn-coordinacion btn-proponer-cambio" onclick="abrirModalCoordinacion(${propuestaId})">
                            <i class="fas fa-edit"></i> Proponer Cambios
                        </button>
                    ` : otroConfirmo ? `
                        <div class="intercambio-confirmado">
                            <i class="fas fa-check-double"></i>
                            <p>¡Intercambio confirmado!</p>
                            <button class="btn-coordinacion btn-marcar-realizado" onclick="marcarIntercambioRealizado(${propuestaId})">
                                <i class="fas fa-handshake"></i> Marcar como Realizado
                            </button>
                        </div>
                    ` : `
                        <div class="esperando-confirmacion">
                            <i class="fas fa-hourglass-half"></i>
                            <p>Esperando confirmación del otro usuario...</p>
                        </div>
                    `}
                </div>
            </div>
        `;
        
    } catch (error) {
        console.error('Error al mostrar panel de coordinación:', error);
    }
}

// Función para confirmar coordinación
async function confirmarCoordinacion(propuestaId) {
    try {
        const response = await fetch('/api/confirmar-coordinacion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                propuesta_id: propuestaId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('✅ Coordinación confirmada', 'success');
            mostrarPanelCoordinacion(propuestaId);
        } else {
            showNotification(data.message || 'Error al confirmar', 'error');
        }
    } catch (error) {
        console.error('Error al confirmar coordinación:', error);
        showNotification('Error al confirmar coordinación', 'error');
    }
}

// Función para marcar intercambio como realizado
async function marcarIntercambioRealizado(propuestaId) {
    if (!confirm('¿Confirmas que el intercambio se realizó exitosamente?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/marcar-intercambio-realizado.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                propuesta_id: propuestaId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('🎉 ¡Intercambio completado exitosamente!', 'success');
            ocultarBannerPropuesta();
            
            // Recargar mensajes para mostrar confirmación
            if (window.currentChatUserId) {
                loadMessages(window.currentChatUserId);
            }
        } else {
            showNotification(data.message || 'Error al marcar como realizado', 'error');
        }
    } catch (error) {
        console.error('Error al marcar intercambio:', error);
        showNotification('Error al completar el intercambio', 'error');
    }
}

// Función auxiliar para formatear fecha
function formatearFecha(fechaStr) {
    const fecha = new Date(fechaStr);
    const opciones = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return fecha.toLocaleDateString('es-ES', opciones);
}

// Función auxiliar para escapar HTML (debe estar definida globalmente)
if (typeof window.escapeHtml === 'undefined') {
    window.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };
}

// Exportar funciones globalmente
window.renderPropuestaIntercambio = renderPropuestaIntercambio;
window.loadPropuestasPendientes = loadPropuestasPendientes;
window.gestionarPropuesta = gestionarPropuesta;
window.abrirContraoferta = abrirContraoferta;
