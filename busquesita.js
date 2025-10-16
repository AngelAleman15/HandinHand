document.addEventListener("DOMContentLoaded", function() {

  const busquedaContainer = document.getElementById("search-container");
  const busquedaIcon = document.getElementById("search-icon");
  const close = document.getElementById("closeBtn");
  const search = document.getElementById("search");
  const searchInput = document.getElementById("search-input");
});

const resultsContainer = document.getElementById("results-container");

let SearchOpen = false;

busquedaIcon.addEventListener("click", () => {
    busquedaContainer.classList.remove("hidden");
    busquedaIcon.style.display = "none";
    searchOpen = true;
});

searchInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        enviarMensaje();
    }

});
async function enviarMensaje() {

    const mensajeUsuario = searchInput.value.trim();
