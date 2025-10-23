/**
 * PERSEO - Sistema de Acciones Inteligentes
 * Procesamiento de Lenguaje Natural + Ejecución de Acciones
 * HandinHand Platform
 */

class PerseoActionSystem {
    constructor() {
        this.userState = {
            reminders: JSON.parse(localStorage.getItem('perseo_reminders') || '[]'),
            notifications: JSON.parse(localStorage.getItem('perseo_notifications') || '[]'),
            quickActions: JSON.parse(localStorage.getItem('perseo_quick_actions') || '[]')
        };
        
        this.actionPatterns = this.initializeActionPatterns();
        this.initializeReminderSystem();
        this.initializeNotificationSystem();
    }

    // Patrones de PLN para detectar intenciones de acción
    initializeActionPatterns() {
        return {
            // Recordatorios
            reminder: {
                patterns: [
                    /recuerd[aá]me\s+(.*?)\s+(ma[ñn]ana|en\s+\d+\s+(minutos?|horas?|d[ií]as?))/i,
                    /recordatorio\s+(.*?)\s+(ma[ñn]ana|en\s+\d+\s+(minutos?|horas?|d[ií]as?))/i,
                    /avisa?me\s+(.*?)\s+(ma[ñn]ana|en\s+\d+\s+(minutos?|horas?|d[ií]as?))/i,
                    /(no\s+)?olvid[ae]s?\s+(.*?)\s+(ma[ñn]ana|en\s+\d+\s+(minutos?|horas?|d[ií]as?))/i
                ],
                action: 'createReminder'
            },
            
            // Navegación y búsqueda
            navigation: {
                patterns: [
                    /(?:ve|ir|navegar|mostrar|abrir|llevame)\s+a\s+(perfil|productos|mis\s+productos|mensajes|configuraci[oó]n|inicio)/i,
                    /(?:mostrar|ver)\s+(mis\s+productos|productos|perfil|mensajes)/i,
                    /quiero\s+ver\s+(mi\s+)?perfil/i,
                    /(?:ver|mostrar)\s+(mi\s+)?perfil/i,
                    /ir\s+a\s+(mi\s+)?perfil/i,
                    /(?:buscar|encontrar)\s+(.+)/i
                ],
                action: 'navigateOrSearch'
            },
            
            // Gestión de productos
            product: {
                patterns: [
                    /(?:crear|a[ñn]adir|subir|publicar)\s+(?:un\s+)?producto/i,
                    /(?:eliminar|borrar|quitar)\s+(?:el\s+)?producto\s+(.*)/i,
                    /(?:editar|modificar|cambiar)\s+(?:el\s+)?producto\s+(.*)/i,
                    /(?:mis\s+productos|ver\s+productos|mostrar\s+productos)/i
                ],
                action: 'manageProduct'
            },
            
            // Configuración de cuenta
            account: {
                patterns: [
                    /cambiar\s+(mi\s+)?(contrase[ñn]a|email|nombre)/i,
                    /(?:actualizar|modificar)\s+(mi\s+)?(contrase[ñn]a|email|informaci[oó]n)/i,
                    /(?:editar|modificar)\s+(mi\s+)?perfil/i,
                    /(?:activar|desactivar)\s+notificaciones/i,
                    /configuraci[oó]n\s+de\s+(privacidad|cuenta|notificaciones)/i
                ],
                action: 'manageAccount'
            },
            
            // Comunicación y mensajes
            messaging: {
                patterns: [
                    /enviar\s+mensaje\s+a\s+(.*)/i,
                    /contactar\s+(?:a\s+)?(.*)/i,
                    /mis\s+mensajes/i,
                    /conversaciones/i
                ],
                action: 'handleMessaging'
            },
            
            // Ayuda contextual
            help: {
                patterns: [
                    /(?:c[oó]mo|ayuda|explicame)\s+(.*)/i,
                    /qu[eé]\s+es\s+(.*)/i,
                    /para\s+qu[eé]\s+sirve\s+(.*)/i,
                    /tutoriales?\s+(.*)/i
                ],
                action: 'provideHelp'
            },
            
            // Acciones rápidas
            quickAction: {
                patterns: [
                    /acci[oó]n\s+r[aá]pida:\s+(.*)/i,
                    /comando:\s+(.*)/i,
                    /ejecutar\s+(.*)/i
                ],
                action: 'executeQuickAction'
            }
        };
    }

    // Análisis principal del mensaje para detectar intenciones
    analyzeMessage(message) {
        const normalizedMessage = message.toLowerCase().trim();
        
        // Orden de prioridad para evitar conflictos
        const priorityOrder = ['navigation', 'reminder', 'product', 'account', 'messaging', 'help', 'quickAction'];
        
        for (const actionType of priorityOrder) {
            const config = this.actionPatterns[actionType];
            if (!config) continue;
            
            for (const pattern of config.patterns) {
                const match = normalizedMessage.match(pattern);
                if (match) {
                    const extractedData = this.extractActionData(actionType, match, message);
                    
                    return {
                        type: actionType,
                        action: config.action,
                        matches: match,
                        originalMessage: message,
                        extractedData: extractedData
                    };
                }
            }
        }
        
        return null; // No se detectó intención de acción
    }

    // Extraer datos específicos según el tipo de acción
    extractActionData(actionType, matches, originalMessage) {
        switch (actionType) {
            case 'reminder':
                return this.extractReminderData(matches, originalMessage);
            case 'navigation':
                return this.extractNavigationData(matches);
            case 'product':
                return this.extractProductData(matches);
            case 'account':
                return this.extractAccountData(matches);
            case 'messaging':
                return this.extractMessagingData(matches);
            case 'help':
                return this.extractHelpData(matches);
            case 'quickAction':
                return this.extractQuickActionData(matches);
            default:
                return { raw: matches };
        }
    }

    // === RECORDATORIOS ===
    extractReminderData(matches, originalMessage) {
        const task = matches[1]?.trim() || 'tarea sin especificar';
        const timeExpression = matches[2]?.trim();
        
        let reminderTime = new Date();
        
        if (timeExpression.includes('mañana')) {
            reminderTime.setDate(reminderTime.getDate() + 1);
            reminderTime.setHours(9, 0, 0, 0); // 9 AM del día siguiente
        } else {
            // Extraer número y unidad de tiempo
            const timeMatch = timeExpression.match(/(\d+)\s+(minutos?|horas?|d[ií]as?)/i);
            if (timeMatch) {
                const amount = parseInt(timeMatch[1]);
                const unit = timeMatch[2].toLowerCase();
                
                if (unit.includes('minuto')) {
                    reminderTime.setMinutes(reminderTime.getMinutes() + amount);
                } else if (unit.includes('hora')) {
                    reminderTime.setHours(reminderTime.getHours() + amount);
                } else if (unit.includes('día') || unit.includes('dia')) {
                    reminderTime.setDate(reminderTime.getDate() + amount);
                }
            }
        }
        
        return {
            task,
            reminderTime,
            timeExpression,
            originalMessage
        };
    }

    async createReminder(data) {
        const reminder = {
            id: Date.now(),
            task: data.task,
            reminderTime: data.reminderTime,
            created: new Date(),
            active: true,
            originalMessage: data.originalMessage
        };
        
        this.userState.reminders.push(reminder);
        this.saveUserState();
        
        // Programar notificación
        this.scheduleNotification(reminder);
        
        // Crear elemento visual en la interfaz
        this.createReminderCard(reminder);
        
        return {
            success: true,
            message: `✅ Recordatorio creado: "${data.task}" para ${this.formatDateTime(data.reminderTime)}`,
            action: 'reminder_created',
            data: reminder
        };
    }

    scheduleNotification(reminder) {
        const now = new Date().getTime();
        const reminderTime = new Date(reminder.reminderTime).getTime();
        const timeUntilReminder = reminderTime - now;
        
        if (timeUntilReminder > 0) {
            setTimeout(() => {
                this.triggerReminder(reminder);
            }, timeUntilReminder);
        }
    }

    triggerReminder(reminder) {
        // Notificación visual
        this.showNotification({
            title: '⏰ Recordatorio de Perseo',
            message: reminder.task,
            type: 'reminder',
            actions: [
                { text: 'Completado', action: () => this.markReminderComplete(reminder.id) },
                { text: 'Posponer 15min', action: () => this.snoozeReminder(reminder.id, 15) }
            ]
        });
        
        // Mensaje en el chat si está abierto
        if (window.chatbotContainer && !window.chatbotContainer.classList.contains('hidden')) {
            window.agregarMensajePerseo?.(`⏰ ¡Recordatorio! ${reminder.task}`);
        }
        
        // Sonido de notificación (opcional)
        this.playNotificationSound();
    }

    // === NAVEGACIÓN Y BÚSQUEDA ===
    extractNavigationData(matches) {
        const fullMatch = matches[0].toLowerCase();
        let action = fullMatch;
        let target = matches[1]?.trim();
        
        // Detectar si es búsqueda o navegación
        // Solo tratar como búsqueda si empieza explícitamente con "buscar" o "encontrar"
        if ((fullMatch.startsWith('buscar') || fullMatch.startsWith('encontrar')) && target) {
            return { 
                type: 'search',
                action: fullMatch, 
                target: target
            };
        }
        
        // Para navegación, si no tenemos target explícito, extraerlo del mensaje completo
        if (!target) {
            if (fullMatch.includes('mis productos') || (fullMatch.includes('productos') && fullMatch.includes('mis'))) {
                target = 'mis productos';
            } else if (fullMatch.includes('perfil')) {
                target = 'perfil';
            } else if (fullMatch.includes('productos') && !fullMatch.includes('mis')) {
                target = 'productos';
            } else if (fullMatch.includes('mensajes')) {
                target = 'mensajes';
            } else if (fullMatch.includes('configuraci')) {
                target = 'configuracion';
            } else if (fullMatch.includes('inicio')) {
                target = 'inicio';
            }
        }
        
        // Limpiar target si viene con "mi" o espacios extra
        if (target && target.includes('mi ')) {
            target = target.replace('mi ', '').trim();
        }
        
        return { 
            type: 'navigation',
            action, 
            target 
        };
    }

    async navigateOrSearch(data) {
        const { type, action, target } = data;
        
        // Si es búsqueda, ejecutar búsqueda
        if (type === 'search' && target) {
            return this.performSearch(target);
        }
        
        // Si es navegación y tenemos target
        if (type === 'navigation' && target) {
            const routes = {
                'perfil': 'perfil.php',
                'productos': 'index.php', // Página principal con productos
                'mis productos': 'mis-productos.php',
                'mensajes': '#', // Página no implementada aún
                'configuracion': 'configuracion.php',
                'configuración': 'configuracion.php',
                'inicio': 'index.php'
            };
            
            const route = routes[target.toLowerCase()];
            
            if (route) {
                if (route === '#') {
                    return {
                        success: false,
                        message: `📧 La sección de ${target} está en desarrollo. Pronto estará disponible.`,
                        action: 'navigation_not_available'
                    };
                }
                
                window.location.href = route;
                return {
                    success: true,
                    message: `🧭 Navegando a tu ${target}...`,
                    action: 'navigation',
                    data: { route, target }
                };
            }
        }
        
        return {
            success: false,
            message: `❓ No pude entender la navegación solicitada. Intenta con:<br>• "ir a perfil"<br>• "mostrar mis productos"<br>• "buscar [término]"`,
            action: 'navigation_failed'
        };
    }

    async performSearch(query) {
        // Mostrar indicador de búsqueda
        this.showSearchIndicator();
        
        try {
            const response = await fetch('api/search.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query })
            });
            
            const data = await response.json();
            
            this.hideSearchIndicator();
            
            if (data.success) {
                this.displaySearchResults(data.results, query);
                return {
                    success: true,
                    message: `🔍 Encontré ${data.results.total} resultados para "${query}"`,
                    action: 'search_completed',
                    data: data.results
                };
            }
        } catch (error) {
            console.error('Error en búsqueda:', error);
            this.hideSearchIndicator();
        }
        
        return {
            success: false,
            message: `🔍 No pude realizar la búsqueda en este momento. Inténtalo de nuevo.`,
            action: 'search_failed'
        };
    }

    showSearchIndicator() {
        // Remover indicador existente
        const existing = document.getElementById('search-indicator');
        if (existing) existing.remove();
        
        const indicator = document.createElement('div');
        indicator.id = 'search-indicator';
        indicator.className = 'search-indicator';
        indicator.innerHTML = '🔍 Buscando...';
        document.body.appendChild(indicator);
    }

    hideSearchIndicator() {
        const indicator = document.getElementById('search-indicator');
        if (indicator) {
            indicator.style.animation = 'slideOut 0.3s ease-in-out';
            setTimeout(() => indicator.remove(), 300);
        }
    }

    displaySearchResults(results, query) {
        // Remover resultados anteriores
        const existing = document.getElementById('search-results-overlay');
        if (existing) existing.remove();
        
        const overlay = document.createElement('div');
        overlay.id = 'search-results-overlay';
        overlay.className = 'search-results-overlay';
        
        overlay.innerHTML = `
            <div class="search-results-header">
                <h3>🔍 Resultados para "${query}"</h3>
                <button onclick="this.closest('.search-results-overlay').remove()" class="modal-close">&times;</button>
            </div>
            <div class="search-results-list">
                ${this.formatSearchResults(results)}
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Auto-remove después de 30 segundos
        setTimeout(() => {
            if (overlay.parentElement) overlay.remove();
        }, 30000);
    }

    formatSearchResults(results) {
        let html = '';
        
        if (results.productos && results.productos.length > 0) {
            html += '<h4 style="margin: 15px 0 10px; color: #313C26;">📦 Productos</h4>';
            results.productos.forEach(producto => {
                html += `
                    <div class="search-result-item" onclick="window.location.href='${producto.url}'">
                        <div class="search-result-title">${producto.title}</div>
                        <div class="search-result-description">${producto.description}</div>
                        <small style="color: #666;">Por ${producto.seller} • ${producto.category}</small>
                    </div>
                `;
            });
        }
        
        if (results.usuarios && results.usuarios.length > 0) {
            html += '<h4 style="margin: 15px 0 10px; color: #313C26;">👥 Usuarios</h4>';
            results.usuarios.forEach(usuario => {
                html += `
                    <div class="search-result-item" onclick="window.location.href='${usuario.url}'">
                        <div class="search-result-title">${usuario.title}</div>
                        <div class="search-result-description">${usuario.description}</div>
                    </div>
                `;
            });
        }
        
        if (html === '') {
            html = '<p style="text-align: center; color: #666; padding: 20px;">No se encontraron resultados</p>';
        }
        
        return html;
    }

    // === GESTIÓN DE PRODUCTOS ===
    extractProductData(matches) {
        const action = matches[0];
        const productName = matches[1]?.trim();
        
        return { action, productName };
    }

    async manageProduct(data) {
        const { action, productName } = data;
        
        if (action.match(/crear|añadir|subir|publicar/i)) {
            return this.createProductFlow();
        } else if (action.match(/eliminar|borrar|quitar/i) && productName) {
            return this.deleteProductFlow(productName);
        } else if (action.match(/editar|modificar|cambiar/i) && productName) {
            return this.editProductFlow(productName);
        } else if (action.match(/mis productos|ver productos|mostrar productos/i)) {
            window.location.href = 'mis-productos.php';
            return {
                success: true,
                message: `📦 Mostrando tus productos...`,
                action: 'view_my_products'
            };
        }
        
        return {
            success: false,
            message: `❓ No entendí la acción del producto. Prueba con:<br>• "mostrar mis productos"<br>• "crear producto"<br>• "editar producto [nombre]"`,
            action: 'product_action_failed'
        };
    }

    createProductFlow() {
        // Abrir modal de creación de producto
        this.showActionModal({
            title: '📦 Crear Nuevo Producto',
            content: `
                <div class="action-form">
                    <div class="form-group">
                        <label>Nombre del producto:</label>
                        <input type="text" id="product-name" placeholder="Ej: iPhone 12 Pro">
                    </div>
                    <div class="form-group">
                        <label>Descripción:</label>
                        <textarea id="product-description" placeholder="Describe tu producto..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Categoría:</label>
                        <select id="product-category">
                            <option value="">Seleccionar categoría</option>
                            <option value="electronica">Electrónica</option>
                            <option value="ropa">Ropa</option>
                            <option value="hogar">Hogar</option>
                            <option value="deportes">Deportes</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>
                </div>
            `,
            actions: [
                {
                    text: 'Crear Producto',
                    primary: true,
                    action: () => this.executeProductCreation()
                },
                {
                    text: 'Cancelar',
                    action: () => this.closeActionModal()
                }
            ]
        });
        
        return {
            success: true,
            message: `📦 Abriendo formulario de creación de producto...`,
            action: 'product_creation_form_opened'
        };
    }

    // === CONFIGURACIÓN DE CUENTA ===
    extractAccountData(matches) {
        const fullMatch = matches[0].toLowerCase();
        let action = fullMatch;
        let target = matches[2]?.trim() || matches[1]?.trim(); // matches[2] para capturar después de "mi"
        
        // Detectar qué tipo de cambio es
        if (!target) {
            if (fullMatch.includes('contrase')) {
                target = 'contraseña';
            } else if (fullMatch.includes('email')) {
                target = 'email';
            } else if (fullMatch.includes('nombre')) {
                target = 'nombre';
            } else if (fullMatch.includes('perfil')) {
                target = 'perfil';
            } else if (fullMatch.includes('notificaciones')) {
                target = 'notificaciones';
            }
        }
        
        return { action, target };
    }

    async manageAccount(data) {
        const { action, target } = data;
        
        if (action.match(/cambiar.*contrase[ñn]a/i) || target === 'contraseña') {
            // Redirigir al perfil con indicador para resaltar el botón de cambiar contraseña
            window.location.href = 'perfil.php?highlight=password';
            return {
                success: true,
                message: `🔑 Redirigiendo al perfil para cambiar contraseña...`,
                action: 'redirect_to_profile_password'
            };
        } else if (action.match(/cambiar.*email/i) || target === 'email') {
            return this.changeEmailFlow();
        } else if (action.match(/actualizar.*perfil/i) || action.match(/editar.*perfil/i) || target === 'perfil') {
            return this.updateProfileFlow();
        } else if (action.match(/notificaciones/i) || target === 'notificaciones') {
            return this.manageNotificationsFlow();
        }
        
        // Navegación por defecto a configuración
        window.location.href = 'configuracion.php';
        return {
            success: true,
            message: `⚙️ Abriendo configuración de cuenta...`,
            action: 'account_settings_opened'
        };
    }

    // === SISTEMA DE NOTIFICACIONES ===
    initializeNotificationSystem() {
        // Crear contenedor de notificaciones si no existe
        if (!document.getElementById('perseo-notifications')) {
            const container = document.createElement('div');
            container.id = 'perseo-notifications';
            container.className = 'perseo-notifications-container';
            document.body.appendChild(container);
        }
        
        // Verificar recordatorios pendientes al cargar
        this.checkPendingReminders();
    }

    showNotification({ title, message, type = 'info', duration = 5000, actions = [] }) {
        const container = document.getElementById('perseo-notifications');
        const notification = document.createElement('div');
        notification.className = `perseo-notification perseo-notification-${type}`;
        
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-header">
                    <strong>${title}</strong>
                    <button class="notification-close">&times;</button>
                </div>
                <div class="notification-message">${message}</div>
                ${actions.length > 0 ? `
                    <div class="notification-actions">
                        ${actions.map((action, index) => 
                            `<button class="notification-action" data-action="${index}">${action.text}</button>`
                        ).join('')}
                    </div>
                ` : ''}
            </div>
        `;
        
        // Event listeners
        notification.querySelector('.notification-close').onclick = () => {
            this.removeNotification(notification);
        };
        
        actions.forEach((action, index) => {
            const btn = notification.querySelector(`[data-action="${index}"]`);
            if (btn) {
                btn.onclick = () => {
                    action.action();
                    this.removeNotification(notification);
                };
            }
        });
        
        container.appendChild(notification);
        
        // Auto-remove
        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);
        }
        
        return notification;
    }

    removeNotification(notification) {
        notification.style.animation = 'slideOut 0.3s ease-in-out';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }

    // === SISTEMA DE RECORDATORIOS ===
    initializeReminderSystem() {
        this.checkPendingReminders();
        
        // Verificar cada minuto
        setInterval(() => {
            this.checkPendingReminders();
        }, 60000);
    }

    checkPendingReminders() {
        const now = new Date();
        this.userState.reminders
            .filter(reminder => reminder.active && new Date(reminder.reminderTime) <= now)
            .forEach(reminder => {
                this.triggerReminder(reminder);
                reminder.active = false;
            });
        
        this.saveUserState();
    }

    // === UTILIDADES ===
    saveUserState() {
        localStorage.setItem('perseo_reminders', JSON.stringify(this.userState.reminders));
        localStorage.setItem('perseo_notifications', JSON.stringify(this.userState.notifications));
        localStorage.setItem('perseo_quick_actions', JSON.stringify(this.userState.quickActions));
    }

    formatDateTime(date) {
        return new Intl.DateTimeFormat('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    }

    playNotificationSound() {
        // Sonido sutil para notificaciones
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj...');
        audio.volume = 0.3;
        audio.play().catch(() => {}); // Ignorar errores de autoplay
    }

    createReminderCard(reminder) {
        // Solo mostrar si estamos en la página de perfil o dashboard
        const reminderContainer = document.getElementById('reminder-container');
        if (!reminderContainer) return;
        
        const card = document.createElement('div');
        card.className = 'reminder-card';
        card.innerHTML = `
            <div class="reminder-content">
                <div class="reminder-task">${reminder.task}</div>
                <div class="reminder-time">${this.formatDateTime(reminder.reminderTime)}</div>
                <div class="reminder-actions">
                    <button onclick="perseoActions.markReminderComplete(${reminder.id})" class="btn-complete">Completar</button>
                    <button onclick="perseoActions.editReminder(${reminder.id})" class="btn-edit">Editar</button>
                </div>
            </div>
        `;
        
        reminderContainer.appendChild(card);
    }

    showActionModal({ title, content, actions }) {
        // Crear modal dinámico para acciones
        const modal = document.createElement('div');
        modal.className = 'perseo-action-modal';
        modal.innerHTML = `
            <div class="modal-backdrop" onclick="this.parentElement.remove()"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>${title}</h3>
                    <button class="modal-close" onclick="this.closest('.perseo-action-modal').remove()">&times;</button>
                </div>
                <div class="modal-body">${content}</div>
                <div class="modal-actions">
                    ${actions.map((action, index) => 
                        `<button class="modal-action-btn ${action.primary ? 'primary' : ''}" data-action="${index}">
                            ${action.text}
                        </button>`
                    ).join('')}
                </div>
            </div>
        `;
        
        // Event listeners para acciones
        actions.forEach((action, index) => {
            const btn = modal.querySelector(`[data-action="${index}"]`);
            btn.onclick = action.action;
        });
        
        document.body.appendChild(modal);
        return modal;
    }

    changePasswordFlow() {
        this.showActionModal({
            title: '🔒 Cambiar Contraseña',
            content: `
                <div class="action-form">
                    <div class="form-group">
                        <label>Contraseña actual:</label>
                        <input type="password" id="current-password" placeholder="Tu contraseña actual">
                    </div>
                    <div class="form-group">
                        <label>Nueva contraseña:</label>
                        <input type="password" id="new-password" placeholder="Nueva contraseña">
                    </div>
                    <div class="form-group">
                        <label>Confirmar nueva contraseña:</label>
                        <input type="password" id="confirm-password" placeholder="Confirmar contraseña">
                    </div>
                </div>
            `,
            actions: [
                {
                    text: 'Cambiar Contraseña',
                    primary: true,
                    action: () => this.executePasswordChange()
                },
                {
                    text: 'Cancelar',
                    action: () => this.closeActionModal()
                }
            ]
        });
        
        return {
            success: true,
            message: `🔒 Formulario de cambio de contraseña abierto`,
            action: 'password_change_form_opened'
        };
    }

    changeEmailFlow() {
        this.showActionModal({
            title: '📧 Cambiar Email',
            content: `
                <div class="action-form">
                    <div class="form-group">
                        <label>Nuevo email:</label>
                        <input type="email" id="new-email" placeholder="nuevo@email.com">
                    </div>
                    <div class="form-group">
                        <label>Contraseña actual (requerida):</label>
                        <input type="password" id="confirm-password-email" placeholder="Tu contraseña actual">
                    </div>
                </div>
            `,
            actions: [
                {
                    text: 'Cambiar Email',
                    primary: true,
                    action: () => this.executeEmailChange()
                },
                {
                    text: 'Cancelar',
                    action: () => this.closeActionModal()
                }
            ]
        });
        
        return {
            success: true,
            message: `📧 Formulario de cambio de email abierto`,
            action: 'email_change_form_opened'
        };
    }

    updateProfileFlow() {
        // Navegación directa al perfil
        window.location.href = 'perfil.php';
        return {
            success: true,
            message: `👤 Abriendo tu perfil para editar...`,
            action: 'profile_opened'
        };
    }

    manageNotificationsFlow() {
        this.showActionModal({
            title: '🔔 Gestionar Notificaciones',
            content: `
                <div class="action-form">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="email-notifications" checked>
                            📧 Notificaciones por email
                        </label>
                        <small style="color: #666;">Recibir emails cuando alguien te contacte</small>
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="reminder-notifications" checked>
                            ⏰ Recordatorios de Perseo
                        </label>
                        <small style="color: #666;">Permitir que Perseo te envíe recordatorios</small>
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="browser-notifications">
                            🔔 Notificaciones del navegador
                        </label>
                        <small style="color: #666;">Mostrar notificaciones en tu escritorio</small>
                    </div>
                </div>
            `,
            actions: [
                {
                    text: 'Guardar Preferencias',
                    primary: true,
                    action: () => this.saveNotificationPreferences()
                },
                {
                    text: 'Cancelar',
                    action: () => this.closeActionModal()
                }
            ]
        });
        
        return {
            success: true,
            message: `🔔 Configuración de notificaciones abierta`,
            action: 'notifications_config_opened'
        };
    }

    // === FUNCIONES DE EJECUCIÓN ===
    executeProductCreation() {
        const name = document.getElementById('product-name')?.value;
        const description = document.getElementById('product-description')?.value;
        const category = document.getElementById('product-category')?.value;
        
        if (!name || !description || !category) {
            this.showNotification({
                title: '⚠️ Error',
                message: 'Por favor completa todos los campos',
                type: 'warning',
                duration: 3000
            });
            return;
        }
        
        // Simulación de creación de producto
        this.closeActionModal();
        this.showNotification({
            title: '✅ Producto Creado',
            message: `Tu producto "${name}" ha sido creado exitosamente`,
            type: 'info',
            duration: 5000
        });
        
        // En una implementación real, aquí enviarías los datos al servidor
        console.log('Producto a crear:', { name, description, category });
    }

    executePasswordChange() {
        const current = document.getElementById('current-password')?.value;
        const newPass = document.getElementById('new-password')?.value;
        const confirm = document.getElementById('confirm-password')?.value;
        
        if (!current || !newPass || !confirm) {
            this.showNotification({
                title: '⚠️ Error',
                message: 'Completa todos los campos',
                type: 'warning',
                duration: 3000
            });
            return;
        }
        
        if (newPass !== confirm) {
            this.showNotification({
                title: '⚠️ Error',
                message: 'Las contraseñas no coinciden',
                type: 'warning',
                duration: 3000
            });
            return;
        }
        
        if (newPass.length < 6) {
            this.showNotification({
                title: '⚠️ Error',
                message: 'La contraseña debe tener al menos 6 caracteres',
                type: 'warning',
                duration: 3000
            });
            return;
        }
        
        this.closeActionModal();
        this.showNotification({
            title: '✅ Contraseña Cambiada',
            message: 'Tu contraseña se ha actualizado correctamente',
            type: 'info',
            duration: 5000
        });
    }

    executeEmailChange() {
        const newEmail = document.getElementById('new-email')?.value;
        const password = document.getElementById('confirm-password-email')?.value;
        
        if (!newEmail || !password) {
            this.showNotification({
                title: '⚠️ Error',
                message: 'Completa todos los campos',
                type: 'warning',
                duration: 3000
            });
            return;
        }
        
        // Validación simple de email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(newEmail)) {
            this.showNotification({
                title: '⚠️ Error',
                message: 'El formato del email no es válido',
                type: 'warning',
                duration: 3000
            });
            return;
        }
        
        this.closeActionModal();
        this.showNotification({
            title: '✅ Email Actualizado',
            message: `Tu email se ha cambiado a ${newEmail}`,
            type: 'info',
            duration: 5000
        });
    }

    saveNotificationPreferences() {
        const emailNotifs = document.getElementById('email-notifications')?.checked;
        const reminderNotifs = document.getElementById('reminder-notifications')?.checked;
        const browserNotifs = document.getElementById('browser-notifications')?.checked;
        
        // Guardar preferencias en localStorage
        const preferences = {
            email: emailNotifs,
            reminders: reminderNotifs,
            browser: browserNotifs
        };
        
        localStorage.setItem('perseo_notification_preferences', JSON.stringify(preferences));
        
        // Si se habilitaron notificaciones del navegador, pedir permiso
        if (browserNotifs && 'Notification' in window) {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification('🔔 Perseo', {
                        body: 'Notificaciones del navegador activadas',
                        icon: 'img/Hand(sinfondo).png'
                    });
                }
            });
        }
        
        this.closeActionModal();
        this.showNotification({
            title: '✅ Preferencias Guardadas',
            message: 'Tus preferencias de notificación han sido actualizadas',
            type: 'info',
            duration: 5000
        });
    }

    closeActionModal() {
        const modal = document.querySelector('.perseo-action-modal');
        if (modal) {
            modal.style.animation = 'modalDisappear 0.3s ease-in-out';
            setTimeout(() => modal.remove(), 300);
        }
    }

    // === FUNCIONES AUXILIARES FALTANTES ===
    async handleMessaging(data) {
        return {
            success: false,
            message: `💬 Sistema de mensajería en desarrollo. Usa el menú principal por ahora.`,
            action: 'messaging_not_implemented'
        };
    }

    async provideHelp(data) {
        const { matches } = data;
        const topic = matches[1] || 'general';
        
        const helpTopics = {
            'intercambio': 'Para intercambiar productos, busca lo que te interesa y contacta al vendedor. ¡Es gratis!',
            'producto': 'Puedes subir productos desde tu perfil. Agrega fotos y descripción detallada.',
            'cuenta': 'Gestiona tu cuenta desde el menú desplegable > Mi Perfil.',
            'recordatorio': 'Dime "recuérdame [tarea] [tiempo]" y yo te avisaré. Ejemplo: "recuérdame revisar el producto mañana"',
            'buscar': 'Dime "buscar [término]" o usa la barra de búsqueda principal.',
            'navegacion': 'Puedo llevarte a diferentes secciones. Prueba "ir a perfil" o "mostrar productos"',
            'contraseña': 'Para cambiar tu contraseña, dime "cambiar mi contraseña" y abriré un formulario seguro.',
            'password': 'Para cambiar tu contraseña, dime "cambiar mi contraseña" y abriré un formulario seguro.'
        };
        
        const help = helpTopics[topic.toLowerCase()] || 
                     `📚 Soy Perseo, tu asistente inteligente. Puedo ayudarte con:
                     
🔍 **Búsquedas**: "buscar iPhone", "encontrar zapatillas" 
🧭 **Navegación**: "ir a perfil", "mostrar mis productos", "ir a configuración"
⏰ **Recordatorios**: "recuérdame revisar esto mañana", "avísame en 2 horas"
📦 **Productos**: "crear producto", "mostrar mis productos" 
⚙️ **Configuración**: "cambiar mi contraseña", "actualizar mi email", "activar notificaciones"

**Comandos de configuración:**
• "cambiar mi contraseña" → Formulario seguro de cambio
• "actualizar mi email" → Modal de cambio de email
• "activar notificaciones" → Panel de preferencias

**Ejemplos específicos:**
• "Ve a mi perfil" → Te lleva a perfil.php
• "Mostrar mis productos" → Abre mis-productos.php
• "Buscar iPhone" → Búsqueda inteligente con resultados
• "Cambiar mi contraseña" → Modal seguro de cambio

¡Habla conmigo en lenguaje natural!`;
        
        return {
            success: true,
            message: help,
            action: 'help_provided',
            data: { topic }
        };
    }

    async executeQuickAction(data) {
        return {
            success: false,
            message: `⚡ Acciones rápidas en desarrollo. Usa comandos normales por ahora.`,
            action: 'quick_actions_not_implemented'
        };
    }

    markReminderComplete(reminderId) {
        const reminder = this.userState.reminders.find(r => r.id === reminderId);
        if (reminder) {
            reminder.active = false;
            reminder.completed = true;
            reminder.completedAt = new Date();
            this.saveUserState();
            
            // Remover tarjeta visual
            const card = document.querySelector(`[data-reminder-id="${reminderId}"]`);
            if (card) {
                card.style.animation = 'slideOut 0.3s ease-in-out';
                setTimeout(() => card.remove(), 300);
            }
            
            this.showNotification({
                title: '✅ Recordatorio Completado',
                message: `"${reminder.task}" marcado como completado`,
                type: 'info',
                duration: 3000
            });
        }
    }

    snoozeReminder(reminderId, minutes) {
        const reminder = this.userState.reminders.find(r => r.id === reminderId);
        if (reminder) {
            const newTime = new Date(Date.now() + minutes * 60000);
            reminder.reminderTime = newTime;
            this.saveUserState();
            
            // Reprogramar notificación
            this.scheduleNotification(reminder);
            
            this.showNotification({
                title: '⏰ Recordatorio Pospuesto',
                message: `Te avisaré en ${minutes} minutos`,
                type: 'info',
                duration: 3000
            });
        }
    }

    // === API PÚBLICA ===
    async executeAction(message) {
        const actionIntent = this.analyzeMessage(message);
        
        if (!actionIntent) {
            return null; // No es una acción, procesar como chat normal
        }
        
        try {
            let result;
            
            switch (actionIntent.action) {
                case 'createReminder':
                    result = await this.createReminder(actionIntent.extractedData);
                    break;
                case 'navigateOrSearch':
                    result = await this.navigateOrSearch(actionIntent.extractedData);
                    break;
                case 'manageProduct':
                    result = await this.manageProduct(actionIntent.extractedData);
                    break;
                case 'manageAccount':
                    result = await this.manageAccount(actionIntent.extractedData);
                    break;
                case 'handleMessaging':
                    result = await this.handleMessaging(actionIntent.extractedData);
                    break;
                case 'provideHelp':
                    result = await this.provideHelp(actionIntent.extractedData);
                    break;
                case 'executeQuickAction':
                    result = await this.executeQuickAction(actionIntent.extractedData);
                    break;
                default:
                    result = {
                        success: false,
                        message: `🤖 Detecté que quieres realizar una acción, pero aún no puedo ejecutar: ${actionIntent.type}`,
                        action: 'unsupported_action'
                    };
            }
            
            return result;
            
        } catch (error) {
            console.error('Error ejecutando acción:', error);
            return {
                success: false,
                message: `⚠️ Hubo un error ejecutando la acción. Inténtalo de nuevo.`,
                action: 'action_error',
                error
            };
        }
    }
}

// Inicializar sistema cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.perseoActions = new PerseoActionSystem();
    console.log('🤖 Perseo Action System inicializado');
});
