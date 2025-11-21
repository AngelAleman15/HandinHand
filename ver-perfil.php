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
$additional_css = '<link rel="stylesheet" href="css/perfil-usuario.css?v=20251110004" id="perfil-css">';
include 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Diseño profesional minimalista premium -->
<style>
:root {
    --perfil-color-principal: #6a994e;
    --perfil-color-secundario: #5a8440;
}

/* Scrollbar personalizada */
html::-webkit-scrollbar,
body::-webkit-scrollbar,
::-webkit-scrollbar {
    width: 12px;
    height: 12px;
}

html::-webkit-scrollbar-track,
body::-webkit-scrollbar-track,
::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

html::-webkit-scrollbar-thumb,
body::-webkit-scrollbar-thumb,
::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--perfil-color-principal), var(--perfil-color-secundario));
    border-radius: 10px;
    border: 2px solid #f1f1f1;
    transition: background 0.3s ease;
}

html::-webkit-scrollbar-thumb:hover,
body::-webkit-scrollbar-thumb:hover,
::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, var(--perfil-color-secundario), #4a7230);
}

/* Firefox scrollbar */
html,
body,
* {
    scrollbar-width: thin;
    scrollbar-color: var(--perfil-color-principal) #f1f1f1;
}

/* Reset y base */
body {
    background: #f5f5f7 !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
}

/* Container principal */
.perfil-container {
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Header rediseñado - verde HandinHand */
.perfil-header {
    background: linear-gradient(135deg, var(--perfil-color-principal) 0%, var(--perfil-color-secundario) 100%) !important;
    padding: 0 !important;
    position: relative !important;
    overflow: hidden !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    margin-bottom: 0 !important;
}

.perfil-header::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>') !important;
    opacity: 1 !important;
    pointer-events: none !important;
}

.perfil-info-principal {
    max-width: 1400px !important;
    margin: 0 auto !important;
    padding: 60px 40px !important;
    display: flex !important;
    align-items: flex-start !important;
    gap: 50px !important;
    position: relative !important;
    z-index: 10 !important;
}

/* Avatar - diseño minimalista con borde fino */
.perfil-avatar-section {
    position: relative !important;
}

.perfil-avatar {
    width: 180px !important;
    height: 180px !important;
    border-radius: 50% !important;
    border: 4px solid rgba(255,255,255,0.95) !important;
    object-fit: cover !important;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3) !important;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.perfil-avatar:hover {
    transform: scale(1.05) !important;
    box-shadow: 0 25px 70px rgba(0,0,0,0.4) !important;
}

/* Datos del perfil */
.perfil-datos {
    flex: 1 !important;
    padding-top: 20px !important;
}

.perfil-nombre {
    font-size: 3.5em !important;
    font-weight: 900 !important;
    color: white !important;
    margin: 0 0 12px 0 !important;
    letter-spacing: -1.5px !important;
    line-height: 1.1 !important;
}

.perfil-username {
    font-size: 1.4em !important;
    font-weight: 500 !important;
    color: rgba(255,255,255,0.7) !important;
    margin: 0 0 20px 0 !important;
    letter-spacing: 0.5px !important;
}

/* Rating moderno */
.perfil-rating {
    display: flex !important;
    align-items: center !important;
    gap: 15px !important;
    margin-bottom: 25px !important;
    flex-wrap: nowrap !important;
}

.stars-display {
    display: flex !important;
    align-items: center !important;
    gap: 4px !important;
}

.stars-display i {
    font-size: 22px !important;
}

.rating-value {
    font-size: 22px !important;
    font-weight: 800 !important;
    color: #FFD700 !important;
    margin-left: 10px !important;
}

.rating-count {
    font-size: 15px !important;
    color: rgba(255,255,255,0.6) !important;
    font-weight: 500 !important;
    white-space: nowrap !important;
}

/* Fecha de miembro minimalista */
.perfil-header p {
    color: rgba(255,255,255,0.6) !important;
    font-size: 15px !important;
    font-weight: 500 !important;
}

/* Botones de acción - diseño moderno mejorado */
.perfil-acciones {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 14px !important;
    margin-top: 30px !important;
    max-width: 600px !important;
}

.btn-accion {
    padding: 16px 28px !important;
    border-radius: 12px !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    letter-spacing: 0.3px !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: none !important;
    cursor: pointer !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 10px !important;
    position: relative !important;
    overflow: hidden !important;
    white-space: nowrap !important;
}

.btn-accion::before {
    content: '' !important;
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    width: 0 !important;
    height: 0 !important;
    border-radius: 50% !important;
    background: rgba(255,255,255,0.3) !important;
    transform: translate(-50%, -50%) !important;
    transition: width 0.6s, height 0.6s !important;
}

.btn-accion:hover::before {
    width: 300px !important;
    height: 300px !important;
}

.btn-accion i {
    font-size: 16px !important;
    position: relative !important;
    z-index: 1 !important;
}

.btn-accion span {
    position: relative !important;
    z-index: 1 !important;
}

.btn-agregar-amigo {
    background: linear-gradient(135deg, #6a994e 0%, #5a8440 100%) !important;
    color: white !important;
    box-shadow: 0 4px 20px rgba(106, 153, 78, 0.3) !important;
}

.btn-agregar-amigo:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 30px rgba(106, 153, 78, 0.5) !important;
}

.btn-enviar-mensaje {
    background: rgba(255,255,255,0.2) !important;
    color: white !important;
    backdrop-filter: blur(10px) !important;
    border: 2px solid rgba(255,255,255,0.4) !important;
}

.btn-enviar-mensaje:hover {
    background: rgba(255,255,255,0.3) !important;
    transform: translateY(-3px) !important;
    border-color: rgba(255,255,255,0.6) !important;
}

.btn-valorar {
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%) !important;
    color: #1a1a1a !important;
    box-shadow: 0 4px 20px rgba(255, 215, 0, 0.3) !important;
}

.btn-valorar:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 30px rgba(255, 215, 0, 0.5) !important;
}

.btn-denunciar {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
    color: white !important;
    box-shadow: 0 4px 20px rgba(220, 53, 69, 0.3) !important;
}

.btn-denunciar:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 30px rgba(220, 53, 69, 0.5) !important;
}

/* Estadísticas - tarjetas flotantes minimalistas */
.perfil-stats {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 30px !important;
    padding: 50px 40px !important;
    max-width: 1400px !important;
    margin: -50px auto 0 !important;
    position: relative !important;
    z-index: 20 !important;
}

.stat-item {
    background: white !important;
    padding: 40px 30px !important;
    border-radius: 20px !important;
    box-shadow: 0 2px 20px rgba(0,0,0,0.06) !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    text-align: center !important;
    border: 1px solid rgba(0,0,0,0.04) !important;
}

.stat-item:hover {
    transform: translateY(-8px) !important;
    box-shadow: 0 12px 40px rgba(0,0,0,0.12) !important;
}

.stat-icon {
    width: 70px !important;
    height: 70px !important;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-radius: 16px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 auto 20px !important;
    font-size: 28px !important;
    color: #1a1a1a !important;
    transition: all 0.3s ease !important;
}

.stat-item:hover .stat-icon {
    transform: scale(1.1) rotate(-5deg) !important;
    background: linear-gradient(135deg, var(--perfil-color-principal) 0%, var(--perfil-color-secundario) 100%) !important;
    color: white !important;
}

.stat-value {
    font-size: 42px !important;
    font-weight: 900 !important;
    color: #1a1a1a !important;
    margin: 0 0 8px 0 !important;
    letter-spacing: -1px !important;
}

.stat-label {
    font-size: 13px !important;
    font-weight: 700 !important;
    color: #6c757d !important;
    text-transform: uppercase !important;
    letter-spacing: 1.5px !important;
}

/* Contenido - secciones minimalistas */
.perfil-contenido {
    padding: 0 40px 80px !important;
    max-width: 1400px !important;
    margin: 0 auto !important;
}

.perfil-seccion {
    background: white !important;
    border-radius: 24px !important;
    padding: 50px !important;
    margin-bottom: 40px !important;
    box-shadow: 0 2px 20px rgba(0,0,0,0.06) !important;
    border: 1px solid rgba(0,0,0,0.04) !important;
}

.perfil-seccion h2 {
    font-size: 2em !important;
    font-weight: 900 !important;
    color: #1a1a1a !important;
    margin: 0 0 35px 0 !important;
    letter-spacing: -0.5px !important;
    display: flex !important;
    align-items: center !important;
    gap: 15px !important;
}

.perfil-seccion h2 i {
    font-size: 28px !important;
    color: #6c757d !important;
}

/* Grid de productos - estilo index.php */
.productos-grid {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 20px !important;
}

.producto-card {
    background: white !important;
    border-radius: 18px !important;
    overflow: hidden !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: 2px solid transparent !important;
    position: relative !important;
    cursor: pointer !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08) !important;
}

.producto-card::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background: linear-gradient(135deg, rgba(106, 153, 78, 0.05), rgba(106, 153, 78, 0.15)) !important;
    opacity: 0 !important;
    transition: opacity 0.4s ease !important;
    pointer-events: none !important;
    z-index: 1 !important;
}

.producto-card:hover::before {
    opacity: 1 !important;
}

.producto-card:hover {
    transform: translateY(-10px) !important;
    box-shadow: 0 18px 35px rgba(106, 153, 78, 0.2) !important;
    border-color: #6a994e !important;
}

.producto-imagen-wrapper {
    position: relative !important;
    overflow: hidden !important;
    background: #f8f9fa !important;
    height: 200px !important;
}

.producto-imagen {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.producto-card:hover .producto-imagen {
    transform: scale(1.15) !important;
}

.producto-info {
    padding: 16px !important;
    background: white !important;
    position: relative !important;
    z-index: 2 !important;
}

.producto-nombre {
    font-size: 15px !important;
    font-weight: 800 !important;
    color: #1a1a1a !important;
    margin: 0 0 10px 0 !important;
    line-height: 1.4 !important;
    display: -webkit-box !important;
    -webkit-line-clamp: 2 !important;
    -webkit-box-orient: vertical !important;
    overflow: hidden !important;
    min-height: 42px !important;
}

.producto-meta {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    flex-wrap: wrap !important;
}

.producto-categoria {
    display: inline-flex !important;
    align-items: center !important;
    font-size: 11px !important;
    color: #6a994e !important;
    background: rgba(106, 153, 78, 0.12) !important;
    padding: 5px 12px !important;
    border-radius: 16px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
}

.producto-estado {
    display: inline-flex !important;
    align-items: center !important;
    font-size: 11px !important;
    padding: 5px 12px !important;
    border-radius: 16px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
}

.producto-estado.disponible {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(40, 167, 69, 0.25)) !important;
    color: #28a745 !important;
    border: 1px solid rgba(40, 167, 69, 0.3) !important;
}

.producto-estado.intercambiado {
    background: linear-gradient(135deg, rgba(108, 117, 125, 0.15), rgba(108, 117, 125, 0.25)) !important;
    color: #6c757d !important;
    border: 1px solid rgba(108, 117, 125, 0.3) !important;
}

.producto-estado.reservado {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(255, 193, 7, 0.25)) !important;
    color: #ffc107 !important;
    border: 1px solid rgba(255, 193, 7, 0.3) !important;
}

.no-productos {
    text-align: center !important;
    padding: 80px 20px !important;
    color: #95a5a6 !important;
    font-size: 16px !important;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
    border-radius: 20px !important;
    border: 2px dashed #e0e0e0 !important;
}

.no-productos i {
    font-size: 4em !important;
    color: #e0e0e0 !important;
    margin-bottom: 20px !important;
    display: block !important;
}

/* Scrollbar para modales */
.modal-body {
    overflow-y: auto !important;
    max-height: 400px !important;
}

.modal-body::-webkit-scrollbar {
    width: 8px !important;
}

.modal-body::-webkit-scrollbar-track {
    background: #f8f9fa !important;
    border-radius: 10px !important;
}

.modal-body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--perfil-color-principal), var(--perfil-color-secundario)) !important;
    border-radius: 10px !important;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, var(--perfil-color-secundario), #4a7230) !important;
}

/* Responsive */
@media (max-width: 1200px) {
    .perfil-info-principal,
    .perfil-stats,
    .perfil-contenido {
        padding-left: 40px !important;
        padding-right: 40px !important;
    }
    
    .productos-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 18px !important;
    }
}

@media (max-width: 768px) {
    .perfil-nombre {
        font-size: 2.5em !important;
    }
    
    .perfil-avatar {
        width: 140px !important;
        height: 140px !important;
    }
    
    .perfil-acciones {
        grid-template-columns: 1fr !important;
    }
    
    .perfil-stats {
        grid-template-columns: 1fr !important;
    }
    
    .productos-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 15px !important;
    }
    
    .producto-imagen-wrapper {
        height: 180px !important;
    }
    
    .producto-nombre {
        font-size: 14px !important;
        min-height: 40px !important;
    }
}

@media (max-width: 480px) {
    .productos-grid {
        grid-template-columns: 1fr !important;
    }
    
    .producto-imagen-wrapper {
        height: 220px !important;
    }
}
</style>

<div class="perfil-container">
    <!-- Header del perfil -->
    <div class="perfil-header">
        <div class="perfil-cover"></div>
        <div class="perfil-info-principal">
            <div class="perfil-avatar-section">
                <img src="<?php echo !empty($perfil_usuario['avatar_path']) ? htmlspecialchars($perfil_usuario['avatar_path']) : 'img/usuario.svg'; ?>" 
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
                    <a href="producto.php?id=<?php echo $producto['id']; ?>" class="producto-card" style="text-decoration: none;">
                        <div class="producto-imagen-wrapper">
                            <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                 class="producto-imagen"
                                 onerror="this.src='img/productos/default.jpg'">
                        </div>
                        <div class="producto-info">
                            <h3 class="producto-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <div class="producto-meta">
                                <span class="producto-categoria">
                                    <?php echo htmlspecialchars($producto['categoria'] ?? 'Sin categoría'); ?>
                                </span>
                                <span class="producto-estado <?php echo strtolower($producto['estado']); ?>">
                                    <?php echo ucfirst($producto['estado']); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-productos">
                        <i class="fas fa-box-open"></i>
                        <p>Este usuario aún no ha publicado productos.</p>
                    </div>
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
                            <img src="<?php echo !empty($valoracion['valorador_avatar']) ? htmlspecialchars($valoracion['valorador_avatar']) : 'img/usuario.svg'; ?>" 
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
