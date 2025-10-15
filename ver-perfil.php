<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/database.php';

// Obtener el ID del usuario a ver
$user_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$user_id) {
    header('Location: index.php');
    exit;
}

// Obtener datos del usuario
$pdo = getConnection();

try {
    // Consultar datos del usuario con estadísticas
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            COALESCE(e.promedio_valoracion, 0) as promedio_valoracion,
            COALESCE(e.total_valoraciones, 0) as total_valoraciones,
            COALESCE(e.total_productos, 0) as total_productos,
            COALESCE(e.total_amigos, 0) as total_amigos
        FROM usuarios u
        LEFT JOIN estadisticas_usuario e ON u.id = e.usuario_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $perfil_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$perfil_usuario) {
        header('Location: index.php');
        exit;
    }
    
    // Verificar si el usuario actual está logueado
    $is_logged_in = isLoggedIn();
    $current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;
    $is_own_profile = ($current_user_id == $user_id);
    
    // Si está logueado y no es su propio perfil, verificar relaciones
    $es_amigo = false;
    $solicitud_pendiente = false;
    $solicitud_recibida = false;
    
    if ($is_logged_in && !$is_own_profile) {
        // Verificar si son amigos
        $stmt = $pdo->prepare("
            SELECT id FROM amistades 
            WHERE (usuario1_id = ? AND usuario2_id = ?) 
               OR (usuario1_id = ? AND usuario2_id = ?)
        ");
        $stmt->execute([$current_user_id, $user_id, $user_id, $current_user_id]);
        $es_amigo = $stmt->fetch() !== false;
        
        // Verificar solicitudes pendientes
        if (!$es_amigo) {
            // Solicitud enviada
            $stmt = $pdo->prepare("
                SELECT id FROM solicitudes_amistad 
                WHERE solicitante_id = ? AND receptor_id = ? AND estado = 'pendiente'
            ");
            $stmt->execute([$current_user_id, $user_id]);
            $solicitud_pendiente = $stmt->fetch() !== false;
            
            // Solicitud recibida
            $stmt = $pdo->prepare("
                SELECT id FROM solicitudes_amistad 
                WHERE solicitante_id = ? AND receptor_id = ? AND estado = 'pendiente'
            ");
            $stmt->execute([$user_id, $current_user_id]);
            $solicitud_recibida = $stmt->fetch() !== false;
        }
    }
    
    // Obtener productos del usuario
    $stmt = $pdo->prepare("
        SELECT * FROM productos 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 12
    ");
    $stmt->execute([$user_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener valoraciones recientes
    $stmt = $pdo->prepare("
        SELECT 
            v.*,
            u.fullname as valorador_nombre,
            u.username as valorador_username,
            u.avatar_path as valorador_avatar
        FROM valoraciones v
        JOIN usuarios u ON v.valorador_id = u.id
        WHERE v.usuario_id = ?
        ORDER BY v.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $valoraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error al cargar perfil: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

$page_title = $perfil_usuario['fullname'] . " - Perfil";
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/perfil-usuario.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="perfil-container">
    <!-- Header del perfil -->
    <div class="perfil-header">
        <div class="perfil-cover"></div>
        <div class="perfil-info-principal">
            <div class="perfil-avatar-section">
                <img src="<?php echo !empty($perfil_usuario['avatar_path']) ? htmlspecialchars($perfil_usuario['avatar_path']) : 'img/usuario.png'; ?>" 
                     alt="<?php echo htmlspecialchars($perfil_usuario['fullname']); ?>" 
                     class="perfil-avatar">
            </div>
            
            <div class="perfil-datos">
                <h1 class="perfil-nombre"><?php echo htmlspecialchars($perfil_usuario['fullname']); ?></h1>
                <p class="perfil-username">@<?php echo htmlspecialchars($perfil_usuario['username']); ?></p>
                
                <!-- Valoración con estrellas -->
                <div class="perfil-rating">
                    <div class="stars-display" data-rating="<?php echo $perfil_usuario['promedio_valoracion']; ?>">
                        <?php 
                        $rating = floatval($perfil_usuario['promedio_valoracion']);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($rating >= $i) {
                                echo '<i class="fas fa-star filled"></i>';
                            } elseif ($rating >= ($i - 0.5)) {
                                echo '<i class="fas fa-star-half-alt filled"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <span class="rating-value"><?php echo number_format($perfil_usuario['promedio_valoracion'], 1); ?></span>
                    <span class="rating-count">(<?php echo $perfil_usuario['total_valoraciones']; ?> valoraciones)</span>
                </div>
                
                <p class="perfil-fecha">
                    <i class="fas fa-calendar-alt"></i>
                    Miembro desde <?php 
                        $meses = [
                            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
                            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
                            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
                            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
                        ];
                        $fecha_en = date('F Y', strtotime($perfil_usuario['created_at']));
                        $partes = explode(' ', $fecha_en);
                        $mes_es = $meses[$partes[0]] ?? $partes[0];
                        echo $mes_es . ' ' . $partes[1];
                    ?>
                </p>
            </div>
            
            <!-- Botones de acción -->
            <?php if ($is_logged_in && !$is_own_profile): ?>
            <div class="perfil-acciones">
                <?php if ($es_amigo): ?>
                    <button class="btn-accion btn-amigo" onclick="eliminarAmistad(<?php echo $user_id; ?>)" title="Haz clic para dejar de ser amigos">
                        <i class="fas fa-user-check"></i>
                        Amigos
                    </button>
                <?php elseif ($solicitud_pendiente): ?>
                    <button class="btn-accion btn-pendiente" disabled>
                        <i class="fas fa-clock"></i>
                        Solicitud enviada
                    </button>
                <?php elseif ($solicitud_recibida): ?>
                    <button class="btn-accion btn-aceptar" onclick="aceptarSolicitud(<?php echo $user_id; ?>)">
                        <i class="fas fa-user-plus"></i>
                        Aceptar solicitud
                    </button>
                <?php else: ?>
                    <button class="btn-accion btn-agregar" onclick="enviarSolicitudAmistad(<?php echo $user_id; ?>)">
                        <i class="fas fa-user-plus"></i>
                        Agregar amigo
                    </button>
                <?php endif; ?>
                
                <a href="mensajeria.php?user=<?php echo $user_id; ?>" class="btn-accion btn-mensaje">
                    <i class="fas fa-comment-dots"></i>
                    Enviar mensaje
                </a>
                
                <button class="btn-accion btn-valorar" onclick="mostrarModalValorar()">
                    <i class="fas fa-star"></i>
                    Valorar
                </button>
                
                <button class="btn-accion btn-denunciar" onclick="mostrarModalDenunciar()">
                    <i class="fas fa-flag"></i>
                    Denunciar
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Estadísticas -->
    <div class="perfil-stats">
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $perfil_usuario['total_productos']; ?></div>
                <div class="stat-label">Productos</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $perfil_usuario['total_amigos']; ?></div>
                <div class="stat-label">Amigos</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $perfil_usuario['total_valoraciones']; ?></div>
                <div class="stat-label">Valoraciones</div>
            </div>
        </div>
    </div>
    
    <!-- Contenido del perfil -->
    <div class="perfil-contenido">
        <!-- Productos del usuario -->
        <div class="perfil-seccion">
            <h2 class="seccion-titulo">
                <i class="fas fa-box"></i>
                Productos
            </h2>
            <div class="productos-grid">
                <?php if (count($productos) > 0): ?>
                    <?php foreach ($productos as $producto): ?>
                    <div class="producto-card">
                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <div class="producto-info">
                            <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <p class="producto-estado estado-<?php echo $producto['estado']; ?>">
                                <?php echo ucfirst($producto['estado']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-productos">Este usuario aún no ha publicado productos.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Valoraciones -->
        <div class="perfil-seccion">
            <h2 class="seccion-titulo">
                <i class="fas fa-star"></i>
                Valoraciones recibidas
            </h2>
            <div class="valoraciones-lista">
                <?php if (count($valoraciones) > 0): ?>
                    <?php foreach ($valoraciones as $valoracion): ?>
                    <div class="valoracion-item" data-valoracion-id="<?php echo $valoracion['id']; ?>">
                        <div class="valoracion-header">
                            <img src="<?php echo !empty($valoracion['valorador_avatar']) ? htmlspecialchars($valoracion['valorador_avatar']) : 'img/usuario.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($valoracion['valorador_nombre']); ?>" 
                                 class="valoracion-avatar">
                            <div class="valoracion-info">
                                <h4><?php echo htmlspecialchars($valoracion['valorador_nombre']); ?></h4>
                                <div class="valoracion-rating">
                                    <?php 
                                    $rating = floatval($valoracion['puntuacion']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($rating >= $i) {
                                            echo '<i class="fas fa-star filled"></i>';
                                        } elseif ($rating >= ($i - 0.5)) {
                                            echo '<i class="fas fa-star-half-alt filled"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                    <span class="rating-number"><?php echo number_format($valoracion['puntuacion'], 1); ?></span>
                                </div>
                                <p class="valoracion-fecha">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($valoracion['created_at'])); ?>
                                </p>
                            </div>
                            <?php if ($is_logged_in && $current_user_id == $valoracion['valorador_id']): ?>
                            <div class="valoracion-actions">
                                <button class="btn-delete-valoracion" onclick="eliminarValoracion(<?php echo $valoracion['id']; ?>)" title="Eliminar mi valoración">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($valoracion['comentario'])): ?>
                        <div class="valoracion-comentario">
                            <i class="fas fa-quote-left"></i>
                            <p><?php echo nl2br(htmlspecialchars($valoracion['comentario'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-valoraciones">
                        <i class="far fa-star"></i>
                        Este usuario aún no tiene valoraciones.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para valorar -->
<div id="modalValorar" class="modal">
    <div class="modal-content modal-valorar">
        <div class="modal-header">
            <h3><i class="fas fa-star"></i> Valorar a <?php echo htmlspecialchars($perfil_usuario['fullname']); ?></h3>
            <button class="modal-close" onclick="cerrarModalValorar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="rating-selector">
                <label>Calificación:</label>
                <div class="stars-input" id="stars-input">
                    <i class="far fa-star" data-value="0.5"></i>
                    <i class="far fa-star" data-value="1"></i>
                    <i class="far fa-star" data-value="1.5"></i>
                    <i class="far fa-star" data-value="2"></i>
                    <i class="far fa-star" data-value="2.5"></i>
                    <i class="far fa-star" data-value="3"></i>
                    <i class="far fa-star" data-value="3.5"></i>
                    <i class="far fa-star" data-value="4"></i>
                    <i class="far fa-star" data-value="4.5"></i>
                    <i class="far fa-star" data-value="5"></i>
                </div>
                <span class="rating-display" id="rating-display">0.0</span>
            </div>
            <div class="comentario-input">
                <label for="comentario-valoracion">Comentario (opcional):</label>
                <textarea id="comentario-valoracion" placeholder="Comparte tu experiencia..." maxlength="500" rows="4"></textarea>
                <div class="char-counter"><span id="char-count">0</span>/500</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModalValorar()">Cancelar</button>
            <button class="btn-enviar" onclick="enviarValoracion()">Enviar valoración</button>
        </div>
    </div>
</div>

<!-- Modal para denunciar -->
<div id="modalDenunciar" class="modal">
    <div class="modal-content modal-denunciar">
        <div class="modal-header">
            <h3><i class="fas fa-flag"></i> Denunciar a <?php echo htmlspecialchars($perfil_usuario['fullname']); ?></h3>
            <button class="modal-close" onclick="cerrarModalDenunciar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="motivo-selector">
                <label>Motivo de la denuncia:</label>
                <select id="motivo-denuncia" class="form-select">
                    <option value="">Selecciona un motivo...</option>
                    <option value="spam">Spam o contenido no deseado</option>
                    <option value="fraude">Fraude o estafa</option>
                    <option value="contenido_inapropiado">Contenido inapropiado</option>
                    <option value="acoso">Acoso o intimidación</option>
                    <option value="suplantacion">Suplantación de identidad</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="descripcion-input">
                <label for="descripcion-denuncia">Descripción:</label>
                <textarea id="descripcion-denuncia" placeholder="Describe el motivo de tu denuncia..." maxlength="1000" rows="5" required></textarea>
                <div class="char-counter"><span id="denuncia-char-count">0</span>/1000</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModalDenunciar()">Cancelar</button>
            <button class="btn-enviar btn-denunciar-enviar" onclick="enviarDenuncia()">Enviar denuncia</button>
        </div>
    </div>
</div>

<script>
const USER_ID = <?php echo $user_id; ?>;
const IS_LOGGED_IN = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
</script>
<script src="js/perfil-usuario.js"></script>

<?php include 'includes/footer.php'; ?>
