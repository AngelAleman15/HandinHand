/**
 * Sistema de gestión de intercambios activos
 * Muestra panel de seguimiento con acciones rápidas
 */

(function() {
    'use strict';
    
    let intercambiosActivos = [];
    let intercambiosCompletados = [];
    let seguimientoActual = null;
    
    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initTabs();
        cargarIntercambiosActivos();
        
        // Actualizar cada 30 segundos
        setInterval(cargarIntercambiosActivos, 30000);
    });
    
    function initTabs() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                switchTab(tabName);
            });
        });
    }
    
    function switchTab(tabName) {
        console.log('Cambiando a tab:', tabName); // Debug
        
        // Actualizar botones
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });
        
        // Actualizar contenido - mostrar/ocultar tabs
        const tabActivos = document.getElementById('tab-activos');
        const tabCompletados = document.getElementById('tab-completados');
        
        if (tabName === 'activos') {
            tabActivos.style.display = 'block';
            tabCompletados.style.display = 'none';
        } else if (tabName === 'completados') {
            tabActivos.style.display = 'none';
            tabCompletados.style.display = 'block';
            
            // Cargar datos completados si aún no se han cargado
            if (intercambiosCompletados.length === 0) {
                console.log('Cargando intercambios completados...'); // Debug
                cargarIntercambiosCompletados();
            }
        }
    }
    
    async function cargarIntercambiosActivos() {
        try {
            const response = await fetch('api/mis-intercambios-activos.php');
            const data = await response.json();
            
            if (data.success) {
                intercambiosActivos = data.intercambios;
                renderIntercambiosActivos(intercambiosActivos);
            }
        } catch (error) {
            console.error('Error al cargar intercambios activos:', error);
        }
    }
    
    async function cargarIntercambiosCompletados() {
        try {
            const container = document.getElementById('intercambios-completados-container');
            const emptyState = document.getElementById('empty-state-completados');
            
            container.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>';
            container.style.display = 'grid';
            emptyState.style.display = 'none';
            
            const response = await fetch('api/mis-intercambios-completados.php');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                intercambiosCompletados = data.intercambios;
                renderIntercambiosCompletados(intercambiosCompletados);
            } else {
                console.error('Error en la respuesta:', data.error);
                container.innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error al cargar intercambios: ${data.error || 'Error desconocido'}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error al cargar intercambios completados:', error);
            const container = document.getElementById('intercambios-completados-container');
            container.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error de conexión: ${error.message}</p>
                </div>
            `;
        }
    }
    
    function renderIntercambiosActivos(intercambios) {
        const container = document.getElementById('intercambios-activos-container');
        const emptyState = document.getElementById('empty-state-activos');
        
        if (!intercambios || intercambios.length === 0) {
            container.style.display = 'none';
            emptyState.style.display = 'flex';
            return;
        }
        
        emptyState.style.display = 'none';
        container.style.display = 'grid';
        
        container.innerHTML = intercambios.map(intercambio => {
            return renderIntercambioCard(intercambio);
        }).join('');
    }
    
    function renderIntercambioCard(intercambio) {
        const estadoInfo = getEstadoInfo(intercambio.estado);
        const esUsuario1 = intercambio.soy_usuario1;
        
        // Determinar si el usuario actual ya marcó como entregado
        const yoMarqueEntregado = esUsuario1 ? intercambio.usuario1_entregado : intercambio.usuario2_entregado;
        const otroMarcoEntregado = esUsuario1 ? intercambio.usuario2_entregado : intercambio.usuario1_entregado;
        
        return `
            <div class="intercambio-card" data-seguimiento-id="${intercambio.id}">
                <div class="card-header">
                    <div class="estado-badge estado-${intercambio.estado}">
                        <i class="${estadoInfo.icono}"></i> ${estadoInfo.texto}
                    </div>
                    <div class="fecha-creacion">
                        <i class="fas fa-clock"></i> ${formatearFechaRelativa(intercambio.fecha_aceptacion)}
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="usuario-info">
                        <img src="${intercambio.otro_usuario_avatar || '/img/usuario.svg'}" alt="Avatar" class="usuario-avatar">
                        <div>
                            <h4>Intercambio con ${intercambio.otro_usuario_nombre}</h4>
                            <p class="text-muted">ID del intercambio: #${intercambio.id}</p>
                        </div>
                    </div>
                    
                    <div class="productos-intercambio">
                        <div class="producto-item">
                            <div class="producto-label">${esUsuario1 ? 'Das' : 'Recibes'}</div>
                            <img src="${intercambio.producto_ofrecido_imagen || '/img/placeholder-producto.svg'}" 
                                 alt="${intercambio.producto_ofrecido_nombre}"
                                 onerror="this.src='/img/placeholder-producto.svg'">
                            <p>${intercambio.producto_ofrecido_nombre || 'Producto'}</p>
                        </div>
                        
                        <div class="intercambio-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        
                        <div class="producto-item">
                            <div class="producto-label">${esUsuario1 ? 'Recibes' : 'Das'}</div>
                            <img src="${intercambio.producto_solicitado_imagen || '/img/placeholder-producto.svg'}" 
                                 alt="${intercambio.producto_solicitado_nombre}"
                                 onerror="this.src='/img/placeholder-producto.svg'">
                            <p>${intercambio.producto_solicitado_nombre || 'Producto'}</p>
                        </div>
                    </div>
                    
                    ${intercambio.lugar_encuentro ? `
                        <div class="info-encuentro">
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>${intercambio.lugar_encuentro}</span>
                            </div>
                            ${intercambio.fecha_encuentro ? `
                                <div class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>${formatearFecha(intercambio.fecha_encuentro)}</span>
                                </div>
                            ` : ''}
                        </div>
                    ` : ''}
                    
                    ${intercambio.acciones_recientes && intercambio.acciones_recientes.length > 0 ? `
                        <div class="actividad-reciente">
                            <h5><i class="fas fa-history"></i> Actividad reciente</h5>
                            ${intercambio.acciones_recientes.slice(0, 3).map(accion => `
                                <div class="accion-item">
                                    <span class="accion-usuario">${accion.usuario_nombre}:</span>
                                    <span class="accion-tipo">${getTipoAccionTexto(accion.tipo)}</span>
                                    <span class="accion-tiempo">${formatearFechaRelativa(accion.created_at)}</span>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
                
                <div class="card-footer">
                    <div class="acciones-rapidas">
                        ${intercambio.estado !== 'completado' && intercambio.estado !== 'cancelado' ? `
                            ${!yoMarqueEntregado ? `
                                <button class="btn-accion btn-en-camino" onclick="marcarEnCamino(${intercambio.id})" title="Estoy en camino">
                                    <i class="fas fa-car"></i> En camino
                                </button>
                                <button class="btn-accion btn-demorado" onclick="abrirMensajeRapido(${intercambio.id}, 'demorado')" title="Reportar demora">
                                    <i class="fas fa-clock"></i> Demorado
                                </button>
                            ` : ''}
                            
                            ${yoMarqueEntregado && !otroMarcoEntregado ? `
                                <div class="esperando-confirmacion">
                                    <i class="fas fa-hourglass-half"></i> Esperando confirmación del otro usuario
                                </div>
                            ` : ''}
                            
                            ${!yoMarqueEntregado ? `
                                <button class="btn-accion btn-entregado" onclick="marcarEntregado(${intercambio.id})" title="Marcar como entregado">
                                    <i class="fas fa-check-circle"></i> Entregado
                                </button>
                            ` : ''}
                            
                            <button class="btn-accion btn-denunciar" onclick="abrirModalDenuncia(${intercambio.id})" title="Reportar problema">
                                <i class="fas fa-flag"></i> Denunciar
                            </button>
                            
                            <button class="btn-accion btn-chat" onclick="abrirChat(${intercambio.otro_usuario_id})" title="Abrir chat">
                                <i class="fas fa-comments"></i> Chat
                            </button>
                        ` : `
                            <div class="intercambio-finalizado">
                                <i class="fas fa-${intercambio.estado === 'completado' ? 'trophy' : 'ban'}"></i>
                                ${intercambio.estado === 'completado' ? 'Intercambio completado' : 'Intercambio cancelado'}
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
    }
    
    function getEstadoInfo(estado) {
        const estados = {
            'coordinando': { texto: 'Coordinando', icono: 'fas fa-handshake', color: '#FFA500' },
            'confirmado': { texto: 'Confirmado', icono: 'fas fa-check-circle', color: '#4CAF50' },
            'en_camino_usuario1': { texto: 'Usuario 1 en camino', icono: 'fas fa-car', color: '#2196F3' },
            'en_camino_usuario2': { texto: 'Usuario 2 en camino', icono: 'fas fa-car', color: '#2196F3' },
            'en_camino_ambos': { texto: 'Ambos en camino', icono: 'fas fa-shipping-fast', color: '#2196F3' },
            'entregado_usuario1': { texto: 'Esperando confirmación', icono: 'fas fa-hourglass-half', color: '#FF9800' },
            'entregado_usuario2': { texto: 'Esperando confirmación', icono: 'fas fa-hourglass-half', color: '#FF9800' },
            'completado': { texto: 'Completado', icono: 'fas fa-trophy', color: '#4CAF50' },
            'cancelado': { texto: 'Cancelado', icono: 'fas fa-times-circle', color: '#F44336' },
            'denunciado': { texto: 'Denunciado', icono: 'fas fa-exclamation-triangle', color: '#FF5722' }
        };
        
        return estados[estado] || { texto: estado, icono: 'fas fa-question-circle', color: '#999' };
    }
    
    function getTipoAccionTexto(tipo) {
        const tipos = {
            'en_camino': '🚗 En camino',
            'demorado': '⏰ Reportó demora',
            'mensaje_rapido': '💬 Envió mensaje',
            'entregado': '📦 Marcó como entregado',
            'cancelar': '❌ Canceló',
            'denuncia': '⚠️ Denunció'
        };
        return tipos[tipo] || tipo;
    }
    
    function formatearFecha(fecha) {
        const d = new Date(fecha);
        return d.toLocaleString('es-ES', { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }
    
    function formatearFechaRelativa(fecha) {
        const ahora = new Date();
        const fechaObj = new Date(fecha);
        const diffMs = ahora - fechaObj;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHoras = Math.floor(diffMin / 60);
        const diffDias = Math.floor(diffHoras / 24);
        
        if (diffSec < 60) return 'Ahora mismo';
        if (diffMin < 60) return `Hace ${diffMin} min`;
        if (diffHoras < 24) return `Hace ${diffHoras}h`;
        if (diffDias === 1) return 'Ayer';
        if (diffDias < 7) return `Hace ${diffDias} días`;
        return fechaObj.toLocaleDateString('es-ES');
    }
    
    // Funciones globales para las acciones
    window.marcarEnCamino = async function(seguimientoId) {
        if (!confirm('¿Confirmas que estás en camino al punto de encuentro?')) return;
        
        try {
            const response = await fetch('/api/accion-seguimiento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    seguimiento_id: seguimientoId,
                    accion: 'en_camino'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                mostrarNotificacion('✅ Notificación enviada: Estás en camino', 'success');
                cargarIntercambiosActivos();
            } else {
                mostrarNotificacion(data.error || 'Error al enviar notificación', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error al enviar notificación', 'error');
        }
    };
    
    window.marcarEntregado = async function(seguimientoId) {
        const confirmacion = await Swal.fire({
            title: '¿Confirmar entrega?',
            text: '¿El intercambio se realizó exitosamente y recibiste el producto acordado?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4CAF50',
            cancelButtonColor: '#999',
            confirmButtonText: 'Sí, confirmar entrega',
            cancelButtonText: 'Cancelar'
        });
        
        if (!confirmacion.isConfirmed) return;
        
        try {
            const response = await fetch('/api/accion-seguimiento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    seguimiento_id: seguimientoId,
                    accion: 'entregado'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Entrega confirmada!',
                    text: data.nuevo_estado === 'completado' 
                        ? '🎉 El intercambio se ha completado. Los productos se han removido del inventario.' 
                        : '✅ Tu confirmación ha sido registrada. Esperando que el otro usuario también confirme.',
                    confirmButtonColor: '#4CAF50'
                });
                cargarIntercambiosActivos();
            } else {
                mostrarNotificacion(data.error || 'Error al confirmar entrega', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error al confirmar entrega', 'error');
        }
    };
    
    window.abrirMensajeRapido = async function(seguimientoId, tipo) {
        const { value: mensaje } = await Swal.fire({
            title: tipo === 'demorado' ? '⏰ Reportar demora' : '💬 Mensaje rápido',
            input: 'textarea',
            inputLabel: 'Mensaje',
            inputPlaceholder: tipo === 'demorado' 
                ? 'Ej: Voy 15 minutos tarde por el tráfico...' 
                : 'Escribe tu mensaje...',
            inputAttributes: {
                'aria-label': 'Escribe tu mensaje'
            },
            showCancelButton: true,
            confirmButtonText: 'Enviar',
            cancelButtonText: 'Cancelar'
        });
        
        if (!mensaje) return;
        
        try {
            const response = await fetch('/api/accion-seguimiento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    seguimiento_id: seguimientoId,
                    accion: tipo,
                    mensaje: mensaje
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                mostrarNotificacion('✅ Mensaje enviado', 'success');
                cargarIntercambiosActivos();
            } else {
                mostrarNotificacion(data.error || 'Error al enviar mensaje', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error al enviar mensaje', 'error');
        }
    };
    
    window.abrirModalDenuncia = function(seguimientoId) {
        document.getElementById('denuncia-seguimiento-id').value = seguimientoId;
        document.getElementById('modal-denuncia').style.display = 'flex';
    };
    
    window.cerrarModalDenuncia = function() {
        document.getElementById('modal-denuncia').style.display = 'none';
        document.getElementById('form-denuncia').reset();
    };
    
    window.enviarDenuncia = async function(event) {
        event.preventDefault();
        
        const seguimientoId = document.getElementById('denuncia-seguimiento-id').value;
        const motivo = document.getElementById('denuncia-motivo').value;
        const descripcion = document.getElementById('denuncia-descripcion').value;
        
        try {
            const response = await fetch('/api/denunciar-intercambio.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    seguimiento_id: seguimientoId,
                    motivo: motivo,
                    descripcion: descripcion
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                cerrarModalDenuncia();
                Swal.fire({
                    icon: 'success',
                    title: 'Denuncia enviada',
                    text: 'Un moderador revisará tu denuncia pronto. Gracias por reportar.',
                    confirmButtonColor: '#4CAF50'
                });
                cargarIntercambiosActivos();
            } else {
                mostrarNotificacion(data.error || 'Error al enviar denuncia', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error al enviar denuncia', 'error');
        }
        
        return false;
    };
    
    window.abrirChat = function(usuarioId) {
        window.location.href = `/mensajeria.php?user=${usuarioId}`;
    };
    
    function mostrarNotificacion(mensaje, tipo) {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: tipo,
                title: mensaje,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            alert(mensaje);
        }
    }
    
    function renderIntercambiosCompletados(intercambios) {
        const container = document.getElementById('intercambios-completados-container');
        const emptyState = document.getElementById('empty-state-completados');
        
        if (!intercambios || intercambios.length === 0) {
            container.style.display = 'none';
            emptyState.style.display = 'flex';
            return;
        }
        
        emptyState.style.display = 'none';
        container.style.display = 'grid';
        
        container.innerHTML = intercambios.map(intercambio => {
            const fecha = new Date(intercambio.fecha_completado);
            const fechaStr = fecha.toLocaleDateString('es-ES', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            return `
                <div class="intercambio-card">
                    <div class="card-header">
                        <span class="estado-badge estado-completado">
                            <i class="fas fa-trophy"></i> Completado
                        </span>
                        <span class="fecha-creacion">${fechaStr}</span>
                    </div>
                    
                    <div class="card-body">
                        <div class="usuario-info">
                            <img src="${intercambio.otro_usuario_avatar || 'img/usuario.svg'}" 
                                 alt="${intercambio.otro_usuario_nombre}"
                                 class="usuario-avatar"
                                 onerror="this.src='img/usuario.svg'">
                            <div>
                                <h4>${intercambio.otro_usuario_nombre}</h4>
                                <p class="text-muted">Intercambio completado</p>
                            </div>
                        </div>
                        
                        <div class="productos-intercambio">
                            <div class="producto-item">
                                <span class="producto-label">Entregaste</span>
                                <img src="${intercambio.mi_producto_imagen}" alt="${intercambio.mi_producto}" 
                                     onerror="this.src='img/placeholder.png'">
                                <p>${intercambio.mi_producto}</p>
                            </div>
                            
                            <div class="intercambio-icon">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            
                            <div class="producto-item">
                                <span class="producto-label">Recibiste</span>
                                <img src="${intercambio.otro_producto_imagen}" alt="${intercambio.otro_producto}"
                                     onerror="this.src='img/placeholder.png'">
                                <p>${intercambio.otro_producto}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="acciones-rapidas">
                            ${intercambio.ya_valoro ? `
                                <button class="btn-accion btn-valorado" disabled>
                                    <i class="fas fa-star"></i> Ya valoraste
                                </button>
                            ` : `
                                <button class="btn-accion btn-valorar" onclick="abrirModalValoracion(${intercambio.id}, ${intercambio.otro_usuario_id}, '${intercambio.otro_usuario_nombre}')">
                                    <i class="fas fa-star"></i> Valorar usuario
                                </button>
                            `}
                            <button class="btn-accion btn-chat" onclick="abrirChat(${intercambio.otro_usuario_id})">
                                <i class="fas fa-comment"></i> Mensaje
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    window.abrirModalValoracion = function(seguimientoId, usuarioId, nombreUsuario) {
        Swal.fire({
            title: `<div style="display: flex; align-items: center; gap: 12px; justify-content: center;">
                        <i class="fas fa-star" style="color: #FFD700; font-size: 28px;"></i>
                        <span style="color: #2c3e50;">Valorar a ${nombreUsuario}</span>
                    </div>`,
            html: `
                <div class="valoracion-modal-content">
                    <p style="color: #7f8c8d; margin-bottom: 24px; font-size: 15px;">
                        Tu opinión ayuda a otros usuarios a tomar mejores decisiones
                    </p>
                    
                    <div class="valoracion-section">
                        <label class="valoracion-label">
                            <i class="fas fa-star" style="color: #FFD700;"></i>
                            Calificación
                        </label>
                        <div id="stars-container" class="stars-container">
                            ${[1,2,3,4,5].map(n => `
                                <i class="far fa-star star-rating" data-rating="${n}"></i>
                            `).join('')}
                        </div>
                        <div id="rating-text" class="rating-text"></div>
                    </div>
                    
                    <div class="valoracion-section">
                        <label class="valoracion-label">
                            <i class="fas fa-comment-dots" style="color: #6a994e;"></i>
                            Comentario <span style="color: #95a5a6; font-weight: normal;">(opcional)</span>
                        </label>
                        <textarea 
                            id="valoracion-comentario" 
                            class="valoracion-textarea" 
                            placeholder="¿Cómo fue tu experiencia? Comparte detalles sobre el intercambio, puntualidad, estado del producto, etc."
                            maxlength="500"
                        ></textarea>
                        <div class="char-counter">
                            <span id="char-count">0</span>/500 caracteres
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-paper-plane"></i> Enviar valoración',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            confirmButtonColor: '#6a994e',
            cancelButtonColor: '#95a5a6',
            width: '600px',
            padding: '2rem',
            customClass: {
                popup: 'valoracion-modal',
                title: 'valoracion-modal-title',
                confirmButton: 'valoracion-btn-confirm',
                cancelButton: 'valoracion-btn-cancel'
            },
            didOpen: () => {
                let selectedRating = 0;
                const stars = document.querySelectorAll('.star-rating');
                const ratingText = document.getElementById('rating-text');
                const textarea = document.getElementById('valoracion-comentario');
                const charCount = document.getElementById('char-count');
                
                const ratingTexts = {
                    1: '⭐ Muy malo',
                    2: '⭐⭐ Malo',
                    3: '⭐⭐⭐ Regular',
                    4: '⭐⭐⭐⭐ Bueno',
                    5: '⭐⭐⭐⭐⭐ Excelente'
                };
                
                // Event listeners para estrellas
                stars.forEach(star => {
                    star.addEventListener('click', function() {
                        selectedRating = parseInt(this.dataset.rating);
                        updateStars(selectedRating);
                        ratingText.textContent = ratingTexts[selectedRating];
                        ratingText.style.opacity = '1';
                    });
                    
                    star.addEventListener('mouseenter', function() {
                        const rating = parseInt(this.dataset.rating);
                        updateStars(rating, true);
                    });
                });
                
                document.getElementById('stars-container').addEventListener('mouseleave', () => {
                    updateStars(selectedRating);
                });
                
                function updateStars(rating, isHover = false) {
                    stars.forEach((s, i) => {
                        if (i < rating) {
                            s.classList.remove('far');
                            s.classList.add('fas');
                            s.style.color = '#FFD700';
                            s.style.transform = isHover ? 'scale(1.2)' : 'scale(1)';
                        } else {
                            s.classList.remove('fas');
                            s.classList.add('far');
                            s.style.color = '#ddd';
                            s.style.transform = 'scale(1)';
                        }
                    });
                }
                
                // Contador de caracteres
                textarea.addEventListener('input', function() {
                    charCount.textContent = this.value.length;
                });
                
                window.selectedRating = () => selectedRating;
            },
            preConfirm: async () => {
                const rating = window.selectedRating();
                if (rating === 0) {
                    Swal.showValidationMessage('⚠️ Por favor selecciona una calificación');
                    return false;
                }
                
                const comentario = document.getElementById('valoracion-comentario').value.trim();
                
                try {
                    const response = await fetch('api/valoraciones.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'crear',
                            usuario_id: usuarioId,
                            puntuacion: rating,
                            comentario: comentario || null,
                            seguimiento_id: seguimientoId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (!data.success) {
                        throw new Error(data.message || 'Error al enviar valoración');
                    }
                    
                    return data;
                } catch (error) {
                    Swal.showValidationMessage(`❌ ${error.message}`);
                    return false;
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    icon: 'success',
                    title: '<span style="color: #2c3e50;">¡Valoración enviada!</span>',
                    html: `
                        <p style="color: #7f8c8d; font-size: 15px;">
                            <i class="fas fa-heart" style="color: #e74c3c;"></i>
                            Gracias por compartir tu experiencia con la comunidad
                        </p>
                    `,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#6a994e',
                    customClass: {
                        popup: 'valoracion-success-modal',
                        confirmButton: 'valoracion-btn-confirm'
                    }
                }).then(() => {
                    // Recargar intercambios completados para reflejar el cambio
                    cargarIntercambiosCompletados();
                });
            }
        });
    };
})();
