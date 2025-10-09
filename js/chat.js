const UserInput = document.getElementById('userinput');
const ContactMessage = document.getElementById('contactmessage');

UserInput.addEventListener('keypress', (e) => {
if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    Sendmessage();
}
});
  async function SendMessage() {
    const mensajeUsuario = UserInput.value.trim();
    if (mensajeUsuario === '') return;
  }
