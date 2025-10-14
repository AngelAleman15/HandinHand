// Sistema de notificaciones de mensajes no leídos con integración de Perseo
(function() {
    let notificationCheckInterval = null;
    let lastUnreadCount = 0;
    let perseoNotificationShown = false;
    let unreadSenderIds = [];
    let perseoOpenedChat = false; // Flag para saber si Perseo abrió el chat
    
    // Inicializar el sistema de notificaciones
    function initNotificationSystem() {
        if (!isUserLoggedIn()) {
            return;
        }
        
        // Verificar mensajes no leídos cada 15 segundos
        checkUnreadMessages();
        notificationCheckInterval = setInterval(checkUnreadMessages, 15000);
        
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
                
                // Actualizar badge en el menú
                updateMenuBadge(currentUnread);
                
                // Si hay nuevos mensajes no leídos
                if (currentUnread > lastUnreadCount && currentUnread > 0) {
                    // Obtener IDs de los remitentes
                    await getUnreadSenders();
                    
                    // Notificar a Perseo solo la primera vez
                    if (!perseoNotificationShown) {
                        notifyPerseoAboutUnreadMessages(currentUnread);
                        perseoNotificationShown = true;
                    }
                }
                
                // Si no hay mensajes sin leer, resetear la bandera
                if (currentUnread === 0) {
                    perseoNotificationShown = false;
                    unreadSenderIds = [];
                }
                
                lastUnreadCount = currentUnread;
            }
        } catch (error) {
            console.error('Error al verificar mensajes no leídos:', error);
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
        
        // Remover badge existente
        const existingBadge = messagesButton.querySelector('.unread-badge');
        if (existingBadge) {
            existingBadge.remove();
        }
        
        // Agregar nuevo badge si hay mensajes
        if (count > 0) {
            const badge = document.createElement('span');
            badge.className = 'unread-badge';
            badge.textContent = count > 99 ? '99+' : count;
            messagesButton.appendChild(badge);
        }
    }
    
    // Notificar a Perseo sobre mensajes no leídos
    function notifyPerseoAboutUnreadMessages(count) {
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
        
        // Agregar mensaje de Perseo al chat
        if (window.agregarMensajePerseo) {
            window.agregarMensajePerseo(mensaje);
            
            // Agregar botones de opción
            setTimeout(() => {
                addPerseoAutoReplyButtons();
            }, 500);
        }
    }
    
    // Agregar botones de respuesta automática
    function addPerseoAutoReplyButtons() {
        const chatMessages = document.getElementById('chatbot-messages');
        if (!chatMessages) return;
        
        // Verificar si ya existen botones
        if (document.querySelector('.perseo-auto-reply-buttons')) return;
        
        const buttonsContainer = document.createElement('div');
        buttonsContainer.className = 'message bot perseo-auto-reply-buttons';
        buttonsContainer.innerHTML = `
            <div class="bot-avatar">P</div>
            <div class="message-content">
                <div class="auto-reply-options">
                    <button class="perseo-btn perseo-btn-yes" onclick="window.perseoAutoReply.accept()">
                        Si, responde por mi
                    </button>
                    <button class="perseo-btn perseo-btn-no" onclick="window.perseoAutoReply.decline()">
                        No, gracias
                    </button>
                </div>
            </div>
        `;
        
        chatMessages.appendChild(buttonsContainer);
        chatMessages.scrollTop = chatMessages.scrollHeight;
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
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotificationSystem);
    } else {
        initNotificationSystem();
    }
})();
