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

<div class="main-wrapper">
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
                            <div class="profile">
                                <?php if (!empty($producto['avatar_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($producto['avatar_path']); ?>"
                                         alt="Avatar de <?php echo htmlspecialchars($producto['vendedor_name']); ?>"
                                         style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;"
                                         onerror="this.style.display='none'; this.parentElement.style.backgroundColor='#C9F89B';">
                                <?php endif; ?>
                            </div>
                            <div class="usercontainer">
                                <div class="name"><?php echo htmlspecialchars($producto['vendedor_name']); ?></div>
                                <div class="stars">
                                    <?php echo generateStars($producto['promedio_estrellas']); ?>
                                </div>
                            </div>
                        </div>
                        <?php if (isLoggedIn() && $_SESSION['user_id'] == $producto['user_id']): ?>
                            <!-- Botón para productos propios -->
                            <div class="owner-actions">
                                <button class="btn-edit" onclick="showWipMessage('Editar producto')" title="Editar producto (En desarrollo)">
                                    <i class="fas fa-edit"></i> Editar <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- Botón contactar para productos de otros usuarios -->
                            <button class="btncontact" onclick="contactarVendedor(<?php echo $producto['id']; ?>)">Contactar</button>
                        <?php endif; ?>
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

    function showWipMessage(feature) {
        Swal.fire({
            icon: 'info',
            title: '🚧 Función en desarrollo',
            text: `La función "${feature}" está siendo desarrollada. Pronto estará disponible.`,
            confirmButtonColor: '#6a994e',
            confirmButtonText: 'Entendido'
        });
    }

    function contactarVendedor(productoId) {
        <?php if (isLoggedIn()): ?>
            // Si está logueado, redirigir a página de mensajes
            window.location.href = 'mensajeria.php?producto=' + productoId;
        <?php else: ?>
            // Si no está logueado, redirigir a login
            alert('Debes iniciar sesión para contactar al vendedor');
            window.location.href = 'iniciarsesion.php';
        <?php endif; ?>
    }

    // Funciones para gestionar productos propios
    function editProduct(productoId) {
        // Redirigir a página de edición de producto
        window.location.href = 'editar-producto.php?id=' + productoId;
    }

    function deleteProduct(productoId) {
        Swal.fire({
            title: '⚠️ ¿Eliminar Producto?',
            text: 'Esta acción no se puede deshacer. El producto será eliminado permanentemente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Eliminando producto...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Realizar petición AJAX para eliminar el producto
                fetch('api/productos.php?id=' + productoId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: 'El producto ha sido eliminado exitosamente',
                            icon: 'success',
                            confirmButtonColor: '#6a994e'
                        }).then(() => {
                            location.reload(); // Recargar página para actualizar la vista
                        });
                    } else {
                        Swal.fire({
                            title: 'Error al eliminar',
                            text: data.message || 'Error desconocido',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error de conexión',
                        text: 'No se pudo comunicar con el servidor',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                });
            }
        });
    }
    </script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
