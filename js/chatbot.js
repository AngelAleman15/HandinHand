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

    // Mostrar mensaje de bienvenida solo la primera vez
    if (chatbotMessages.children.length === 0) {
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

  // Mostrar mensajes de bienvenida
  function mostrarMensajesBienvenida() {
    mensajesBienvenida.forEach((mensaje, index) => {
      setTimeout(() => {
        agregarMensaje("bot", mensaje, false);
      }, index * 1000);
    });
  }

  // Función principal para enviar mensajes
  function enviarMensaje() {
    const mensajeUsuario = chatbotInput.value.trim();
    
    if (!mensajeUsuario) {
      mostrarError("Por favor escribe un mensaje");
      return;
    }

    // Agregar mensaje del usuario
    agregarMensaje("user", mensajeUsuario);
    chatbotInput.value = "";

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
  function agregarMensaje(remitente, mensaje, animado = true) {
    const elementoMensaje = document.createElement("div");
    elementoMensaje.classList.add("message", remitente);
    
    if (remitente === "bot") {
      elementoMensaje.innerHTML = `
        <div class="bot-avatar">P</div>
        <div class="message-content">${formatearMensaje(mensaje)}</div>
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

  // Formatear mensaje para mostrar saltos de línea
  function formatearMensaje(mensaje) {
    return mensaje.replace(/\n/g, '<br>');
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
    const errorElement = document.createElement("div");
    errorElement.classList.add("error-message");
    errorElement.textContent = mensaje;
    errorElement.style.cssText = `
      position: absolute;
      top: -30px;
      left: 50%;
      transform: translateX(-50%);
      background: #ff4444;
      color: white;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 12px;
      z-index: 1000;
    `;
    
    chatbotInput.parentElement.style.position = "relative";
    chatbotInput.parentElement.appendChild(errorElement);
    
    setTimeout(() => {
      errorElement.remove();
    }, 2000);
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
