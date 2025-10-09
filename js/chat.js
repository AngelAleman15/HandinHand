const UserInput = document.getElementById('userinput');

UserInput.addEventListener('keypress', (e) => {
if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    Sendmessage();
}
});
  async function SendMessage() {
    const mensajeUsuario = UserInput.value;
  }
