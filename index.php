<?php
session_start();

// Incluir funciones
require_once 'includes/functions.php';

// Verificar si se cerró sesión
$logout_success = isset($_GET['logout']) && $_GET['logout'] === 'success';

// Obtener parámetros de búsqueda y filtros
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : null;
$tipo_busqueda = isset($_GET['tipo']) ? $_GET['tipo'] : 'productos'; // 'productos' o 'usuarios'
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : null;

// Configuración de la página
$page_title = "HandinHand - Inicio";
$body_class = "body-index " . ($tipo_busqueda === 'usuarios' ? 'tema-usuarios' : 'tema-productos');

// Obtener productos o usuarios según el tipo de búsqueda
if ($tipo_busqueda === 'usuarios') {
    $usuarios = buscarUsuarios($busqueda, 20);
    $productos = [];
} else {
    $productos = getProductosFiltrados(20, $busqueda, $filtro_categoria, $filtro_estado);
    $usuarios = [];
}

// Obtener productos recomendados (solo si no hay búsqueda activa)
$productos_recomendados = [];
if (!$busqueda && !$filtro_categoria && !$filtro_estado && $tipo_busqueda === 'productos') {
    $productos_recomendados = getProductosRecomendados(8);
}

// Incluir header
include 'includes/header.php';
?>

<div class="main-wrapper">
<script>
window.IS_LOGGED_IN = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
</script>
    <div>
        <div class="navbar-container">
            <div class="quote"><p>"Reutilizá, Intercambiá, Conectá"</p></div>
            <div class="navbar">
                <form method="GET" action="index.php" id="search-form">
                    <!-- Toggle Productos/Usuarios -->
                    <div class="search-toggle">
                        <button type="button" class="toggle-btn <?php echo $tipo_busqueda === 'productos' ? 'active' : ''; ?>" onclick="cambiarTipoBusqueda('productos')">
                            <i class="fas fa-box"></i> Productos
                        </button>
                        <button type="button" class="toggle-btn <?php echo $tipo_busqueda === 'usuarios' ? 'active' : ''; ?>" onclick="cambiarTipoBusqueda('usuarios')">
                            <i class="fas fa-users"></i> Usuarios
                        </button>
                    </div>
                    <input type="hidden" name="tipo" id="tipo-busqueda" value="<?php echo htmlspecialchars($tipo_busqueda); ?>">
                    
                    <!-- Barra de búsqueda -->
                    <div style="display: flex; align-items: center; gap: 10px; width: 100%;">
                        <input type="text" name="busqueda" placeholder="<?php echo $tipo_busqueda === 'usuarios' ? '¿A quién buscás?' : '¿Qué te interesa?'; ?>" class="inputnav" value="<?php echo htmlspecialchars($busqueda ?: ''); ?>">
                        
                        <!-- Botón de filtros (solo para productos) -->
                        <?php if ($tipo_busqueda === 'productos'): ?>
                        <button type="button" class="btn-filtros" onclick="toggleFiltros()">
                            <i class="fas fa-filter"></i> Filtros
                        </button>
                        <?php endif; ?>
                        
                        <button class="btnnav" type="submit">Buscar</button>
                    </div>
                    
                    <!-- Panel de filtros (solo para productos) -->
                    <?php if ($tipo_busqueda === 'productos'): ?>
                    <div class="filtros-panel" id="filtros-panel" style="display: <?php echo ($filtro_categoria || $filtro_estado) ? 'flex' : 'none'; ?>;">
                        <div class="filtro-grupo">
                            <label><i class="fas fa-tags"></i> Categoría:</label>
                            <select name="categoria" class="filtro-select">
                                <option value="">Todas</option>
                                <option value="Electrónicos" <?php echo $filtro_categoria === 'Electrónicos' ? 'selected' : ''; ?>>Electrónicos</option>
                                <option value="Ropa" <?php echo $filtro_categoria === 'Ropa' ? 'selected' : ''; ?>>Ropa</option>
                                <option value="Calzado" <?php echo $filtro_categoria === 'Calzado' ? 'selected' : ''; ?>>Calzado</option>
                                <option value="Libros" <?php echo $filtro_categoria === 'Libros' ? 'selected' : ''; ?>>Libros</option>
                                <option value="Deportes" <?php echo $filtro_categoria === 'Deportes' ? 'selected' : ''; ?>>Deportes</option>
                                <option value="Música" <?php echo $filtro_categoria === 'Música' ? 'selected' : ''; ?>>Música</option>
                                <option value="Hogar" <?php echo $filtro_categoria === 'Hogar' ? 'selected' : ''; ?>>Hogar</option>
                                <option value="Juguetes" <?php echo $filtro_categoria === 'Juguetes' ? 'selected' : ''; ?>>Juguetes</option>
                                <option value="Otros" <?php echo $filtro_categoria === 'Otros' ? 'selected' : ''; ?>>Otros</option>
                            </select>
                        </div>
                        <div class="filtro-grupo">
                            <label><i class="fas fa-check-circle"></i> Estado:</label>
                            <select name="estado" class="filtro-select">
                                <option value="">Todos</option>
                                <option value="disponible" <?php echo $filtro_estado === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                <option value="reservado" <?php echo $filtro_estado === 'reservado' ? 'selected' : ''; ?>>Reservado</option>
                            </select>
                        </div>
                        <button type="button" class="btn-limpiar-filtros" onclick="limpiarFiltros()">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Sección "Para Ti" (FYP) -->
        <?php if (!empty($productos_recomendados) && $tipo_busqueda === 'productos'): ?>
        <div class="fyp-section">
            <div class="fyp-header">
                <h2 class="fyp-title">
                    <i class="fas fa-star"></i> Para Ti
                </h2>
                <p class="fyp-subtitle">Recomendaciones personalizadas basadas en tus intereses</p>
            </div>
            
            <div class="fyp-carousel-wrapper">
                <!-- Botón anterior -->
                <button class="fyp-nav-btn prev" id="fyp-prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <div class="fyp-container" id="fyp-carousel">
                    <?php foreach ($productos_recomendados as $producto): ?>
                    <div class="card fyp-card" data-producto-id="<?php echo $producto['id']; ?>">
                        <a href="producto.php?id=<?php echo $producto['id']; ?>" style="text-decoration:none;color:inherit;display:block;">
                            <div class="cardimg">
                                <?php if ($producto['score_total'] > 20): ?>
                                    <div class="badge-trending">🔥 Trending</div>
                                <?php endif; ?>
                                <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                            </div>
                            <div class="card-body">
                                <div class="cardtitle"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                <div class="card-badges">
                                    <span class="badge-estado badge-<?php echo $producto['estado']; ?>">
                                        <?php echo ucfirst($producto['estado']); ?>
                                    </span>
                                    <?php if (!empty($producto['categoria'])): ?>
                                        <span class="badge-categoria">
                                            <?php echo htmlspecialchars($producto['categoria']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-seller">
                                    <div class="contact-avatar-small">
                                        <?php if (!empty($producto['avatar_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($producto['avatar_path']); ?>"
                                                 alt="Avatar de <?php echo htmlspecialchars($producto['vendedor_name']); ?>"
                                                 onerror="this.style.display='none'; this.parentElement.style.backgroundColor='#C9F89B';">
                                        <?php endif; ?>
                                    </div>
                                    <div class="seller-info">
                                        <div class="name"><?php echo htmlspecialchars($producto['vendedor_name']); ?></div>
                                        <div class="stars">
                                            <?php echo generateStars($producto['promedio_estrellas']); ?>
                                            <span class="rating-count">(<?php echo (int)$producto['total_valoraciones']; ?>)</span>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($producto['total_vistas'] > 0 || $producto['total_guardados'] > 0): ?>
                                <div class="card-stats">
                                    <?php if ($producto['total_vistas'] > 0): ?>
                                        <span><i class="fas fa-eye"></i> <?php echo $producto['total_vistas']; ?></span>
                                    <?php endif; ?>
                                    <?php if ($producto['total_guardados'] > 0): ?>
                                        <span><i class="fas fa-heart"></i> <?php echo $producto['total_guardados']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card-actions">
                        <?php if (isLoggedIn() && $_SESSION['user_id'] == $producto['user_id']): ?>
                            <a href="editar-producto.php?id=<?php echo $producto['id']; ?>" class="btn-card btn-edit-card" onclick="event.stopPropagation();">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        <?php else: ?>
                            <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn-card btn-intercambiar">
                                <i class="fas fa-exchange-alt"></i> Proponer intercambio
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Botón siguiente -->
            <button class="fyp-nav-btn next" id="fyp-next">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <!-- Indicadores del carrusel -->
        <div class="fyp-indicators" id="fyp-indicators"></div>
        </div>
        <?php endif; ?>
        
        <!-- Sección "Todos los productos" -->
        <?php if (!empty($productos_recomendados)): ?>
        <div class="section-divider">
            <h2 class="section-title">
                <i class="fas fa-box-open"></i> Todos los productos
            </h2>
        </div>
        <?php endif; ?>
        
        <div class="cardscontainer">
            <?php if ($tipo_busqueda === 'usuarios' && !empty($usuarios)): ?>
                <!-- Tarjetas de usuarios -->
                <?php foreach ($usuarios as $usuario): ?>
                <div class="card card-usuario">
                    <a href="ver-perfil.php?id=<?php echo $usuario['id']; ?>" style="text-decoration:none;color:inherit;display:block;">
                        <div class="usuario-header">
                            <div class="usuario-avatar-grande">
                                <img src="<?php echo !empty($usuario['avatar_path']) ? htmlspecialchars($usuario['avatar_path']) : 'img/usuario.png'; ?>"
                                     alt="<?php echo htmlspecialchars($usuario['fullname']); ?>"
                                     onerror="this.src='img/usuario.png'">
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="cardtitle"><?php echo htmlspecialchars($usuario['fullname']); ?></div>
                            <div class="usuario-username">@<?php echo htmlspecialchars($usuario['username']); ?></div>
                            <?php if (!empty($usuario['ubicacion'])): ?>
                            <div class="usuario-ubicacion">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($usuario['ubicacion']); ?>
                            </div>
                            <?php endif; ?>
                            <div class="usuario-stats">
                                <span><i class="fas fa-box"></i> <?php echo (int)$usuario['total_productos']; ?> productos</span>
                                <span><i class="fas fa-exchange-alt"></i> <?php echo (int)$usuario['total_intercambios']; ?> intercambios</span>
                            </div>
                        </div>
                    </a>
                    <div class="card-actions">
                        <?php if (isLoggedIn() && $_SESSION['user_id'] != $usuario['id']): ?>
                            <a href="ver-perfil.php?id=<?php echo $usuario['id']; ?>" class="btn-card btn-intercambiar">
                                <i class="fas fa-user"></i> Ver perfil
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php elseif (!empty($productos)): ?>
                <!-- Tarjetas de productos (código original) -->
                <?php foreach ($productos as $producto): ?>
                <div class="card">
                    <a href="producto.php?id=<?php echo $producto['id']; ?>" style="text-decoration:none;color:inherit;display:block;">
                        <div class="cardimg">
                            <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        </div>
                        <div class="card-body">
                            <div class="cardtitle"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                            <div class="card-badges">
                                <span class="badge-estado badge-<?php echo $producto['estado']; ?>">
                                    <?php echo ucfirst($producto['estado']); ?>
                                </span>
                                <?php if (!empty($producto['categoria'])): ?>
                                    <span class="badge-categoria">
                                        <?php echo htmlspecialchars($producto['categoria']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-seller">
                                <div class="contact-avatar-small">
                                    <?php if (!empty($producto['avatar_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($producto['avatar_path']); ?>"
                                             alt="Avatar de <?php echo htmlspecialchars($producto['vendedor_name']); ?>"
                                             onerror="this.style.display='none'; this.parentElement.style.backgroundColor='#C9F89B';">
                                    <?php endif; ?>
                                </div>
                                <div class="seller-info">
                                    <div class="name"><?php echo htmlspecialchars($producto['vendedor_name']); ?></div>
                                    <div class="stars">
                                        <?php echo generateStars($producto['promedio_estrellas']); ?>
                                        <span class="rating-count">(<?php echo (int)$producto['total_valoraciones']; ?>)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                    <div class="card-actions">
                        <?php if (isLoggedIn() && $_SESSION['user_id'] == $producto['user_id']): ?>
                            <!-- Botón para productos propios -->
                            <a href="editar-producto.php?id=<?php echo $producto['id']; ?>" class="btn-card btn-edit-card" onclick="event.stopPropagation();">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        <?php else: ?>
                            <!-- Botón ver detalle para productos de otros usuarios -->
                            <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn-card btn-intercambiar">
                                <i class="fas fa-exchange-alt"></i> Proponer intercambio
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                    <h3>No se encontraron <?php echo $tipo_busqueda === 'usuarios' ? 'usuarios' : 'productos'; ?></h3>
                    <?php if ($busqueda): ?>
                        <p>No hay <?php echo $tipo_busqueda; ?> que coincidan con "<?php echo htmlspecialchars($busqueda); ?>"</p>
                        <a href="index.php" style="color: #6a994e;">Ver todos los productos</a>
                    <?php else: ?>
                        <p>Aún no hay <?php echo $tipo_busqueda; ?> disponibles</p>
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

    // Aplicar tema al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const tipoBusqueda = '<?php echo $tipo_busqueda; ?>';
        aplicarTema(tipoBusqueda);
    });

    function cambiarTipoBusqueda(tipo) {
        document.getElementById('tipo-busqueda').value = tipo;
        // Actualizar botones activos
        document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
        event.target.closest('.toggle-btn').classList.add('active');
        // Actualizar placeholder
        const input = document.querySelector('input[name="busqueda"]');
        input.placeholder = tipo === 'usuarios' ? '¿A quién buscás?' : '¿Qué te interesa?';
        // Ocultar filtros si se selecciona usuarios
        if (tipo === 'usuarios') {
            document.getElementById('filtros-panel').style.display = 'none';
        }
        // Aplicar tema de colores
        aplicarTema(tipo);
    }

    function aplicarTema(tipo) {
        const body = document.body;
        const navbar = document.querySelector('.navbar-container');
        
        if (tipo === 'usuarios') {
            // Activar tema usuarios (paleta rosa/morado/cyan)
            body.classList.add('tema-usuarios');
            body.classList.remove('tema-productos');
        } else {
            // Activar tema productos (paleta verde original)
            body.classList.add('tema-productos');
            body.classList.remove('tema-usuarios');
        }
    }

    function toggleFiltros() {
        const panel = document.getElementById('filtros-panel');
        panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
    }

    function limpiarFiltros() {
        document.querySelector('select[name="categoria"]').value = '';
        document.querySelector('select[name="estado"]').value = '';
        document.getElementById('search-form').submit();
    }

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
            // Si está logueado, redirigir al chat del vendedor
            window.location.href = 'mensajeria.php?user=' + productoId;
        <?php else: ?>
            // Si no está logueado, mostrar notificación personalizada
            Swal.fire({
                icon: 'info',
                title: 'Inicia sesión para contactar',
                text: 'Debes iniciar sesión para contactar al vendedor.',
                confirmButtonColor: '#6a994e',
                confirmButtonText: 'Iniciar sesión'
            }).then(() => {
                window.location.href = 'iniciarsesion.php';
            });
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
