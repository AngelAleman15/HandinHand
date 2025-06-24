// Login

// Array de usuarios válidos
const users = [
    { username: "Angel", password: "12345" }
];

// Lógica de login
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    // Crear elementos para mostrar errores si no existen
    let errorMsg = document.createElement('div');
    errorMsg.id = "login-error";
    errorMsg.style.color = "#d32f2f";
    errorMsg.style.fontSize = "16px";
    errorMsg.style.marginTop = "8px";
    errorMsg.style.fontWeight = "500";
    errorMsg.style.textAlign = "center";
    // Insertar el mensaje de error después del botón
    const submitBtn = loginForm.querySelector('button[type="submit"]');
    submitBtn.insertAdjacentElement('afterend', errorMsg);

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        errorMsg.textContent = ""; // Limpiar mensaje anterior

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();

        if (!username || !password) {
            errorMsg.textContent = "Por favor, completa todos los campos.";
            return;
        }

        const userFound = users.find(user => user.username === username && user.password === password);

        if (userFound) {
            errorMsg.textContent = "";
            window.location.href = "index.html";
        } else {
            errorMsg.textContent = "Usuario o contraseña incorrectos.";
        }
    });
}
