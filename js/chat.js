// Obtener los elementos del DOM
const input = document.getElementById('InsertInput');
const userMessage = document.getElementById('UserMessage');
const contactMessage = document.getElementById('ContactMessage');
const contactName = document.getElementById('ContactName');
const chatContainer = document.getElementById('ChatContainer');

function sendMessage() {
    const messageText = input.value;
    if (messageText.trim() !== '') {
        const userMsgElement = document.createElement('div');
        userMsgElement.className = '';
        userMsgElement.textContent = messageText;
        chatContainer.appendChild(userMsgElement);

        input.value = '';

        // Respuesta simulada
        setTimeout(() => {
            const responseText = `Hola, ${contactName.textContent}! Este es un mensaje de prueba.`;
            const contactMsgElement = document.createElement('div');
            contactMsgElement.className = '';
            contactMsgElement.textContent = responseText;
            chatContainer.appendChild(contactMsgElement);
        }, 1000);
    }
}

input.addEventListener('keypress', (event) => {
    if (event.key === 'Enter') {
        sendMessage();
    }
});
