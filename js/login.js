// Este script maneja el inicio de sesión de los usuarios en la página de login.

// Creamos un array con usuarios válidos por defecto (hardcodeados)
// Esto es solo para pruebas, normalmente los usuarios se registran y se guardan en localStorage
const users = [
    { username: "Angel", password: "12345" }
];

// Obtenemos el formulario de login del DOM
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    // Creamos un div para mostrar mensajes de error debajo del botón de login
    let errorMsg = document.createElement('div');
    errorMsg.id = "login-error";
    errorMsg.style.color = "#d32f2f";
    errorMsg.style.fontSize = "16px";
    errorMsg.style.marginTop = "8px";
    errorMsg.style.fontWeight = "500";
    errorMsg.style.textAlign = "center";
    // Insertamos el div de error después del botón de submit
    const submitBtn = loginForm.querySelector('button[type="submit"]');
    submitBtn.insertAdjacentElement('afterend', errorMsg);

    // Cuando el usuario envía el formulario de login
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Evita que la página se recargue
        errorMsg.textContent = ""; // Limpia mensajes de error anteriores

        // Obtenemos lo que el usuario escribió en los campos de usuario y contraseña
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();

        // Verificamos que ambos campos estén completos
        if (!username || !password) {
            errorMsg.textContent = "Por favor, completa todos los campos.";
            return;
        }

        // Buscamos si el usuario existe en el array de usuarios por defecto
        let userFound = users.find(user => user.username === username && user.password === password);

        // Si no lo encontramos, buscamos en los usuarios registrados en localStorage
        // localStorage es una memoria del navegador donde guardamos los usuarios registrados
        const registeredUsers = JSON.parse(localStorage.getItem("registeredUsers")) || [];
        if (!userFound && registeredUsers.length > 0) {
            // Comparamos el campo 'name' (nombre) y la contraseña
            userFound = registeredUsers.find(user => user.name === username && user.password === password);
        }

        // Si encontramos el usuario, redirigimos al index (inicio)
        if (userFound) {
            errorMsg.textContent = "";
            window.location.href = "index.html";
        } else {
            // Si no, mostramos mensaje de error
            errorMsg.textContent = "Usuario o contraseña incorrectos.";
        }
    });
}
