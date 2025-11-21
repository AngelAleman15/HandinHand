<?php
session_start();
require_once 'includes/functions.php';

// Verificar que esté logueado
requireLogin();

// Configuración de la página
$page_title = "Mis Productos - HandinHand";
$body_class = "body-my-products";

// Obtener productos del usuario actual
$user = getCurrentUser();
require_once 'config/database.php';
$pdo = getConnection();

// Obtener productos con estadísticas
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        COALESCE(ps.score_total, 0) as score_total,
        COALESCE(pv.total_vistas, 0) as total_vistas,
        COALESCE(pg.total_guardados, 0) as total_guardados,
        COALESCE(pc.total_chats, 0) as total_chats
    FROM productos p
    LEFT JOIN producto_scores ps ON p.id = ps.producto_id
    LEFT JOIN (SELECT producto_id, COUNT(*) as total_vistas FROM producto_vistas GROUP BY producto_id) pv ON p.id = pv.producto_id
    LEFT JOIN (SELECT producto_id, COUNT(*) as total_guardados FROM producto_guardados GROUP BY producto_id) pg ON p.id = pg.producto_id
    LEFT JOIN (SELECT producto_id, COUNT(*) as total_chats FROM producto_chats GROUP BY producto_id) pc ON p.id = pc.producto_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user['id']]);
$misProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir header
include 'includes/header.php';
?>

<style>
/* Container principal */
.my-products-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Header moderno */
.my-products-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 32px 40px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(106, 153, 78, 0.15);
    margin-bottom: 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.header-info h1 {
    color: #2c3e50;
    font-size: 2em;
    margin: 0 0 8px 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-info h1 i {
    color: #6a994e;
    font-size: 0.9em;
}

.header-stats {
    display: flex;
    gap: 25px;
    margin-top: 12px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6c757d;
    font-size: 0.95em;
}

.stat-item i {
    color: #6a994e;
    font-size: 1.1em;
}

.stat-item strong {
    color: #2c3e50;
    font-size: 1.1em;
}

.btn-add-product {
    background: linear-gradient(135deg, #6a994e 0%, #5a8442 100%);
    color: white;
    padding: 14px 28px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1em;
    box-shadow: 0 4px 12px rgba(106, 153, 78, 0.3);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
}

.btn-add-product:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(106, 153, 78, 0.4);
}

.btn-add-product i {
    font-size: 1.1em;
}

/* Grid de productos */
.my-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

/* Card de producto */
.my-product-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    border: 2px solid transparent;
    cursor: pointer;
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.my-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(106, 153, 78, 0.25);
    border-color: #6a994e;
}

/* Imagen del producto */
.my-product-image {
    width: 100%;
    height: 240px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    padding: 12px;
}

.my-product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.my-product-card:hover .my-product-image img {
    transform: scale(1.05);
}

/* Estado badge */
.product-status-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75em;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: all 0.2s ease;
}

.product-status-badge:hover {
    transform: scale(1.05);
}

.status-disponible {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-reservado {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

.status-intercambiado {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* Contenido de la card */
.my-product-content {
    padding: 16px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.my-product-title {
    font-size: 1.1em;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 8px 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.my-product-category {
    display: inline-block;
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 500;
    margin-bottom: 12px;
}

.my-product-description {
    font-size: 0.9em;
    color: #7f8c8d;
    margin: 0 0 12px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
    height: 2.8em;
}

/* Estadísticas del producto */
.my-product-stats {
    display: flex;
    justify-content: space-around;
    padding: 12px 0;
    border-top: 1px solid #ecf0f1;
    border-bottom: 1px solid #ecf0f1;
    margin-bottom: 12px;
}

.my-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.my-stat i {
    font-size: 1.2em;
    color: #6a994e;
}

.my-stat span {
    font-size: 0.85em;
    color: #7f8c8d;
    font-weight: 500;
}

.my-stat strong {
    font-size: 1.1em;
    color: #2c3e50;
}

/* Acciones */
.my-product-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: auto;
}

.btn-action {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 0.9em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    text-decoration: none;
    white-space: nowrap;
}

.btn-product-edit {
    background: #6c757d;
    color: white;
}

.btn-product-edit:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-product-delete {
    background: #dc3545;
    color: white;
}

.btn-product-delete:hover {
    background: #c82333;
    transform: translateY(-2px);
}

/* Estado vacío */
.no-products-container {
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    border: 2px dashed #6a994e;
}

.no-products-container i {
    font-size: 4em;
    color: #6a994e;
    margin-bottom: 20px;
    opacity: 0.6;
}

.no-products-container h3 {
    color: #2c3e50;
    font-size: 1.8em;
    margin: 0 0 12px 0;
}

.no-products-container p {
    color: #6c757d;
    font-size: 1.1em;
    margin: 0 0 30px 0;
}

/* Responsive */
@media (max-width: 768px) {
    .my-products-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .header-stats {
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .my-products-grid {
        grid-template-columns: 1fr;
    }
    
    .btn-add-product {
        width: 100%;
        justify-content: center;
    }
}

/* Estilos para modal de confirmación de eliminación */
.btn-confirm-delete,
.btn-cancel-delete {
    padding: 10px 20px !important;
    font-weight: 600 !important;
    border-radius: 6px !important;
    transition: all 0.2s ease !important;
}

.btn-confirm-delete:hover {
    background: #c82333 !important;
    transform: translateY(-1px);
}

.btn-cancel-delete:hover {
    background: #5a6268 !important;
}
</style>

<div class="my-products-container">
    <!-- Header -->
    <div class="my-products-header">
        <div class="header-info">
            <h1>
                <i class="fas fa-box-open"></i>
                Mis Productos
            </h1>
            <div class="header-stats">
                <div class="stat-item">
                    <i class="fas fa-boxes"></i>
                    <span>Total: <strong><?php echo count($misProductos); ?></strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Disponibles: <strong><?php echo count(array_filter($misProductos, fn($p) => $p['estado'] === 'disponible')); ?></strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <span>Reservados: <strong><?php echo count(array_filter($misProductos, fn($p) => $p['estado'] === 'reservado')); ?></strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Intercambiados: <strong><?php echo count(array_filter($misProductos, fn($p) => $p['estado'] === 'intercambiado')); ?></strong></span>
                </div>
            </div>
        </div>
        <a href="crear-producto.php" class="btn-add-product">
            <i class="fas fa-plus-circle"></i>
            Crear Producto
        </a>
    </div>

    <!-- Grid de productos -->
    <?php if (!empty($misProductos)): ?>
        <div class="my-products-grid">
            <?php foreach ($misProductos as $producto): ?>
                <div class="my-product-card" onclick="window.location.href='producto.php?id=<?php echo $producto['id']; ?>'">
                    <!-- Imagen -->
                    <div class="my-product-image">
                        <span class="product-status-badge status-<?php echo $producto['estado']; ?>" 
                              data-producto-id="<?php echo $producto['id']; ?>" 
                              data-estado="<?php echo $producto['estado']; ?>"
                              onclick="event.stopPropagation(); cambiarEstado(this);"
                              title="Click para cambiar estado">
                            <?php echo strtoupper($producto['estado']); ?>
                        </span>
                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" 
                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                             onerror="this.src='img/productos/default.jpg'">
                    </div>

                    <!-- Contenido -->
                    <div class="my-product-content">
                        <h3 class="my-product-title"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                        
                        <span class="my-product-category">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($producto['categoria'] ?? 'Sin categoría'); ?>
                        </span>
                        
                        <p class="my-product-description">
                            <?php echo htmlspecialchars($producto['descripcion']); ?>
                        </p>

                        <!-- Estadísticas -->
                        <div class="my-product-stats">
                            <div class="my-stat">
                                <i class="fas fa-eye"></i>
                                <strong><?php echo $producto['total_vistas']; ?></strong>
                                <span>Vistas</span>
                            </div>
                            <div class="my-stat">
                                <i class="fas fa-heart"></i>
                                <strong><?php echo $producto['total_guardados']; ?></strong>
                                <span>Guardados</span>
                            </div>
                            <div class="my-stat">
                                <i class="fas fa-comments"></i>
                                <strong><?php echo $producto['total_chats']; ?></strong>
                                <span>Chats</span>
                            </div>
                            <div class="my-stat">
                                <i class="fas fa-fire"></i>
                                <strong><?php echo round($producto['score_total'], 1); ?></strong>
                                <span>Score</span>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="my-product-actions">
                            <a href="<?php echo url('editar-producto.php?id=' . $producto['id']); ?>" 
                               class="btn-action btn-product-edit"
                               onclick="event.stopPropagation();">
                                <i class="fas fa-edit"></i>
                                Editar
                            </a>
                            <button class="btn-action btn-product-delete" 
                                    onclick="event.stopPropagation(); deleteProduct(<?php echo $producto['id']; ?>);">
                                <i class="fas fa-trash"></i>
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-products-container">
            <i class="fas fa-box-open"></i>
            <h3>No tienes productos publicados</h3>
            <p>¡Agrega tu primer producto y comienza a intercambiar!</p>
            <a href="crear-producto.php" class="btn-add-product">
                <i class="fas fa-plus"></i>
                Publicar mi primer producto
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
// Cambiar estado del producto
function cambiarEstado(element) {
    const productoId = element.getAttribute('data-producto-id');
    const estadoActual = element.getAttribute('data-estado');
    const estados = ['disponible', 'reservado', 'intercambiado'];
    const idx = estados.indexOf(estadoActual);
    const nuevoEstado = estados[(idx + 1) % estados.length];
    
    fetch('api/update-producto-estado.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(productoId)}&estado=${encodeURIComponent(nuevoEstado)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            element.setAttribute('data-estado', nuevoEstado);
            element.className = `product-status-badge status-${nuevoEstado}`;
            element.textContent = nuevoEstado.toUpperCase();
            
            Swal.fire({
                icon: 'success',
                title: 'Estado actualizado',
                text: `El producto ahora está: ${nuevoEstado}`,
                confirmButtonColor: '#6a994e',
                timer: 2000,
                timerProgressBar: true
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo actualizar el estado',
                confirmButtonColor: '#6a994e'
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
            confirmButtonColor: '#6a994e'
        });
    });
}

// Eliminar producto
function deleteProduct(id) {
    Swal.fire({
        title: '¿Eliminar este producto?',
        html: `
            <p>Esta acción eliminará:</p>
            <ul style="text-align: left; margin: 15px auto; max-width: 300px;">
                <li>El producto completo</li>
                <li>Todas sus imágenes</li>
                <li>Estadísticas y vistas</li>
                <li>Guardados de otros usuarios</li>
            </ul>
            <p style="color: #dc3545; font-weight: bold;">⚠️ Esta acción no se puede deshacer</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash"></i> Sí, eliminar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        focusCancel: true,
        customClass: {
            confirmButton: 'btn-confirm-delete',
            cancelButton: 'btn-cancel-delete'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando producto...',
                html: '<i class="fas fa-spinner fa-spin fa-3x" style="color: #6a994e;"></i>',
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            
            // Realizar petición DELETE
            fetch(`api/delete-producto.php?id=${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Producto eliminado!',
                        html: `
                            <p>${data.message}</p>
                            ${data.imagenes_eliminadas > 0 ? `<small>Se eliminaron ${data.imagenes_eliminadas} imagen(es)</small>` : ''}
                        `,
                        confirmButtonColor: '#6a994e',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        // Recargar la página para actualizar la lista
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'No se pudo eliminar el producto');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al eliminar',
                    text: error.message || 'No se pudo conectar con el servidor',
                    confirmButtonColor: '#6a994e',
                    footer: '<small>Si el problema persiste, contacta con soporte</small>'
                });
            });
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
