// Sistema de notificaciones de mensajes no leídos con integración de Perseo
(function() {
    let notificationCheckInterval = null;
    let lastUnreadCount = 0;
    let perseoNotificationShown = false;
    let unreadSenderIds = [];
    let perseoOpenedChat = false; // Flag para saber si Perseo abrió el chat
    let socketNotifConnected = false;
    
    // Inicializar el sistema de notificaciones
    function initNotificationSystem() {
        if (!isUserLoggedIn()) {
            return;
        }
        
        // Solo usar polling si Socket.IO no está conectado
        if (!socketNotifConnected) {
            checkUnreadMessages();
            notificationCheckInterval = setInterval(checkUnreadMessages, 15000);
        }
        // Limpiar interval al cerrar la página
        window.addEventListener('beforeunload', () => {
            if (notificationCheckInterval) {
                clearInterval(notificationCheckInterval);
            }
        });
    }
    
    // Verificar si el usuario está logueado
    function isUserLoggedIn() {
        // Verificar si existe el header con información de usuario
        const userHeader = document.querySelector('.dropdown-header');
        return userHeader !== null;
    }
    
    // Verificar mensajes no leídos
    async function checkUnreadMessages() {
        try {
            const response = await fetch('api/get-total-unread.php');
            const data = await response.json();
            if (data.success) {
                const currentUnread = data.total;
                updateMenuBadge(currentUnread);
                // Si hay nuevos mensajes no leídos (el contador aumenta)
                if (currentUnread > lastUnreadCount && currentUnread > 0) {
                    await getUnreadSenders();
                    perseoNotificationShown = false; // Permitir notificación
                    window.perseoNotificationBlocked = false; // Permitir notificación de nuevo
                    
                    // Limpiar localStorage para que Perseo vuelva a preguntar con los nuevos mensajes
                    localStorage.removeItem('perseoLastAskedTimestamp');
                    localStorage.removeItem('perseoLastAskedCount');
                    localStorage.removeItem('perseoUserDeclined');
                    
                    notifyPerseoAboutUnreadMessages(currentUnread); // Lanzar notificación inmediatamente
                }
                // Si no hay mensajes sin leer, resetear la bandera y el bloqueo
                if (currentUnread === 0) {
                    perseoNotificationShown = false;
                    unreadSenderIds = [];
                    window.perseoNotificationBlocked = false;
                    window.perseoLastUnreadCount = 0;
                }
                lastUnreadCount = currentUnread;
                // Consultar solicitudes pendientes y actualizar badge
                checkPendingRequests();
            }
        } catch (error) {
            console.error('Error al verificar mensajes no leídos:', error);
        }
    }

    // Verificar solicitudes de amistad pendientes
    async function checkPendingRequests() {
        try {
            const response = await fetch('api/amistades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'listar_solicitudes_pendientes' })
            });
            const result = await response.json();
            if (result.status === 'success' && Array.isArray(result.data)) {
                updateRequestsBadge(result.data.length);
            } else {
                updateRequestsBadge(0);
                showSolicitudesError(result.message || 'No se pudo obtener las solicitudes.');
            }
        } catch (err) {
            updateRequestsBadge(0);
            showSolicitudesError('Error de conexión o sesión no activa.');
        }
    }

    // Mostrar mensaje de error de solicitudes
    function showSolicitudesError(msg) {
        const messagesLink = document.querySelector('a[href="mensajeria.php"]');
        if (!messagesLink) return;
        let errorMsg = messagesLink.querySelector('.solicitudes-error-msg');
        if (errorMsg) errorMsg.remove();
        errorMsg = document.createElement('span');
        errorMsg.className = 'solicitudes-error-msg';
        errorMsg.textContent = msg;
        errorMsg.style.color = '#ff6b6b';
        errorMsg.style.fontSize = '12px';
        errorMsg.style.marginLeft = '8px';
        errorMsg.style.fontWeight = 'bold';
        messagesLink.querySelector('.dropdown-item').appendChild(errorMsg);
        setTimeout(() => { if (errorMsg) errorMsg.remove(); }, 6000);
    }

    // Actualizar badge de solicitudes en el menú
    function updateRequestsBadge(count) {
        const messagesLink = document.querySelector('a[href="mensajeria.php"]');
        if (!messagesLink) return;
        let solicitudesBadge = messagesLink.querySelector('.solicitudes-badge');
        // Remover si existe
        if (solicitudesBadge) solicitudesBadge.remove();
        // Solo mostrar si hay solicitudes
        if (count > 0) {
            solicitudesBadge = document.createElement('span');
            solicitudesBadge.className = 'solicitudes-badge';
            solicitudesBadge.textContent = count > 99 ? '99+' : count;
            solicitudesBadge.style.background = '#ff6b6b';
            solicitudesBadge.style.color = 'white';
            solicitudesBadge.style.fontWeight = 'bold';
            solicitudesBadge.style.fontSize = '12px';
            solicitudesBadge.style.borderRadius = '8px';
            solicitudesBadge.style.padding = '2px 7px';
            solicitudesBadge.style.marginLeft = '6px';
            solicitudesBadge.style.verticalAlign = 'middle';
            messagesLink.querySelector('.dropdown-item').appendChild(solicitudesBadge);
        }
    }
    
    // Obtener IDs de los remitentes con mensajes no leídos
    async function getUnreadSenders() {
        try {
            const response = await fetch('api/get-unread-count.php');
            const data = await response.json();
            
            if (data.status === 'success') {
                unreadSenderIds = Object.keys(data.unread_counts).map(id => parseInt(id));
            }
        } catch (error) {
            console.error('Error al obtener remitentes:', error);
        }
    }
    
    // Actualizar badge en el menú dropdown
    function updateMenuBadge(count) {
        const messagesLink = document.querySelector('a[href="mensajeria.php"]');
        if (!messagesLink) return;
        const messagesButton = messagesLink.querySelector('.dropdown-item');
        if (!messagesButton) return;

        // Remover badge existente en el menú de mensajes
        const existingBadge = messagesButton.querySelector('.unread-badge');
        if (existingBadge) {
            existingBadge.remove();
        }

        // Remover badge existente en el menú hamburguesa
        const menuToggle = document.getElementById('menu-toggle');
        if (menuToggle) {
            const badgeHamburguesa = menuToggle.parentElement.querySelector('.unread-badge');
            if (badgeHamburguesa) badgeHamburguesa.remove();
        }

        // Agregar nuevo badge si hay mensajes
        if (count > 0) {
            const badge = document.createElement('span');
            badge.className = 'unread-badge';
            badge.textContent = count > 99 ? '99+' : count;
            messagesButton.appendChild(badge);

            // También agregar badge al menú hamburguesa
            if (menuToggle) {
                const badge2 = document.createElement('span');
                badge2.className = 'unread-badge';
                badge2.textContent = badge.textContent;
                badge2.style.position = 'absolute';
                badge2.style.bottom = '2px';
                badge2.style.right = '-6px';
                badge2.style.top = '';
                badge2.style.zIndex = '100001';
                menuToggle.parentElement.style.position = 'relative';
                menuToggle.parentElement.appendChild(badge2);
            }
        }
    }
    
    // Agregar botones de respuesta automática
    function addPerseoAutoReplyButtons() {
        const chatMessages = document.getElementById('chatbot-messages');
        if (!chatMessages) return;
        // Verificar si ya existen botones
        if (document.querySelector('.perseo-auto-reply-buttons')) return;

        const buttonsDiv = document.createElement('div');
        buttonsDiv.className = 'perseo-auto-reply-buttons';
        buttonsDiv.style.display = 'flex';
        buttonsDiv.style.gap = '10px';
        buttonsDiv.style.marginTop = '10px';

        const btnSi = document.createElement('button');
        btnSi.textContent = 'Sí, responde por mí';
        btnSi.className = 'perseo-btn perseo-btn-yes';
        btnSi.onclick = function() {
            // Acción de respuesta automática
            window.perseoAutoReplyEnabled = true;
            buttonsDiv.remove();
        };

        const btnNo = document.createElement('button');
        btnNo.textContent = 'No, gracias';
        btnNo.className = 'perseo-btn perseo-btn-no';
        btnNo.onclick = function() {
            // Desactivar notificación hasta que haya nuevos mensajes
            window.perseoNotificationBlocked = true;
            window.perseoLastUnreadCount = lastUnreadCount;
            
            // Actualizar localStorage para que no vuelva a preguntar hasta nuevos mensajes
            localStorage.setItem('perseoUserDeclined', 'true');
            
            buttonsDiv.remove();
            // Asegurar que el chat esté abierto antes de mostrar los mensajes
            const chatbotContainer = document.getElementById('chatbot-container');
            const chatbotIcon = document.getElementById('chatbot-icon');
            if (chatbotContainer && chatbotContainer.classList.contains('hidden') && chatbotIcon) {
                chatbotIcon.click();
            }
            // Mostrar mensaje del usuario en el chat
            if (window.agregarMensajePerseo) {
                setTimeout(function() {
                    window.agregarMensajePerseo('No, gracias', 'user');
                    setTimeout(function() {
                        window.agregarMensajePerseo('¡Entendido! No te molesto más por ahora. Si necesitas ayuda, aquí estaré. 😊', 'bot');
                    }, 500);
                }, 300);
            }
        };

        buttonsDiv.appendChild(btnSi);
        buttonsDiv.appendChild(btnNo);
        chatMessages.appendChild(buttonsDiv);
    }

    // Modificar notificación para respetar bloqueo
    function notifyPerseoAboutUnreadMessages(count) {
        // Verificar si el usuario rechazó la notificación
        const userDeclined = localStorage.getItem('perseoUserDeclined');
        if (userDeclined === 'true') {
            console.log('[Perseo] Usuario rechazó auto-reply, no vuelvo a preguntar hasta nuevos mensajes');
            return;
        }
        
        // Obtener el timestamp del último mensaje desde localStorage
        const lastAskedTimestamp = localStorage.getItem('perseoLastAskedTimestamp');
        const lastAskedCount = localStorage.getItem('perseoLastAskedCount');
        
        // Si el usuario ya respondió a la pregunta para esta cantidad de mensajes, no preguntar de nuevo
        if (lastAskedTimestamp && lastAskedCount === count.toString()) {
            console.log('[Perseo] Ya pregunté sobre estos mensajes, no vuelvo a preguntar hasta que lleguen nuevos');
            return;
        }
        
        // Si el usuario bloqueó la notificación y no hay nuevos mensajes, no notificar
        if (window.perseoNotificationBlocked && window.perseoLastUnreadCount === count) {
            return;
        }
        
        // Esperar un poco antes de que Perseo notifique (para no ser intrusivo)
        setTimeout(() => {
            // Abrir automáticamente el chat de Perseo si está disponible
            const chatbotIcon = document.getElementById('chatbot-icon');
            if (chatbotIcon && chatbotIcon.style.display !== 'none') {
                // Marcar que Perseo está abriendo el chat
                perseoOpenedChat = true;
                window.perseoOpenedChat = true; // Exponer globalmente
                
                // Limpiar mensajes de bienvenida si existen
                const chatMessages = document.getElementById('chatbot-messages');
                if (chatMessages) {
                    chatMessages.innerHTML = '';
                }
                
                chatbotIcon.click();
                
                // Esperar a que se abra el chat
                setTimeout(() => {
                    showPerseoUnreadNotification(count);
                    // Resetear la flag después de mostrar la notificación
                    setTimeout(() => {
                        perseoOpenedChat = false;
                        window.perseoOpenedChat = false;
                    }, 1000);
                }, 500);
            }
        }, 2000); // 2 segundos después de detectar mensajes
    }
    
    // Mostrar notificación de Perseo sobre mensajes no leídos
    function showPerseoUnreadNotification(count) {
        const mensaje = `Tienes ${count} mensaje${count > 1 ? 's' : ''} sin leer!\n\n¿Quieres que responda automaticamente por ti indicando que no estas disponible?`;
        
        // Guardar que ya preguntamos sobre estos mensajes
        localStorage.setItem('perseoLastAskedTimestamp', Date.now().toString());
        localStorage.setItem('perseoLastAskedCount', count.toString());
        
        // Agregar mensaje de Perseo al chat
        if (window.agregarMensajePerseo) {
            window.agregarMensajePerseo(mensaje);
            
            // Agregar botones de opción
            setTimeout(() => {
                addPerseoAutoReplyButtons();
            }, 500);
        }
    }
    
    // Funciones para manejar respuestas automáticas
    window.perseoAutoReply = {
        accept: async function() {
            // Deshabilitar botones
            const buttons = document.querySelectorAll('.perseo-auto-reply-buttons button');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.5';
            });
            
            if (window.agregarMensajePerseo) {
                window.agregarMensajePerseo('Enviando respuestas automaticas...');
            }
            
            try {
                const response = await fetch('api/perseo-auto-reply.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sender_ids: unreadSenderIds
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (window.agregarMensajePerseo) {
                        window.agregarMensajePerseo(`Listo! He enviado ${data.count} respuesta${data.count > 1 ? 's' : ''} automatica${data.count > 1 ? 's' : ''} indicando que no estas disponible.\n\nLos mensajes estan identificados como respuestas automaticas para que los demas sepan que fue enviado por mi.`);
                    }
                    
                    // Remover botones después de éxito
                    setTimeout(() => {
                        const buttonContainer = document.querySelector('.perseo-auto-reply-buttons');
                        if (buttonContainer) {
                            buttonContainer.style.opacity = '0';
                            setTimeout(() => buttonContainer.remove(), 300);
                        }
                    }, 1000);
                    
                    // Resetear banderas
                    perseoNotificationShown = false;
                    unreadSenderIds = [];
                    
                    // Limpiar localStorage - ya respondió
                    localStorage.removeItem('perseoLastAskedTimestamp');
                    localStorage.removeItem('perseoLastAskedCount');
                    
                } else {
                    if (window.agregarMensajePerseo) {
                        window.agregarMensajePerseo(`Lo siento, hubo un problema: ${data.message}`);
                    }
                }
            } catch (error) {
                console.error('Error al enviar respuestas automáticas:', error);
                if (window.agregarMensajePerseo) {
                    window.agregarMensajePerseo('Error al enviar las respuestas automaticas. Por favor, intenta mas tarde.');
                }
            }
        },
        
        decline: function() {
            if (window.agregarMensajePerseo) {
                window.agregarMensajePerseo('Entendido. No enviare respuestas automaticas. Puedes revisar tus mensajes cuando quieras.');
            }
            
            // Remover botones
            setTimeout(() => {
                const buttonContainer = document.querySelector('.perseo-auto-reply-buttons');
                if (buttonContainer) {
                    buttonContainer.style.opacity = '0';
                    setTimeout(() => buttonContainer.remove(), 300);
                }
            }, 500);
            
            // Resetear bandera para que pueda preguntar nuevamente más tarde
            perseoNotificationShown = false;
        }
    };
    
    // Integración robusta con Socket.IO para notificación instantánea
    function tryInitSocketNotif(attempts = 0) {
    if (window.io && window.CURRENT_USER_ID) {
            const CHAT_SERVER_URL = window.CHAT_SERVER_URL || 'http://localhost:3000';
            console.log('[Socket.IO] Intentando conectar a:', CHAT_SERVER_URL);
            const socketNotif = window.io(CHAT_SERVER_URL, {
                reconnection: true,
                reconnectionAttempts: 10,
                reconnectionDelay: 500,
                timeout: 2000
            });
            socketNotif.on('connect', () => {
                console.log('[Socket.IO] Conectado, emitiendo user_connected:', window.CURRENT_USER_ID);
                socketNotif.emit('user_connected', window.CURRENT_USER_ID);
                socketNotifConnected = true;
                // Detener polling si estaba activo
                if (notificationCheckInterval) {
                    clearInterval(notificationCheckInterval);
                    notificationCheckInterval = null;
                }
            });
            socketNotif.on('reconnect', (attempt) => {
                console.log('[Socket.IO] Reconectado en intento:', attempt);
                socketNotif.emit('user_connected', window.CURRENT_USER_ID);
            });
            socketNotif.on('chat_message', async (data) => {
                const clientReceiveTime = Date.now();
                console.log('[Socket.IO] chat_message recibido:', data, '| ServerEmitTime:', data.serverEmitTime, '| ClientReceiveTime:', clientReceiveTime, '| Delay(ms):', clientReceiveTime - (data.serverEmitTime || clientReceiveTime));
                if (data.to === window.CURRENT_USER_ID && window.location.pathname.indexOf('mensajeria.php') === -1) {
                    // Consultar el número real de mensajes no leídos y actualizar badge en tiempo real
                    try {
                        const response = await fetch('api/get-total-unread.php');
                        const result = await response.json();
                        if (result.success) {
                            lastUnreadCount = result.total;
                            updateMenuBadge(lastUnreadCount);
                            notifyPerseoAboutUnreadMessages(lastUnreadCount);
                            console.log('[Socket.IO] Badge actualizado a (real):', lastUnreadCount);
                        } else {
                            console.warn('[Socket.IO] Error al obtener el total de no leídos en tiempo real');
                        }
                    } catch (err) {
                        console.error('[Socket.IO] Error en consulta de no leídos:', err);
                    }
                } else {
                    console.log('[Socket.IO] chat_message ignorado (no es para este usuario o está en mensajeria.php)');
                }
            });
            socketNotif.on('disconnect', (reason) => {
                console.warn('[Socket.IO] Desconectado:', reason);
                socketNotifConnected = false;
                // Reactivar polling como respaldo
                if (!notificationCheckInterval) {
                    checkUnreadMessages();
                    notificationCheckInterval = setInterval(checkUnreadMessages, 15000);
                }
            });
            socketNotif.on('reconnect_error', (error) => {
                console.error('[Socket.IO] Error de reconexión:', error);
            });
        } else if (attempts < 20) {
        } else if (attempts < 20) {
        } else if (attempts < 20) {
            // Reintentar cada 250ms hasta 5 segundos
            setTimeout(() => tryInitSocketNotif(attempts + 1), 250);
        } else {
            console.warn('No se pudo inicializar Socket.IO para notificaciones después de varios intentos.');
        }
    }
    tryInitSocketNotif();
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotificationSystem);
    } else {
        initNotificationSystem();
    }
})();
