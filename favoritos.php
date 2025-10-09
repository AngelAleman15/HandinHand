<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: iniciarsesion.php');
    exit();
}

$page_title = "Mis Favoritos - HandinHand";
require_once 'includes/header.php';
?>

<div class="main-wrapper">
    <h1 class="page-title">Mis Productos Favoritos</h1>
    
    <div class="cardscontainer" id="favoritos-container">
        <!-- Los productos favoritos se cargarán aquí dinámicamente -->
        <div class="loading">Cargando productos favoritos...</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cargar productos favoritos
    fetch('api/favoritos.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('favoritos-container');
            
            if (data.success && data.productos.length > 0) {
                container.innerHTML = data.productos.map(producto => `
                    <div class="card" onclick="window.location.href='producto.php?id=${producto.id}'">
                        <div class="cardcontent">
                            <div class="cardimg">
                                <img src="${producto.imagen}" alt="${producto.nombre}">
                            </div>
                            <div class="cardtitle">${producto.nombre}</div>
                            <div class="carddescription">${producto.descripcion}</div>
                        </div>
                        <div class="cardfooter">
                            <div class="sellerinfo">
                                <div class="profile">
                                    ${producto.avatar_url ? 
                                        `<img src="${producto.avatar_url}" alt="Avatar de ${producto.vendedor_name}"
                                             style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">` :
                                        ''}
                                </div>
                                <div class="usercontainer">
                                    <div class="name">${producto.vendedor_name}</div>
                                    <div class="stars">
                                        ${generateStars(producto.promedio_estrellas)}
                                    </div>
                                </div>
                            </div>
                            <button class="btncontact" onclick="event.stopPropagation(); contactarVendedor(${producto.id})">
                                Contactar
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="no-favorites">
                        <h3>No tienes productos guardados en favoritos</h3>
                        <p>¡Explora productos y guárdalos para verlos más tarde!</p>
                        <a href="index.php" class="btn-primary">Ver productos</a>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('favoritos-container').innerHTML = `
                <div class="error-message">
                    <h3>Error al cargar los favoritos</h3>
                    <p>Por favor, intenta de nuevo más tarde.</p>
                </div>
            `;
        });

    // Función para generar estrellas
    function generateStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        
        return `
            ${'<img src="img/starfilled.png" alt="star">'.repeat(fullStars)}
            ${hasHalfStar ? '<img src="img/starhalf.png" alt="star">' : ''}
            ${'<img src="img/star.png" alt="star">'.repeat(emptyStars)}
        `;
    }
});

function contactarVendedor(productoId) {
    window.location.href = 'mensajeria.php?producto=' + productoId;
}
</script>

<style>
.page-title {
    text-align: center;
    margin: 30px 0;
    color: #333;
}

.loading {
    text-align: center;
    padding: 50px;
    color: #666;
    grid-column: 1 / -1;
}

.no-favorites {
    grid-column: 1 / -1;
    text-align: center;
    padding: 50px;
    color: #666;
}

.no-favorites h3 {
    margin-bottom: 15px;
    color: #333;
}

.no-favorites .btn-primary {
    display: inline-block;
    background-color: #6a994e;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    margin-top: 20px;
    text-decoration: none;
}

.no-favorites .btn-primary:hover {
    background-color: #386641;
}

.error-message {
    grid-column: 1 / -1;
    text-align: center;
    padding: 50px;
    color: #dc3545;
}
</style>

<?php require_once 'includes/footer.php'; ?>