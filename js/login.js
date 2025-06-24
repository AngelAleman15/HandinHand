// Login

// Array de usuarios válidos por defecto (hardcodeados)
const users = [
    { username: "Angel", password: "12345" }
];

// Obtener el formulario de login del DOM
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    // Crear un elemento para mostrar mensajes de error si no existe
    let errorMsg = document.createElement('div');
    errorMsg.id = "login-error";
    errorMsg.style.color = "#d32f2f";
    errorMsg.style.fontSize = "16px";
    errorMsg.style.marginTop = "8px";
    errorMsg.style.fontWeight = "500";
    errorMsg.style.textAlign = "center";
    // Insertar el mensaje de error después del botón de submit
    const submitBtn = loginForm.querySelector('button[type="submit"]');
    submitBtn.insertAdjacentElement('afterend', errorMsg);

    // Evento al enviar el formulario
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Evita el envío tradicional del formulario
        errorMsg.textContent = ""; // Limpiar mensaje anterior

        // Obtener los valores ingresados por el usuario
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();

        // Validar que ambos campos estén completos
        if (!username || !password) {
            errorMsg.textContent = "Por favor, completa todos los campos.";
            return;
        }

        // Buscar usuario en el array de usuarios por defecto
        let userFound = users.find(user => user.username === username && user.password === password);

        // Buscar usuario registrado en localStorage
        // localStorage es una memoria local del navegador donde se pueden guardar datos en formato clave-valor.
        // Aquí se guarda el usuario registrado desde el formulario de registro.
        const registeredUser = localStorage.getItem("registeredUser");
        if (!userFound && registeredUser) {
            const regUser = JSON.parse(registeredUser); // Convertir el string a objeto
            // Comprobar si el usuario y contraseña coinciden con los guardados en localStorage
            if (regUser.username === username && regUser.password === password) {
                userFound = regUser;
            }
        }

        // Si se encontró el usuario, redirige al index
        if (userFound) {
            errorMsg.textContent = "";
            window.location.href = "index.html";
        } else {
            // Si no, muestra mensaje de error
            errorMsg.textContent = "Usuario o contraseña incorrectos.";
        }
    });
}
