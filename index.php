<?php
session_start();

// Configuración de la página
$page_title = "HandinHand - Inicio";
$body_class = "body-index";

// Incluir funciones
require_once 'includes/functions.php';

// Verificar si se cerró sesión
$logout_success = isset($_GET['logout']) && $_GET['logout'] === 'success';

// Obtener productos de la base de datos
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : null;
$productos = getProductos(20, $busqueda); // Limitar a 20 productos

// Incluir header
include 'includes/header.php';
?>

    <div>
        <div class="navbar-container">
            <div class="quote"><p>"Reutilizá, Intercambiá, Conectá"</p></div>
            <div class="navbar">
                <form method="GET" action="index.php" style="display: flex; align-items: center;">
                    <input type="text" name="busqueda" placeholder="¿Qué te interesa?" class="inputnav" value="<?php echo htmlspecialchars($busqueda ?: ''); ?>">
                    <button class="btnnav" type="submit">Buscar</button>
                </form>
            </div>
        </div>
        <div class="cardscontainer">
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $producto): ?>
                <div class="card">
                    <div class="cardcontent">
                        <div class="cardimg"><img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>"></div>
                        <div class="cardtitle"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                        <div class="carddescription"><?php echo htmlspecialchars($producto['descripcion']); ?></div>
                    </div>
                    <div class="cardfooter">
                        <div class="sellerinfo">
                            <div class="profile"></div>
                            <div class="usercontainer">
                                <div class="name"><?php echo htmlspecialchars($producto['vendedor_name']); ?></div>
                                <div class="stars">
                                    <?php echo generateStars($producto['promedio_estrellas']); ?>
                                </div>
                            </div>
                        </div>
                        <button class="btncontact" onclick="contactarVendedor(<?php echo $producto['id']; ?>)">Contactar</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                    <h3>No se encontraron productos</h3>
                    <?php if ($busqueda): ?>
                        <p>No hay productos que coincidan con "<?php echo htmlspecialchars($busqueda); ?>"</p>
                        <a href="index.php" style="color: #6a994e;">Ver todos los productos</a>
                    <?php else: ?>
                        <p>Aún no hay productos disponibles</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    <?php if ($logout_success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Sesión cerrada',
            text: 'Has cerrado sesión exitosamente',
            confirmButtonColor: '#6a994e',
            timer: 3000,
            timerProgressBar: true
        });
    <?php endif; ?>
    
    function contactarVendedor(productoId) {
        <?php if (isLoggedIn()): ?>
            // Si está logueado, redirigir a página de mensajes
            window.location.href = 'mensajes.php?producto=' + productoId;
        <?php else: ?>
            // Si no está logueado, redirigir a login
            alert('Debes iniciar sesión para contactar al vendedor');
            window.location.href = 'iniciarsesion.php';
        <?php endif; ?>
    }
    </script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
