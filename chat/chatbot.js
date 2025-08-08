const respuestas = {
  hola: "¡Hola! ¿Cómo estás?",
  precio: "Nuestros precios varían según el producto.",
  adios: "¡Hasta luego!"
};

function sendMessage(){
  const input = document.getElementById('input');
  const texto = input.value.toLowerCase();
  document.getElementById('messages').innerHTML += `<p><b>Tú:</b> ${texto}</p>`;
  document.getElementById('messages').innerHTML += `<p><b>Bot:</b> ${respuestas[texto] || "No entendí eso."}</p>`;
  input.value = "";
}