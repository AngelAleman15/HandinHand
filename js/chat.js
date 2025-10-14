// Variables globales
let currentChatUserId = null;
let socket = null;
let onlineUsers = new Set();

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Inicializando sistema de chat...');
    
    // Elementos del DOM
    const contactsList = document.getElementById('contacts-list');
    const chatMessages = document.getElementById('chat-messages');
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const searchInput = document.getElementById('search-contacts');
    const welcomeScreen = document.getElementById('welcome-screen');
    const chatPanel = document.getElementById('chat-panel');
    
    // Inicializar Socket.IO
    initializeSocket();
    
    // Cargar usuarios
    loadUsers();
    
    // Eventos de búsqueda
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            filterContacts(e.target.value);
        });
    }
    
    // Eventos de envío de mensajes
    if (messageInput) {
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }
    
    // Función para inicializar Socket.IO
    function initializeSocket() {
        try {
            console.log('📡 Conectando a Socket.IO en:', CHAT_SERVER_URL);
            socket = io(CHAT_SERVER_URL);
            
            socket.on('connect', () => {
                console.log('✅ Conectado al servidor de chat');
                socket.emit('user_connected', CURRENT_USER_ID);
            });
            
            socket.on('disconnect', () => {
                console.log('❌ Desconectado del servidor de chat');
            });
            
            socket.on('chat_message', (data) => {
                handleIncomingMessage(data);
            });
            
            socket.on('users_online', (users) => {
                updateOnlineStatus(users);
            });
            
            socket.on('error', (error) => {
                console.error('Error de Socket.io:', error);
            });
            
        } catch (error) {
            console.error('Error al inicializar Socket.io:', error);
        }
    }
    
    // Función para cargar usuarios
    async function loadUsers() {
        try {
            const response = await fetch('api/users.php');
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
        if (!contactsList) return;
        
        contactsList.innerHTML = users.map(user => `
            <div class="contact-item" data-user-id="${user.id}" data-username="${user.username}">
                <div class="contact-avatar">
                    <img src="${user.avatar}" alt="${user.username}">
                    <div class="status-indicator offline" data-user-id="${user.id}"></div>
                    <span class="unread-badge" data-user-id="${user.id}">0</span>
                </div>
                <div class="contact-info">
                    <div class="contact-name">${user.username}</div>
                    <div class="contact-preview">Haz clic para chatear</div>
                </div>
                <div class="contact-meta">
                    <div class="contact-time"></div>
                </div>
            </div>
        `).join('');
        
        // Agregar eventos a los contactos
        document.querySelectorAll('.contact-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                const username = item.dataset.username;
                const avatar = item.querySelector('img').src;
                selectUser(userId, username, avatar);
            });
        });
    }
    
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
    
    // Función para actualizar el header del chat
    function updateChatHeader(userId, username, avatar) {
        const chatUserName = document.getElementById('chat-user-name');
        const chatUserAvatar = document.getElementById('chat-user-avatar');
        const chatUserStatus = document.getElementById('chat-user-status');
        const chatUserStatusText = document.getElementById('chat-user-status-text');
        
        if (chatUserName) chatUserName.textContent = username;
        if (chatUserAvatar) chatUserAvatar.src = avatar;
        
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
            const response = await fetch(`api/get-messages.php?user_id=${userId}`);
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
        if (!chatMessages) return;
        
        const isOwnMessage = messageData.sender_id.toString() === CURRENT_USER_ID.toString();
        const time = formatTime(messageData.timestamp);
        const isPerseoAuto = messageData.is_perseo_auto == 1 || messageData.is_perseo_auto === true;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isOwnMessage ? 'own' : ''} ${isPerseoAuto ? 'perseo-auto' : ''}`;
        
        const avatarSrc = isOwnMessage ? CURRENT_USER_AVATAR : document.getElementById('chat-user-avatar')?.src || 'img/usuario.png';
        
        // Clase especial para el bubble si es de Perseo
        const bubbleClass = isPerseoAuto ? 'message-bubble perseo-auto-message' : 'message-bubble';
        
        messageDiv.innerHTML = `
            <div class="message-avatar">
                <img src="${avatarSrc}" alt="Avatar">
            </div>
            <div class="message-content">
                <div class="${bubbleClass}">
                    ${escapeHtml(messageData.message)}
                </div>
                <div class="message-time">${time}${isPerseoAuto ? ' <span style="color: #9FC131; font-weight: 600;">• Respuesta Automática</span>' : ''}</div>
            </div>
        `;
        
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }
    
    // Función para enviar mensaje
    async function sendMessage() {
        if (!messageInput || !currentChatUserId) return;
        
        const message = messageInput.value.trim();
        if (!message) return;
        
        const messageData = {
            message: message,
            sender_id: CURRENT_USER_ID,
            receiver_id: currentChatUserId,
            timestamp: new Date().toISOString()
        };
        
        try {
            // Guardar en base de datos
            const response = await fetch('api/save-message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    receiver_id: currentChatUserId
                })
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                // Emitir a través de Socket.io
                if (socket && socket.connected) {
                    socket.emit('chat_message', messageData);
                }
                
                // Mostrar mensaje en el chat
                appendMessage(messageData);
                
                // Limpiar input
                messageInput.value = '';
                messageInput.focus();
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
        const isForCurrentChat = currentChatUserId && 
            (data.sender_id.toString() === currentChatUserId.toString() || 
             data.sender_id.toString() === CURRENT_USER_ID.toString());
        
        if (isForCurrentChat) {
            // Mensaje para el chat actual
            appendMessage(data);
            
            // Si no es nuestro mensaje, marcarlo como leído
            if (data.sender_id.toString() !== CURRENT_USER_ID.toString()) {
                markMessagesAsRead(data.sender_id);
            }
        } else if (data.receiver_id.toString() === CURRENT_USER_ID.toString()) {
            // Mensaje para otro chat, incrementar badge
            incrementUnreadBadge(data.sender_id);
        }
    }
    
    // Función para cargar conteo de no leídos
    async function loadUnreadCounts() {
        try {
            const response = await fetch('api/get-unread-count.php');
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
            await fetch('api/mark-as-read.php', {
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
    
    // Función para escapar HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    console.log('✅ Sistema de chat inicializado correctamente');
});
