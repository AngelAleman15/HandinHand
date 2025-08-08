const respuestas = {
hola_bien: "¡Hola! ¿Cómo estás?",
hola_mal: "Ola komo tas?",

adios_bien: "¡Hasta luego!",
adios_mal: "Asta luegoh!",

"¿te puedo preguntar un precio?_bien": "Nuestros precios cambian según el producto.",
"¿te puedo preguntar un precio?_mal": "Nuetros preios canbian segun el producto.",

"¿cuál es mi usuario?_bien": "Tú lo creaste, revisa tus datos.",
"¿cuál es mi usuario?_mal": "Tu lo crest, reviza tus dato.",

"¿cuál es mi nombre?_bien": "No tienes uno, pero puedo inventarte uno si quieres.",
"¿cuál es mi nombre?_mal": "No tenes un, pero pueo invetarte uno si qieres.",

"¿cuál es mi contraseña?_bien": "Deberías tenerla anotada, no la conozco.",
"¿cuál es mi contraseña?_mal": "Deverias tenela anota, no la conoso.",

"¿qué categorías tienes?_bien": "No hay categorías disponibles actualmente",
"¿qué categorías tienes?_mal": "No ay categorias disonibles actulmente",

"¿cuáles son las normas?_bien": "No están disponibles actualmente.",
"¿cuáles son las normas?_mal": "No estan disponibes actulmente.",

"¿qué artículos se venden más?_bien": "Esa información aún no está disponible.",
"¿qué artículos se venden más?_mal": "Esa imformasion aun no esta disonible.",

"¿cómo sabes mis gustos?_bien": "Tengo un sistema mágico de reconocimiento de datos que me susurra tus preferencias.",
"¿cómo sabes mis gustos?_mal": "Teno un sistma majico de reconosiminto de dato que me susura tus preferensias."

};

function sendMessage(){
  const input = document.getElementById('input');
  const texto = input.value.toLowerCase();
  document.getElementById('messages').innerHTML += `<p><b>Tú:</b> ${texto}</p>`;
  document.getElementById('messages').innerHTML += `<p><b>Bot:</b> ${respuestas[texto] || "No entendí eso."}</p>`;
  input.value = "";
}