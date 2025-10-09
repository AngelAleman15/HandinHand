<?php
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: error404.php');
    exit();
}

$producto_id = intval($_GET['id']);
$sql = "SELECT p.*, u.fullname, u.email, u.avatar_path 
        FROM productos p 
        JOIN usuarios u ON p.user_id = u.id 
        WHERE p.id = ?";

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        header('Location: error404.php');
        exit();
    }

    // Obtener categorías del producto
    $sql_categorias = "SELECT c.nombre 
                      FROM categorias c 
                      JOIN producto_categoria pc ON c.id = pc.categoria_id 
                      WHERE pc.producto_id = ?";
    $stmt_cat = $pdo->prepare($sql_categorias);
    $stmt_cat->execute([$producto_id]);
    $categorias = $stmt_cat->fetchAll();
} catch (Exception $e) {
    error_log("Error en producto.php: " . $e->getMessage());
    header('Location: error404.php');
    exit();
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<link rel="stylesheet" href="css/share.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<div class="product-detail-container">
    <div class="product-main">
        <div class="product-image">
            <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
        </div>
        
        <div class="product-info">
            <h1><?php echo htmlspecialchars($producto['nombre']); ?></h1>
            
            <div class="product-categories">
                <?php foreach ($categorias as $categoria): ?>
                    <span class="category-tag"><?php echo htmlspecialchars($categoria['nombre']); ?></span>
                <?php endforeach; ?>
            </div>
            
            <div class="product-state">
                <span class="state-tag"><?php echo htmlspecialchars($producto['estado']); ?></span>
            </div>
            
            <p class="product-description"><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
            
            <div class="product-actions">
                <button id="contactBtn" class="btn-action contact">
                    <img src="img/chat.png" alt="Contactar">
                    Contactar
                </button>
                
                <button id="saveBtn" class="btn-action save">
                    <i class="fas fa-bookmark"></i>
                    <span id="saveText">Guardar</span>
                </button>
                
                <button id="shareBtn" class="btn-action share">
                    <i class="fas fa-share-alt"></i>
                    Compartir
                </button>
                
                <button id="reportBtn" class="btn-action report">
                    <i class="fas fa-flag"></i>
                    Denunciar
                </button>

                <script>
                // Verificar si el producto está en favoritos
                fetch(`api/favoritos.php?producto_id=<?php echo $producto_id; ?>`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.isFavorite) {
                            document.getElementById('saveBtn').classList.add('saved');
                            document.getElementById('saveText').textContent = 'Guardado';
                        }
                    });

                // Manejar clic en botón de guardar
                document.getElementById('saveBtn').addEventListener('click', function() {
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        window.location.href = 'iniciarsesion.php';
                        return;
                    <?php endif; ?>

                    fetch('api/favoritos.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            producto_id: <?php echo $producto_id; ?>
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const btn = document.getElementById('saveBtn');
                            const text = document.getElementById('saveText');
                            if (data.isFavorite) {
                                btn.classList.add('saved');
                                text.textContent = 'Guardado';
                            } else {
                                btn.classList.remove('saved');
                                text.textContent = 'Guardar';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al guardar el producto');
                    });
                });
                </script>
            </div>
        </div>
    </div>
    
    <div class="seller-section">
        <div class="seller-info">
            <div class="seller-avatar">
                <img src="<?php echo $producto['avatar_path'] ? htmlspecialchars($producto['avatar_path']) : 'img/profile-example.png'; ?>" 
                     alt="Avatar de <?php echo htmlspecialchars($producto['fullname']); ?>">
            </div>
            <div class="seller-details">
                <h3><?php echo htmlspecialchars($producto['fullname']); ?></h3>
                <div class="seller-rating">
                    <!-- Aquí irá el sistema de valoraciones cuando lo implementemos -->
                    <div class="stars">
                        <img src="img/starfilled.png" alt="star">
                        <img src="img/starfilled.png" alt="star">
                        <img src="img/starfilled.png" alt="star">
                        <img src="img/star.png" alt="star">
                        <img src="img/star.png" alt="star">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="exchange-location">
        <h2>Ubicación de intercambio</h2>
        <?php if ($producto['ubicacion_lat'] && $producto['ubicacion_lng']): ?>
            <div id="map" style="height: 300px;"></div>
            <div class="location-name">
                <i class="fas fa-map-marker-alt"></i>
                <span><?php echo htmlspecialchars($producto['ubicacion_nombre']); ?></span>
            </div>
        <?php elseif ($producto['user_id'] == $_SESSION['user_id']): ?>
            <div class="no-location">
                <p>No has establecido una ubicación para el intercambio</p>
                <button id="setLocationBtn" class="btn-action">
                    <i class="fas fa-map-marker-alt"></i>
                    Establecer ubicación
                </button>
            </div>
        <?php else: ?>
            <div class="no-location">
                <p>El vendedor aún no ha establecido una ubicación para el intercambio</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($producto['ubicacion_lat'] && $producto['ubicacion_lng']): ?>
            // Inicializar mapa con la ubicación guardada
            var map = L.map('map').setView([<?php echo $producto['ubicacion_lat']; ?>, <?php echo $producto['ubicacion_lng']; ?>], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            L.marker([<?php echo $producto['ubicacion_lat']; ?>, <?php echo $producto['ubicacion_lng']; ?>])
                .addTo(map)
                .bindPopup("Punto de intercambio");
        <?php endif; ?>

        <?php if ($producto['user_id'] == $_SESSION['user_id']): ?>
            // Si es el dueño del producto, permitir establecer ubicación
            document.getElementById('setLocationBtn')?.addEventListener('click', function() {
                Swal.fire({
                    title: 'Establecer ubicación',
                    html: `
                        <div style="margin-bottom: 15px;">
                            <input type="text" id="locationSearch" class="swal2-input" placeholder="Buscar ubicación...">
                        </div>
                        <div id="searchMap" style="height: 300px;"></div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar ubicación',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#6a994e',
                    cancelButtonColor: '#dc3545',
                    didOpen: () => {
                        // Inicializar mapa de búsqueda
                        var searchMap = L.map('searchMap').setView([37.3826, -5.9965], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(searchMap);

                        let marker;
                        let selectedLocation = null;

                        // Manejar búsqueda de ubicación
                        const searchInput = document.getElementById('locationSearch');
                        let timeoutId = null;

                        searchInput.addEventListener('input', function(e) {
                            if (timeoutId) clearTimeout(timeoutId);
                            
                            timeoutId = setTimeout(() => {
                                const query = e.target.value;
                                if (query.length < 3) return;

                                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.length > 0) {
                                            const location = data[0];
                                            selectedLocation = {
                                                lat: parseFloat(location.lat),
                                                lng: parseFloat(location.lon),
                                                name: location.display_name
                                            };

                                            searchMap.setView([selectedLocation.lat, selectedLocation.lng], 16);
                                            
                                            if (marker) marker.remove();
                                            marker = L.marker([selectedLocation.lat, selectedLocation.lng]).addTo(searchMap);
                                        }
                                    });
                            }, 500);
                        });

                        // Permitir hacer clic en el mapa
                        searchMap.on('click', function(e) {
                            const lat = e.latlng.lat;
                            const lng = e.latlng.lng;

                            // Obtener nombre de la ubicación
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                                .then(response => response.json())
                                .then(data => {
                                    selectedLocation = {
                                        lat: lat,
                                        lng: lng,
                                        name: data.display_name
                                    };

                                    if (marker) marker.remove();
                                    marker = L.marker([lat, lng]).addTo(searchMap);
                                });
                        });

                        Swal.getConfirmButton().addEventListener('click', () => {
                            if (!selectedLocation) {
                                Swal.showValidationMessage('Por favor selecciona una ubicación');
                                return false;
                            }

                            // Guardar ubicación
                            fetch('api/productos.php', {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    id: <?php echo $producto_id; ?>,
                                    ubicacion_lat: selectedLocation.lat,
                                    ubicacion_lng: selectedLocation.lng,
                                    ubicacion_nombre: selectedLocation.name
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                } else {
                                    throw new Error(data.message);
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: error.message || 'Error al guardar la ubicación'
                                });
                            });
                        });
                    }
                });
            });
        <?php endif; ?>
    });
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar mapa
    var map = L.map('map').setView([37.3826, -5.9965], 13); // Coordenadas de Sevilla
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Funcionalidad de compartir
    document.getElementById('shareBtn').addEventListener('click', function() {
        const shareUrl = window.location.href;
        const shareTitle = '<?php echo addslashes($producto['nombre']); ?>';
        const shareText = '¡Mira este producto en HandinHand!';

        if (navigator.share) {
            // Usar la API Web Share si está disponible
            navigator.share({
                title: shareTitle,
                text: shareText,
                url: shareUrl
            }).catch((error) => {
                console.log('Error sharing:', error);
                fallbackShare();
            });
        } else {
            fallbackShare();
        }

        function fallbackShare() {
            Swal.fire({
                title: 'Compartir producto',
                html: `
                    <div class="share-options">
                        <button onclick="window.open('https://wa.me/?text=${encodeURIComponent(shareTitle + ' - ' + shareUrl)}', '_blank')" class="share-button whatsapp">
                            <img src="img/wasaicon.png" alt="WhatsApp"> WhatsApp
                        </button>
                        <button onclick="window.open('https://telegram.me/share/url?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(shareTitle)}', '_blank')" class="share-button telegram">
                            <i class="fab fa-telegram"></i> Telegram
                        </button>
                        <button onclick="window.open('https://twitter.com/intent/tweet?text=${encodeURIComponent(shareTitle)}&url=${encodeURIComponent(shareUrl)}', '_blank')" class="share-button twitter">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                        <div class="share-link-container">
                            <input type="text" value="${shareUrl}" readonly class="share-link">
                            <button onclick="copyToClipboard(this)" class="copy-link" data-url="${shareUrl}">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    container: 'share-dialog'
                }
            });
        }
    });

    function copyToClipboard(button) {
        const url = button.dataset.url;
        navigator.clipboard.writeText(url).then(() => {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.add('copied');
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('copied');
            }, 2000);
        });
    }
    
    // Funcionalidad de contactar
    document.getElementById('contactBtn').addEventListener('click', function() {
        window.location.href = `mensajeria.php?user_id=${<?php echo $producto['usuario_id']; ?>}`;
    });
    
    // WIP: Funcionalidad de guardar y denunciar
    document.getElementById('saveBtn').addEventListener('click', function() {
        alert('Funcionalidad en desarrollo');
    });
    
    document.getElementById('reportBtn').addEventListener('click', function() {
        <?php if (!isset($_SESSION['user_id'])): ?>
            window.location.href = 'iniciarsesion.php';
            return;
        <?php endif; ?>

        Swal.fire({
            title: 'Denunciar Producto',
            html: `
                <form id="reportForm">
                    <div class="form-group">
                        <label for="motivo">Motivo de la denuncia *</label>
                        <select id="motivo" class="swal2-input" required>
                            <option value="">Selecciona un motivo</option>
                            <option value="contenido_inapropiado">Contenido inapropiado</option>
                            <option value="producto_ilegal">Producto ilegal</option>
                            <option value="fraude">Posible fraude</option>
                            <option value="spam">Spam o publicidad engañosa</option>
                            <option value="otro">Otro motivo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción detallada</label>
                        <textarea id="descripcion" class="swal2-textarea" placeholder="Describe el problema..."></textarea>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Enviar Denuncia',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            preConfirm: () => {
                const motivo = document.getElementById('motivo').value;
                const descripcion = document.getElementById('descripcion').value;

                if (!motivo) {
                    Swal.showValidationMessage('Por favor selecciona un motivo');
                    return false;
                }

                return { motivo, descripcion };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('api/denuncias.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        producto_id: <?php echo $producto_id; ?>,
                        motivo: result.value.motivo,
                        descripcion: result.value.descripcion
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Gracias por reportar!',
                            text: 'Tu denuncia será revisada por nuestro equipo.',
                            confirmButtonColor: '#6a994e'
                        });
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Hubo un error al procesar la denuncia',
                        confirmButtonColor: '#dc3545'
                    });
                });
            }
        });
    });
});
</script>

<style>
.product-detail-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

.product-main {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
}

.product-image {
    flex: 0 0 50%;
    border-radius: 10px;
    overflow: hidden;
    background-color: #f9f9f9;
}

.product-image img {
    width: 100%;
    height: auto;
    object-fit: contain;
}

.product-info {
    flex: 1;
}

.product-info h1 {
    font-size: 28px;
    color: #333;
    margin-bottom: 15px;
}

.product-categories {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.category-tag {
    background-color: #6a994e;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 14px;
}

.state-tag {
    background-color: #386641;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 14px;
    display: inline-block;
    margin-bottom: 15px;
}

.product-description {
    color: #666;
    line-height: 1.6;
    margin-bottom: 30px;
}

.product-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 10px 20px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-action img {
    width: 20px;
    height: 20px;
}

.btn-action.contact {
    background-color: #6a994e;
    color: white;
}

.btn-action.save {
    background-color: #a7c957;
    color: #333;
}

.btn-action.share {
    background-color: #f2e8cf;
    color: #333;
}

.btn-action.report {
    background-color: #bc4749;
    color: white;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-action.save.saved {
    background-color: #6a994e;
    color: white;
}

.btn-action.save.saved:hover {
    background-color: #386641;
}

.seller-section {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.seller-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.seller-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
}

.seller-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.seller-details h3 {
    margin: 0;
    color: #333;
    font-size: 18px;
}

.seller-rating {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 5px;
}

.stars {
    display: flex;
    gap: 2px;
}

.stars img {
    width: 15px;
    height: 15px;
}

.exchange-location {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 10px;
}

.exchange-location h2 {
    margin-bottom: 15px;
    color: #333;
}

@media (max-width: 768px) {
    .product-main {
        flex-direction: column;
    }
    
    .product-image {
        flex: 0 0 100%;
    }
    
    .product-actions {
        justify-content: center;
    }
    
    .btn-action {
        flex: 1;
        min-width: 120px;
        justify-content: center;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>