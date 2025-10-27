// Variables globales
// Función global para abrir el chat temporal amarillo al contactar desde index.php
window.contactarVendedor = function(userId) {
    // Verificar si el usuario está logueado
    if (window.IS_LOGGED_IN) {
        window.location.href = '/mensajeria.php?user=' + userId;
    } else {
        showNotification('Debes iniciar sesión para usar la mensajería.', 'error');
    }
};
// Detectar si el usuario está logueado (variable generada en index.php)
window.IS_LOGGED_IN = window.IS_LOGGED_IN || false;
let currentChatUserId = null;
let socket = null;
let onlineUsers = new Set();
let replyingToMessage = null; // Para almacenar el mensaje al que se está respondiendo
let welcomeScreen = null;
let chatPanel = null;
let chatMessages = null;
let messageInput = null;
let sendBtn = null;

// Función para mostrar notificaciones
function showNotification(message, type = 'success') {
    // Crear o usar elemento de notificación existente
    let notification = document.querySelector('.chat-notification');
    
    if (!notification) {
        notification = document.createElement('div');
        notification.className = 'chat-notification';
        document.body.appendChild(notification);
    }
    
    // Limpiar clases anteriores
    notification.classList.remove('show', 'success', 'error');
    
    // Configurar la notificación
    notification.className = `chat-notification ${type}`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    notification.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
    `;
    
    // Mostrar la notificación
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Funciones globales (necesarias para ser llamadas desde HTML inline)
function replyToMessage(messageId, messageText, senderName) {
    replyingToMessage = {
        id: messageId,
        text: messageText,
        sender: senderName
    };

    // Mostrar vista previa de respuesta
    const replyPreview = document.getElementById('reply-preview');
    const replyUsername = document.getElementById('reply-preview-username');
    const replyText = document.getElementById('reply-preview-text');

    if (replyPreview && replyUsername && replyText) {
        replyUsername.textContent = `Respondiendo a ${senderName}`;
        replyText.textContent = messageText;
        replyPreview.classList.add('show');
    }

    // Enfocar el input
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.focus();
    }

    // Cerrar el menú de opciones
    document.querySelectorAll('.message-options-menu').forEach(menu => {
        menu.classList.remove('show');
    });
}

function cancelReply() {
    replyingToMessage = null;
    const replyPreview = document.getElementById('reply-preview');
    if (replyPreview) {
        replyPreview.classList.remove('show');
    }
}

// Función para mostrar el modal de confirmación
function deleteChatHistory() {
    if (!currentChatUserId) return;
    
    const modal = document.getElementById('deleteConfirmModal');
    if (modal) {
        modal.classList.add('show');
    }
    
    // Cerrar el menú de opciones
    const chatOptionsMenu = document.getElementById('chat-options-menu');
    if (chatOptionsMenu) {
        chatOptionsMenu.classList.remove('show');
    }
}

// Función para cerrar el modal
function closeDeleteModal() {
    const modal = document.getElementById('deleteConfirmModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Función para confirmar la eliminación
async function confirmDeleteHistory() {
    if (!currentChatUserId) return;

    try {
    const response = await fetch('/api/delete-chat-history.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                other_user_id: currentChatUserId
            })
        });

        const result = await response.json();

        if (result.status === 'success') {
            // Limpiar mensajes de la pantalla
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.innerHTML = '';
            }
            
            // Cerrar el modal
            closeDeleteModal();
            
            // Mostrar notificación de éxito
            showSuccessNotification('Historial eliminado correctamente');
        } else {
            // Cerrar el modal
            closeDeleteModal();
            
            // Mostrar error
            showErrorNotification('Error al eliminar el historial');
        }
    } catch (error) {
        console.error('Error al eliminar historial:', error);
        
        // Cerrar el modal
        closeDeleteModal();
        
        // Mostrar error
        showErrorNotification('Error de conexión');
    }
}

// Función para mostrar notificación de éxito
function showSuccessNotification(message) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = 'chat-notification success';
    notification.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Función para mostrar notificación de error
function showErrorNotification(message) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = 'chat-notification error';
    notification.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    // Estado global para indicador de escribiendo
    let typingFromUserId = null;
    // Usar el socket global para mantener online en toda la web
    if (window.globalSocket) {
        socket = window.globalSocket;
    }
    // Si no existe, inicializar solo en mensajería
    if (!socket && window.io && window.CHAT_SERVER_URL) {
        socket = io(window.CHAT_SERVER_URL, { transports: ['websocket', 'polling'] });
        socket.emit('user_connected', window.CURRENT_USER_ID);
    }

    // --- INDICADOR DE ESCRIBIENDO ---
    let typingTimeout = null;
    let isTyping = false;
    if (messageInput) {
        messageInput.addEventListener('input', () => {
            if (!currentChatUserId || !socket) return;
            if (!isTyping) {
                socket.emit('typing', { to: currentChatUserId, from: CURRENT_USER_ID });
                isTyping = true;
            }
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                socket.emit('stop_typing', { to: currentChatUserId, from: CURRENT_USER_ID });
                isTyping = false;
            }, 1200);
        });
    }

    // Mostrar animación de escribiendo y recibir mensajes en tiempo real
    if (socket) {
        socket.on('typing', ({ from }) => {
            // Comparar como string para evitar errores de tipo
            if (currentChatUserId && String(from) === String(currentChatUserId)) {
                typingFromUserId = from;
                showTypingIndicator();
            }
        });
        socket.on('stop_typing', ({ from }) => {
            if (currentChatUserId && String(from) === String(currentChatUserId)) {
                typingFromUserId = null;
                hideTypingIndicator();
            }
        });
        // Restaurar recepción de mensajes en tiempo real
        socket.on('chat_message', (data) => {
            handleIncomingMessage(data);
            // Refrescar lista de usuarios para reordenar chats
            loadUsers();
            // Si el usuario estaba escribiendo, volver a mostrar el indicador
            if (typingFromUserId && currentChatUserId && typingFromUserId == currentChatUserId) {
                showTypingIndicator();
            }
        });
        // Actualizar estado online en tiempo real
        socket.on('users_online', updateOnlineStatus);
    }

    // Funciones para mostrar/ocultar el indicador de escribiendo
    function showTypingIndicator() {
        // Si se cambia de chat, limpiar el indicador
        const typingDivOld = document.getElementById('typing-indicator');
        if (typingDivOld && typingFromUserId && String(typingFromUserId) !== String(currentChatUserId)) {
            typingDivOld.style.display = 'none';
            typingFromUserId = null;
        }
        let typingDiv = document.getElementById('typing-indicator');
        if (!typingDiv) {
            typingDiv = document.createElement('div');
            typingDiv.id = 'typing-indicator';
            typingDiv.innerHTML = `<div class="typing-bubble"><span></span><span></span><span></span></div>`;
            typingDiv.style.display = 'flex';
            typingDiv.style.alignItems = 'center';
            typingDiv.style.margin = '8px 0 8px 8px';
            if (chatMessages) chatMessages.appendChild(typingDiv);
        } else {
            typingDiv.style.display = 'flex';
        }
        scrollToBottom();
    }
    function hideTypingIndicator() {
        const typingDiv = document.getElementById('typing-indicator');
        if (typingDiv) typingDiv.style.display = 'none';
    }
    // Inicializar sendBtn global
    sendBtn = document.getElementById('send-btn');
    // Inicializar messageInput global
    messageInput = document.getElementById('message-input');
    // Inicializar chatMessages global
    chatMessages = document.getElementById('chat-messages');

    // Habilitar envío de mensajes con click y Enter
    if (sendBtn) {
        sendBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            await sendMessage();
        });
    }
    if (messageInput) {
        messageInput.addEventListener('keydown', async (e) => {
            if (e.key === 'Enter' && !e.shiftKey && !sendBtn.disabled) {
                e.preventDefault();
                await sendMessage();
            }
        });
    }

    // Inicializar welcomeScreen y chatPanel globales
    welcomeScreen = document.getElementById('welcome-screen');
    chatPanel = document.getElementById('chat-panel');

    // --- LÓGICA DE PESTAÑAS CONTACTOS/SOLICITUDES ---

    // Declarar variables de pestañas primero

    const tabContactos = document.getElementById('tab-contactos');
    const tabSolicitudes = document.getElementById('tab-solicitudes');
    window.contactsList = document.getElementById('contacts-list');
    const solicitudesList = document.getElementById('solicitudes-list');
    const contactsSearchWrapper = document.getElementById('contacts-search-wrapper');

    // Badge azul para solicitudes pendientes (después de declarar tabSolicitudes)
    const solicitudesBadge = document.createElement('span');
    solicitudesBadge.id = 'solicitudes-badge';
    solicitudesBadge.style.display = 'none';
    solicitudesBadge.style.background = '#3498db';
    solicitudesBadge.style.color = 'white';
    solicitudesBadge.style.fontSize = '12px';
    solicitudesBadge.style.fontWeight = 'bold';
    solicitudesBadge.style.borderRadius = '10px';
    solicitudesBadge.style.padding = '2px 8px';
    solicitudesBadge.style.marginLeft = '6px';
    solicitudesBadge.style.verticalAlign = 'middle';
    if (tabSolicitudes) tabSolicitudes.appendChild(solicitudesBadge);

    // Cargar contactos al iniciar la mensajería
    loadUsers();

    if (tabContactos && tabSolicitudes && window.contactsList && solicitudesList) {
        tabContactos.addEventListener('click', () => {
            tabContactos.classList.add('active');
            tabSolicitudes.classList.remove('active');
            window.contactsList.style.display = '';
            solicitudesList.style.display = 'none';
            contactsSearchWrapper.style.display = '';
        });
        tabSolicitudes.addEventListener('click', () => {
            tabSolicitudes.classList.add('active');
            tabContactos.classList.remove('active');
            window.contactsList.style.display = 'none';
            solicitudesList.style.display = '';
            contactsSearchWrapper.style.display = 'none';
            loadSolicitudesPendientes();
        });
    }

    // --- FUNCIÓN PARA CARGAR SOLICITUDES DE AMISTAD ---
    async function loadSolicitudesPendientes() {
        // Delegar eventos para aceptar/rechazar después de renderizar
    setTimeout(() => {
            solicitudesList.querySelectorAll('.btn-aceptar-solicitud').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const solicitanteId = btn.getAttribute('data-id');
                    btn.disabled = true;
                    btn.textContent = 'Aceptando...';
                    try {
                        const resp = await fetch('/api/amistades.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'aceptar_solicitud', solicitante_id: solicitanteId })
                        });
                        const res = await resp.json();
                        if (res.status === 'success' || res.success === true) {
                            btn.textContent = 'Aceptada';
                            btn.style.background = '#51cf66';
                            setTimeout(loadSolicitudesPendientes, 800);
                        } else {
                            btn.textContent = res.message || 'Error';
                            btn.style.background = '#ff6b6b';
                        }
                    } catch (err) {
                        btn.textContent = 'Error de red';
                        btn.style.background = '#ff6b6b';
                    }
                });
            });
            solicitudesList.querySelectorAll('.btn-rechazar-solicitud').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const solicitanteId = btn.getAttribute('data-id');
                    btn.disabled = true;
                    btn.textContent = 'Rechazando...';
                    try {
                        const resp = await fetch('/api/amistades.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'rechazar_solicitud', solicitante_id: solicitanteId })
                        });
                        const res = await resp.json();
                        if (res.status === 'success' || res.success === true) {
                            btn.textContent = 'Rechazada';
                            btn.style.background = '#888';
                            setTimeout(loadSolicitudesPendientes, 800);
                        } else {
                            btn.textContent = res.message || 'Error';
                            btn.style.background = '#ff6b6b';
                        }
                    } catch (err) {
                        btn.textContent = 'Error de red';
                        btn.style.background = '#ff6b6b';
                    }
                });
            });
        }, 300);
        solicitudesList.innerHTML = '<div style="text-align:center; color:#888; padding:20px;">Cargando solicitudes...</div>';
        try {
            const response = await fetch('/api/amistades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'listar_solicitudes_pendientes' })
            });
            const result = await response.json();
            console.log('[Solicitudes] Respuesta del backend:', result);
            const isSuccess = (result.status === 'success' || result.success === true);
            // Mostrar badge azul si hay solicitudes
            if (window.solicitudesBadge) {
                if (isSuccess && Array.isArray(result.data) && result.data.length > 0) {
                    window.solicitudesBadge.textContent = result.data.length;
                    window.solicitudesBadge.style.display = 'inline-block';
                } else {
                    window.solicitudesBadge.textContent = '';
                    window.solicitudesBadge.style.display = 'none';
                }
            }
            if (isSuccess && Array.isArray(result.data) && result.data.length > 0) {
                solicitudesList.innerHTML = result.data.map(s => `
                    <div class="solicitud-item" style="display:flex;align-items:center;gap:12px;padding:14px 0 14px 0;border-bottom:1px solid #f0f0f0;background:rgba(162,203,141,0.06);border-radius:10px;flex-wrap:wrap;">
                        <div style="flex-shrink:0;">
                            <a href="ver-perfil.php?id=${s.solicitante_id}" target="_blank" tabindex="-1">
                                <img src="${s.solicitante_avatar || 'img/usuario.png'}" alt="${s.solicitante_username}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;box-shadow:0 2px 8px rgba(162,203,141,0.10);border:2px solid #e9ecef;cursor:pointer;">
                            </a>
                        </div>
                        <div style="flex:1;display:flex;flex-direction:column;justify-content:center;min-width:0;">
                            <span style="font-weight:700;font-size:17px;display:inline-block;vertical-align:middle;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#222;max-width:170px;">${s.solicitante_nombre || s.solicitante_username}</span>
                            <span style="font-size:14px;color:#3498db;display:inline-block;vertical-align:middle;margin-left:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px;">@${s.solicitante_username}</span>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:8px;flex-shrink:0;min-width:90px;max-width:110px;width:100%;align-items:stretch;">
                            <button class="btn-aceptar-solicitud" data-id="${s.solicitante_id}" style="background:linear-gradient(135deg,#A2CB8D,#7ba05a);color:white;border:none;padding:7px 0;border-radius:7px;cursor:pointer;font-weight:600;box-shadow:0 2px 8px rgba(162,203,141,0.10);transition:background 0.2s;min-width:70px;max-width:100px;white-space:nowrap;">Aceptar</button>
                            <button class="btn-rechazar-solicitud" data-id="${s.solicitante_id}" style="background:linear-gradient(135deg,#ff6b6b,#ff8787);color:white;border:none;padding:7px 0;border-radius:7px;cursor:pointer;font-weight:600;box-shadow:0 2px 8px rgba(255,107,107,0.10);transition:background 0.2s;min-width:70px;max-width:100px;white-space:nowrap;">Rechazar</button>
                        </div>
                    </div>
                `).join('');
            } else {
                solicitudesList.innerHTML = '<div style="text-align:center; color:#888; padding:20px;">No tienes solicitudes pendientes.</div>';
            }
        } catch (error) {
            solicitudesList.innerHTML = '<div style="text-align:center; color:#888; padding:20px;">Error al cargar solicitudes.</div>';
            console.error('Error al cargar solicitudes pendientes:', error);
        }
    }

    // Función para cargar usuarios
    async function loadUsers() {
        try {
            const response = await fetch('/api/users.php');
            const data = await response.json();

            if (data.status === 'success' && data.users) {
                renderContacts(data.users);
                loadUnreadCounts();
            }
        } catch (error) {
            console.error('Error al cargar usuarios:', error);
        }
    }

    // Función para renderizar contactos
    function renderContacts(users) {
        if (!window.contactsList) return;

        // Ordenar usuarios por fecha del último mensaje (descendente), los que no tienen mensaje van al final
        users.sort((a, b) => {
            if (a.last_message_time && b.last_message_time) {
                return new Date(b.last_message_time) - new Date(a.last_message_time);
            } else if (a.last_message_time) {
                return -1;
            } else if (b.last_message_time) {
                return 1;
            } else {
                return 0;
            }
        });

        window.contactsList.innerHTML = users.map(user => {
            // Formatear el último mensaje
            let lastMessagePreview = 'Haz clic para chatear';
            if (user.last_message) {
                const sender = user.last_message_sender || '';
                lastMessagePreview = `${sender}: ${user.last_message}`;
            }
            
            // Formatear la hora del último mensaje
            let lastMessageTime = '';
            if (user.last_message_time) {
                const date = new Date(user.last_message_time);
                const now = new Date();
                const diffInHours = (now - date) / (1000 * 60 * 60);
                
                if (diffInHours < 24) {
                    // Si es de hoy, mostrar solo la hora
                    lastMessageTime = date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                } else if (diffInHours < 48) {
                    // Si es de ayer
                    lastMessageTime = 'Ayer';
                } else if (diffInHours < 168) {
                    // Si es de esta semana
                    lastMessageTime = date.toLocaleDateString('es-ES', { weekday: 'short' });
                } else {
                    // Si es más antiguo
                    lastMessageTime = date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
                }
            }
            
            // Botón de rechazar solo para no-amigos
            const botonRechazar = !user.es_amigo ? `
                <button class="btn-rechazar-contacto" onclick="rechazarContacto(${user.id}, '${user.username}', event)" title="Rechazar y eliminar chat">
                    <i class="fas fa-times-circle"></i>
                </button>
            ` : '';
            
            // Badge de no-amigo
            const badgeNoAmigo = !user.es_amigo ? `
                <span class="badge-no-amigo" title="No es tu amigo">
                    <i class="fas fa-user-slash"></i>
                </span>
            ` : '';
            
            const avatarSrc = user.avatar && user.avatar !== '' ? user.avatar : 'img/usuario.png';
            return `
                <div class="contact-item ${!user.es_amigo ? 'no-amigo' : ''}" data-user-id="${user.id}" data-username="${user.username}">
                    <div class="contact-avatar">
                        <img src="${avatarSrc}" alt="${user.username}" onerror="this.onerror=null;this.src='img/usuario.png';">
                        <div class="status-indicator offline" data-user-id="${user.id}"></div>
                        <span class="unread-badge" data-user-id="${user.id}">0</span>
                    </div>
                    <div class="contact-info">
                        <div class="contact-name">
                            ${user.username}
                            ${badgeNoAmigo}
                        </div>
                        <div class="contact-preview">${lastMessagePreview}</div>
                    </div>
                    <div class="contact-meta">
                        <div class="contact-time">${lastMessageTime}</div>
                        ${botonRechazar}
                    </div>
                </div>
            `;
        }).join('');

        // Agregar eventos a los contactos
        document.querySelectorAll('.contact-item').forEach(item => {
            item.addEventListener('click', (e) => {
                // No abrir el chat si se hace clic en el botón de rechazar
                if (e.target.closest('.btn-rechazar-contacto')) {
                    return;
                }
                const userId = item.dataset.userId;
                const username = item.dataset.username;
                const avatar = item.querySelector('img').src;
                selectUser(userId, username, avatar);
            });
        });
    }

    // Función para actualizar la vista previa del último mensaje de un contacto
    function updateContactPreview(userId, messageText, senderName) {
        const contactItem = document.querySelector(`.contact-item[data-user-id="${userId}"]`);
        if (!contactItem) return;
        
        const preview = contactItem.querySelector('.contact-preview');
        const timeElement = contactItem.querySelector('.contact-time');
        
        if (preview) {
            // Truncar mensaje si es muy largo
            let displayText = messageText;
            if (displayText.length > 40) {
                displayText = displayText.substring(0, 40) + '...';
            }
            preview.textContent = `${senderName}: ${displayText}`;
        }
        
        if (timeElement) {
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        }
    }

    // Función para rechazar y eliminar contacto no-amigo
    async function rechazarContacto(userId, username, event) {
        event.preventDefault();
        event.stopPropagation();
        
        console.log('🚫 Intentando rechazar contacto:', userId, username);
        
        // Confirmar con SweetAlert2
        const result = await Swal.fire({
            title: '¿Rechazar contacto?',
            html: `¿Deseas rechazar a <strong>${username}</strong> y eliminar todo el historial de chat?<br><small>Esta acción no se puede deshacer.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, rechazar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        });
        
        if (!result.isConfirmed) {
            return;
        }
        
        try {
            const response = await fetch('/api/bloquear-contacto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'eliminar_chat',
                    contacto_id: userId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Contacto rechazado',
                    text: 'El chat ha sido eliminado correctamente.',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Si el chat actual es con este usuario, cerrarlo
                if (currentChatUserId == userId) {
                    currentChatUserId = null;
                    if (chatPanel) chatPanel.classList.remove('active');
                    if (welcomeScreen) welcomeScreen.classList.remove('hidden');
                }
                
                // Recargar la lista de contactos
                await loadUsers();
                
            } else {
                throw new Error(data.message || 'Error al rechazar el contacto');
            }
        } catch (error) {
            console.error('Error al rechazar contacto:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo rechazar el contacto. Intenta de nuevo.',
                confirmButtonText: 'OK'
            });
        }
    }
    
    // Hacer la función accesible globalmente
    window.rechazarContacto = rechazarContacto;

    // Función para seleccionar un usuario
    async function selectUser(userId, username, avatar) {
        console.log('👤 Seleccionando usuario:', userId, username);

        currentChatUserId = userId;

        // Mostrar panel de chat y ocultar bienvenida
        if (welcomeScreen) welcomeScreen.classList.add('hidden');
        if (chatPanel) chatPanel.classList.add('active');

        // Actualizar header del chat
        updateChatHeader(userId, username, avatar);

        // Marcar contacto como activo
        document.querySelectorAll('.contact-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.userId === userId) {
                item.classList.add('active');
            }
        });

        // Limpiar mensajes anteriores
        if (chatMessages) {
            chatMessages.innerHTML = '';
        }

        // Cargar mensajes
        await loadMessages(userId);

        // Marcar mensajes como leídos
        markMessagesAsRead(userId);

        // Ocultar badge de no leídos
        hideUnreadBadge(userId);

        // Habilitar input
        if (messageInput) {
            messageInput.disabled = false;
            messageInput.placeholder = `Escribe un mensaje para ${username}...`;
            messageInput.focus();
        }

        if (sendBtn) {
            sendBtn.disabled = false;
        }
    }
    
    // Exponer función globalmente para uso externo
    window.selectUserById = selectUser;

    // Función para actualizar el header del chat
    function updateChatHeader(userId, username, avatar) {
        const chatUserName = document.getElementById('chat-user-name');
        const chatUserAvatar = document.getElementById('chat-user-avatar');
        const chatUserStatus = document.getElementById('chat-user-status');
        const chatUserStatusText = document.getElementById('chat-user-status-text');
        const chatHeaderAvatarLink = document.getElementById('chat-header-avatar-link');

        if (chatUserName) chatUserName.textContent = username;
        if (chatUserAvatar) chatUserAvatar.src = avatar;
        
        // Actualizar link del avatar para ir al perfil
        if (chatHeaderAvatarLink) {
            chatHeaderAvatarLink.href = `ver-perfil.php?id=${userId}`;
        }

        // Actualizar estado online/offline
        const isOnline = onlineUsers.has(parseInt(userId));
        if (chatUserStatus) {
            chatUserStatus.className = `status-indicator ${isOnline ? 'online' : 'offline'}`;
        }
        if (chatUserStatusText) {
            chatUserStatusText.textContent = isOnline ? 'En línea' : 'Desconectado';
        }
    }

    // Función para cargar mensajes
    async function loadMessages(userId) {
        try {
            const response = await fetch(`/api/get-messages.php?user_id=${userId}`);
            const data = await response.json();

            if (data.status === 'success' && data.messages) {
                data.messages.forEach(msg => {
                    appendMessage(msg);
                });

                // Scroll al último mensaje
                scrollToBottom();
            }
        } catch (error) {
            console.error('Error al cargar mensajes:', error);
        }
    }

    // Función para agregar un mensaje al chat
    function appendMessage(messageData) {
        const chatMessagesElement = document.getElementById('chat-messages');
    // ...debug removido...
        
        if (!chatMessagesElement) {
            console.error('❌ ERROR: No se encontró el elemento chat-messages');
            return;
        }

        // Evitar duplicados: si el mensaje ya existe, no agregarlo
        if (messageData.id) {
            const existingMessage = chatMessagesElement.querySelector(`[data-message-id="${messageData.id}"]`);
            if (existingMessage) {
                console.log('   ⚠️ Mensaje duplicado ignorado, ID:', messageData.id);
                return;
            }
        }

        const isOwnMessage = messageData.sender_id.toString() === CURRENT_USER_ID.toString();
        const time = formatTime(messageData.timestamp);

        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isOwnMessage ? 'own' : ''}`;
        messageDiv.dataset.messageId = messageData.id || '';

        const avatarSrc = isOwnMessage ? CURRENT_USER_AVATAR : document.getElementById('chat-user-avatar')?.src || 'img/usuario.png';
        const senderName = isOwnMessage ? 'Tú' : (document.getElementById('chat-user-name')?.textContent || 'Usuario');

        // HTML para la respuesta si existe
        let replyHTML = '';
        if (messageData.reply_to_message_id && messageData.reply_to_message) {
            replyHTML = `
                <div class="message-reply-preview">
                    <div class="reply-username">${escapeHtml(messageData.reply_to_username || 'Usuario')}</div>
                    <div class="reply-text">${escapeHtml(messageData.reply_to_message)}</div>
                </div>
            `;
        }

        // Indicador de mensaje editado
        const editedLabel = messageData.edited_at ? '<span class="message-edited">(editado)</span>' : '';

        // Opciones del menú según si es mensaje propio o no
        let menuOptions = `
            <div class="message-option-item" onclick="replyToMessage(${messageData.id || 0}, '${escapeHtml(messageData.message || messageData.mensaje).replace(/'/g, "\\'")}', '${senderName}')">
                <i class="fas fa-reply"></i>
                <span>Responder</span>
            </div>
        `;

        if (isOwnMessage) {
            // Si es mensaje propio: editar, eliminar para todos y eliminar para mí
            menuOptions += `
                <div class="message-option-item" onclick="editMessage(${messageData.id || 0}, '${escapeHtml(messageData.message || messageData.mensaje).replace(/'/g, "\\'")}')">
                    <i class="fas fa-edit"></i>
                    <span>Editar</span>
                </div>
                <div class="message-option-item danger" onclick="deleteMessage(${messageData.id || 0}, true, 'all')">
                    <i class="fas fa-trash-alt"></i>
                    <span>Eliminar para todos</span>
                </div>
                <div class="message-option-item danger" onclick="deleteMessage(${messageData.id || 0}, true, 'me')">
                    <i class="fas fa-trash"></i>
                    <span>Eliminar para mí</span>
                </div>
            `;
        } else {
            // Si es mensaje recibido: solo eliminar para mí
            menuOptions += `
                <div class="message-option-item danger" onclick="deleteMessage(${messageData.id || 0}, false, 'me')">
                    <i class="fas fa-trash"></i>
                    <span>Eliminar para mí</span>
                </div>
            `;
        }

        const isPerseoAuto = messageData.is_perseo_auto == 1 || messageData.is_perseo_auto === true;
        messageDiv.innerHTML = `
            <div class="message-avatar">
                <img src="${avatarSrc}" alt="Avatar">
            </div>
            <div class="message-content">
                <div class="message-bubble perseo-bubble" data-message-id="${messageData.id || 0}">
                    ${isPerseoAuto ? '<span class="perseo-badge">🤖</span>' : ''}
                    ${replyHTML}
                    <span class="message-text">${escapeHtml(messageData.message || messageData.mensaje)}</span>
                    ${editedLabel}
                </div>
                <div class="message-time">${time}</div>
            </div>
            <div class="message-options">
                <button class="message-options-btn" onclick="event.stopPropagation(); toggleMessageMenu(this)">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="message-options-menu">
                    ${menuOptions}
                </div>
            </div>
        `;

    // ...debug removido...
        chatMessagesElement.appendChild(messageDiv);
        scrollToBottom();
    }

    // Función para enviar mensaje
    async function sendMessage() {
        console.log('📤 sendMessage() llamado');
        console.log('   messageInput:', messageInput);
        console.log('   currentChatUserId:', currentChatUserId);
        
        if (!messageInput || !currentChatUserId) {
            console.error('❌ No hay messageInput o currentChatUserId');
            return;
        }

        const message = messageInput.value.trim();
        console.log('   Mensaje a enviar:', message);
        
        if (!message) {
            console.log('   ⚠️ Mensaje vacío, abortando');
            return;
        }

        const messageData = {
            message: message,
            sender_id: CURRENT_USER_ID,
            receiver_id: currentChatUserId,
            timestamp: new Date().toISOString(),
            reply_to_message_id: replyingToMessage ? replyingToMessage.id : null
        };

        // Emitir a través de Socket.io inmediatamente
        if (socket && socket.connected) {
            console.log('� Emitiendo mensaje a Socket.IO...');
            socket.emit('chat_message', messageData);
            console.log('✅ Mensaje emitido - Esperando respuesta del servidor');
        } else {
            // ...debug removido...
            appendMessage(messageData);
            scrollToBottom();
        }

        // Guardar en base de datos en segundo plano
        console.log('💾 Guardando mensaje en BD...');
    try {
            const response = await fetch('/api/save-message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    receiver_id: currentChatUserId,
                    reply_to_message_id: replyingToMessage ? replyingToMessage.id : null
                })
            });
            const result = await response.json();
            console.log('📥 Respuesta de save-message.php:', result);
            if (result.status === 'success') {
                // Si había una respuesta, añadirla a los datos del mensaje
                if (replyingToMessage) {
                    messageData.reply_to_message = replyingToMessage.text;
                    messageData.reply_to_username = replyingToMessage.sender;
                    messageData.reply_to_message_id = replyingToMessage.id;
                }
                // Añadir el ID del mensaje
                messageData.id = result.message_id;
                console.log('🆔 ID del mensaje:', messageData.id);
                // Cancelar respuesta si había una
                if (replyingToMessage) {
                    cancelReply();
                }
                // Limpiar input
                messageInput.value = '';
                messageInput.focus();
                // Actualizar vista previa del contacto
                updateContactPreview(currentChatUserId, message, 'Tú');
            } else {
                console.error('Error al guardar mensaje:', result);
                alert('Error al enviar el mensaje. Por favor, intenta de nuevo.');
            }
        } catch (error) {
            console.error('Error al enviar mensaje:', error);
            alert('Error de conexión. Por favor, verifica tu conexión e intenta de nuevo.');
        }
    }

    // Función para manejar mensajes entrantes
    function handleIncomingMessage(data) {
        console.log('📬 Procesando mensaje entrante:', data);
        console.log('   Chat actual abierto con:', currentChatUserId);
        console.log('   Mi ID:', CURRENT_USER_ID);
        console.log('   Sender ID:', data.sender_id);
        console.log('   Receiver ID:', data.receiver_id);
        
        // Determinar si el mensaje es para el chat actual
        const isForCurrentChat = currentChatUserId &&
            ((data.sender_id.toString() === currentChatUserId.toString() && data.receiver_id.toString() === CURRENT_USER_ID.toString()) ||
             (data.sender_id.toString() === CURRENT_USER_ID.toString() && data.receiver_id.toString() === currentChatUserId.toString()));

        if (isForCurrentChat) {
            // Mensaje para el chat actual (mío o del otro usuario)
            console.log('   ✅ Mostrando mensaje en chat actual');
            appendMessage(data);
            scrollToBottom();

            // Si no es mi mensaje, marcarlo como leído y actualizar vista previa
            if (data.sender_id.toString() !== CURRENT_USER_ID.toString()) {
                markMessagesAsRead(data.sender_id);
                
                // Obtener el nombre del remitente del contacto
                const contactItem = document.querySelector(`.contact-item[data-user-id="${data.sender_id}"]`);
                const senderName = contactItem?.dataset.username || 'Usuario';
                updateContactPreview(data.sender_id, data.message, senderName);
            }
        } else if (data.receiver_id.toString() === CURRENT_USER_ID.toString()) {
            // Mensaje para mí pero en otro chat
            console.log('   📬 Mensaje para otro chat, incrementando badge');
            incrementUnreadBadge(data.sender_id);
            
            // Actualizar vista previa del contacto
            const contactItem = document.querySelector(`.contact-item[data-user-id="${data.sender_id}"]`);
            const senderName = contactItem?.dataset.username || 'Usuario';
            updateContactPreview(data.sender_id, data.message, senderName);
        }
    }

    // Función para cargar conteo de no leídos
    async function loadUnreadCounts() {
        try {
            const response = await fetch('/api/get-unread-count.php');
            const data = await response.json();

            if (data.status === 'success') {
                updateAllUnreadBadges(data.unread_counts);
            }
        } catch (error) {
            console.error('Error al cargar conteo de no leídos:', error);
        }
    }

    // Función para actualizar todos los badges
    function updateAllUnreadBadges(counts) {
        Object.keys(counts).forEach(userId => {
            const count = counts[userId];
            const badge = document.querySelector(`.unread-badge[data-user-id="${userId}"]`);

            if (badge && count > 0) {
                const displayCount = count > 15 ? '+15' : count;
                badge.textContent = displayCount;
                badge.classList.add('show', 'pulse');
            }
        });
    }

    // Función para incrementar badge
    function incrementUnreadBadge(userId) {
        const badge = document.querySelector(`.unread-badge[data-user-id="${userId}"]`);

        if (badge) {
            let currentCount = 0;
            const currentText = badge.textContent;

            if (currentText === '+15') {
                return; // Ya está al máximo
            } else {
                currentCount = parseInt(currentText) || 0;
            }

            currentCount++;
            const displayCount = currentCount > 15 ? '+15' : currentCount;
            badge.textContent = displayCount;
            badge.classList.add('show', 'pulse');
        }
    }

    // Función para ocultar badge
    function hideUnreadBadge(userId) {
        const badge = document.querySelector(`.unread-badge[data-user-id="${userId}"]`);
        if (badge) {
            badge.classList.remove('show', 'pulse');
        }
    }

    // Función para marcar mensajes como leídos
    async function markMessagesAsRead(senderId) {
        try {
            await fetch('/api/mark-as-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sender_id: senderId
                })
            });
        } catch (error) {
            console.error('Error al marcar mensajes como leídos:', error);
        }
    }

    // Función para actualizar estado online
    function updateOnlineStatus(users) {
        console.log('👥 Usuarios online:', users);
        onlineUsers = new Set(users.map(id => parseInt(id)));

        // Actualizar indicadores de estado
        document.querySelectorAll('.status-indicator').forEach(indicator => {
            const userId = indicator.dataset.userId;
            if (userId) {
                const isOnline = onlineUsers.has(parseInt(userId));
                indicator.className = `status-indicator ${isOnline ? 'online' : 'offline'}`;
            }
        });

        // Actualizar estado en el header del chat si hay un chat abierto
        if (currentChatUserId) {
            const isOnline = onlineUsers.has(parseInt(currentChatUserId));
            const chatUserStatus = document.getElementById('chat-user-status');
            const chatUserStatusText = document.getElementById('chat-user-status-text');

            if (chatUserStatus) {
                chatUserStatus.className = `status-indicator ${isOnline ? 'online' : 'offline'}`;
            }
            if (chatUserStatusText) {
                chatUserStatusText.textContent = isOnline ? 'En línea' : 'Desconectado';
            }
        }
    }

    // Función para filtrar contactos
    function filterContacts(query) {
        const items = document.querySelectorAll('.contact-item');
        const lowerQuery = query.toLowerCase();

        items.forEach(item => {
            const username = item.dataset.username.toLowerCase();
            if (username.includes(lowerQuery)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Función para hacer scroll al final
    function scrollToBottom() {
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    // Función para formatear tiempo
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffInMs = now - date;
        const diffInMins = Math.floor(diffInMs / 60000);
        const diffInHours = Math.floor(diffInMs / 3600000);
        const diffInDays = Math.floor(diffInMs / 86400000);

        if (diffInMins < 1) {
            return 'Ahora';
        } else if (diffInMins < 60) {
            return `Hace ${diffInMins} min`;
        } else if (diffInHours < 24) {
            return `Hace ${diffInHours} h`;
        } else if (diffInDays < 7) {
            return `Hace ${diffInDays} días`;
        } else {
            return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
        }
    }

    // Función auxiliar para escapar HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Toggle del menú de opciones del mensaje
    window.toggleMessageMenu = function(button) {
        const menu = button.nextElementSibling;
        
        // Cerrar todos los menús abiertos
        document.querySelectorAll('.message-options-menu.show').forEach(m => {
            if (m !== menu) {
                m.classList.remove('show');
                m.classList.remove('show-above');
            }
        });
        
        // Toggle del menú
        const isShowing = menu.classList.contains('show');
        menu.classList.toggle('show');
        
        if (!isShowing) {
            // El menú se está abriendo, posicionarlo
            setTimeout(() => {
                const buttonRect = button.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const viewportWidth = window.innerWidth;
                
                // Calcular espacio disponible
                const spaceBelow = viewportHeight - buttonRect.bottom;
                const spaceAbove = buttonRect.top;
                const menuHeight = menuRect.height;
                
                let top, left;
                
                // Decidir si mostrar arriba o abajo
                if (spaceBelow < menuHeight + 10 && spaceAbove > menuHeight + 10) {
                    // Mostrar arriba
                    top = buttonRect.top - menuHeight - 4;
                    menu.classList.add('show-above');
                } else {
                    // Mostrar abajo
                    top = buttonRect.bottom + 4;
                    menu.classList.remove('show-above');
                }
                
                // Posicionar a la derecha del botón, pero ajustar si se sale del viewport
                left = buttonRect.right - menu.offsetWidth;
                
                // Asegurar que no se salga por la izquierda
                if (left < 10) {
                    left = 10;
                }
                
                // Asegurar que no se salga por la derecha
                if (left + menu.offsetWidth > viewportWidth - 10) {
                    left = viewportWidth - menu.offsetWidth - 10;
                }
                
                // Aplicar posición
                menu.style.top = `${top}px`;
                menu.style.left = `${left}px`;
            }, 0);
        } else {
            // El menú se está cerrando
            menu.classList.remove('show-above');
        }
    };

    // Cerrar menús al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.message-options')) {
            document.querySelectorAll('.message-options-menu.show').forEach(m => {
                m.classList.remove('show');
            });
        }
    });

    // Cerrar chat al presionar ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            // Si hay un modal abierto, cerrarlo primero
            const editModal = document.getElementById('editMessageModal');
            const deleteModal = document.getElementById('deleteMessageModal');
            
            if (editModal?.classList.contains('show')) {
                closeEditModal();
                return;
            }
            
            if (deleteModal?.classList.contains('show')) {
                closeDeleteMessageModal();
                return;
            }
            
            // Si no hay modales abiertos y hay un chat activo, cerrarlo
            const chatPanel = document.getElementById('chat-panel');
            if (chatPanel?.classList.contains('active')) {
                closeChatPanel();
            }
        }
    });

    // Función para cerrar el panel de chat
    function closeChatPanel() {
        const chatPanel = document.getElementById('chat-panel');
        const welcomeScreen = document.getElementById('welcome-screen');
        
        if (chatPanel) {
            chatPanel.classList.remove('active');
        }
        
        if (welcomeScreen) {
            welcomeScreen.classList.remove('hidden');
        }
        
        // Desmarcar contacto activo
        document.querySelectorAll('.contact-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Limpiar el chat actual
        currentChatUserId = null;
        
        // Limpiar mensajes
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            messagesContainer.innerHTML = '';
        }
        
        console.log('✅ Chat cerrado');
    }

    // Variables para edición
    let currentEditMessageId = null;
    let currentEditOriginalText = '';

    // Función para abrir modal de edición
    window.editMessage = function(messageId, currentText) {
        console.log('✏️ Abriendo modal de edición:', messageId);
        
        // Cerrar menú
        document.querySelectorAll('.message-options-menu.show').forEach(m => m.classList.remove('show'));
        
        // Guardar datos
        currentEditMessageId = messageId;
        currentEditOriginalText = currentText;
        
        // Llenar el textarea
        const textarea = document.getElementById('edit-message-textarea');
        if (textarea) {
            textarea.value = currentText;
            updateCharCount();
            
            // Mostrar modal
            const modal = document.getElementById('editMessageModal');
            if (modal) {
                modal.classList.add('show');
                
                // Enfocar el textarea después de que se muestre el modal
                setTimeout(() => {
                    textarea.focus();
                    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
                }, 100);
            }
        }
    };

    // Función para cerrar modal de edición
    window.closeEditModal = function() {
        const modal = document.getElementById('editMessageModal');
        if (modal) {
            modal.classList.remove('show');
        }
        currentEditMessageId = null;
        currentEditOriginalText = '';
    };

    // Función para actualizar contador de caracteres
    function updateCharCount() {
        const textarea = document.getElementById('edit-message-textarea');
        const counter = document.getElementById('edit-char-count');
        const counterContainer = textarea?.parentElement.querySelector('.edit-message-counter');
        
        if (textarea && counter) {
            const length = textarea.value.length;
            counter.textContent = length;
            
            if (counterContainer) {
                counterContainer.classList.remove('warning', 'error');
                if (length > 1800) {
                    counterContainer.classList.add('error');
                } else if (length > 1500) {
                    counterContainer.classList.add('warning');
                }
            }
        }
    }

    // Listener para el textarea
    const editTextarea = document.getElementById('edit-message-textarea');
    if (editTextarea) {
        editTextarea.addEventListener('input', updateCharCount);
        
        // Permitir guardar con Ctrl+Enter
        editTextarea.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') {
                saveEditedMessage();
            }
            // Cerrar con Escape
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });
    }

    // Función para guardar mensaje editado
    window.saveEditedMessage = async function() {
        const textarea = document.getElementById('edit-message-textarea');
        if (!textarea || !currentEditMessageId) return;
        
        const newMessage = textarea.value.trim();
        
        if (!newMessage) {
            showNotification('El mensaje no puede estar vacío', 'error');
            return;
        }
        
        if (newMessage === currentEditOriginalText.trim()) {
            console.log('⚠️ El mensaje no cambió');
            closeEditModal();
            return;
        }
        
        if (newMessage.length > 2000) {
            showNotification('El mensaje es demasiado largo (máximo 2000 caracteres)', 'error');
            return;
        }
        
        try {
            const response = await fetch('api/edit-message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message_id: currentEditMessageId,
                    new_message: newMessage
                }),
                credentials: 'include'
            });
            
            const data = await response.json();
            
            console.log('📥 Respuesta del servidor:', data);
            
            if (data.success === true) {
                console.log('✅ Mensaje editado correctamente');
                
                // Actualizar el mensaje en el DOM
                const messageBubble = document.querySelector(`.message-bubble[data-message-id="${currentEditMessageId}"]`);
                if (messageBubble) {
                    const messageText = messageBubble.querySelector('.message-text');
                    if (messageText) {
                        messageText.textContent = newMessage;
                        
                        // Agregar indicador de editado si no existe
                        let editedLabel = messageBubble.querySelector('.message-edited');
                        if (!editedLabel) {
                            editedLabel = document.createElement('span');
                            editedLabel.className = 'message-edited';
                            editedLabel.textContent = ' (editado)';
                            messageBubble.appendChild(editedLabel);
                        }
                    }
                }
                
                // Notificar al otro usuario via Socket.IO
                if (socket && currentChatUserId) {
                    console.log('📡 Emitiendo evento message_edited:', {
                        message_id: currentEditMessageId,
                        new_message: newMessage,
                        receiver_id: currentChatUserId,
                        edited_at: data.data?.edited_at || new Date().toISOString()
                    });
                    
                    socket.emit('message_edited', {
                        message_id: currentEditMessageId,
                        new_message: newMessage,
                        receiver_id: currentChatUserId,
                        edited_at: data.data?.edited_at || new Date().toISOString()
                    });
                } else {
                    console.warn('⚠️ No se pudo emitir message_edited. socket:', !!socket, 'currentChatUserId:', currentChatUserId);
                }
                
                showNotification('Mensaje editado correctamente', 'success');
                closeEditModal();
            } else {
                console.error('❌ Error del servidor:', data);
                showNotification(data.message || 'Error al editar el mensaje', 'error');
            }
        } catch (error) {
            console.error('❌ Error en saveEditedMessage:', error);
            showNotification('Error al editar el mensaje', 'error');
        }
    };

    // Variables para eliminación
    let currentDeleteMessageId = null;
    let currentDeleteIsOwn = false;
    let currentDeleteType = 'me'; // 'all' o 'me'

    // Función para mostrar modal de eliminación
    window.deleteMessage = function(messageId, isOwnMessage, deleteType = 'me') {
        console.log('🗑️ Abriendo modal de eliminación:', messageId, 'isOwn:', isOwnMessage, 'type:', deleteType);
        
        // Cerrar menú
        document.querySelectorAll('.message-options-menu.show').forEach(m => m.classList.remove('show'));
        
        // Guardar datos
        currentDeleteMessageId = messageId;
        currentDeleteIsOwn = isOwnMessage;
        currentDeleteType = deleteType;
        
        // Obtener el texto del mensaje
        const messageBubble = document.querySelector(`.message-bubble[data-message-id="${messageId}"]`);
        const messageText = messageBubble?.querySelector('.message-text')?.textContent || '';
        
        // Configurar el modal
        const modal = document.getElementById('deleteMessageModal');
        const title = document.getElementById('delete-message-title');
        const description = document.getElementById('delete-message-description');
        const preview = document.getElementById('delete-message-text');
        const buttonText = document.getElementById('delete-button-text');
        
        if (modal && title && description && preview && buttonText) {
            if (deleteType === 'all') {
                title.textContent = '¿Eliminar para todos?';
                description.textContent = 'Este mensaje se eliminará para ti y para el otro usuario. Esta acción no se puede deshacer.';
                buttonText.textContent = 'Eliminar para todos';
            } else {
                title.textContent = '¿Eliminar para ti?';
                description.textContent = 'Este mensaje se eliminará solo para ti. El otro usuario aún podrá verlo.';
                buttonText.textContent = 'Eliminar para mí';
            }
            
            preview.textContent = messageText;
            modal.classList.add('show');
        }
    };

    // Función para cerrar modal de eliminación
    window.closeDeleteMessageModal = function() {
        const modal = document.getElementById('deleteMessageModal');
        if (modal) {
            modal.classList.remove('show');
        }
        currentDeleteMessageId = null;
        currentDeleteIsOwn = false;
        currentDeleteType = 'me';
    };

    // Función para confirmar eliminación
    window.confirmDeleteMessage = async function() {
        if (!currentDeleteMessageId) return;
        
        const messageId = currentDeleteMessageId;
        const deleteType = currentDeleteType;
        
        console.log('🗑️ Confirmando eliminación:', messageId, 'tipo:', deleteType);
        
        // Cerrar modal
        closeDeleteMessageModal();
        
        try {
            const response = await fetch('api/delete-message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message_id: messageId,
                    delete_for_all: deleteType === 'all'
                }),
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success === true) {
                console.log('✅ Mensaje eliminado correctamente');
                
                // Eliminar el mensaje del DOM
                const messageDiv = document.querySelector(`[data-message-id="${messageId}"]`)?.closest('.message');
                if (messageDiv) {
                    messageDiv.remove();
                }
                
                // Notificar al otro usuario via Socket.IO si es eliminación completa
                if (socket && currentChatUserId && data.data.delete_type === 'complete') {
                    socket.emit('message_deleted', {
                        message_id: messageId,
                        receiver_id: currentChatUserId
                    });
                }
                
                const deleteTypeText = deleteType === 'all' ? 'para todos' : 'para ti';
                showNotification(`Mensaje eliminado ${deleteTypeText}`, 'success');
            } else {
                console.error('❌ Error al eliminar:', data.message);
                showNotification(data.message || 'Error al eliminar el mensaje', 'error');
            }
        } catch (error) {
            console.error('❌ Error en confirmDeleteMessage:', error);
            showNotification('Error al eliminar el mensaje', 'error');
        }
    };

    // ==================== EMOJI PICKER ====================
    
    const emojiPicker = document.getElementById('emoji-picker');
    const emojiBtn = document.getElementById('emoji-btn');
    const emojiContent = document.getElementById('emoji-content');
    
    // Categorías de emojis
    const emojiCategories = {
        smileys: ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴'],
        gestures: ['👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '💪', '🦾', '🦿', '🦵', '🦶'],
        animals: ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🐔', '🐧', '🐦', '🐤', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌', '🐞', '🐜', '🦟', '🐢', '🐍', '🦎', '🦖', '🦕', '🐙', '🦑', '🦐', '🦞', '🦀', '🐡', '🐠', '🐟', '🐬', '🐳', '🐋', '🦈'],
        food: ['🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🫐', '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥬', '🥒', '🌶️', '🫑', '🌽', '🥕', '🧄', '🧅', '🥔', '🍠', '🥐', '🥯', '🍞', '🥖', '🥨', '🧀', '🥚', '🍳', '🧈', '🥞', '🧇', '🥓', '🥩', '🍗', '🍖', '🌭', '🍔', '🍟', '🍕', '🫓', '🥪', '🥙', '🧆', '🌮', '🌯', '🫔', '🥗', '🥘', '🫕', '🍝', '🍜', '🍲', '🍛', '🍣', '🍱', '🥟', '🍤', '🍙', '🍚', '🍘', '🍥', '🥠', '🥮', '🍢', '🍡', '🍧', '🍨', '🍦', '🥧', '🧁', '🍰', '🎂', '🍮', '🍭', '🍬', '🍫', '🍿', '🍩', '🍪', '🌰', '🥜', '🍯', '🥛', '🍼', '☕', '🍵', '🧃', '🥤', '🍶', '🍺', '🍻', '🥂', '🍷', '🥃', '🍸', '🍹', '🧉', '🍾', '🧊'],
        activities: ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛼', '⛸️', '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️', '🤸', '🤺', '🤾', '🏌️', '🏇', '🧘', '🏊', '🤽', '🚣', '🧗', '🚴', '🚵', '🎯', '🎮', '🎰', '🎲', '🧩', '🎪', '🎭', '🎨', '🎬', '🎤', '🎧', '🎼', '🎹', '🥁', '🎷', '🎺', '🪗', '🎸', '🪕', '🎻'],
        objects: ['⌚', '📱', '📲', '💻', '⌨️', '🖥️', '🖨️', '🖱️', '🖲️', '🕹️', '🗜️', '💽', '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥', '📽️', '🎞️', '📞', '☎️', '📟', '📠', '📺', '📻', '🎙️', '🎚️', '🎛️', '🧭', '⏱️', '⏲️', '⏰', '🕰️', '⌛', '⏳', '📡', '🔋', '🔌', '💡', '🔦', '🕯️', '🪔', '🧯', '🛢️', '💸', '💵', '💴', '💶', '💷', '💰', '💳', '💎', '⚖️', '🪜', '🧰', '🪛', '🔧', '🔨', '⚒️', '🛠️', '⛏️', '🪓', '🪚', '🔩', '⚙️', '🧱', '⛓️', '🧲', '🔫', '💣', '🧨', '🪃', '🔪', '🗡️', '⚔️', '🛡️', '🚬', '⚰️', '🪦', '⚱️', '🏺', '🔮', '📿', '🧿', '💈', '⚗️', '🔭', '🔬', '🕳️', '🩹', '🩺', '💊', '💉', '🩸', '🧬', '🦠', '🧫', '🧪', '🌡️', '🧹', '🪠', '🧺', '🧻', '🚽', '🚰', '🚿', '🛁', '🛀', '🧼', '🪥', '🪒', '🧽', '🪣', '🧴', '🛎️', '🔑', '🗝️', '🚪', '🪑', '🛋️', '🛏️', '🛌', '🧸', '🖼️', '🪆', '🪞', '🪟', '🛍️', '🛒', '🎁', '🎈', '🎏', '🎀', '🪄', '🪅', '🎊', '🎉', '🎎', '🏮', '🎐', '🧧', '✉️', '📩', '📨', '📧', '💌', '📥', '📤', '📦', '🏷️', '🪧', '📪', '📫', '📬', '📭', '📮', '📯', '📜', '📃', '📄', '📑', '🧾', '📊', '📈', '📉', '🗒️', '🗓️', '📆', '📅', '🗑️', '📇', '🗃️', '🗳️', '🗄️', '📋', '📁', '📂', '🗂️', '🗞️', '📰', '📓', '📔', '📒', '📕', '📗', '📘', '📙', '📚', '📖', '🔖', '🧷', '🔗', '📎', '🖇️', '📐', '📏', '🧮', '📌', '📍', '✂️', '🖊️', '🖋️', '✒️', '🖌️', '🖍️', '📝', '✏️', '🔍', '🔎', '🔏', '🔐', '🔒', '🔓'],
        symbols: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉️', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️', '📴', '📳', '🈶', '🈚', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮', '🉐', '㊙️', '㊗️', '🈴', '🈵', '🈹', '🈲', '🅰️', '🅱️', '🆎', '🆑', '🅾️', '🆘', '❌', '⭕', '🛑', '⛔', '📛', '🚫', '💯', '💢', '♨️', '🚷', '🚯', '🚳', '🚱', '🔞', '📵', '🚭', '❗', '❕', '❓', '❔', '‼️', '⁉️', '🔅', '🔆', '〽️', '⚠️', '🚸', '🔱', '⚜️', '🔰', '♻️', '✅', '🈯', '💹', '❇️', '✳️', '❎', '🌐', '💠', 'Ⓜ️', '🌀', '💤', '🏧', '🚾', '♿', '🅿️', '🛗', '🈳', '🈂️', '🛂', '🛃', '🛄', '🛅', '🚹', '🚺', '🚼', '⚧️', '🚻', '🚮', '🎦', '📶', '🈁', '🔣', 'ℹ️', '🔤', '🔡', '🔠', '🆖', '🆗', '🆙', '🆒', '🆕', '🆓', '0️⃣', '1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟', '🔢', '#️⃣', '*️⃣', '⏏️', '▶️', '⏸️', '⏯️', '⏹️', '⏺️', '⏭️', '⏮️', '⏩', '⏪', '⏫', '⏬', '◀️', '🔼', '🔽', '➡️', '⬅️', '⬆️', '⬇️', '↗️', '↘️', '↙️', '↖️', '↕️', '↔️', '↪️', '↩️', '⤴️', '⤵️', '🔀', '🔁', '🔂', '🔄', '🔃', '🎵', '🎶', '➕', '➖', '➗', '✖️', '♾️', '💲', '💱', '™️', '©️', '®️', '〰️', '➰', '➿', '🔚', '🔙', '🔛', '🔝', '🔜', '✔️', '☑️', '🔘', '🔴', '🟠', '🟡', '🟢', '🔵', '🟣', '⚫', '⚪', '🟤', '🔺', '🔻', '🔸', '🔹', '🔶', '🔷', '🔳', '🔲', '▪️', '▫️', '◾', '◽', '◼️', '◻️', '🟥', '🟧', '🟨', '🟩', '🟦', '🟪', '⬛', '⬜', '🟫', '🔈', '🔇', '🔉', '🔊', '🔔', '🔕', '📣', '📢', '💬', '💭', '🗯️', '♠️', '♣️', '♥️', '♦️', '🃏', '🎴', '🀄', '🕐', '🕑', '🕒', '🕓', '🕔', '🕕', '🕖', '🕗', '🕘', '🕙', '🕚', '🕛', '🕜', '🕝', '🕞', '🕟', '🕠', '🕡', '🕢', '🕣', '🕤', '🕥', '🕦', '🕧']
    };
    
    let currentCategory = 'smileys';
    
    // Renderizar emojis de una categoría
    function renderEmojis(category) {
        emojiContent.innerHTML = '';
        const emojis = emojiCategories[category] || [];
        
        emojis.forEach(emoji => {
            const button = document.createElement('button');
            button.className = 'emoji-item';
            button.textContent = emoji;
            button.onclick = () => insertEmoji(emoji);
            emojiContent.appendChild(button);
        });
    }
    
    // Insertar emoji en el input
    function insertEmoji(emoji) {
        const cursorPos = messageInput.selectionStart;
        const textBefore = messageInput.value.substring(0, cursorPos);
        const textAfter = messageInput.value.substring(cursorPos);
        
        messageInput.value = textBefore + emoji + textAfter;
        messageInput.focus();
        
        // Colocar el cursor después del emoji
        const newCursorPos = cursorPos + emoji.length;
        messageInput.setSelectionRange(newCursorPos, newCursorPos);
        
        // NO cerrar el picker para permitir seleccionar múltiples emojis
        // emojiPicker.classList.remove('show');
    }
    
    // Toggle del emoji picker
    if (emojiBtn) {
        emojiBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isShowing = emojiPicker.classList.contains('show');
            emojiPicker.classList.toggle('show');
            
            if (!isShowing) {
                // Posicionar el picker
                const btnRect = emojiBtn.getBoundingClientRect();
                const pickerWidth = Math.min(360, window.innerWidth * 0.95); // Max 360px o 95% del viewport
                const pickerHeight = 380; // Altura aproximada con header
                
                let left = btnRect.left;
                let top = btnRect.top - pickerHeight - 10;
                
                // Ajustar si se sale por la izquierda
                if (left < 10) left = 10;
                
                // Ajustar si se sale por la derecha
                if (left + pickerWidth > window.innerWidth - 10) {
                    left = window.innerWidth - pickerWidth - 10;
                }
                
                // Si no hay espacio arriba, mostrar abajo
                if (top < 10) {
                    top = btnRect.bottom + 10;
                }
                
                emojiPicker.style.left = `${left}px`;
                emojiPicker.style.top = `${top}px`;
                emojiPicker.style.width = `${pickerWidth}px`;
                
                // Renderizar emojis de la categoría actual
                renderEmojis(currentCategory);
            }
        });
    }
    
    // Botones de categorías
    document.querySelectorAll('.emoji-category-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const category = btn.dataset.category;
            currentCategory = category;
            
            // Actualizar botón activo
            document.querySelectorAll('.emoji-category-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Renderizar emojis de la nueva categoría
            renderEmojis(category);
        });
    });
    
    // Cerrar picker al hacer click fuera
    document.addEventListener('click', (e) => {
        if (!emojiPicker.contains(e.target) && e.target !== emojiBtn) {
            emojiPicker.classList.remove('show');
        }
    });

    console.log('✅ Sistema de chat inicializado correctamente');
    
    // Flag para indicar que el chat está listo
    window.chatInitialized = true;

});

