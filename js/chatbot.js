document.addEventListener("DOMContentLoaded", function () {
  const chatbotContainer = document.getElementById("chatbot-container");
  const chatbotIcon = document.getElementById("chatbot-icon");
  const closeBtn = document.getElementById("close-btn");
  const sendBtn = document.getElementById("send-btn");
  const chatbotInput = document.getElementById("chatbot-input");
  const chatbotMessages = document.getElementById("chatbot-messages");

  // Mensajes de bienvenida de Perseo
  const mensajesBienvenida = [
    "¡Hola! 👋 Soy Perseo, tu asistente inteligente de HandinHand.",
    "Estoy aquí para ayudarte con intercambios, productos y cualquier duda que tengas.",
    "¿En qué puedo ayudarte hoy? 🤖"
  ];

  let chatAbierto = false;

  // Mostrar el chatbot
  chatbotIcon.addEventListener("click", () => {
    chatbotContainer.classList.remove("hidden");
    chatbotIcon.style.display = "none";
    chatAbierto = true;

    // Mostrar mensaje de bienvenida solo la primera vez y si Perseo no abrió el chat
    if (chatbotMessages.children.length === 0 && !window.perseoOpenedChat) {
      mostrarMensajesBienvenida();
    }

    // Enfocar en el input
    chatbotInput.focus();
  });

  // Cerrar chatbot
  closeBtn.addEventListener("click", () => {
    chatbotContainer.classList.add("hidden");
    chatbotIcon.style.display = "flex";
    chatAbierto = false;
  });

  // Enviar mensaje con Enter
  chatbotInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      enviarMensaje();
    }
  });

  // Enviar mensaje con botón
  sendBtn.addEventListener("click", enviarMensaje);

  // Cerrar chatbot con tecla ESC
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && chatAbierto) {
      chatbotContainer.classList.add("hidden");
      chatbotIcon.style.display = "flex";
      chatAbierto = false;
    }
  });

  // Mostrar mensajes de bienvenida
  function mostrarMensajesBienvenida() {
    mensajesBienvenida.forEach((mensaje, index) => {
      setTimeout(() => {
        agregarMensaje("bot", mensaje, false);
      }, index * 1000);
    });
  }

  // Función principal para enviar mensajes
  async function enviarMensaje() {
    const mensajeUsuario = chatbotInput.value.trim();

    if (!mensajeUsuario) {
      mostrarError("Por favor escribe un mensaje");
      return;
    }

    // Agregar mensaje del usuario
    agregarMensaje("user", mensajeUsuario);
    chatbotInput.value = "";

    // === SISTEMA DE ACCIONES INTELIGENTES ===
    // Verificar si el mensaje contiene una acción ejecutable
    if (window.perseoActions) {
      try {
        const actionResult = await window.perseoActions.executeAction(mensajeUsuario);

        if (actionResult) {
          // Es una acción, mostrar resultado directamente
          agregarMensaje("bot", actionResult.message, true, actionResult);

          // Si la acción fue exitosa, no enviar a la API de chat
          if (actionResult.success) {
            return;
          }
        }
      } catch (error) {
        console.error('Error en sistema de acciones:', error);
        // Continuar con el chat normal si hay error en acciones
      }
    }

    // Mostrar indicador de escritura
    mostrarIndicadorEscritura();

    // Enviar a la API de Perseo
    fetch('api/chatbot.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        mensaje: mensajeUsuario
      })
    })
    .then(response => {
      console.log('Status:', response.status);
      console.log('Content-Type:', response.headers.get('content-type'));

      // Verificar si la respuesta es JSON
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        // Si no es JSON, obtener el texto para ver el error
        return response.text().then(text => {
          console.error('Respuesta no es JSON:', text);
          throw new Error('La API no devolvió JSON válido');
        });
      }

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      console.log('Respuesta de la API:', data);
      // Remover indicador de escritura
      removerIndicadorEscritura();

      if (data.success && data.data && data.data.respuesta) {
        // Simular tiempo de respuesta para UX más natural
        setTimeout(() => {
          agregarMensaje("bot", data.data.respuesta);
        }, 500);
      } else {
        console.error('Error en la respuesta:', data);
        const errorMsg = data.message || 'No pude procesar tu mensaje';
        agregarMensaje("bot", "Lo siento, " + errorMsg + ". ¿Podrías reformularlo?");
      }
    })
    .catch(error => {
      console.error('Error detallado en Perseo:', error);
      removerIndicadorEscritura();

      // Respuesta de fallback más amigable
      setTimeout(() => {
        let mensajeError = "🔧 Tengo un pequeño problema técnico.";
        if (error.message.includes('JSON')) {
          mensajeError += " El servidor no está respondiendo correctamente.";
        } else {
          mensajeError += " Error: " + error.message;
        }
        agregarMensaje("bot", mensajeError);
      }, 500);
    });
  }

  // Agregar mensaje al chat
  function agregarMensaje(remitente, mensaje, animado = true, actionData = null) {
    const elementoMensaje = document.createElement("div");
    elementoMensaje.classList.add("message", remitente);

    // Agregar clases especiales para acciones
    if (actionData) {
      elementoMensaje.classList.add("action-message");
      if (actionData.success) {
        elementoMensaje.classList.add("action-success");
      } else {
        elementoMensaje.classList.add("action-error");
      }
    }

    if (remitente === "bot") {
      elementoMensaje.innerHTML = `
        <div class="bot-avatar">P</div>
        <div class="message-content">
          ${formatearMensaje(mensaje)}
          ${actionData ? crearElementosAccion(actionData) : ''}
        </div>
      `;
    } else {
      elementoMensaje.innerHTML = `
        <div class="message-content">${formatearMensaje(mensaje)}</div>
      `;
    }

    if (animado) {
      elementoMensaje.style.opacity = "0";
      elementoMensaje.style.transform = "translateY(10px)";
    }

    chatbotMessages.appendChild(elementoMensaje);

    // Animación de entrada
    if (animado) {
      setTimeout(() => {
        elementoMensaje.style.transition = "all 0.3s ease";
        elementoMensaje.style.opacity = "1";
        elementoMensaje.style.transform = "translateY(0)";
      }, 100);
    }

    // Scroll automático
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
  }

  // Crear elementos visuales para acciones
  function crearElementosAccion(actionData) {
    if (!actionData || !actionData.action) return '';

    let elementoExtra = '';

    switch (actionData.action) {
      case 'reminder_created':
        elementoExtra = `
          <div class="action-result reminder-result">
            <div class="action-icon">⏰</div>
            <div class="action-details">
              <strong>Recordatorio creado</strong>
              <p>Te avisaré: ${new Date(actionData.data.reminderTime).toLocaleString('es-ES')}</p>
            </div>
          </div>
        `;
        break;

      case 'navigation':
        elementoExtra = `
          <div class="action-result navigation-result">
            <div class="action-icon">🧭</div>
            <div class="action-details">
              <strong>Navegando...</strong>
              <p>Redirigiendo a ${actionData.data.target}</p>
            </div>
          </div>
        `;
        break;

      case 'search_completed':
        elementoExtra = `
          <div class="action-result search-result">
            <div class="action-icon">🔍</div>
            <div class="action-details">
              <strong>Búsqueda completada</strong>
              <p>${actionData.data.length} resultados encontrados</p>
              <button onclick="window.perseoActions.showSearchResults()" class="action-button">Ver resultados</button>
            </div>
          </div>
        `;
        break;

      case 'product_creation_form_opened':
        elementoExtra = `
          <div class="action-result product-result">
            <div class="action-icon">📦</div>
            <div class="action-details">
              <strong>Formulario abierto</strong>
              <p>Completa los datos para crear tu producto</p>
            </div>
          </div>
        `;
        break;

      default:
        if (actionData.success) {
          elementoExtra = `
            <div class="action-result success-result">
              <div class="action-icon">✅</div>
              <div class="action-details">
                <strong>Acción completada</strong>
              </div>
            </div>
          `;
        }
    }

    return elementoExtra;
  }

  // Exponer función para sistema de acciones
  window.agregarMensajePerseo = function(mensaje, actionData = null) {
    agregarMensaje("bot", mensaje, true, actionData);
  };

  // Formatear mensaje para mostrar saltos de línea e imágenes
  function formatearMensaje(mensaje) {
    // Primero reemplazar saltos de línea
    let mensajeFormateado = mensaje.replace(/\n/g, '<br>');

    // Detectar y convertir URLs de imágenes
    const regexImagen = /🖼️\s*([^\s<br>]+\.(jpg|jpeg|png|gif|webp|bmp))/gi;
    mensajeFormateado = mensajeFormateado.replace(regexImagen, (match, url) => {
      // Limpiar la URL
      const urlLimpia = url.trim();
      return `<br><div class="product-image"><img src="${urlLimpia}" alt="Imagen del producto" style="max-width: 200px; max-height: 150px; border-radius: 8px; margin: 5px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" onload="this.parentElement.parentElement.parentElement.parentElement.scrollTop = this.parentElement.parentElement.parentElement.parentElement.scrollHeight" onerror="this.style.display='none'; this.parentElement.innerHTML='🖼️ [Imagen no disponible]';"></div>`;
    });

    // Detectar y estilizar links WIP
    const regexWIP = /\[([^\]]+)\] \(WIP - En desarrollo\)/gi;
    mensajeFormateado = mensajeFormateado.replace(regexWIP, (match, textoLink) => {
      return `<span style="color: #6c757d; font-style: italic; border: 1px dashed #dee2e6; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; background: #f8f9fa;">[${textoLink}] (🚧 En desarrollo)</span>`;
    });

    return mensajeFormateado;
  }

  // Mostrar indicador de escritura
  function mostrarIndicadorEscritura() {
    const indicador = document.createElement("div");
    indicador.classList.add("message", "bot", "typing-indicator");
    indicador.innerHTML = `
      <div class="bot-avatar">P</div>
      <div class="message-content">
        <div class="typing-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    `;

    chatbotMessages.appendChild(indicador);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
  }

  // Remover indicador de escritura
  function removerIndicadorEscritura() {
    const indicador = chatbotMessages.querySelector('.typing-indicator');
    if (indicador) {
      indicador.remove();
    }
  }

  // Mostrar error temporal
  function mostrarError(mensaje) {
    // Remover cualquier mensaje de error existente
    const errorExistente = chatbotContainer.querySelector('.temp-error-message');
    if (errorExistente) {
      errorExistente.remove();
    }

    // Crear elemento de error que aparece sobre el input
    const errorElement = document.createElement("div");
    errorElement.classList.add("temp-error-message");
    errorElement.textContent = mensaje;
    errorElement.style.cssText = `
      background: #ff4444;
      color: white;
      padding: 6px 12px;
      margin: 5px 10px 0 10px;
      border-radius: 6px;
      font-size: 12px;
      text-align: center;
      opacity: 0;
      transition: opacity 0.3s ease;
      position: absolute;
      bottom: 60px;
      left: 0;
      right: 0;
      z-index: 1000;
    `;

    // Agregar al contenedor del chatbot (no al área de mensajes)
    chatbotContainer.appendChild(errorElement);

    // Animar aparición
    setTimeout(() => {
      errorElement.style.opacity = "1";
    }, 10);

    // Remover después de 2 segundos
    setTimeout(() => {
      if (errorElement.parentElement) {
        errorElement.style.opacity = "0";
        setTimeout(() => {
          if (errorElement.parentElement) {
            errorElement.remove();
          }
        }, 300);
      }
    }, 2000);

    // Hacer que el input destelle para indicar el error
    chatbotInput.style.borderColor = "#ff4444";
    chatbotInput.style.boxShadow = "0 0 5px rgba(255, 68, 68, 0.5)";

    setTimeout(() => {
      chatbotInput.style.borderColor = "";
      chatbotInput.style.boxShadow = "";
    }, 1500);
  }

  // Comandos especiales del teclado
  chatbotInput.addEventListener("keydown", (e) => {
    // Ctrl + L para limpiar chat
    if (e.ctrlKey && e.key === 'l') {
      e.preventDefault();
      limpiarChat();
    }
  });

  // Limpiar chat
  function limpiarChat() {
    chatbotMessages.innerHTML = "";
    mostrarMensajesBienvenida();
  }

  // Auto-resize del input
  chatbotInput.addEventListener("input", function() {
    this.style.height = "auto";
    this.style.height = Math.min(this.scrollHeight, 100) + "px";
  });

  // Detectar inactividad para mostrar sugerencias
  let tiempoInactividad;
  const TIEMPO_SUGERENCIA = 30000; // 30 segundos

  function reiniciarTiempoInactividad() {
    clearTimeout(tiempoInactividad);
    if (chatAbierto) {
      tiempoInactividad = setTimeout(() => {
        if (chatbotMessages.children.length > 3) { // Solo si ya hay conversación
          agregarMensaje("bot", "💡 ¿Necesitas ayuda con algo más? Puedes preguntarme sobre intercambios, productos o tu cuenta.");
        }
      }, TIEMPO_SUGERENCIA);
    }
  }

  // Reiniciar timer en actividad
  ['click', 'keypress', 'scroll'].forEach(evento => {
    chatbotContainer.addEventListener(evento, reiniciarTiempoInactividad);
  });

  chatbotIcon.addEventListener("click", reiniciarTiempoInactividad);
});
