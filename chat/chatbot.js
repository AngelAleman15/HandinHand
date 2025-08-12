document.addEventListener("DOMContentLoaded", function () {
  const chatbotContainer = document.getElementById("chatbot-container");
  const closeBtn = document.getElementById("close-btn");
  const sendBtn = document.getElementById("send-btn");
  const chatbotInput = document.getElementById("chatbot-input");
  const chatbotMessages = document.getElementById("chatbot-messages");

  const chatbotIcon = document.getElementById("chatbot-icon");
  const closeButton = document.getElementById("close-btn");

  // activar o desactivar la visibilidad del chatbot al hacer clic en el icono
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
    const apiKey = "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";
    const modelId = "mistralai/Mistral-7B-Instruct-v0.3"; 
    const apiUrl = `https://api-inference.huggingface.co/models/${modelId}`;

    try {
        const response = await fetch(apiUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${apiKey}`,
            },
            body: JSON.stringify({
                inputs: `Usuario: ${userMessage}\nBot:`,
                parameters: {
                    max_new_tokens: 200,
                    temperature: 0.7
                }
            }),
        });

        const data = await response.json();
        console.log("Respuesta API:", data);

        let botMessage = "Lo siento, no entendí la respuesta.";
        if (Array.isArray(data) && data.length > 0 && data[0].generated_text) {
            botMessage = data[0].generated_text.replace(`Usuario: ${userMessage}\nBot:`, "").trim();
        }
        appendMessage("bot", botMessage);

    } catch (error) {
        console.error("Error al obtener la respuesta del bot:", error);
        appendMessage("bot", "Lo siento, algo salió mal. Inténtalo de nuevo.");
    }
}
});
