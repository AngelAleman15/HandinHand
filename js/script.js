const menuToggle = document.getElementById("menu-toggle");
const dropdownMenu = document.getElementById("dropdown-menu");

menuToggle.addEventListener("click", (e) => {
    e.stopPropagation();
    menuToggle.classList.toggle("active");
    dropdownMenu.classList.toggle("show");
});

document.addEventListener("click", (e) => {
    if (!menuToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
        menuToggle.classList.remove("active");
        dropdownMenu.classList.remove("show");
    }
});