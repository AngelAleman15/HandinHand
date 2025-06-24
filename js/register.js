// Este script maneja el registro de nuevos usuarios en la página de registro.

document.addEventListener("DOMContentLoaded", () => {
    // Obtenemos el formulario de registro y el div donde mostraremos errores
    const registerForm = document.getElementById("registerForm");
    const errorMsg = document.getElementById("register-error");

    // Si existe el formulario en la página
    if (registerForm) {
        // Cuando el usuario envía el formulario (hace click en "Registrar")
        registerForm.addEventListener("submit", (e) => {
            e.preventDefault(); // Evita que la página se recargue
            errorMsg.textContent = ""; // Limpia mensajes de error anteriores

            // Tomamos los valores que el usuario escribió en los campos del formulario
            const name = document.getElementById("name").value.trim();
            const surname = document.getElementById("surname").value.trim();
            const password = document.getElementById("password").value.trim();
            const email = document.getElementById("email").value.trim();
            const phone = document.getElementById("phone").value.trim();
            const birthdate = document.getElementById("birthdate").value;

            // Verificamos que todos los campos estén completos
            if (!name || !surname || !password || !email || !phone || !birthdate) {
                errorMsg.textContent = "Por favor, completa todos los campos.";
                return;
            }

            // Calculamos la edad del usuario a partir de la fecha de nacimiento
            const birth = new Date(birthdate);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            // Si es menor de 18, mostramos error
            if (age < 18) {
                errorMsg.textContent = "Debes ser mayor de edad para registrarte.";
                return;
            }

            // Obtenemos la lista de usuarios ya registrados (si no hay, usamos un array vacío)
            let users = JSON.parse(localStorage.getItem("registeredUsers")) || [];
            // Verificamos que el correo no esté ya registrado
            if (users.some(u => u.username === email)) {
                errorMsg.textContent = "El correo ya está registrado.";
                return;
            }

            // Creamos un objeto usuario con los datos ingresados
            // Guardamos el email como 'username' para identificar al usuario
            const user = { username: email, password, name, surname, phone, birthdate };
            users.push(user); // Agregamos el nuevo usuario al array

            // Guardamos el array actualizado en el almacenamiento local del navegador
            localStorage.setItem("registeredUsers", JSON.stringify(users));
            errorMsg.textContent = "";

            // Redirigimos al usuario a la página de login
            window.location.href = "login.html";
        });
    }
});