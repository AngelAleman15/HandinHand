function register() {
    const user = {
        name: document.getElementById("name").value,
        surname: document.getElementById("surname").value,
        password: document.getElementById("password").value,
        email: document.getElementById("email").value,
        phone: document.getElementById("phone").value
    
    };

    localStorage.setItem("registeredUser", JSON.stringify(user));
    alert('Usuario registrado con exito');
    return user;

}

 document.addEventListener("DOMContentLoaded", () => {
        const btn = document.getElementById("btn-registrar");
        btn.addEventListener("click", register);
    });