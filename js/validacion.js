// Datos de usuarios registrados
const objetos = [
    { nombre: "Angel", contrasenia: "AA" },
    { nombre: "Alejo", contrasenia: "AB" },
    { nombre: "Milagros", contrasenia: "AC" }
];

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

if (btnregister){
    btnregister.addEventListener("click", registerValidate);
}

if (btnlogin) {
    btnlogin.addEventListener("click", loginValidate);
}

function registerValidate(event) {
    event.preventDefault();
    const nombrecompleto = fullname.value;
    const nombredeusuario = username.value;
    const correo = email.value;
    const telefono = phone.value;
    const contrasenia = password.value;
    const fechaNacimiento = birthdate.value;
    
    error.textContent = "";
    error.style.color = "transparent";
    fullname.style.border = "2px solid transparent";
    username.style.border = "2px solid transparent";
    email.style.border = "2px solid transparent";
    phone.style.border = "2px solid transparent";
    password.style.border = "2px solid transparent";
    birthdate.style.border = "2px solid transparent";

    if (validarRegistro(nombrecompleto, nombredeusuario, correo, telefono, contrasenia, fechaNacimiento)) {
        objetos.push({ nombrecompleto, nombredeusuario, correo, telefono, contrasenia, fechaNacimiento });
        Swal.fire({
            title: "Registro exitoso",
            text: "Te has registrado correctamente.",
            icon: "success",
            confirmButtonText: "Aceptar"
        }).then(() => {
            window.location.href = "iniciarsesion.html";
        });
    }else{
        return;
    }
}

function loginValidate(event) {
    event.preventDefault();
    const nombre = namefield.value;
    const contrasenia = passwordfield.value;
    error.textContent = "";
    error.style.color = "transparent";
    namefield.style.border = "2px solid transparent";
    passwordfield.style.border = "2px solid transparent";

    if (validarUsuario(nombre, contrasenia)) {
        window.location.href = "index.html";
    } else {
        return;
    }
}

function validarUsuario(nombre, contrasenia){
    if (nombre === "" || contrasenia === "") {
        error.textContent = "Por favor, complete todos los campos.";
        error.style.color = "red";
        if (nombre === "") {
            namefield.style.border = "2px solid red";
        }
        if (contrasenia === "") {
            passwordfield.style.border = "2px solid red";
        }
        return false;
    }
    const usuarioValido = objetos.some(objeto => objeto.nombre === nombre && objeto.contrasenia === contrasenia);
    if (!usuarioValido) {
        error.textContent = "Nombre de usuario o contraseña incorrectos.";
        error.style.color = "red";
        namefield.style.border = "2px solid red";
        passwordfield.style.border = "2px solid red";
        return false;
    }
    error.textContent = "";
    return true;
}

function validarRegistro(nombrecompleto, nombredeusuario, correo, telefono, contrasenia, fechaNacimiento) {
    if (nombrecompleto === "" || nombredeusuario === "" || correo === "" || telefono === "" || contrasenia === "" || fechaNacimiento === "") {
        error.textContent = "Por favor, complete todos los campos.";
        error.style.color = "red";
        if (nombrecompleto === "") {
            fullname.style.border = "2px solid red";
        }
        if (nombredeusuario === "") {
            username.style.border = "2px solid red";
        }
        if (correo === "") {
            email.style.border = "2px solid red";
        }
        if (telefono === "") {
            phone.style.border = "2px solid red";
        }
        if (contrasenia === "") {
            password.style.border = "2px solid red";
        }
        if (fechaNacimiento === "") {
            birthdate.style.border = "2px solid red";
        }
        return false;
    }
    if (correo !== "" && !validarEmail(correo)) {
    email.style.border = "2px solid red";
    error.textContent += "Por favor, utilice el formato email@ejemplo.com";
    error.style.color = "red";
    return false;
    }

    const usuarioExistente = objetos.some(objeto => objeto.nombre === nombredeusuario);
    if (usuarioExistente) {
        error.textContent = "El nombre de usuario ya está en uso.";
        error.style.color = "red";
        username.style.border = "2px solid red";
        return false;
    }
    error.textContent = "";
    return true;
}

function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}