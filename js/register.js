document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("btn-registrar");

    if (btn) {
        btn.addEventListener("click", () => {
            const user = {
                name: document.getElementById("name").value,
                surname: document.getElementById("surname").value,
                password: document.getElementById("password").value,
                email: document.getElementById("email").value,
                phone: document.getElementById("phone").value
            };

            localStorage.setItem("registeredUser", JSON.stringify(user));
            alert("Usuario registrado con éxito ");
        });
    }
    document.getElementById("name").value = "";
    document.getElementById("surname").value = "";
    document.getElementById("email").value = "";
    document.getElementById("password").value = "";
    document.getElementById("phone").value = "";
});