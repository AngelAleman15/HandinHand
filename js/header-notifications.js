/**
 * Sistema de notificaciones en el header
 * Maneja: Solicitudes de amistad, intercambios, seguimiento, etc.
 * NO incluye mensajes del chat
 */

(function() {
    'use strict';
    
    let notificationsPanel = null;
    let notificationsBadge = null;
    let notificationsBtn = null;
    let notificationsList = null;
    let markAllReadBtn = null;
    
    // Iconos para cada tipo de notificación
    const NOTIFICATION_ICONS = {
        'solicitud_amistad': 'fa-user-plus',
        'amistad_aceptada': 'fa-user-check',
        'propuesta_intercambio': 'fa-exchange-alt',
        'intercambio_aceptado': 'fa-check-circle',
        'intercambio_rechazado': 'fa-times-circle',
        'contraoferta': 'fa-reply',
        'en_camino': 'fa-car',
        'demorado': 'fa-clock',
        'entregado': 'fa-box-check',
        'intercambio_completado': 'fa-trophy',
        'denuncia': 'fa-exclamation-triangle',
        'valoracion': 'fa-star'
    };
    
    function initNotifications() {
        notificationsBtn = document.getElementById('notifications-btn');
        notificationsPanel = document.getElementById('notifications-panel');
        notificationsBadge = document.getElementById('notifications-badge');
        notificationsList = document.getElementById('notifications-list');
        markAllReadBtn = document.getElementById('mark-all-read-btn');
        
        if (!notificationsBtn || !notificationsPanel) {
            return; // No hay botón de notificaciones (usuario no logueado)
        }
        
        // Toggle panel al hacer clic en el botón
        notificationsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleNotificationsPanel();
        });
        
        // Marcar todas como leídas
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function() {
                marcarTodasLeidas();
            });
        }
        
        // Cerrar panel al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!notificationsPanel.contains(e.target) && !notificationsBtn.contains(e.target)) {
                closeNotificationsPanel();
            }
        });
        
        // Cargar notificaciones iniciales
        cargarNotificaciones();
        
        // Actualizar cada 30 segundos
        setInterval(cargarNotificaciones, 30000);
        
        // Escuchar nuevas notificaciones por Socket.IO
        if (window.globalSocket) {
            window.globalSocket.on('nueva_notificacion', function(data) {
                console.log('📬 Nueva notificación recibida:', data);
                cargarNotificaciones();
                mostrarToastNotificacion(data);
            });
        }
    }
    
    function toggleNotificationsPanel() {
        const isVisible = notificationsPanel.style.display === 'block';
        
        if (isVisible) {
            closeNotificationsPanel();
        } else {
            openNotificationsPanel();
        }
    }
    
    function openNotificationsPanel() {
        notificationsPanel.style.display = 'block';
        cargarNotificaciones();
    }
    
    function closeNotificationsPanel() {
        notificationsPanel.style.display = 'none';
    }
    
    async function cargarNotificaciones() {
        try {
            const response = await fetch('api/notificaciones.php');
            
            // Verificar si la respuesta es JSON válido
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('❌ La API no devolvió JSON. Revisa que la tabla notificaciones exista.');
                notificationsList.innerHTML = `
                    <div class="empty-notifications">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error al cargar notificaciones</p>
                        <small>Ejecuta el archivo SQL primero</small>
                    </div>
                `;
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                actualizarBadge(data.total_no_leidas);
                renderNotificaciones(data.notificaciones);
                
                // Mostrar warning si existe
                if (data.warning) {
                    console.warn('⚠️', data.warning);
                }
            } else {
                console.error('❌ Error en respuesta:', data.error);
            }
        } catch (error) {
            console.error('❌ Error al cargar notificaciones:', error);
            notificationsList.innerHTML = `
                <div class="empty-notifications">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error de conexión</p>
                    <small>${error.message}</small>
                </div>
            `;
        }
    }
    
    function actualizarBadge(count) {
        if (count > 0) {
            notificationsBadge.textContent = count > 99 ? '99+' : count;
            notificationsBadge.style.display = 'flex';
        } else {
            notificationsBadge.style.display = 'none';
        }
    }
    
    function renderNotificaciones(notificaciones) {
        if (!notificaciones || notificaciones.length === 0) {
            notificationsList.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <p>No tienes notificaciones</p>
                </div>
            `;
            return;
        }
        
        notificationsList.innerHTML = notificaciones.map(notif => {
            const iconClass = NOTIFICATION_ICONS[notif.tipo] || notif.icono || 'fa-bell';
            const leidaClass = notif.leida == 1 ? 'leida' : 'no-leida';
            const tiempoRelativo = obtenerTiempoRelativo(notif.created_at);
            
            return `
                <div class="notification-item ${leidaClass}" data-id="${notif.id}" data-url="${notif.url || '#'}">
                    <div class="notification-icon">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${notif.titulo}</div>
                        <div class="notification-message">${notif.mensaje}</div>
                        <div class="notification-time">
                            <i class="fas fa-clock"></i> ${tiempoRelativo}
                        </div>
                    </div>
                    ${notif.leida == 0 ? '<div class="notification-dot"></div>' : ''}
                </div>
            `;
        }).join('');
        
        // Añadir event listeners a cada notificación
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const id = this.dataset.id;
                const url = this.dataset.url;
                
                marcarComoLeida(id, url);
            });
        });
    }
    
    async function marcarComoLeida(notificacionId, url) {
        try {
            await fetch('api/marcar-notificacion-leida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notificacion_id: notificacionId })
            });
            
            // Redirigir si hay URL
            if (url && url !== '#' && url !== 'null') {
                window.location.href = url;
            } else {
                cargarNotificaciones();
            }
        } catch (error) {
            console.error('❌ Error al marcar notificación:', error);
        }
    }
    
    async function marcarTodasLeidas() {
        try {
            await fetch('api/marcar-notificacion-leida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ marcar_todas: true })
            });
            
            cargarNotificaciones();
        } catch (error) {
            console.error('❌ Error al marcar todas:', error);
        }
    }
    
    function mostrarToastNotificacion(data) {
        // Mostrar notificación toast usando SweetAlert2
        if (window.Swal) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            
            Toast.fire({
                icon: 'info',
                title: data.titulo || 'Nueva notificación',
                text: data.mensaje
            });
        }
    }
    
    function obtenerTiempoRelativo(fecha) {
        const ahora = new Date();
        const fechaNotif = new Date(fecha);
        const diffMs = ahora - fechaNotif;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHoras = Math.floor(diffMin / 60);
        const diffDias = Math.floor(diffHoras / 24);
        
        if (diffSec < 60) {
            return 'Ahora mismo';
        } else if (diffMin < 60) {
            return `Hace ${diffMin} min`;
        } else if (diffHoras < 24) {
            return `Hace ${diffHoras}h`;
        } else if (diffDias === 1) {
            return 'Ayer';
        } else if (diffDias < 7) {
            return `Hace ${diffDias} días`;
        } else {
            return fechaNotif.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        }
    }
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotifications);
    } else {
        initNotifications();
    }
})();
