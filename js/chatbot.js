document.addEventListener("DOMContentLoaded", function () {
  const chatbotContainer = document.getElementById("chatbot-container");
  const chatbotIcon = document.getElementById("chatbot-icon");
  const closeBtn = document.getElementById("close-btn");
  const sendBtn = document.getElementById("send-btn");
  const chatbotInput = document.getElementById("chatbot-input");
  const chatbotMessages = document.getElementById("chatbot-messages");

  const respuestas = {
    "hola": "¡Hola! ¿Cómo estás?",
    "¿Cómo puedo iniciar sesión?": "Para iniciar sesión, haz clic en 'Iniciar sesión' en la esquina superior derecha e ingresa tu usuario y contraseña.",
    "precio": "Nuestros precios varían según el producto. Puedes verlos en la sección 'Productos'.",
    "adios": "¡Hasta luego!",
    "gracias": "¡De nada! 😊",
    "horarios": "Nuestro horario es de lunes a viernes de 9:00 a 18:00.",
    "contacto": "Puedes contactarnos al correo XXXXXXXXXXXXXX o al WhatsApp +598 XXX XXX"
  };

  // Función para que no afecten los tildes o mayusculas
  function normalizarTexto(texto) {
    return texto
      .toLowerCase()
      .normalize("NFD") // separa acentos
      .replace(/[\u0300-\u036f]/g, "") // quita acentos
      .replace(/[¿?]/g, "") // quita signos de pregunta
      .trim();
  }

  // Mostrar el chatbot
  chatbotIcon.addEventListener("click", () => {
    chatbotContainer.classList.remove("hidden");
    chatbotIcon.style.display = "none";
  });

  // Cerrar chatbot
  closeBtn.addEventListener("click", () => {
    chatbotContainer.classList.add("hidden");
    chatbotIcon.style.display = "flex";
  });

  // Enviar mensaje
  sendBtn.addEventListener("click", sendMessage);
  chatbotInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendMessage();
  });

  function sendMessage() {
    const userMessage = chatbotInput.value.trim();
    if (userMessage) {
      appendMessage("user", userMessage);
      chatbotInput.value = "";
      
      // Mostrar indicador de escritura
      appendMessage("bot", "Escribiendo...", true);
      
      // Llamar a la API del chatbot
      fetch('api/chatbot.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          mensaje: userMessage
        })
      })
      .then(response => response.json())
      .then(data => {
        // Remover el indicador de escritura
        removeTypingIndicator();
        
        if (data.success) {
          appendMessage("bot", data.data.respuesta);
        } else {
          appendMessage("bot", "Lo siento, ocurrió un error. Intenta de nuevo.");
        }
      })
      .catch(error => {
        console.error('Error en el chatbot:', error);
        removeTypingIndicator();
        appendMessage("bot", "Lo siento, no puedo responder en este momento. Intenta más tarde.");
      });
    }
  }

  function appendMessage(sender, message, isTyping = false) {
    const messageElement = document.createElement("div");
    messageElement.classList.add("message", sender);
    
    if (isTyping) {
      messageElement.classList.add("typing");
      messageElement.innerHTML = `
        <div class="typing-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
      `;
    } else {
      messageElement.textContent = message;
    }
    
    chatbotMessages.appendChild(messageElement);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
  }

  function removeTypingIndicator() {
    const typingMessage = chatbotMessages.querySelector('.typing');
    if (typingMessage) {
      typingMessage.remove();
    }
  }
});
