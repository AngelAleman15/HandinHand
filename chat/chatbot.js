document.addEventListener("DOMContentLoaded", function () {
  const chatbotContainer = document.getElementById("chatbot-container");
  const closeBtn = document.getElementById("close-btn");
  const sendBtn = document.getElementById("send-btn");
  const chatbotInput = document.getElementById("chatbot-input");
  const chatbotMessages = document.getElementById("chatbot-messages");

  const chatbotIcon = document.getElementById("chatbot-icon");
  const closeButton = document.getElementById("close-btn");

// activar o desactivar la visibilidad del chatbot al hacer clic en el icono
// mostrar el chatbot al hacer clic en el icono
  chatbotIcon.addEventListener("click", function () {
    chatbotContainer.classList.remove("hidden");
    chatbotIcon.style.display = "none"; // esconder el icono del chatbot
  });

  // tambien activa o desactiva cuando se clickea el boton de cerrar
  closeButton.addEventListener("click", function () {
    chatbotContainer.classList.add("hidden");
    chatbotIcon.style.display = "flex"; // muestra de nuevo 
  });

  sendBtn.addEventListener("click", sendMessage);
  chatbotInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      sendMessage();
    }
  });

  function sendMessage() {
    const userMessage = chatbotInput.value.trim();
    if (userMessage) {
      appendMessage("user", userMessage);
      chatbotInput.value = "";
      getBotResponse(userMessage);
    }
  }

  function appendMessage(sender, message) {
    const messageElement = document.createElement("div");
    messageElement.classList.add("message", sender);
    messageElement.textContent = message;
    chatbotMessages.appendChild(messageElement);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
  }

  async function getBotResponse(userMessage) {
    const apiKey = "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"; // Aqui usamos una APIKEY, pero yo estoy usando la mia. No, Angel, no me van a robar el codigo unos indios salidos de Arabia Saudi, tranquilo
    const apiUrl = "https://api.openai.com/v1/chat/completions";

    try {
      const response = await fetch(apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${apiKey}`,
        },
        body: JSON.stringify({
          model: "gpt-5-mini",
          messages: [{ role: "user", content: userMessage }],
          max_tokens: 150,
        }),
      });

      const data = await response.json();
      const botMessage = data.choices[0].message.content;
      appendMessage("bot", botMessage);
    } catch (error) {
      console.error("Error al obtener la respuesta del bot:", error);
      appendMessage("bot", "Lo siento, algo salió mal. Inténtalo de nuevo.");
    }
  }
});