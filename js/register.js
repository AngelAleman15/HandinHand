document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("btn-registrar");

    if (btn) {
    btn.addEventListener("click", () => {
        const name = document.getElementById("name").value.trim();
        const surname = document.getElementById("surname").value.trim();
        const password = document.getElementById("password").value.trim();
        const email = document.getElementById("email").value.trim();
        const phone = document.getElementById("phone").value.trim();

        if (name && surname && password && email && phone) {
            const user = { name, surname, password, email, phone };
            localStorage.setItem("registeredUser", JSON.stringify(user));
            alert("Usuario registrado con éxito");
        } else {
            alert("Por favor, agregá toda la información.");
        }
    });
}

    document.getElementById("name").value = "";
    document.getElementById("surname").value = "";
    document.getElementById("email").value = "";
    document.getElementById("password").value = "";
    document.getElementById("phone").value = "";
});