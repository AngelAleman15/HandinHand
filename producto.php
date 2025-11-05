<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$producto = null;
if ($id) {
    // Obtener datos reales desde la API
    $curl = curl_init();
    $apiUrl = "http://" . $_SERVER['HTTP_HOST'] . "/api/productos.php?id=" . $id;
    curl_setopt($curl, CURLOPT_URL, $apiUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    $data = json_decode($response, true);
    
    // Debug: descomentar para ver qué está devolviendo la API
    // echo "<!-- DEBUG API Response: " . htmlspecialchars($response) . " -->";
    // echo "<!-- DEBUG HTTP Code: " . $httpCode . " -->";
    
    if ($data && isset($data['data']) && is_array($data['data']) && !empty($data['data']['id'])) {
        $producto = $data['data'];
    }
}
if (!$producto) {
    require_once 'includes/header.php';
    echo '<div style="padding:2em;text-align:center;color:#A2CB8D;font-size:1.3em">Producto no encontrado o no disponible</div>';
    echo '<div style="padding:1em;text-align:center;"><a href="index.php" style="color:#A2CB8D;">Volver al inicio</a></div>';
    require_once 'includes/footer.php';
    exit;
}

// Incluir header
require_once 'includes/header.php';
?>
<link rel="stylesheet" href="css/producto.css">

<div class="producto-layout">
    <!-- Columna izquierda: Imagen -->
    <div class="producto-imagen-section">
        <div class="imagen-principal" id="imagenContainer">
            <?php if (!empty($producto['imagenes']) && isset($producto['imagenes'][0])): ?>
                <img src="<?php echo htmlspecialchars($producto['imagenes'][0]); ?>" alt="<?php echo htmlspecialchars($producto['nombre'] ?? 'Producto'); ?>" id="imagenPrincipal">
                <?php if (count($producto['imagenes']) > 1): ?>
                    <button class="carrusel-btn prev" onclick="cambiarImagen(-1)">❮</button>
                    <button class="carrusel-btn next" onclick="cambiarImagen(1)">❯</button>
                    <div class="carrusel-indicadores">
                        <?php foreach ($producto['imagenes'] as $idx => $img): ?>
                            <span class="indicador <?php echo $idx === 0 ? 'activo' : ''; ?>" onclick="irAImagen(<?php echo $idx; ?>)"></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <img src="img/productos/default.jpg" alt="Imagen por defecto" id="imagenPrincipal">
            <?php endif; ?>
        </div>
        
        <!-- Miniaturas -->
        <?php if (!empty($producto['imagenes']) && count($producto['imagenes']) > 1): ?>
        <div class="imagen-miniaturas">
            <?php foreach ($producto['imagenes'] as $idx => $img): ?>
                <div class="miniatura <?php echo $idx === 0 ? 'activa' : ''; ?>" onclick="irAImagen(<?php echo $idx; ?>)">
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="Vista <?php echo $idx + 1; ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const imagenes = <?php echo json_encode($producto['imagenes'] ?? []); ?>;
        let imagenActual = 0;
        
        function cambiarImagen(direccion) {
            imagenActual += direccion;
            if (imagenActual < 0) imagenActual = imagenes.length - 1;
            if (imagenActual >= imagenes.length) imagenActual = 0;
            actualizarImagen();
        }
        
        function irAImagen(indice) {
            imagenActual = indice;
            actualizarImagen();
        }
        
        function actualizarImagen() {
            document.getElementById('imagenPrincipal').src = imagenes[imagenActual];
            
            // Actualizar indicadores
            const indicadores = document.querySelectorAll('.indicador');
            indicadores.forEach((ind, idx) => {
                ind.classList.toggle('activo', idx === imagenActual);
            });
            
            // Actualizar miniaturas
            const miniaturas = document.querySelectorAll('.miniatura');
            miniaturas.forEach((min, idx) => {
                min.classList.toggle('activa', idx === imagenActual);
            });
        }
        
        // Efecto lupa - zoom en la posición del mouse
        const container = document.getElementById('imagenContainer');
        const imagen = document.getElementById('imagenPrincipal');
        
        container.addEventListener('mousemove', function(e) {
            const rect = container.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            
            imagen.style.transformOrigin = `${x}% ${y}%`;
        });
        
        container.addEventListener('mouseenter', function() {
            imagen.style.transform = 'scale(2)';
        });
        
        container.addEventListener('mouseleave', function() {
            imagen.style.transform = 'scale(1)';
            imagen.style.transformOrigin = 'center center';
        });
    </script>

    <!-- Columna central: Información del producto -->
    <div class="producto-info-section">
        <!-- Badges de estado -->
        <div class="producto-badges">
            <span class="badge-estado-detalle badge-<?php echo htmlspecialchars($producto['estado'] ?? 'disponible'); ?>">
                <?php echo ucfirst(htmlspecialchars($producto['estado'] ?? 'disponible')); ?>
            </span>
            <?php if (!empty($producto['categorias']) && is_array($producto['categorias'])): ?>
                <?php foreach ($producto['categorias'] as $cat): ?>
                    <span class="badge-categoria-detalle">
                        <?php echo htmlspecialchars(is_array($cat) ? ($cat['nombre'] ?? 'Sin categoría') : ($cat ?? 'Sin categoría')); ?>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Título -->
        <h1 class="producto-titulo"><?php echo htmlspecialchars($producto['nombre'] ?? 'Sin título'); ?></h1>

        <!-- Valoración del vendedor -->
        <div class="producto-rating">
            <span class="stars-detalle">
                <?php echo generateStars(isset($producto['promedio_estrellas']) ? $producto['promedio_estrellas'] : 0); ?>
            </span>
            <span class="rating-text"><?php echo number_format(isset($producto['promedio_estrellas']) ? $producto['promedio_estrellas'] : 0, 1); ?></span>
            <span class="rating-count-detalle">(<?php echo isset($producto['total_valoraciones']) ? (int)$producto['total_valoraciones'] : 0; ?> valoraciones)</span>
        </div>

        <!-- Descripción -->
        <div class="producto-descripcion-section">
            <h2 class="section-title">Descripción</h2>
            <p class="producto-descripcion-texto">
                <?php echo nl2br(htmlspecialchars($producto['descripcion'] ?? 'Sin descripción disponible')); ?>
            </p>
        </div>

        <!-- Información del vendedor -->
        <div class="vendedor-section">
            <h2 class="section-title">Información del vendedor</h2>
            <div class="vendedor-info-card">
                <div class="vendedor-avatar-large">
                    <?php if (!empty($producto['avatar_path'])): ?>
                        <img src="<?php echo htmlspecialchars($producto['avatar_path']); ?>" alt="Avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?php echo strtoupper(substr($producto['vendedor_name'] ?? 'U', 0, 1)); ?></div>
                    <?php endif; ?>
                </div>
                <div class="vendedor-datos">
                    <div class="vendedor-nombre-detalle"><?php echo htmlspecialchars(isset($producto['vendedor_name']) ? $producto['vendedor_name'] : 'Usuario'); ?></div>
                    <div class="vendedor-username">@<?php echo htmlspecialchars(isset($producto['username']) ? $producto['username'] : (isset($producto['vendedor_username']) ? $producto['vendedor_username'] : 'usuario')); ?></div>
                </div>
            </div>
        </div>

        <!-- Puntos de Encuentro (movido aquí desde columna derecha) -->
        <div class="puntos-encuentro-section">
            <h2 class="section-title"><i class="fas fa-map-marker-alt"></i> Puntos de encuentro sugeridos</h2>
            <p class="puntos-info">El vendedor sugiere estos lugares seguros para realizar el intercambio</p>
            
            <div id="listaPuntosEncuentro" class="lista-puntos-encuentro">
                <div class="loading">Cargando ubicaciones...</div>
            </div>
            
            <div id="mapaContainer" class="mapa-container-central" style="display: none;">
                <div id="mapa" style="width: 100%; height: 450px; border-radius: 8px;"></div>
            </div>
        </div>

        <!-- Punto de encuentro antiguo (comentado) -->
        <?php /* if (!empty($producto['latitud']) && !empty($producto['longitud'])): ?>
        <div class="ubicacion-section">
            <h2 class="section-title">Punto de encuentro</h2>
            <div class="mapa-container">
                <iframe 
                    width="100%" 
                    height="250" 
                    style="border:0;border-radius:8px;" 
                    loading="lazy"
                    src="https://maps.google.com/maps?q=<?php echo $producto['latitud'] . ',' . $producto['longitud']; ?>&z=15&output=embed">
                </iframe>
            </div>
            <a href="https://www.google.com/maps?q=<?php echo $producto['latitud'] . ',' . $producto['longitud']; ?>" 
               target="_blank" 
               class="btn-ver-mapa">
                <i class="fas fa-map-marker-alt"></i> Ver en Google Maps
            </a>
        </div>
        <?php endif; */ ?>
    </div>

    <!-- Columna derecha: Acciones -->
    <div class="producto-acciones-section">
        <div class="acciones-card">
            <div class="acciones-title">Acciones disponibles</div>
            
            <button class="btn-accion btn-accion-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
                <i class="fas fa-exchange-alt"></i>
                Proponer intercambio
            </button>
            
            <button class="btn-accion btn-accion-secondary" onclick="contactarVendedor(<?php echo $producto['user_id']; ?>, '<?php echo htmlspecialchars($producto['vendedor_name'] ?? 'Usuario', ENT_QUOTES); ?>')">
                <i class="fas fa-comments"></i>
                Contactar vendedor
            </button>
            
            <div class="acciones-extras">
                <button class="btn-accion-icon" id="btnFavorito" title="Guardar producto" onclick="toggleFavorito(<?php echo $id; ?>)">
                    <i class="far fa-bookmark"></i>
                </button>
                <button class="btn-accion-icon" title="Compartir producto" onclick="compartirProducto()">
                    <i class="fas fa-share-nodes"></i>
                </button>
                <button class="btn-accion-icon btn-reportar" title="Reportar problema" onclick="reportarProducto(<?php echo $id; ?>)">
                    <i class="fas fa-exclamation-triangle"></i>
                </button>
            </div>
            
            <!-- Sección de valoración -->
            <div class="valoracion-section">
                <h3 class="valoracion-title">Valorar este producto</h3>
                <div class="estrellas-valoracion" id="estrellasValorar">
                    <i class="far fa-star" data-valor="1" onclick="seleccionarEstrella(1)"></i>
                    <i class="far fa-star" data-valor="2" onclick="seleccionarEstrella(2)"></i>
                    <i class="far fa-star" data-valor="3" onclick="seleccionarEstrella(3)"></i>
                    <i class="far fa-star" data-valor="4" onclick="seleccionarEstrella(4)"></i>
                    <i class="far fa-star" data-valor="5" onclick="seleccionarEstrella(5)"></i>
                </div>
                <textarea id="comentarioValoracion" class="comentario-valoracion" placeholder="Escribe un comentario (opcional)..." maxlength="500"></textarea>
                <div class="caracteres-restantes">
                    <span id="contadorCaracteres">0</span>/500 caracteres
                </div>
                <button class="btn-valorar" id="btnValorar" onclick="enviarValoracion(<?php echo $producto['id']; ?>)">
                    Enviar valoración
                </button>
            </div>
            
            <!-- Ver todas las valoraciones -->
            <div class="ver-valoraciones-section">
                <button class="btn-ver-valoraciones" onclick="toggleValoraciones()">
                    <i class="fas fa-star"></i>
                    Ver todas las valoraciones (<?php echo isset($producto['total_valoraciones']) ? (int)$producto['total_valoraciones'] : 0; ?>)
                </button>
            </div>
        </div>

        <!-- Info adicional -->
        <div class="info-adicional-card">
            <div class="info-item">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <div class="info-title">Intercambio seguro</div>
                    <div class="info-text">Encuentra en lugar público</div>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-handshake"></i>
                <div>
                    <div class="info-title">Sin intermediarios</div>
                    <div class="info-text">Acuerdo directo entre usuarios</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Valoraciones -->
<div id="modalValoraciones" class="modal-valoraciones" style="display: none;">
    <div class="modal-valoraciones-content">
        <div class="modal-valoraciones-header">
            <h2>Valoraciones del vendedor</h2>
            <button class="modal-close" onclick="toggleValoraciones()">×</button>
        </div>
        <div class="modal-valoraciones-body" id="listaValoraciones">
            <div class="loading">Cargando valoraciones...</div>
        </div>
    </div>
</div>

<!-- Cargar Leaflet (OpenStreetMap) - Gratuito sin API Key -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let valoracionSeleccionada = 0;
let mapaLeaflet = null;
let marcadores = [];
let puntosEncuentro = [];

// Inicializar cuando cargue la página
document.addEventListener('DOMContentLoaded', function() {
    cargarPuntosEncuentro(<?php echo $producto['id']; ?>);
});


// Seleccionar estrella
function seleccionarEstrella(valor) {
    valoracionSeleccionada = valor;
    actualizarEstrellas();
}

// Sistema de valoración con estrellas - hover
const estrellas = document.querySelectorAll('#estrellasValorar i');
estrellas.forEach(estrella => {
    estrella.addEventListener('mouseenter', function() {
        const valor = parseInt(this.dataset.valor);
        estrellas.forEach((e, idx) => {
            if (idx < valor) {
                e.classList.remove('far');
                e.classList.add('fas');
            } else {
                e.classList.remove('fas');
                e.classList.add('far');
            }
        });
    });
});

document.getElementById('estrellasValorar').addEventListener('mouseleave', function() {
    actualizarEstrellas();
});

function actualizarEstrellas() {
    estrellas.forEach((e, idx) => {
        if (idx < valoracionSeleccionada) {
            e.classList.remove('far');
            e.classList.add('fas');
        } else {
            e.classList.remove('fas');
            e.classList.add('far');
        }
    });
}

// Contador de caracteres
const comentarioInput = document.getElementById('comentarioValoracion');
const contadorCaracteres = document.getElementById('contadorCaracteres');

if (comentarioInput) {
    comentarioInput.addEventListener('input', function() {
        contadorCaracteres.textContent = this.value.length;
    });
}

// Enviar valoración del PRODUCTO
function enviarValoracion(productoId) {
    if (valoracionSeleccionada === 0) {
        mostrarNotificacion('Por favor selecciona una calificación', 'warning');
        return;
    }
    
    const comentario = document.getElementById('comentarioValoracion').value.trim();
    const payload = {
        action: 'crear',
        producto_id: productoId,
        puntuacion: valoracionSeleccionada,
        comentario: comentario || null
    };
    
    fetch('api/valoraciones-productos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (response.status === 401) {
            mostrarNotificacion('Debes iniciar sesión para valorar', 'warning');
            setTimeout(() => {
                window.location.href = 'iniciarsesion.php';
            }, 2000);
            throw new Error('No autenticado');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            mostrarNotificacion('✓ Valoración enviada exitosamente', 'success');
            valoracionSeleccionada = 0;
            actualizarEstrellas();
            document.getElementById('comentarioValoracion').value = '';
            contadorCaracteres.textContent = '0';
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacion('Error: ' + (data.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.message !== 'No autenticado') {
            mostrarNotificacion('Error al enviar la valoración', 'error');
        }
    });
}

// Ver valoraciones del PRODUCTO
function toggleValoraciones() {
    const modal = document.getElementById('modalValoraciones');
    const isVisible = modal.style.display === 'block';
    
    if (!isVisible) {
        modal.style.display = 'block';
        cargarValoraciones(<?php echo $producto['id']; ?>);
    } else {
        modal.style.display = 'none';
    }
}

function cargarValoraciones(productoId) {
    const lista = document.getElementById('listaValoraciones');
    lista.innerHTML = '<div class="loading">Cargando valoraciones...</div>';
    
    fetch(`api/valoraciones-productos.php?producto_id=${productoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.valoraciones && data.data.valoraciones.length > 0) {
                let html = '';
                data.data.valoraciones.forEach(val => {
                    const estrellas = '★'.repeat(Math.floor(val.puntuacion)) + '☆'.repeat(5 - Math.floor(val.puntuacion));
                    const fecha = new Date(val.created_at).toLocaleDateString('es-ES');
                    html += `
                        <div class="valoracion-item">
                            <div class="valoracion-header">
                                <div class="valoracion-usuario">
                                    <strong>${val.usuario_nombre || 'Usuario'}</strong>
                                    <span class="valoracion-fecha">${fecha}</span>
                                </div>
                                <div class="valoracion-estrellas">${estrellas}</div>
                            </div>
                            ${val.comentario ? `<div class="valoracion-comentario">${val.comentario}</div>` : ''}
                        </div>
                    `;
                });
                lista.innerHTML = html;
            } else {
                lista.innerHTML = '<div class="no-valoraciones">No hay valoraciones aún</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            lista.innerHTML = '<div class="error-valoraciones">Error al cargar valoraciones</div>';
        });
}

// Contactar vendedor
// Contactar vendedor (sin confirmación)
function contactarVendedor(userId, userName) {
    window.location.href = `mensajeria.php?user=${userId}`;
}

// Toggle guardar producto (favorito)
let esGuardado = false;
function toggleFavorito(productoId) {
    const btn = document.getElementById('btnFavorito');
    const icon = btn.querySelector('i');
    
    esGuardado = !esGuardado;
    
    if (esGuardado) {
        icon.classList.remove('far');
        icon.classList.add('fas');
        btn.style.backgroundColor = '#4CAF50';
        btn.style.color = 'white';
        
        // Mostrar notificación
        mostrarNotificacion('✓ Producto guardado', 'success');
    } else {
        icon.classList.remove('fas');
        icon.classList.add('far');
        btn.style.backgroundColor = '';
        btn.style.color = '';
        
        mostrarNotificacion('Producto eliminado de guardados', 'info');
    }
}

// Compartir producto
function compartirProducto() {
    const url = window.location.href;
    const titulo = '<?php echo htmlspecialchars($producto['nombre'] ?? 'Producto', ENT_QUOTES); ?>';
    const texto = `Mira este producto en HandinHand: ${titulo}`;
    
    // Intentar usar Web Share API solo en móviles
    if (navigator.share && /mobile/i.test(navigator.userAgent)) {
        navigator.share({
            title: titulo,
            text: texto,
            url: url
        }).then(() => {
            mostrarNotificacion('✓ Compartido exitosamente', 'success');
        }).catch(err => {
            if (err.name !== 'AbortError') {
                copiarEnlace(url);
            }
        });
    } else {
        // En desktop, copiar directamente al portapapeles
        copiarEnlace(url);
    }
}

function copiarEnlace(url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            mostrarNotificacion('✓ Enlace copiado al portapapeles', 'success');
        }).catch(() => {
            // Fallback con textarea temporal
            copiarConTextarea(url);
        });
    } else {
        copiarConTextarea(url);
    }
}

function copiarConTextarea(url) {
    const textarea = document.createElement('textarea');
    textarea.value = url;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        mostrarNotificacion('✓ Enlace copiado al portapapeles', 'success');
    } catch (err) {
        mostrarNotificacion('No se pudo copiar el enlace', 'error');
    }
    document.body.removeChild(textarea);
}

function mostrarEnlaceManual(url) {
    const enlace = prompt('Copia este enlace para compartir:', url);
}

// Reportar producto
function reportarProducto(productoId) {
    const motivos = [
        'Producto inapropiado o ofensivo',
        'Información engañosa',
        'Producto prohibido',
        'Spam o fraude',
        'Duplicado',
        'Otro motivo'
    ];
    
    let opcionesHtml = '<div style="text-align: left; margin: 20px 0;">';
    opcionesHtml += '<p style="margin-bottom: 15px; font-weight: bold;">Selecciona el motivo del reporte:</p>';
    motivos.forEach((motivo, idx) => {
        opcionesHtml += `
            <div style="margin: 8px 0;">
                <input type="radio" name="motivo" value="${motivo}" id="motivo${idx}" style="margin-right: 8px;">
                <label for="motivo${idx}" style="cursor: pointer;">${motivo}</label>
            </div>
        `;
    });
    opcionesHtml += '<p style="margin-top: 15px;">Detalles adicionales (opcional):</p>';
    opcionesHtml += '<textarea id="detallesReporte" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd; margin-top: 5px;" rows="3" placeholder="Describe el problema..."></textarea>';
    opcionesHtml += '</div>';
    
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;';
    modal.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%;">
            <h3 style="margin: 0 0 20px 0; color: #333;">Reportar Producto</h3>
            ${opcionesHtml}
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button onclick="enviarReporte(${productoId})" style="flex: 1; padding: 12px; background: #f44336; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    Enviar Reporte
                </button>
                <button onclick="this.closest('[style*=\\'position: fixed\\']').remove()" style="flex: 1; padding: 12px; background: #ddd; color: #333; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    Cancelar
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function enviarReporte(productoId) {
    const motivoSeleccionado = document.querySelector('input[name="motivo"]:checked');
    const detalles = document.getElementById('detallesReporte').value;
    
    if (!motivoSeleccionado) {
        alert('Por favor selecciona un motivo');
        return;
    }
    
    const motivo = motivoSeleccionado.value + (detalles ? ': ' + detalles : '');
    
    fetch('api/denuncias.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            tipo: 'producto',
            elemento_id: productoId,
            motivo: motivo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('✓ Reporte enviado. Gracias por ayudarnos.', 'success');
            document.querySelector('[style*="position: fixed"]').remove();
        } else {
            mostrarNotificacion('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al enviar el reporte', 'error');
    });
}

// Sistema de notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    const colores = {
        success: '#4CAF50',
        error: '#f44336',
        info: '#2196F3',
        warning: '#ff9800'
    };
    
    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: ${colores[tipo]};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 999;
        animation: slideIn 0.3s ease;
        font-weight: 500;
        max-width: 350px;
    `;
    notif.textContent = mensaje;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Agregar animaciones CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Suprimir errores de extensiones del navegador
window.addEventListener('unhandledrejection', function(event) {
    // Ignorar errores de extensiones del navegador
    if (event.reason && event.reason.message && 
        event.reason.message.includes('message channel closed')) {
        event.preventDefault();
    }
});

// ========== PUNTOS DE ENCUENTRO Y MAPA ==========

// Inicializar mapa de Google Maps
function initMap() {
    cargarPuntosEncuentro(<?php echo $producto['id']; ?>);
}

// Cargar puntos de encuentro del producto
function cargarPuntosEncuentro(productoId) {
    const lista = document.getElementById('listaPuntosEncuentro');
    
    fetch(`api/puntos-encuentro.php?producto_id=${productoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.puntos_encuentro.length > 0) {
                puntosEncuentro = data.data.puntos_encuentro;
                mostrarPuntosEncuentro(puntosEncuentro);
                inicializarMapa(puntosEncuentro);
            } else {
                lista.innerHTML = `
                    <div class="no-puntos">
                        <i class="fas fa-map-marker-alt" style="font-size: 2em; color: #ccc; margin-bottom: 10px;"></i>
                        <p>El vendedor no ha agregado puntos de encuentro aún</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error al cargar puntos de encuentro:', error);
            lista.innerHTML = '<div class="error-puntos">Error al cargar ubicaciones</div>';
        });
}

// Mostrar lista de puntos de encuentro
function mostrarPuntosEncuentro(puntos) {
    const lista = document.getElementById('listaPuntosEncuentro');
    let html = '';
    
    puntos.forEach((punto, index) => {
        const isPrincipal = punto.es_principal == 1;
        html += `
            <div class="punto-encuentro-item ${isPrincipal ? 'principal' : ''}" onclick="centrarMarcador(${index})">
                <div class="punto-header">
                    <div class="punto-nombre">
                        <i class="fas fa-map-marker-alt" style="color: ${isPrincipal ? '#4CAF50' : '#A2CB8D'};"></i>
                        <strong>${punto.nombre}</strong>
                        ${isPrincipal ? '<span class="badge-principal">Principal</span>' : ''}
                    </div>
                </div>
                <div class="punto-direccion">
                    <i class="fas fa-location-arrow"></i> ${punto.direccion}
                </div>
                ${punto.descripcion ? `<div class="punto-descripcion">${punto.descripcion}</div>` : ''}
                ${punto.referencia ? `
                    <div class="punto-referencia">
                        <i class="fas fa-info-circle"></i> ${punto.referencia}
                    </div>
                ` : ''}
                ${punto.horario_sugerido ? `
                    <div class="punto-horario">
                        <i class="fas fa-clock"></i> ${punto.horario_sugerido}
                    </div>
                ` : ''}
                <button class="btn-ver-mapa" onclick="event.stopPropagation(); centrarMarcador(${index})">
                    <i class="fas fa-map"></i> Ver en mapa
                </button>
            </div>
        `;
    });
    
    lista.innerHTML = html;
}

// Inicializar mapa con Leaflet (OpenStreetMap)
function inicializarMapa(puntos) {
    if (puntos.length === 0) return;
    
    const mapaContainer = document.getElementById('mapaContainer');
    mapaContainer.style.display = 'block';
    
    // Centro del mapa (primer punto o principal)
    const puntoPrincipal = puntos.find(p => p.es_principal == 1) || puntos[0];
    const centro = [parseFloat(puntoPrincipal.latitud), parseFloat(puntoPrincipal.longitud)];
    
    try {
        // Crear mapa Leaflet
        mapaLeaflet = L.map('mapa').setView(centro, 13);
        
        // Agregar capa de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(mapaLeaflet);
        
        // Iconos personalizados
        const iconoPrincipal = L.divIcon({
            className: 'custom-marker-principal',
            html: '<div style="background: #4CAF50; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">★</div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });
        
        const iconoSecundario = (numero) => L.divIcon({
            className: 'custom-marker-secundario',
            html: `<div style="background: #A2CB8D; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px; border: 2px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">${numero}</div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });
        
        // Agregar marcadores
        puntos.forEach((punto, index) => {
            const position = [parseFloat(punto.latitud), parseFloat(punto.longitud)];
            const isPrincipal = punto.es_principal == 1;
            
            // Crear marcador
            const marcador = L.marker(position, {
                icon: isPrincipal ? iconoPrincipal : iconoSecundario(index + 1),
                title: punto.nombre
            }).addTo(mapaLeaflet);
            
            // Contenido del popup
            const popupContent = `
                <div style="min-width: 250px; padding: 5px;">
                    <h4 style="margin: 0 0 8px 0; color: #333; font-size: 16px;">
                        ${punto.nombre}
                        ${isPrincipal ? '<span style="color: #4CAF50; font-size: 0.8em;"> ⭐ Principal</span>' : ''}
                    </h4>
                    <p style="margin: 5px 0; color: #666; font-size: 13px;">
                        <i class="fas fa-location-arrow"></i> ${punto.direccion}
                    </p>
                    ${punto.descripcion ? `<p style="margin: 5px 0; color: #666; font-size: 12px; font-style: italic;">${punto.descripcion}</p>` : ''}
                    ${punto.referencia ? `<p style="margin: 5px 0; color: #888; font-size: 12px;"><i class="fas fa-info-circle"></i> ${punto.referencia}</p>` : ''}
                    ${punto.horario_sugerido ? `<p style="margin: 5px 0; color: #888; font-size: 12px;"><i class="fas fa-clock"></i> ${punto.horario_sugerido}</p>` : ''}
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${punto.latitud},${punto.longitud}" target="_blank" 
                       style="display: inline-block; margin-top: 10px; padding: 8px 12px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600;">
                        <i class="fas fa-directions"></i> Cómo llegar
                    </a>
                </div>
            `;
            
            // Bind popup
            marcador.bindPopup(popupContent);
            
            // Guardar referencia
            marcadores.push(marcador);
        });
        
        // Si hay múltiples puntos, ajustar vista para mostrarlos todos
        if (puntos.length > 1) {
            const group = L.featureGroup(marcadores);
            mapaLeaflet.fitBounds(group.getBounds().pad(0.1));
        }
        
    } catch (error) {
        console.error('Error al inicializar mapa:', error);
        document.getElementById('mapa').innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f5f5f5; color: #666; text-align: center; padding: 20px;">
                <div>
                    <i class="fas fa-map-marked-alt" style="font-size: 3em; color: #ccc; margin-bottom: 10px;"></i>
                    <p>No se pudo cargar el mapa</p>
                    <small>Usa los enlaces "Cómo llegar" en cada punto</small>
                </div>
            </div>
        `;
    }
}

// Centrar mapa en un marcador específico
function centrarMarcador(index) {
    if (!mapaLeaflet || !marcadores[index]) return;
    
    const marcador = marcadores[index];
    
    // Centrar en el marcador
    mapaLeaflet.setView(marcador.getLatLng(), 15, {
        animate: true,
        duration: 0.5
    });
    
    // Abrir popup
    setTimeout(() => {
        marcador.openPopup();
    }, 300);
    
    // Scroll suave al mapa
    document.getElementById('mapaContainer').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center' 
    });
}
</script>

<script src="js/producto.js"></script>

<?php require_once 'includes/footer.php'; ?>
