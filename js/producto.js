// Carrusel de imágenes de producto
document.addEventListener('DOMContentLoaded', function() {
    const carruselItems = document.querySelectorAll('.carrusel-item');
    const btnPrev = document.querySelector('.carrusel-btn.prev');
    const btnNext = document.querySelector('.carrusel-btn.next');
    let carruselIndex = 0;

    function mostrarImagen(index) {
        carruselItems.forEach((item, i) => {
            item.classList.toggle('activo', i === index);
        });
    }

    function siguienteImagen() {
        carruselIndex = (carruselIndex + 1) % carruselItems.length;
        mostrarImagen(carruselIndex);
    }

    function anteriorImagen() {
        carruselIndex = (carruselIndex - 1 + carruselItems.length) % carruselItems.length;
        mostrarImagen(carruselIndex);
    }

    if (btnPrev && btnNext && carruselItems.length > 0) {
        btnPrev.addEventListener('click', anteriorImagen);
        btnNext.addEventListener('click', siguienteImagen);
        mostrarImagen(carruselIndex);
    }

    // Favorito toggle
    const btnFavorito = document.querySelector('.btn-favorito');
    if (btnFavorito) {
        btnFavorito.addEventListener('click', function() {
            btnFavorito.classList.toggle('activo');
            // Aquí puedes añadir lógica para guardar el favorito en backend
        });
    }

    // Compartir producto
    const btnCompartir = document.querySelector('.btn-compartir');
    if (btnCompartir) {
        btnCompartir.addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: document.querySelector('.producto-nombre').textContent,
                    url: window.location.href
                });
            } else {
                alert('La función de compartir no está disponible en este navegador.');
            }
        });
    }

    // Contactar vendedor
    const btnContactar = document.querySelector('.btn-contactar');
    if (btnContactar) {
        btnContactar.addEventListener('click', function() {
            // Aquí puedes añadir lógica para contactar al vendedor
            alert('Función de contactar al vendedor aún no implementada.');
        });
    }

    // Denunciar producto
    const btnDenunciar = document.querySelector('.btn-denunciar');
    if (btnDenunciar) {
        btnDenunciar.addEventListener('click', function() {
            // Aquí puedes añadir lógica para denunciar el producto
            alert('Función de denuncia aún no implementada.');
        });
    }

    // Abrir mapa del punto de encuentro
    const btnMapa = document.querySelector('.btn-mapa');
    if (btnMapa) {
        // El botón ya tiene el onclick en PHP, pero si quieres usar JS:
        // btnMapa.addEventListener('click', function() {
        //     window.open(btnMapa.getAttribute('data-map-url'), '_blank');
        // });
    }
});
