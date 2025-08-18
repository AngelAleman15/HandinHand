// JavaScript solo para validación de interfaz y experiencia de usuario
// La validación real se hace en PHP con la base de datos

document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM cargado, inicializando validación...");
    
    const namefield = document.getElementById("name");
    const passwordfield = document.getElementById("password");
    const btnlogin = document.getElementById("login-button");
    const error = document.getElementById("error");

    const btnregister = document.getElementById("btn-register");
    const fullname = document.getElementById("fullname");
    const username = document.getElementById("username");
    const email = document.getElementById("email");
    const phone = document.getElementById("phone");
    const password = document.getElementById("password");
    const birthdate = document.getElementById("birthdate");

    console.log("Elementos encontrados:");
    console.log("- btnlogin:", btnlogin);
    console.log("- btnregister:", btnregister);
    console.log("- namefield:", namefield);
    console.log("- error:", error);

    if (btnregister){
        btnregister.addEventListener("click", registerValidate);
        console.log("Event listener agregado al botón registro");
    }

    if (btnlogin) {
        btnlogin.addEventListener("click", loginValidate);
        console.log("Event listener agregado al botón login");
    } else {
        console.log("Botón login no encontrado");
    }

    function registerValidate(event) {
        // Solo validación de interfaz - PHP maneja la lógica de base de datos
        const nombrecompleto = fullname ? fullname.value.trim() : "";
        const nombredeusuario = username ? username.value.trim() : "";
        const correo = email ? email.value.trim() : "";
        const telefono = phone ? phone.value.trim() : "";
        const contrasenia = password ? password.value.trim() : "";
        const fechaNacimiento = birthdate ? birthdate.value : "";
        
        // Limpiar errores visuales previos
        clearFieldErrors();
        
        let hasErrors = false;

        // Validar campos vacíos
        if (!nombrecompleto) {
            setFieldError(fullname, "Nombre completo requerido");
            hasErrors = true;
        }
        
        if (!nombredeusuario) {
            setFieldError(username, "Nombre de usuario requerido");
            hasErrors = true;
        }
        
        if (!correo) {
            setFieldError(email, "Email requerido");
            hasErrors = true;
        } else if (!validarEmail(correo)) {
            setFieldError(email, "Formato de email inválido");
            hasErrors = true;
        }
        
        if (!telefono) {
            setFieldError(phone, "Teléfono requerido");
            hasErrors = true;
        }
        
        if (!contrasenia) {
            setFieldError(password, "Contraseña requerida");
            hasErrors = true;
        } else if (contrasenia.length < 6) {
            setFieldError(password, "La contraseña debe tener al menos 6 caracteres");
            hasErrors = true;
        }
        
        if (!fechaNacimiento) {
            setFieldError(birthdate, "Fecha de nacimiento requerida");
            hasErrors = true;
        }

        // Si hay errores de interfaz, prevenir envío
        if (hasErrors) {
            event.preventDefault();
            showError("Por favor, corrija los errores marcados.");
            return false;
        }
        
        // Si pasa validación de interfaz, permitir que PHP procese
        return true;
    }

    function loginValidate(event) {
        console.log("loginValidate ejecutándose");
        
        // Solo validación de interfaz - PHP maneja la lógica de base de datos
        const nombre = namefield ? namefield.value.trim() : "";
        const contrasenia = passwordfield ? passwordfield.value.trim() : "";
        
        console.log("Nombre:", nombre, "Contraseña:", contrasenia ? "***" : "vacía");
        
        // Limpiar errores visuales previos
        clearFieldErrors();
        
        let hasErrors = false;

        if (!nombre) {
            setFieldError(namefield, "Nombre de usuario requerido");
            hasErrors = true;
            console.log("Error: nombre vacío");
        }
        
        if (!contrasenia) {
            setFieldError(passwordfield, "Contraseña requerida");
            hasErrors = true;
            console.log("Error: contraseña vacía");
        }

        // Si hay errores de interfaz, prevenir envío
        if (hasErrors) {
            event.preventDefault();
            showError("Por favor, complete todos los campos.");
            console.log("Formulario bloqueado por errores");
            return false;
        }
        
        console.log("Validación exitosa, enviando formulario");
        // Si pasa validación de interfaz, permitir que PHP procese
        return true;
    }

    function clearFieldErrors() {
        // Limpiar bordes rojos y errores
        const fields = [namefield, passwordfield, fullname, username, email, phone, password, birthdate];
        fields.forEach(field => {
            if (field) {
                field.style.border = "2px solid transparent";
                field.classList.remove("error"); // Remover clase de error
            }
        });
        
        if (error) {
            error.textContent = "p"; // Restaurar la "p" invisible cuando se limpia
            error.style.color = "transparent";
        }
    }

    function setFieldError(field, message) {
        console.log("Aplicando borde rojo a:", field);
        if (field) {
            field.classList.add("error"); // Agregar clase de error
            field.style.border = "2px solid red"; // También estilo inline como respaldo
            console.log("Clase 'error' agregada. Clases actuales:", field.className);
        } else {
            console.log("Campo no encontrado para aplicar borde rojo");
        }
    }

    function showError(message) {
        console.log("Mostrando error:", message);
        if (error) {
            error.textContent = message; // Sustituir completamente el contenido, sin "p"
            error.style.color = "red";
            console.log("Error aplicado al elemento:", error);
        } else {
            console.log("Elemento error no encontrado");
        }
    }

    function validarEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

}); // Cierre del DOMContentLoaded