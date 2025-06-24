document.addEventListener("DOMContentLoaded", () => {
    const registerForm = document.getElementById("registerForm");
    const errorMsg = document.getElementById("register-error");

    if (registerForm) {
        registerForm.addEventListener("submit", (e) => {
            e.preventDefault();
            errorMsg.textContent = "";

            const name = document.getElementById("name").value.trim();
            const surname = document.getElementById("surname").value.trim();
            const password = document.getElementById("password").value.trim();
            const email = document.getElementById("email").value.trim();
            const phone = document.getElementById("phone").value.trim();
            const birthdate = document.getElementById("birthdate").value;

            if (!name || !surname || !password || !email || !phone || !birthdate) {
                errorMsg.textContent = "Por favor, completa todos los campos.";
                return;
            }

            // Validar mayoría de edad
            const birth = new Date(birthdate);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            if (age < 18) {
                errorMsg.textContent = "Debes ser mayor de edad para registrarte.";
                return;
            }

            // Guardar usuario usando el email como username
            const user = { username: email, password, name, surname, phone, birthdate };
            localStorage.setItem("registeredUser", JSON.stringify(user));
            errorMsg.textContent = "";
            // Redirigir a login
            window.location.href = "login.html";
        });
    }
});