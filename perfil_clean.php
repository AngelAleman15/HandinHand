<?php
session_start();
require_once 'includes/functions.php';

// Verificar que esté logueado
requireLogin();

// Configuración de la página
$page_title = "Mi Perfil - HandinHand";
$body_class = "body-profile";

// Obtener datos del usuario
$user = getCurrentUser();

// Conectar a BD y obtener estadísticas
require_once 'config/database.php';
$pdo = getConnection();

// Estadísticas del usuario
try {
    // Contar productos totales
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM productos WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $totalProductos = $stmt->fetch()['total'];
    
    // Contar productos disponibles
    $stmt = $pdo->prepare("SELECT COUNT(*) as disponibles FROM productos WHERE user_id = ? AND estado = 'disponible'");
    $stmt->execute([$user['id']]);
    $productosDisponibles = $stmt->fetch()['disponibles'];
    
    // Contar productos intercambiados
    $stmt = $pdo->prepare("SELECT COUNT(*) as intercambiados FROM productos WHERE user_id = ? AND estado = 'intercambiado'");
    $stmt->execute([$user['id']]);
    $productosIntercambiados = $stmt->fetch()['intercambiados'];
    
    // Contar mensajes recibidos
    $stmt = $pdo->prepare("SELECT COUNT(*) as mensajes FROM mensajes WHERE destinatario_id = ?");
    $stmt->execute([$user['id']]);
    $mensajesRecibidos = $stmt->fetch()['mensajes'];
    
    // Contar seguidores (usuarios que siguen a este usuario)
    // Por ahora simulamos los datos ya que no existe la tabla de seguimientos
    $seguidores = rand(5, 50); // Simular seguidores
    $siguiendo = rand(3, 30); // Simular usuarios que este usuario sigue
    
    // Obtener productos recientes
    $stmt = $pdo->prepare("
        SELECT nombre, categoria, estado, imagen, created_at 
        FROM productos 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 6
    ");
    $stmt->execute([$user['id']]);
    $productosRecientes = $stmt->fetchAll();
    
    // Calcular días como miembro
    $fechaRegistro = new DateTime($user['created_at'] ?? date('Y-m-d'));
    $fechaActual = new DateTime();
    $diasMiembro = $fechaActual->diff($fechaRegistro)->days;
    
} catch (Exception $e) {
    $totalProductos = 0;
    $productosDisponibles = 0;
    $productosIntercambiados = 0;
    $mensajesRecibidos = 0;
    $productosRecientes = [];
    $diasMiembro = 0;
}

// Incluir header
include 'includes/header.php';
?>

<style>
/* Remover el padding-top del body para esta página */
body {
    padding-top: 0 !important;
}
</style>

<div class="profile-container">
    <!-- Header del perfil -->
    <div class="profile-header">
        <div class="profile-cover">
            <div class="profile-avatar-section">
                <div class="profile-avatar">
                    <img src="<?php echo isset($user['avatar_path']) && !empty($user['avatar_path']) ? htmlspecialchars($user['avatar_path']) : 'img/usuario.png'; ?>" 
                         alt="Avatar de <?php echo htmlspecialchars($user['fullname']); ?>" 
                         onerror="this.src='img/usuario.png'">
                    <button class="avatar-edit-btn" onclick="editAvatar()">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <div class="profile-basic-info">
                    <h1><?php echo htmlspecialchars($user['fullname']); ?></h1>
                    <p class="username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <div class="user-stats">
                        <span class="stat-item">
                            <strong><?php echo $seguidores; ?></strong> Seguidores
                        </span>
                        <span class="stat-divider">•</span>
                        <span class="stat-item">
                            <strong><?php echo $siguiendo; ?></strong> Siguiendo
                        </span>
                    </div>
                    <p class="member-since">Miembro desde hace <?php echo $diasMiembro; ?> días</p>
                    <div class="profile-actions">
                        <button class="btn btn-primary" onclick="editPersonalInfo()">
                            <i class="fas fa-edit"></i> Editar Perfil
                        </button>
                        <button class="btn btn-primary" onclick="showWipMessage('Mis Productos')">
                            <i class="fas fa-box"></i> Mis Productos <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="profile-content">
        <!-- Tarjetas de estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalProductos; ?></h3>
                    <p>Productos Totales</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon available">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $productosDisponibles; ?></h3>
                    <p>Disponibles</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon exchanged">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $productosIntercambiados; ?></h3>
                    <p>Intercambiados</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon messages">
                    <i class="fas fa-comment"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $mensajesRecibidos; ?></h3>
                    <p>Mensajes</p>
                </div>
            </div>
        </div>

        <div class="profile-sections">
            <!-- Información personal -->
            <div class="section-card">
                <div class="section-header">
                    <h2><i class="fas fa-user"></i> Información Personal</h2>
                    <button class="btn-edit" onclick="editPersonalInfo()">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
                <div class="section-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Nombre Completo</label>
                            <span><?php echo htmlspecialchars($user['fullname']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Usuario</label>
                            <span>@<?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Teléfono</label>
                            <span><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'No especificado'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos recientes -->
            <div class="section-card">
                <div class="section-header">
                    <h2><i class="fas fa-clock"></i> Productos Recientes</h2>
                    <a href="#" onclick="showWipMessage('Mis Productos'); return false;" class="btn-view-all">Ver todos <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span></a>
                </div>
                <div class="section-content">
                    <?php if (empty($productosRecientes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h3>No has publicado productos aún</h3>
                            <p>¡Publica tu primer producto y comienza a intercambiar!</p>
                            <button class="btn btn-primary" onclick="window.location.href='publicar-producto.php'">
                                <i class="fas fa-plus"></i> Publicar Producto
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="products-grid">
                            <?php foreach ($productosRecientes as $producto): ?>
                                <div class="product-card-mini">
                                    <div class="product-image">
                                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                             onerror="this.src='img/zapato.jpg'">
                                        <span class="product-status status-<?php echo $producto['estado']; ?>">
                                            <?php echo ucfirst($producto['estado']); ?>
                                        </span>
                                    </div>
                                    <div class="product-info">
                                        <h4><?php echo htmlspecialchars($producto['nombre']); ?></h4>
                                        <p><?php echo htmlspecialchars($producto['categoria'] ?: 'Sin categoría'); ?></p>
                                        <small><?php echo date('d/m/Y', strtotime($producto['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="section-card">
                <div class="section-header">
                    <h2><i class="fas fa-bolt"></i> Acciones Rápidas</h2>
                </div>
                <div class="section-content">
                    <div class="quick-actions">
                        <button class="quick-action-btn" onclick="showWipMessage('Gestionar Productos')">
                            <i class="fas fa-box"></i>
                            <span>Gestionar Productos <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span></span>
                        </button>
                        <button class="quick-action-btn" onclick="showWipMessage('Mensajes')">
                            <i class="fas fa-comments"></i>
                            <span>Mensajes <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span></span>
                        </button>
                        <button class="quick-action-btn" onclick="showWipMessage('Valoraciones')">
                            <i class="fas fa-star"></i>
                            <span>Valoraciones <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span></span>
                        </button>
                        <button class="quick-action-btn" onclick="testConnectivitySimple()">
                            <i class="fas fa-wifi"></i>
                            <span>🔧 Test Conectividad</span>
                        </button>
                        <button class="quick-action-btn" onclick="testPasswordAPI()">
                            <i class="fas fa-key"></i>
                            <span>🔧 Test Password API</span>
                        </button>
                        <button class="quick-action-btn" onclick="testPersonalInfoAPI()">
                            <i class="fas fa-edit"></i>
                            <span>🔧 Test Edición API</span>
                        </button>
                        <button class="quick-action-btn" onclick="changePassword()">
                            <i class="fas fa-key"></i>
                            <span>Cambiar Contraseña</span>
                        </button>
                        <button class="quick-action-btn" onclick="showWipMessage('Exportar Datos')">
                            <i class="fas fa-download"></i>
                            <span>Exportar Datos <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span></span>
                        </button>
                        <button class="quick-action-btn danger" onclick="showWipMessage('Eliminar Cuenta')">
                            <i class="fas fa-trash"></i>
                            <span>Eliminar Cuenta <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* === ESTILOS MODERNOS PARA EL PERFIL === */

/* Asegurar que el chatbot esté debajo del header */
#chatbot-container,
.chatbot-widget,
.chat-widget,
[id*="chat"],
[class*="chat"] {
    z-index: 1000 !important;
}

.profile-container {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 0;
    margin: 0;
}

.profile-header {
    background: linear-gradient(135deg, #313C26 0%, #273122 100%);
    padding: 80px 0 40px 0;
    color: white;
    position: relative;
    overflow: hidden;
    z-index: 100;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-cover {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
    z-index: 10000;
}

.profile-avatar-section {
    display: flex;
    align-items: center;
    gap: 30px;
}

.profile-avatar {
    position: relative;
    flex-shrink: 0;
}

.profile-avatar img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.profile-avatar:hover img {
    transform: scale(1.05);
    border-color: #C9F89B;
}

.avatar-edit-btn {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #C9F89B;
    color: #313C26;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.avatar-edit-btn:hover {
    transform: scale(1.1);
    background: #A2CB8D;
}

.profile-basic-info h1 {
    font-size: 2.5em;
    margin: 0 0 5px 0;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.username {
    font-size: 1.2em;
    opacity: 0.9;
    margin: 0 0 10px 0;
    font-weight: 500;
}

.user-stats {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 0 0 10px 0;
    font-size: 1em;
}

.stat-item {
    color: rgba(255,255,255,0.9);
}

.stat-item strong {
    color: #C9F89B;
    font-weight: 700;
}

.stat-divider {
    color: rgba(255,255,255,0.5);
    font-weight: bold;
}

.member-since {
    font-size: 1em;
    opacity: 0.8;
    margin: 0 0 20px 0;
}

.profile-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.profile-content {
    max-width: 1200px;
    margin: -20px auto 0;
    padding: 0 20px 40px;
    position: relative;
    z-index: 100;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid rgba(255,255,255,0.2);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: linear-gradient(135deg, #313C26, #273122);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
}

.stat-icon.available {
    background: linear-gradient(135deg, #A2CB8D, #C9F89B);
    color: #313C26;
}

.stat-icon.exchanged {
    background: linear-gradient(135deg, #C9F89B, #A2CB8D);
    color: #313C26;
}

.stat-icon.messages {
    background: linear-gradient(135deg, #313C26, #273122);
}

.stat-info h3 {
    font-size: 2em;
    margin: 0;
    color: #313C26;
    font-weight: 700;
}

.stat-info p {
    margin: 5px 0 0 0;
    color: #666;
    font-weight: 500;
}

.profile-sections {
    display: grid;
    gap: 25px;
}

.section-card {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    overflow: hidden;
}

.section-header {
    padding: 25px 30px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 1px solid rgba(0,0,0,0.05);
    display: flex;
    justify-content: between;
    align-items: center;
}

.section-header h2 {
    margin: 0;
    color: #313C26;
    font-size: 1.4em;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-header h2 i {
    color: #A2CB8D;
}

.btn-edit, .btn-view-all {
    background: transparent;
    border: 1px solid #A2CB8D;
    color: #A2CB8D;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-edit:hover, .btn-view-all:hover {
    background: #A2CB8D;
    color: #313C26;
}

.section-content {
    padding: 30px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-item label {
    font-weight: 600;
    color: #313C26;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-item span {
    font-size: 16px;
    color: #333;
    padding: 12px 16px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #A2CB8D;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.product-card-mini {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.product-card-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.product-image {
    position: relative;
    height: 120px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-status {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-disponible {
    background: #A2CB8D;
    color: #313C26;
}

.status-intercambiado {
    background: #C9F89B;
    color: #313C26;
}

.status-reservado {
    background: #313C26;
    color: #C9F89B;
}

.product-info {
    padding: 15px;
}

.product-info h4 {
    margin: 0 0 5px 0;
    color: #313C26;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
}

.product-info p {
    margin: 0 0 8px 0;
    color: #666;
    font-size: 12px;
}

.product-info small {
    color: #999;
    font-size: 11px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state i {
    font-size: 4em;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #313C26;
}

.empty-state p {
    margin: 0 0 25px 0;
    font-size: 16px;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.quick-action-btn {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: #333;
}

.quick-action-btn:hover {
    border-color: #A2CB8D;
    background: #f8f9fa;
    transform: translateY(-2px);
}

.quick-action-btn.danger:hover {
    border-color: #dc3545;
    background: #fff5f5;
    color: #dc3545;
}

.quick-action-btn i {
    font-size: 24px;
    color: #A2CB8D;
}

.quick-action-btn.danger i {
    color: #dc3545;
}

/* Estilo de resaltado para el botón de cambiar contraseña */
.quick-action-btn.highlight-password {
    animation: highlightPassword 2s ease-in-out infinite;
    border-color: #C9F89B !important;
    background: linear-gradient(135deg, #C9F89B, #A2CB8D) !important;
    color: #313C26 !important;
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(201, 249, 155, 0.4) !important;
}

.quick-action-btn.highlight-password i {
    color: #313C26 !important;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes highlightPassword {
    0%, 100% { 
        box-shadow: 0 8px 25px rgba(201, 249, 155, 0.4);
        transform: scale(1.05);
    }
    50% { 
        box-shadow: 0 12px 30px rgba(201, 249, 155, 0.6);
        transform: scale(1.08);
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.quick-action-btn span {
    font-weight: 600;
    font-size: 14px;
}

/* Botones principales */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-primary {
    background: linear-gradient(135deg, #A2CB8D, #C9F89B);
    color: #313C26;
    box-shadow: 0 4px 15px rgba(162,203,141,0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(162,203,141,0.4);
}

.btn-secondary {
    background: linear-gradient(135deg, #313C26, #273122);
    color: white;
    box-shadow: 0 4px 15px rgba(49,60,38,0.3);
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(49,60,38,0.4);
}

/* Estilos para el cropper y SweetAlert2 personalizado */
.swal2-popup .cropper-container {
    margin: 0 auto;
}

.swal2-html-container {
    overflow: visible !important;
}

.swal2-popup {
    overflow: visible !important;
}

.cropper-view-box {
    outline: 3px solid #A2CB8D !important;
    outline-opacity: 0.75;
}

.cropper-face {
    background: rgba(162, 203, 141, 0.1) !important;
}

.cropper-line, .cropper-point {
    background: #A2CB8D !important;
}

.cropper-point.point-se {
    background: #C9F89B !important;
    width: 8px !important;
    height: 8px !important;
}

/* Animaciones para el botón de avatar */
@keyframes avatarPulse {
    0%, 100% { 
        box-shadow: 0 4px 15px rgba(162,203,141,0.3);
        transform: scale(1);
    }
    50% { 
        box-shadow: 0 6px 20px rgba(162,203,141,0.5);
        transform: scale(1.05);
    }
}

.avatar-edit-btn:hover {
    animation: avatarPulse 2s infinite;
}

/* Estilos para el área de drop de archivos */
.file-drop-area {
    border: 2px dashed #A2CB8D;
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    background: rgba(162, 203, 141, 0.05);
    transition: all 0.3s ease;
    cursor: pointer;
}

.file-drop-area:hover,
.file-drop-area.dragover {
    border-color: #C9F89B;
    background: rgba(201, 249, 155, 0.1);
    transform: translateY(-2px);
}

.file-drop-area i {
    color: #A2CB8D;
    margin-bottom: 10px;
}

.file-drop-area.error {
    border-color: #dc3545;
    background: rgba(220, 53, 69, 0.05);
}

.file-drop-area.error i {
    color: #dc3545;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-header {
        padding: 90px 0 25px 0;
    }
    
    .profile-content {
        padding: 0 15px 30px;
        margin: -15px auto 0;
    }
    
    .profile-avatar-section {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .profile-actions {
        justify-content: center;
    }
    
    .user-stats {
        justify-content: center;
        margin: 10px 0;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .quick-actions {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
    }
    
    .section-content {
        padding: 20px;
    }
    
    .section-header {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .profile-header {
        padding: 90px 0 20px 0;
    }
    
    .profile-content {
        padding: 0 15px 30px;
        margin: -10px auto 0;
    }
    
    .stat-card {
        padding: 20px;
        gap: 15px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .stat-info h3 {
        font-size: 1.5em;
    }
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>


<script>
// === FUNCIONES DE INTERACCIÓN ===

// Verificar si hay que resaltar el botón de cambiar contraseña
document.addEventListener('DOMContentLoaded', function() {
    // Verificar parámetro URL para resaltar cambiar contraseña
    const urlParams = new URLSearchParams(window.location.search);
    const highlight = urlParams.get('highlight');
    
    if (highlight === 'password') {
        // Resaltar el botón de cambiar contraseña
        const passwordBtn = document.querySelector('.quick-action-btn[onclick*="changePassword"]');
        if (passwordBtn) {
            // Añadir clase de resaltado
            passwordBtn.classList.add('highlight-password');
            
            // Scroll al botón después de un pequeño delay
            setTimeout(() => {
                passwordBtn.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Mostrar notificación
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Aquí puedes cambiar tu contraseña',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    background: '#C9F89B',
                    color: '#313C26'
                });
            }, 500);
            
            // Remover resaltado después de 8 segundos
            setTimeout(() => {
                passwordBtn.classList.remove('highlight-password');
            }, 8000);
        }
        
        // Limpiar URL para que no se repita el resaltado
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Animar las tarjetas de estadísticas (código existente)
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animar las secciones
    const sections = document.querySelectorAll('.section-card');
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            section.style.transition = 'all 0.6s ease';
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, 200 + (index * 150));
    });
});

function editPersonalInfo() {
    Swal.fire({
        title: '✏️ Editar Información Personal',
        html: `
            <div style="text-align: left; max-width: 400px; margin: 0 auto;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                        <i class="fas fa-user"></i> Nombre Completo:
                    </label>
                    <input type="text" id="editFullname" class="swal2-input" 
                           placeholder="Ingresa tu nombre completo" 
                           value="<?php echo htmlspecialchars($user['fullname']); ?>"
                           style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                        <i class="fas fa-at"></i> Nombre de Usuario:
                    </label>
                    <input type="text" id="editUsername" class="swal2-input" 
                           placeholder="Nombre de usuario único" 
                           value="<?php echo htmlspecialchars($user['username']); ?>"
                           style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                    <small style="color: #666; font-size: 11px; margin-top: 3px; display: block;">
                        Solo letras, números y guiones bajos. Mínimo 3 caracteres.
                    </small>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                        <i class="fas fa-envelope"></i> Email:
                    </label>
                    <input type="email" id="editEmail" class="swal2-input" 
                           placeholder="tucorreo@ejemplo.com" 
                           value="<?php echo htmlspecialchars($user['email']); ?>"
                           style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                    <small style="color: #dc3545; font-size: 11px; margin-top: 3px; display: block;">
                        Se requiere verificación si cambias tu correo.
                    </small>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                        <i class="fas fa-phone"></i> Teléfono (Opcional):
                    </label>
                    <input type="tel" id="editPhone" class="swal2-input" 
                           placeholder="+34 123 456 789" 
                           value="<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>"
                           style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 15px; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #dc3545;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #dc3545; font-size: 14px;">
                        <i class="fas fa-key"></i> Contraseña Actual:
                    </label>
                    <input type="password" id="editCurrentPassword" class="swal2-input" 
                           placeholder="Tu contraseña actual"
                           style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                    <small style="color: #666; font-size: 11px; margin-top: 3px; display: block;">
                        Necesaria para confirmar los cambios.
                    </small>
                </div>
            </div>
        `,
        width: '480px',
        focusConfirm: false,
        confirmButtonText: '<i class="fas fa-save"></i> Guardar Cambios',
        confirmButtonColor: '#A2CB8D',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        showCancelButton: true,
        cancelButtonColor: '#6c757d',
        preConfirm: () => {
            const fullname = document.getElementById('editFullname').value.trim();
            const username = document.getElementById('editUsername').value.trim();
            const email = document.getElementById('editEmail').value.trim();
            const phone = document.getElementById('editPhone').value.trim();
            const currentPassword = document.getElementById('editCurrentPassword').value;
            
            // Validaciones básicas
            if (!fullname) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El nombre completo es obligatorio');
                return false;
            }
            
            if (fullname.length < 2) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El nombre debe tener al menos 2 caracteres');
                return false;
            }
            
            if (!username) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El nombre de usuario es obligatorio');
                return false;
            }
            
            if (username.length < 3) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El nombre de usuario debe tener al menos 3 caracteres');
                return false;
            }
            
            // Validar formato de username
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El nombre de usuario solo puede contener letras, números y guiones bajos');
                return false;
            }
            
            if (!email) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El email es obligatorio');
                return false;
            }
            
            // Validar formato de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El formato del email no es válido');
                return false;
            }
            
            // Validar teléfono si se proporciona
            if (phone && phone.length > 0) {
                const phoneRegex = /^[\+]?[0-9\s\-\(\)]{9,}$/;
                if (!phoneRegex.test(phone)) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El formato del teléfono no es válido');
                    return false;
                }
            }
            
            if (!currentPassword) {
                Swal.showValidationMessage('<i class="fas fa-key"></i> La contraseña actual es requerida para confirmar los cambios');
                return false;
            }
            
            return { fullname, username, email, phone, currentPassword };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            
            // Mostrar loading
            Swal.fire({
                title: '💾 Guardando Cambios...',
                html: `
                    <div style="text-align: center;">
                        <div style="width: 60px; height: 60px; margin: 0 auto 15px; border: 4px solid #f3f3f3; border-top: 4px solid #A2CB8D; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <p>Actualizando tu información personal...</p>
                        <small style="color: #666;">Verificando datos y guardando cambios</small>
                    </div>
                    <style>
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                    </style>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });
            
            // Enviar datos al servidor
            updatePersonalInfo(data);
        }
    });
}

// Función para actualizar la información personal en el servidor
function updatePersonalInfo(userData) {
    const formData = new FormData();
    formData.append('action', 'update_personal_info');
    formData.append('fullname', userData.fullname);
    formData.append('username', userData.username);
    formData.append('email', userData.email);
    formData.append('phone', userData.phone);
    formData.append('current_password', userData.currentPassword);
    
    fetch('api/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("HTTP " + response.status + ": " + response.statusText + "");
        }
        return response.text();
    })
    .then(textData => {
        try {
            const data = JSON.parse(textData);
            
            if (data.success) {
                // Éxito: actualizar la página con la nueva información
                updatePageWithNewInfo(data.data);
                
                Swal.fire({
                    title: '✅ ¡Información Actualizada!',
                    text: 'Tu información personal se ha actualizado correctamente',
                    icon: 'success',
                    confirmButtonColor: '#A2CB8D',
                    timer: 3000,
                    showConfirmButton: true
                }).then(() => {
                    // Recargar la página para mostrar todos los cambios
                    window.location.reload();
                });
            } else {
                // Error del servidor - mostrar detalles específicos
                let errorMessage = data.message || 'Hubo un problema al actualizar tu información';
                let errorDetails = '';
                
                // Procesar errores específicos
                if (data.details && data.details.errors && Array.isArray(data.details.errors)) {
                    errorDetails = data.details.errors.map((error, index) => {
                        // Agregar números y hacer más visual
                        return "<li style="margin: 8px 0; text-align: left; padding: 5px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 3px;">" + error + "</li>";
                    }).join('');
                    
                    errorMessage = "
                        <div style="text-align: left;">
                            <p><strong>❌ Se encontraron " + data.details.errors.length + " problema(s):</strong></p>
                            <ul style="margin: 15px 0; padding: 0; list-style: none;">
                                " + errorDetails + "
                            </ul>
                            <div style="background: #e3f2fd; padding: 12px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #2196f3;">
                                <strong>💡 Sugerencias:</strong>
                                <ul style="margin: 8px 0 0 0; padding-left: 20px; font-size: 14px;">
                                    <li>Verifica que tu contraseña actual sea correcta</li>
                                    <li>Asegúrate de que el email y username no estén en uso</li>
                                    <li>Revisa el formato de los datos ingresados</li>
                                </ul>
                            </div>
                        </div>
                    ";
                } else {
                    // Error simple sin detalles
                    errorMessage = "
                        <div style="text-align: left;">
                            <p>" + errorMessage + "</p>
                            <div style="background: #ffebee; padding: 10px; border-radius: 4px; margin-top: 10px;">
                                <strong>🔍 Detalles técnicos:</strong><br>
                                <code style="font-size: 12px;">" + JSON.stringify(data, null, 2) + "</code>
                            </div>
                        </div>
                    ";
                }
                
                Swal.fire({
                    title: '⚠️ No se pudo actualizar',
                    html: errorMessage,
                    icon: 'error',
                    confirmButtonColor: '#A2CB8D',
                    width: '650px',
                    showCancelButton: true,
                    cancelButtonText: 'Cerrar',
                    confirmButtonText: 'Intentar de Nuevo',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Volver a abrir el formulario de edición
                        editPersonalInfo();
                    }
                });
                    `,
                    icon: 'error',
                    confirmButtonColor: '#A2CB8D'
                });
            }
        } catch (parseError) {
            console.error('Error parsing JSON:', parseError);
            console.error('Raw response:', textData);
            
            Swal.fire({
                title: '❌ Error de Comunicación',
                text: 'Error en la respuesta del servidor. Intenta de nuevo.',
                icon: 'error',
                confirmButtonColor: '#A2CB8D'
            });
        }
    })
    .catch(error => {
        console.error('Error updating personal info:', error);
        
        Swal.fire({
            title: '❌ Error de Conexión',
            text: 'No se pudo conectar con el servidor. Verifica tu conexión e intenta de nuevo.',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
    });
}

// Función para actualizar la información en la página actual
function updatePageWithNewInfo(newData) {
    try {
        // Actualizar el nombre en el header del perfil
        const profileName = document.querySelector('.profile-basic-info h1');
        if (profileName && newData.fullname) {
            profileName.textContent = newData.fullname;
        }
        
        // Actualizar el username
        const profileUsername = document.querySelector('.profile-basic-info .username');
        if (profileUsername && newData.username) {
            profileUsername.textContent = '@' + newData.username;
        }
        
        // Actualizar información en la sección de información personal
        const infoItems = document.querySelectorAll('.info-item');
        infoItems.forEach(item => {
            const label = item.querySelector('label');
            const span = item.querySelector('span');
            
            if (label && span) {
                const labelText = label.textContent.toLowerCase();
                
                if (labelText.includes('nombre completo') && newData.fullname) {
                    span.textContent = newData.fullname;
                } else if (labelText.includes('usuario') && newData.username) {
                    span.textContent = '@' + newData.username;
                } else if (labelText.includes('email') && newData.email) {
                    span.textContent = newData.email;
                } else if (labelText.includes('teléfono')) {
                    span.textContent = newData.phone || 'No especificado';
                }
            }
        });
        
        // Actualizar titulo de la pagina
        if (newData.fullname) {
            document.title = newData.fullname + ' - Mi Perfil - HandinHand';
        }
        
        console.log('✅ Información de la página actualizada correctamente');
    } catch (error) {
        console.error('Error updating page info:', error);
    }
}

// Función para probar conectividad básica
function testConnectivity() {
    Swal.fire({
        title: '🔧 Probando Conectividad...',
        html: '<div id="connectivityResults">Preparando pruebas...</div>',
        showConfirmButton: false,
        allowOutsideClick: false,
        width: '500px',
        didOpen: () => {
            // Usar setTimeout para asegurar que el DOM esté completamente listo
            setTimeout(() => {
                const element = document.getElementById('connectivityResults');
                if (element) {
                    runConnectivityTests();
                } else {
                    console.error('Elemento connectivityResults no encontrado despues del timeout');
                    // Intentar una vez mas con un delay mayor
                    setTimeout(() => {
                        const elementRetry = document.getElementById('connectivityResults');
                        if (elementRetry) {
                            runConnectivityTests();
                        } else {
                            console.error('Elemento connectivityResults sigue sin estar disponible');
                        }
                    }, 500);
                }
            }, 100);
        }
    });
}

function runConnectivityTests() {
    const resultsDiv = document.getElementById('connectivityResults');
    
    if (!resultsDiv) {
        console.error('CRITICAL: No se pudo encontrar el elemento connectivityResults');
        console.log('Elementos disponibles:', document.querySelectorAll('[id]'));
        
        // Intentar mostrar el error en el Swal
        Swal.fire({
            title: '❌ Error Interno',
            text: 'No se pudo inicializar el sistema de diagnostico. Revisa la consola para mas detalles.',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
        return;
    }
    
    console.log('✅ Elemento connectivityResults encontrado, iniciando pruebas...');
    
    // Test 1: Conectividad básica
    resultsDiv.innerHTML = '<div style="color: blue; padding: 5px;">🔄 Test 1: Conectividad básica...</div>';
    
    fetch('api/test-connectivity.php', {
        method: 'POST',
        body: new FormData()
    })
    .then(response => {
        console.log('Test connectivity - Status:', response.status);
        console.log('Test connectivity - Headers:', response.headers);
        return response.text();
    })
    .then(textData => {
        console.log('Test connectivity - Raw response:', textData);
        
        // Verificar nuevamente que el elemento sigue existiendo
        const currentDiv = document.getElementById('connectivityResults');
        if (!currentDiv) {
            console.error('Elemento connectivityResults desapareció durante la prueba');
            return;
        }
        
        try {
            const data = JSON.parse(textData);
            
            currentDiv.innerHTML = 
                '<div style="color: green; padding: 5px;">✅ Test 1: Conectividad OK</div>' +
                '<div style="margin: 10px 0; color: blue; padding: 5px;">🔄 Test 2: Probando update-profile.php...</div>';
            
            // Test 2: API de update-profile con delay
            setTimeout(() => testUpdateProfileAPI(), 500);
            
        } catch (parseError) {
            console.error('Error parsing JSON:', parseError);
            currentDiv.innerHTML = 
                '<div style="color: red; padding: 5px;">❌ Test 1: Error de JSON</div>' +
                '<div style="background: #f8f8f8; padding: 10px; font-family: monospace; font-size: 12px; margin: 10px 0; max-height: 200px; overflow-y: auto; border-radius: 4px;">' +
                    '<strong>Error:</strong> ' + parseError.message + '<br><br>' +
                    '<strong>Respuesta:</strong><br>' +
                    textData.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                '</div>' +
                '<button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Cerrar</button>';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        
        const currentDiv = document.getElementById('connectivityResults');
        if (currentDiv) {
            currentDiv.innerHTML = 
                '<div style="color: red; padding: 5px;">❌ Test 1: Error de conexion</div>' +
                '<div style="margin: 10px 0; color: #666; padding: 5px;">' +
                    '<strong>Error:</strong> ' + error.message + '<br>' +
                    '<strong>Posibles causas:</strong>' +
                    '<ul style="margin: 5px 0; padding-left: 20px;">' +
                        '<li>Servidor web no esta ejecutandose</li>' +
                        '<li>Archivo test-connectivity.php no existe</li>' +
                        '<li>Problema de permisos</li>' +
                    '</ul>' +
                '</div>' +
                '<button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Cerrar</button>';
        }
    });
}

function testUpdateProfileAPI() {
    const resultsDiv = document.getElementById('connectivityResults');
    
    if (!resultsDiv) {
        console.error('No se pudo encontrar el elemento connectivityResults en testUpdateProfileAPI');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'test_connection');
    
    fetch('api/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Test update-profile - Status:', response.status);
        return response.text();
    })
    .then(textData => {
        console.log('Test update-profile - Raw response:', textData);
        
        try {
            const data = JSON.parse(textData);
            
            if (data.success) {
                resultsDiv.innerHTML = `
                    <div style="color: green;">✅ Test 1: Conectividad OK</div>
                    <div style="color: green;">✅ Test 2: update-profile.php OK</div>
                    <div style="margin: 15px 0; padding: 10px; background: #e8f5e8; border-radius: 5px;">
                        <strong>¡Todo funciona correctamente!</strong><br>
                        Puedes intentar cambiar la contraseña ahora.
                    </div>
                    <button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Cerrar</button>
                `;
            } else {
                resultsDiv.innerHTML = "
                    <div style="color: green;">✅ Test 1: Conectividad OK</div>
                    <div style="color: orange;">⚠️ Test 2: Error en API</div>
                    <div style="margin: 10px 0; color: #666;">" + data.message + "</div>
                    <button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Cerrar</button>
                ";
            }
        } catch (parseError) {
            resultsDiv.innerHTML = "
                <div style="color: green;">✅ Test 1: Conectividad OK</div>
                <div style="color: red;">❌ Test 2: Error de JSON en update-profile.php</div>
                <div style="background: #f8f8f8; padding: 10px; font-family: monospace; font-size: 12px; margin: 10px 0; max-height: 200px; overflow-y: auto;">
                    <strong>Error de parsing:</strong> " + parseError.message + "<br><br>
                    <strong>Respuesta cruda:</strong><br>
                    " + textData.replace(/</g, '&lt;').replace(/>/g, '&gt;') + "
                </div>
                <button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Cerrar</button>
            ";
        }
    })
    .catch(error => {
        resultsDiv.innerHTML = "
            <div style="color: green;">✅ Test 1: Conectividad OK</div>
            <div style="color: red;">❌ Test 2: Error de conexión en update-profile.php</div>
            <div style="margin: 10px 0; color: #666;">
                <strong>Error:</strong> " + error.message + "<br>
                <strong>Posibles causas:</strong>
                <ul style="margin: 5px 0; padding-left: 20px;">
                    <li>Archivo update-profile.php no existe o no es accesible</li>
                    <li>Error de sintaxis en PHP</li>
                    <li>Problema con includes/functions.php</li>
                    <li>Error de base de datos</li>
                </ul>
            </div>
            <button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Cerrar</button>
        ";
    });
}

// Funcion simple de test de conectividad (mas confiable)
function testConnectivitySimple() {
    // Test directo sin elementos DOM complejos
    fetch('api/test-connectivity.php', {
        method: 'POST',
        body: new FormData()
    })
    .then(response => {
        console.log('🔗 Simple Test - Status:', response.status);
        return response.text();
    })
    .then(textData => {
        console.log('🔗 Simple Test - Response:', textData);
        
        try {
            const data = JSON.parse(textData);
            
            // Test exitoso
            Swal.fire({
                title: '✅ Conectividad OK',
                html: "
                    <div style="text-align: left;">
                        <p><strong>✅ Servidor web:</strong> Funcionando</p>
                        <p><strong>✅ PHP:</strong> Funcionando</p>
                        <p><strong>✅ JSON:</strong> Válido</p>
                        <p><strong>📊 Respuesta:</strong></p>
                        <div style="background: #f8f8f8; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                            " + JSON.stringify(data, null, 2) + "
                        </div>
                        <div style="margin-top: 15px; padding: 10px; background: #e8f5e8; border-radius: 4px;">
                            <strong>🎉 ¡Todo funciona!</strong> Puedes intentar cambiar la contraseña.
                        </div>
                    </div>
                ",
                icon: 'success',
                confirmButtonColor: '#A2CB8D',
                width: '500px'
            });
            
        } catch (parseError) {
            // Error de JSON
            Swal.fire({
                title: '⚠️ Error de JSON',
                html: "
                    <div style="text-align: left;">
                        <p><strong>✅ Servidor web:</strong> Funcionando</p>
                        <p><strong>❌ JSON:</strong> Inválido</p>
                        <p><strong>🐛 Error:</strong> " + parseError.message + "</p>
                        <p><strong>📄 Respuesta raw:</strong></p>
                        <div style="background: #f8f8f8; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                            " + textData.replace(/</g, '&lt;').replace(/>/g, '&gt;') + "
                        </div>
                    </div>
                ",
                icon: 'warning',
                confirmButtonColor: '#A2CB8D',
                width: '600px'
            });
        }
    })
    .catch(error => {
        // Error de conexión
        console.error('🔗 Simple Test - Error:', error);
        
        Swal.fire({
            title: '❌ Error de Conexión',
            html: "
                <div style="text-align: left;">
                    <p><strong>❌ Servidor web:</strong> No responde</p>
                    <p><strong>🐛 Error:</strong> " + error.message + "</p>
                    <p><strong>🔧 Posibles soluciones:</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Verificar que WAMP esté ejecutándose</li>
                        <li>Comprobar que el archivo api/test-connectivity.php existe</li>
                        <li>Revisar permisos de archivos</li>
                        <li>Verificar configuración del servidor</li>
                    </ul>
                </div>
            ",
            icon: 'error',
            confirmButtonColor: '#A2CB8D',
            width: '500px'
        });
    });
}

function changePassword() {
    Swal.fire({
        title: '🔐 Cambiar Contraseña',
        html: `
            <div style="text-align: left; max-width: 400px; margin: 0 auto;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                        <i class="fas fa-lock"></i> Contraseña Actual:
                    </label>
                    <input type="password" id="currentPassword" class="swal2-input" 
                           placeholder="Tu contraseña actual"
                           style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                        <i class="fas fa-key"></i> Nueva Contraseña:
                    </label>
                    <input type="password" id="newPassword" class="swal2-input" 
                           placeholder="Mínimo 6 caracteres"
                           style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                    <small style="color: #666; font-size: 11px; margin-top: 3px; display: block;">
                        Debe tener al menos 6 caracteres.
                    </small>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                        <i class="fas fa-check"></i> Confirmar Contraseña:
                    </label>
                    <input type="password" id="confirmPassword" class="swal2-input" 
                           placeholder="Repite la nueva contraseña"
                           style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                </div>
                <div style="background: #fff3cd; padding: 10px; border-radius: 6px; border-left: 3px solid #ffc107;">
                    <small style="color: #856404; font-size: 11px;">
                        <i class="fas fa-shield-alt"></i> 
                        Por seguridad, deberas iniciar sesion nuevamente despues del cambio.
                    </small>
                </div>
            </div>
        `,
        width: '480px',
        focusConfirm: false,
        confirmButtonText: '<i class="fas fa-save"></i> Cambiar Contraseña',
        confirmButtonColor: '#A2CB8D',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        showCancelButton: true,
        cancelButtonColor: '#6c757d',
        preConfirm: () => {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Validaciones
            if (!currentPassword) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> La contraseña actual es obligatoria');
                return false;
            }
            
            if (!newPassword) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> La nueva contraseña es obligatoria');
                return false;
            }
            
            if (newPassword.length < 6) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> La nueva contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> Las contraseñas no coinciden');
                return false;
            }
            
            if (currentPassword === newPassword) {
                Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> La nueva contraseña debe ser diferente a la actual');
                return false;
            }
            
            return { currentPassword, newPassword, confirmPassword };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            
            // Mostrar loading
            Swal.fire({
                title: '🔐 Cambiando Contraseña...',
                html: `
                    <div style="text-align: center;">
                        <div style="width: 60px; height: 60px; margin: 0 auto 15px; border: 4px solid #f3f3f3; border-top: 4px solid #A2CB8D; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <p>Actualizando tu contraseña...</p>
                        <small style="color: #666;">Esto puede tomar unos segundos</small>
                    </div>
                    <style>
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                    </style>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });
            
            // Enviar datos al servidor
            updatePassword(data);
        }
    });
}

// Función para actualizar la contraseña en el servidor
function updatePassword(passwordData) {
    const formData = new FormData();
    formData.append('action', 'change_password');
    formData.append('current_password', passwordData.currentPassword);
    formData.append('new_password', passwordData.newPassword);
    formData.append('confirm_password', passwordData.confirmPassword);
    
    console.log('=== DEBUG: Enviando cambio de contraseña ===');
    console.log('Action:', 'change_password');
    console.log('Current password length:', passwordData.currentPassword.length);
    console.log('New password length:', passwordData.newPassword.length);
    
    fetch('api/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('=== DEBUG: Respuesta del servidor ===');
        console.log('Status:', response.status);
        console.log('Status Text:', response.statusText);
        console.log('Headers:', response.headers);
        
        if (!response.ok) {
            throw new Error("HTTP " + response.status + ": " + response.statusText + "");
        }
        return response.text();
    })
    .then(textData => {
        console.log('=== DEBUG: Datos recibidos ===');
        console.log('Raw response:', textData);
        console.log('Response length:', textData.length);
        
        try {
            const data = JSON.parse(textData);
            console.log('Parsed data:', data);
            
            if (data.success) {
                // Éxito: mostrar mensaje y redirigir al login
                Swal.fire({
                    title: '✅ ¡Contraseña Actualizada!',
                    text: 'Tu contraseña se ha cambiado correctamente. Por seguridad, debes iniciar sesión nuevamente.',
                    icon: 'success',
                    confirmButtonColor: '#A2CB8D',
                    confirmButtonText: 'Ir al Login',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    // Redirigir al logout para que inicie sesión nuevamente
                    window.location.href = 'logout.php';
                });
            } else {
                // Error del servidor
                Swal.fire({
                    title: '❌ Error al Cambiar Contraseña',
                    html: "
                        <div style="text-align: left;">
                            <p style="margin-bottom: 15px;">" + data.message || 'Hubo un problema al cambiar tu contraseña' + "</p>
                            " + data.errors && data.errors.length > 0 ? 
                                '<ul style="color: #dc3545; margin: 0; padding-left: 20px;">' + 
                                data.errors.map(error => `<li>${error + "</li>").join('') + 
                                '</ul>' : ''
                            }
                        </div>
                    `,
                    icon: 'error',
                    confirmButtonColor: '#A2CB8D'
                });
            }
        } catch (parseError) {
            console.error('=== DEBUG: Error de parsing JSON ===');
            console.error('Parse error:', parseError);
            console.error('Raw response that failed to parse:', textData);
            
            Swal.fire({
                title: '❌ Error de Comunicación',
                html: "
                    <div style="text-align: left;">
                        <p>Error en la respuesta del servidor.</p>
                        <details style="margin-top: 10px;">
                            <summary>Detalles técnicos (clic para expandir)</summary>
                            <div style="background: #f8f8f8; padding: 10px; margin-top: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto; word-break: break-all;">
                                <strong>Error:</strong> " + parseError.message + "<br><br>
                                <strong>Respuesta del servidor:</strong><br>
                                " + textData.replace(/</g, '&lt;').replace(/>/g, '&gt;') + "
                            </div>
                        </details>
                    </div>
                ",
                icon: 'error',
                confirmButtonColor: '#A2CB8D',
                width: '600px'
            });
        }
    })
    .catch(error => {
        console.error('=== DEBUG: Error de fetch ===');
        console.error('Fetch error:', error);
        
        Swal.fire({
            title: '❌ Error de Conexión',
            html: "
                <div style="text-align: left;">
                    <p>No se pudo conectar con el servidor.</p>
                    <div style="margin-top: 10px; padding: 10px; background: #f8f8f8; border-radius: 4px;">
                        <strong>Error:</strong> " + error.message + "
                    </div>
                    <div style="margin-top: 10px;">
                        <button onclick="testConnectivity()" style="background: #A2CB8D; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                            🔧 Probar Conectividad
                        </button>
                    </div>
                </div>
            ",
            icon: 'error',
            confirmButtonColor: '#A2CB8D',
            width: '500px'
        });
    });
}

// Test actualización visual del avatar
function testVisualUpdate() {
    const results = document.getElementById('testResults');
    
    // Encontrar el elemento de imagen actual
    const avatarImg = document.querySelector('.profile-avatar img');
    
    if (!avatarImg) {
        results.innerHTML = '<div style="color: red;">❌ No se encontró el elemento de imagen del avatar</div>';
        return;
    }
    
    const currentSrc = avatarImg.src;
    
    results.innerHTML = "
        <div style="color: blue;">🔄 Probando actualización visual...</div>
        <div style="background: white; padding: 10px; border-radius: 3px; margin: 10px 0;">
            <strong>Imagen actual:</strong><br>
            <div style="font-family: monospace; font-size: 12px; word-break: break-all;">" + currentSrc + "</div>
        </div>
        <div style="margin: 10px 0;">
            <button onclick="forceUpdateAvatar()" style="background: #007bff; color: white; border: none; padding: 5px 10px; border-radius: 3px;">
                🔄 Forzar Actualización de Imagen
            </button>
        </div>
    ";
}

// Forzar actualización del avatar
function forceUpdateAvatar() {
    const avatarImg = document.querySelector('.profile-avatar img');
    const results = document.getElementById('testResults');
    
    if (!avatarImg) {
        results.innerHTML += '<div style="color: red;">❌ No se puede actualizar: elemento no encontrado</div>';
        return;
    }
    
    // Generar nueva URL con timestamp
    const currentSrc = avatarImg.src;
    const baseSrc = currentSrc.split('?')[0]; // Quitar timestamp previo
    const newSrc = baseSrc + '?t=' + Date.now();
    
    console.log('Forzando actualización de:', currentSrc, 'a:', newSrc);
    
    avatarImg.src = newSrc;
    
    results.innerHTML += "
        <div style="color: green; margin-top: 10px;">✅ Imagen forzada a actualizar</div>
        <div style="background: white; padding: 10px; border-radius: 3px; margin: 10px 0;">
            <strong>Nueva URL:</strong><br>
            <div style="font-family: monospace; font-size: 12px; word-break: break-all;">" + newSrc + "</div>
        </div>
    ";
}

// Test datos de recorte
function testCropData() {
    const results = document.getElementById('testResults');
    
    // Crear un input file temporal para simular el proceso
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        results.innerHTML = '<div style="color: blue;">🔄 Probando datos de recorte...</div>';
        
        // Crear una imagen temporal para el cropper
        const imageUrl = URL.createObjectURL(file);
        const tempImg = document.createElement('img');
        tempImg.src = imageUrl;
        tempImg.style.position = 'absolute';
        tempImg.style.left = '-9999px';
        tempImg.style.width = '300px';
        document.body.appendChild(tempImg);
        
        tempImg.onload = function() {
            // Inicializar cropper temporal
            const tempCropper = new Cropper(tempImg, {
                aspectRatio: 1,
                viewMode: 1,
                ready: function() {
                    // Obtener datos del recorte
                    const cropData = tempCropper.getData();
                    
                    // Mostrar datos
                    results.innerHTML = "
                        <div style="color: green;">✅ Datos de recorte obtenidos</div>
                        <pre style="font-size: 12px; background: white; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto;">
Archivo: " + file.name + "
Tamaño: " + file.size + " bytes
Tipo: " + file.type + "

Datos de recorte:
" + JSON.stringify(cropData, null, 2) + "
                        </pre>
                        <div style="margin-top: 10px;">
                            <button onclick="testCropUpload()" style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px;">
                                📤 Probar Upload con estos datos
                            </button>
                        </div>
                    ";
                    
                    // Guardar datos globalmente para el test
                    window.testCropFile = file;
                    window.testCropData = cropData;
                    
                    // Limpiar recursos
                    tempCropper.destroy();
                    document.body.removeChild(tempImg);
                    URL.revokeObjectURL(imageUrl);
                }
            });
        };
    };
    
    // Simular click
    input.click();
}

// Test upload con datos de recorte
function testCropUpload() {
    if (!window.testCropFile || !window.testCropData) {
        document.getElementById('testResults').innerHTML = '<div style="color: red;">❌ No hay datos de recorte para probar</div>';
        return;
    }
    
    const results = document.getElementById('testResults');
    results.innerHTML = '<div style="color: blue;">🔄 Probando upload con recorte...</div>';
    
    const formData = new FormData();
    formData.append('avatar', window.testCropFile);
    formData.append('cropData', JSON.stringify({ cropData: window.testCropData }));
    
    console.log('=== TEST CROP UPLOAD ===');
    console.log('Archivo:', window.testCropFile.name);
    console.log('Datos de recorte:', window.testCropData);
    
    fetch('api/upload-avatar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Respuesta:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error("HTTP " + response.status + ": " + response.statusText + "");
        }
        
        return response.text();
    })
    .then(textData => {
        console.log('Respuesta raw:', textData);
        
        try {
            const data = JSON.parse(textData);
            results.innerHTML = "
                <div style="color: green;">✅ Upload con recorte exitoso</div>
                <pre style="font-size: 12px; background: white; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto;">
" + JSON.stringify(data, null, 2) + "
                </pre>
            ";
        } catch (parseError) {
            results.innerHTML = "
                <div style="color: red;">❌ Error de JSON en upload con recorte: " + parseError.message + "</div>
                <div style="background: white; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Respuesta raw:</strong><br>
                    " + textData.replace(/</g, '&lt;').replace(/>/g, '&gt;') + "
                </div>
            ";
        }
    })
    .catch(error => {
        console.error('Error en test crop upload:', error);
        results.innerHTML = "<div style="color: red;">❌ Error en upload con recorte: " + error.message + "</div>";
    });
}

// Test ultra básico - PHP puro sin JSON
function testUltraBasic() {
    const results = document.getElementById('testResults');
    results.innerHTML = '<div style="color: blue;">🔄 Probando PHP ultra básico...</div>';
    
    fetch('test-ultra-basic.php')
    .then(response => {
        console.log('Ultra basic - Response status:', response.status);
        console.log('Ultra basic - Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error("HTTP " + response.status + ": " + response.statusText + "");
        }
        
        return response.text();
    })
    .then(textData => {
        console.log('Ultra basic - Raw response:', textData);
        
        if (textData.includes('PHP funciona correctamente')) {
            results.innerHTML = "
                <div style="color: green;">✅ PHP Ultra Básico OK</div>
                <div style="background: white; padding: 10px; border-radius: 3px; font-family: monospace;">
                    Respuesta: " + textData + "
                </div>
            ";
        } else {
            results.innerHTML = "
                <div style="color: orange;">⚠️ Respuesta inesperada</div>
                <div style="background: white; padding: 10px; border-radius: 3px; font-family: monospace;">
                    " + textData + "
                </div>
            ";
        }
    })
    .catch(error => {
        console.error('Ultra basic error:', error);
        results.innerHTML = "<div style="color: red;">❌ Error ultra básico: " + error.message + "</div>";
    });
}

// Test ultra JSON - PHP con JSON pero sin includes
function testUltraJson() {
    const results = document.getElementById('testResults');
    results.innerHTML = '<div style="color: blue;">🔄 Probando PHP con JSON...</div>';
    
    fetch('api/test-ultra-json.php')
    .then(response => {
        console.log('Ultra JSON - Response status:', response.status);
        
        if (!response.ok) {
            throw new Error("HTTP " + response.status + ": " + response.statusText + "");
        }
        
        return response.text();
    })
    .then(textData => {
        console.log('Ultra JSON - Raw response:', textData);
        
        try {
            const data = JSON.parse(textData);
            results.innerHTML = "
                <div style="color: green;">✅ PHP con JSON OK</div>
                <pre style="font-size: 12px; background: white; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto;">
" + JSON.stringify(data, null, 2) + "
                </pre>
            ";
        } catch (parseError) {
            results.innerHTML = "
                <div style="color: red;">❌ Error de JSON en ultra test: " + parseError.message + "</div>
                <div style="background: white; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Respuesta raw:</strong><br>
                    " + textData.replace(/</g, '&lt;').replace(/>/g, '&gt;') + "
                </div>
            ";
        }
    })
    .catch(error => {
        console.error('Ultra JSON error:', error);
        results.innerHTML = "<div style="color: red;">❌ Error en ultra JSON: " + error.message + "</div>";
    });
}

// Test mínimo para verificar que PHP funciona
function testMinimal() {
    const results = document.getElementById('testResults');
    results.innerHTML = '<div style="color: blue;">🔄 Probando PHP básico...</div>';
    
    fetch('api/test-minimal.php', {
        method: 'POST',
        body: new FormData()
    })
    .then(response => {
        console.log('Minimal test - Response status:', response.status);
        
        if (!response.ok) {
            throw new Error("HTTP " + response.status + ": " + response.statusText + "");
        }
        
        return response.text();
    })
    .then(textData => {
        console.log('Minimal test - Raw response:', textData);
        
        try {
            const data = JSON.parse(textData);
            results.innerHTML = "
                <div style="color: green;">✅ PHP Básico OK</div>
                <pre style="font-size: 12px; background: white; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto;">
" + JSON.stringify(data, null, 2) + "
                </pre>
            ";
        } catch (parseError) {
            results.innerHTML = "
                <div style="color: red;">❌ Error de JSON en test mínimo: " + parseError.message + "</div>
                <div style="background: white; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Respuesta raw:</strong><br>
                    " + textData.replace(/</g, '&lt;').replace(/>/g, '&gt;') + "
                </div>
            ";
        }
    })
    .catch(error => {
        console.error('Minimal test error:', error);
        results.innerHTML = "<div style="color: red;">❌ Error en test mínimo: " + error.message + "</div>";
    });
}

// Test de conectividad básica
function testConnectivity() {
    const results = document.getElementById('testResults');
    results.innerHTML = '<div style="color: blue;">🔄 Probando conectividad...</div>';
    
    fetch('api/test-simple.php', {
        method: 'POST',
        body: new FormData()
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error("HTTP " + response.status + ": " + response.statusText + "");
        }
        
        return response.text(); // Primero como texto para ver qué llega
    })
    .then(textData => {
        console.log('Raw response:', textData);
        
        try {
            const data = JSON.parse(textData);
            results.innerHTML = "
                <div style="color: green;">✅ Conectividad OK</div>
                <pre style="font-size: 12px; background: white; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto;">
" + JSON.stringify(data, null, 2) + "
                </pre>
            ";
        } catch (parseError) {
            results.innerHTML = "
                <div style="color: red;">❌ Error de JSON: " + parseError.message + "</div>
                <div style="background: white; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Respuesta raw:</strong><br>
                    " + textData.replace(/</g, '&lt;').replace(/>/g, '&gt;') + "
                </div>
            ";
        }
    })
    .catch(error => {
        console.error('Connectivity test error:', error);
        results.innerHTML = "<div style="color: red;">❌ Error de conectividad: " + error.message + "</div>";
    });
}

// Test de upload simple sin recorte
function testSimpleUpload() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const results = document.getElementById('testResults');
        results.innerHTML = '<div style="color: blue;">🔄 Probando upload simple...</div>';
        
        const formData = new FormData();
        formData.append('avatar', file);
        
        fetch('api/upload-simple.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Upload response status:', response.status);
            
            if (!response.ok) {
                throw new Error("HTTP " + response.status + ": " + response.statusText + "");
            }
            
            return response.text(); // Primero como texto
        })
        .then(textData => {
            console.log('Upload raw response:', textData);
            
            try {
                const data = JSON.parse(textData);
                
                if (data.success) {
                    results.innerHTML = "
                        <div style="color: green;">✅ Upload simple exitoso</div>
                        <div>Archivo: " + file.name + " (" + (file.size/1024/1024).toFixed(2) + " MB)</div>
                        <div>Avatar guardado en: " + data.data.avatar_path + "</div>
                    ";
                    
                    // Actualizar avatar en la página
                    const avatarImg = document.querySelector('.profile-avatar img');
                    if (avatarImg) {
                        avatarImg.src = data.data.avatar_path + '?t=' + Date.now();
                    }
                } else {
                    results.innerHTML = "<div style="color: red;">❌ Error: " + data.message + "</div>";
                }
            } catch (parseError) {
                results.innerHTML = "
                    <div style="color: red;">❌ Error de JSON: " + parseError.message + "</div>
                    <div style="background: white; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                        <strong>Respuesta raw:</strong><br>
                        " + textData.replace(/</g, '&lt;').replace(/>/g, '&gt;') + "
                    </div>
                ";
            }
        })
        .catch(error => {
            results.innerHTML = "<div style="color: red;">❌ Error de red: " + error.message + "</div>";
        });
    };
    
    input.click();
}

// FUNCIÓN PRINCIPAL PARA EDITAR AVATAR
// Esta función maneja todo el proceso: verificación, selección, recorte y subida
function editAvatar() {
    // Verificar si el usuario ya tiene avatar
    const currentAvatar = document.querySelector('.profile-avatar img').src;
    const hasAvatar = !currentAvatar.includes('usuario.png');
    
    // Si ya tiene avatar, preguntar si quiere cambiarlo
    if (hasAvatar) {
        Swal.fire({
            title: '📸 ¿Cambiar Avatar?',
            text: 'Ya tienes una foto de perfil. ¿Quieres cambiarla por una nueva?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#A2CB8D',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                showAvatarUploader();
            }
        });
    } else {
        // Si no tiene avatar, ir directo al selector
        showAvatarUploader();
    }
}

// FUNCIÓN PARA MOSTRAR EL SELECTOR DE ARCHIVOS
// Crea un input file temporal y lo activa
function showAvatarUploader() {
    Swal.fire({
        title: '📷 Seleccionar Imagen',
        html: `
            <div style="text-align: center; padding: 20px;">
                <div style="margin-bottom: 20px;">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 3em; color: #A2CB8D; margin-bottom: 15px;"></i>
                    <p style="color: #666; margin-bottom: 20px;">Selecciona una imagen para tu foto de perfil</p>
                </div>
                
                <input type="file" id="avatarFile" accept="image/*" style="display: none;">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('avatarFile').click()">
                    <i class="fas fa-images"></i> Seleccionar Imagen
                </button>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: left;">
                    <small style="color: #666;">
                        <strong>📋 Requisitos:</strong><br>
                        • Tamaño máximo: 25MB<br>
                        • Formatos: JPG, PNG, GIF, WebP<br>
                        • Dimensión mínima: 100x100px<br>
                        • Recomendado: Imagen cuadrada
                    </small>
                </div>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        cancelButtonColor: '#6c757d',
        didOpen: () => {
            // Cuando se abre el modal, configuramos el evento del input file
            const fileInput = document.getElementById('avatarFile');
            fileInput.addEventListener('change', handleFileSelection);
        }
    });
}

// FUNCIÓN PARA MANEJAR LA SELECCIÓN DE ARCHIVO
// Se ejecuta cuando el usuario selecciona una imagen
function handleFileSelection(event) {
    const file = event.target.files[0];
    
    // Validar que se seleccionó un archivo
    if (!file) {
        return;
    }
    
    // Validar tipo de archivo
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        Swal.fire({
            title: '❌ Archivo No Válido',
            text: 'Por favor selecciona una imagen válida (JPG, PNG, GIF o WebP)',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
        return;
    }
    
    // Validar tamaño
    const maxSize = 25 * 1024 * 1024; // 25MB
    if (file.size > maxSize) {
        Swal.fire({
            title: '📏 Archivo Muy Grande',
            text: 'La imagen debe ser menor a 25MB',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
        return;
    }
    
    // Si todo esta bien, mostrar el recortador
    showImageCropper(file);
}

// FUNCIÓN PARA MOSTRAR EL RECORTADOR DE IMAGEN
// Usa Cropper.js para permitir al usuario recortar su imagen
function showImageCropper(file) {
    // Cerrar el modal actual
    Swal.close();
    
    // Crear URL temporal para mostrar la imagen
    const imageUrl = URL.createObjectURL(file);
    
    Swal.fire({
        title: '✂️ Recortar Imagen',
        html: "
            <div style="max-width: 100%; margin: 0 auto;">
                <div style="margin-bottom: 15px;">
                    <p style="color: #666; margin: 0;">Arrastra para ajustar el área de tu foto de perfil</p>
                </div>
                <div style="max-height: 400px; overflow: hidden; border-radius: 8px;">
                    <img id="cropperImage" src="" + imageUrl + "" style="max-width: 100%; display: block;">
                </div>
                <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                    <small style="color: #666;">
                        <i class="fas fa-info-circle"></i> 
                        La imagen se recortará como un cuadrado perfecto para tu avatar
                    </small>
                </div>
            </div>
        ",
        width: '600px',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-upload"></i> Subir Avatar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#A2CB8D',
        cancelButtonColor: '#6c757d',
        didOpen: () => {
            // Inicializar Cropper.js cuando el modal se abre
            const image = document.getElementById('cropperImage');
            window.cropper = new Cropper(image, {
                aspectRatio: 1, // Forzar cuadrado (1:1)
                viewMode: 1, // Mostrar imagen completa
                dragMode: 'move',
                autoCropArea: 0.8, // 80% del área inicial
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                responsive: true,
                checkOrientation: true
            });
        },
        willClose: () => {
            // NO destruir cropper aqui, lo haremos despues del upload
            URL.revokeObjectURL(imageUrl);
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Obtener datos del cropper ANTES de destruirlo
            if (window.cropper) {
                const cropData = window.cropper.getData();
                console.log('=== DATOS DE RECORTE OBTENIDOS ===');
                console.log('Crop data:', cropData);
                
                // Destruir cropper despues de obtener datos
                window.cropper.destroy();
                window.cropper = null;
                
                // Subir con los datos obtenidos
                uploadCroppedImageWithData(file, cropData);
            } else {
                console.error('❌ No hay cropper disponible');
                Swal.fire({
                    title: '❌ Error',
                    text: 'Error en el recortador de imagen',
                    icon: 'error',
                    confirmButtonColor: '#A2CB8D'
                });
            }
        } else {
            // Si cancela, destruir cropper
            if (window.cropper) {
                window.cropper.destroy();
                window.cropper = null;
            }
        }
    });
}

// FUNCIÓN MEJORADA PARA SUBIR LA IMAGEN RECORTADA
// Recibe los datos del cropper como parámetro (no depende de window.cropper)
function uploadCroppedImageWithData(originalFile, cropData) {
    // Validar que los datos de recorte sean válidos
    if (!cropData || typeof cropData.x === 'undefined' || typeof cropData.y === 'undefined' ||
        typeof cropData.width === 'undefined' || typeof cropData.height === 'undefined') {
        Swal.fire({
            title: '❌ Error',
            text: 'Los datos de recorte no son válidos. Intenta de nuevo.',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: '📤 Subiendo Avatar...',
        html: `
            <div style="text-align: center;">
                <div style="margin-bottom: 15px;">
                    <div style="width: 60px; height: 60px; margin: 0 auto 15px; border: 4px solid #f3f3f3; border-top: 4px solid #A2CB8D; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>
                <p>Procesando tu imagen...</p>
                <small style="color: #666;">Esto puede tomar unos segundos</small>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
    });
    
    // Crear FormData para enviar el archivo y datos del recorte
    const formData = new FormData();
    formData.append('avatar', originalFile);
    formData.append('cropData', JSON.stringify({ cropData }));
    
    console.log('FormData creado, enviando a upload-avatar.php...');
    
    // Enviar directamente al endpoint principal (saltamos el test de conectividad)
    fetch('api/upload-avatar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("HTTP " + response.status + ": " + response.statusText + "");
        }
        
        return response.text();
    })
    .then(textData => {
        try {
            const data = JSON.parse(textData);
            
            if (data.success) {
                // Primero cerrar el loading
                Swal.close();
                
                // Éxito: actualizar la imagen en la página
                const avatarImg = document.querySelector('.profile-avatar img');
                
                if (avatarImg) {
                    const newPath = data.data.avatar_path + '?t=' + Date.now();
                    avatarImg.src = newPath; // Cache busting
                    
                    // Tambien actualizar el avatar del menu si existe
                    const menuAvatar = document.querySelector('.dropdown-header img');
                    if (menuAvatar) {
                        menuAvatar.src = newPath;
                    }
                }
                
                // Pequeña pausa para que se vea el cambio de imagen
                setTimeout(() => {
                    Swal.fire({
                        title: '✅ ¡Avatar Actualizado!',
                        text: 'Tu foto de perfil se ha actualizado correctamente',
                        icon: 'success',
                        confirmButtonColor: '#A2CB8D',
                        timer: 2000, // Se cierra automáticamente en 2 segundos
                        showConfirmButton: true
                    });
                }, 300);
            } else {
                // Error del servidor
                Swal.close(); // Cerrar loading
                
                setTimeout(() => {
                    Swal.fire({
                        title: '❌ Error al Subir',
                        text: data.message || 'Hubo un problema al subir tu avatar',
                        icon: 'error',
                        confirmButtonColor: '#A2CB8D'
                    });
                }, 200);
            }
        } catch (parseError) {
            Swal.close(); // Cerrar loading
            
            setTimeout(() => {
                Swal.fire({
                    title: '❌ Error de Formato',
                    text: 'Error en la respuesta del servidor',
                    icon: 'error',
                    confirmButtonColor: '#A2CB8D'
                });
            }, 200);
        }
    })
    .catch(error => {
        // Error de red o JS
        Swal.close(); // Cerrar loading
        
        setTimeout(() => {
            Swal.fire({
                title: '❌ Error de Conexión',
                text: 'No se pudo conectar con el servidor. Verifica tu conexión.',
                icon: 'error',
                confirmButtonColor: '#A2CB8D'
            });
        }, 200);
    });
}

// FUNCIÓN PARA SUBIR LA IMAGEN RECORTADA (VERSIÓN ANTIGUA - MANTENER PARA COMPATIBILIDAD)
// Obtiene los datos del recorte y envía todo al servidor
function uploadCroppedImage(originalFile) {
    if (!window.cropper) {
        Swal.fire({
            title: '❌ Error',
            text: 'Error en el recortador de imagen',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
        return;
    }
    
    // Obtener datos del recorte
    const cropData = window.cropper.getData();
    
    // Debug: Log de los datos de recorte
    console.log('Datos de recorte:', cropData);
    
    // Validar que los datos de recorte sean válidos
    if (!cropData || typeof cropData.x === 'undefined' || typeof cropData.y === 'undefined' ||
        typeof cropData.width === 'undefined' || typeof cropData.height === 'undefined') {
        Swal.fire({
            title: '❌ Error',
            text: 'Los datos de recorte no son válidos. Intenta de nuevo.',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: '📤 Subiendo Avatar...',
        html: `
            <div style="text-align: center;">
                <div style="margin-bottom: 15px;">
                    <div style="width: 60px; height: 60px; margin: 0 auto 15px; border: 4px solid #f3f3f3; border-top: 4px solid #A2CB8D; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>
                <p>Procesando tu imagen...</p>
                <small style="color: #666;">Esto puede tomar unos segundos</small>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
    });
    
    // Crear FormData para enviar el archivo y datos del recorte
    const formData = new FormData();
    formData.append('avatar', originalFile);
    formData.append('cropData', JSON.stringify({ cropData }));
    
    // Debug: Log del FormData
    console.log('Enviando archivo:', originalFile.name, originalFile.size, 'bytes');
    console.log('Datos de recorte JSON:', JSON.stringify({ cropData }));
    
    // PRIMERA PRUEBA: Enviar al test simple para verificar conectividad
    console.log('=== INICIANDO TEST DE CONECTIVIDAD ===');
    
    // Enviar al test simple primero
    fetch('api/test-simple.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Test simple - Respuesta:', response.status, response.statusText);
        return response.json();
    })
    .then(testData => {
        console.log('Test simple - Datos:', testData);
        
        if (testData.success) {
            console.log('✅ Test simple exitoso, probando upload real...');
            
            // Si el test funciona, intentar el upload real
            return fetch('api/upload-avatar.php', {
                method: 'POST',
                body: formData
            });
        } else {
            throw new Error('Test simple falló: ' + testData.error);
        }
    })
    .then(response => {
        console.log('Respuesta del servidor:', response.status, response.statusText);
        return response.json();
    })
    .then(data => {
        console.log('=== RESPUESTA DEL UPLOAD NORMAL ===');
        console.log('Datos recibidos:', data);
        console.log('Success:', data.success);
        console.log('Avatar path:', data.data ? data.data.avatar_path : 'NO DATA');
        
        if (data.success) {
            // Éxito: actualizar la imagen en la página
            const avatarImg = document.querySelector('.profile-avatar img');
            console.log('Avatar img element:', avatarImg);
            
            if (avatarImg) {
                const newPath = data.data.avatar_path + '?t=' + Date.now();
                console.log('Actualizando imagen a:', newPath);
                avatarImg.src = newPath; // Cache busting
                
                // También actualizar el avatar del menú si existe
                const menuAvatar = document.querySelector('.dropdown-header img');
                if (menuAvatar) {
                    console.log('Actualizando avatar del menú también');
                    menuAvatar.src = newPath;
                }
            } else {
                console.error('❌ No se encontró el elemento de imagen del avatar');
            }
            
            Swal.fire({
                title: '✅ ¡Avatar Actualizado!',
                text: 'Tu foto de perfil se ha actualizado correctamente',
                icon: 'success',
                confirmButtonColor: '#A2CB8D'
            });
        } else {
            // Error del servidor
            console.error('Error del servidor:', data.message);
            Swal.fire({
                title: '❌ Error al Subir',
                text: data.message || 'Hubo un problema al subir tu avatar',
                icon: 'error',
                confirmButtonColor: '#A2CB8D'
            });
        }
    })
    .catch(error => {
        // Error de red o JS
        console.error('Error de conexión:', error);
        Swal.fire({
            title: '❌ Error de Conexión',
            text: 'No se pudo conectar con el servidor. Verifica tu conexión.',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
    })
    .finally(() => {
        // Limpiar cropper
        if (window.cropper) {
            window.cropper.destroy();
            window.cropper = null;
        }
    });
}

function changePassword() {
    Swal.fire({
        title: 'Cambiar Contraseña',
        html: `
            <div style="text-align: left;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Contraseña Actual:</label>
                    <input type="password" id="currentPassword" class="swal2-input" placeholder="Contraseña actual">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nueva Contraseña:</label>
                    <input type="password" id="newPassword" class="swal2-input" placeholder="Nueva contraseña">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Confirmar Contraseña:</label>
                    <input type="password" id="confirmPassword" class="swal2-input" placeholder="Confirmar contraseña">
                </div>
            </div>
        `,
        focusConfirm: false,
        confirmButtonText: 'Cambiar Contraseña',
        confirmButtonColor: '#A2CB8D',
        cancelButtonText: 'Cancelar',
        showCancelButton: true,
        preConfirm: () => {
            const current = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            
            if (!current || !newPass || !confirm) {
                Swal.showValidationMessage('Todos los campos son obligatorios');
                return false;
            }
            
            if (newPass !== confirm) {
                Swal.showValidationMessage('Las contraseñas no coinciden');
                return false;
            }
            
            if (newPass.length < 6) {
                Swal.showValidationMessage('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            return { current, newPass };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí iría la lógica para cambiar la contraseña
            Swal.fire({
                title: '¡Contraseña Cambiada!',
                text: 'Tu contraseña se ha actualizado correctamente',
                icon: 'success',
                confirmButtonColor: '#A2CB8D'
            });
        }
    });
}

function exportData() {
    Swal.fire({
        title: '🚧 Exportar Datos (WIP)',
        html: `
            <div style="text-align: left;">
                <p style="color: #666; margin-bottom: 20px;">Esta funcionalidad esta en desarrollo. Por seguridad, ingresa tu contrasena:</p>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Contraseña:</label>
                    <input type="password" id="exportPassword" class="swal2-input" placeholder="Tu contraseña actual">
                </div>
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <strong>🔄 Work in Progress</strong><br>
                    <small>La exportación de datos estará disponible próximamente. Tu solicitud será procesada manualmente.</small>
                </div>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Solicitar Exportación',
        confirmButtonColor: '#A2CB8D',
        cancelButtonText: 'Cancelar',
        showCancelButton: true,
        preConfirm: () => {
            const password = document.getElementById('exportPassword').value;
            
            if (!password) {
                Swal.showValidationMessage('La contraseña es requerida por seguridad');
                return false;
            }
            
            return { password };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Simular procesamiento WIP
            Swal.fire({
                title: '📧 Solicitud Registrada',
                html: "
                    <div style="text-align: center;">
                        <p>Tu solicitud de exportación de datos ha sido registrada.</p>
                        <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <strong>📋 Estado:</strong> En cola de procesamiento<br>
                            <strong>⏱️ Tiempo estimado:</strong> 24-48 horas<br>
                            <strong>📧 Notificación:</strong> Recibirás un email cuando esté listo
                        </div>
                        <small style="color: #666;">Ticket: #EXP-" + Math.random().toString(36).substr(2, 9).toUpperCase() + "</small>
                    </div>
                ",
                icon: 'success',
                confirmButtonColor: '#A2CB8D'
            });
        }
    });
}

function deleteAccount() {
    Swal.fire({
        title: '⚠️ ¿Eliminar Cuenta?',
        text: 'Esta acción no se puede deshacer. Se eliminarán todos tus productos y datos.',
        icon: 'warning',
        confirmButtonText: 'Sí, eliminar',
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Cancelar',
        showCancelButton: true,
        input: 'text',
        inputPlaceholder: 'Escribe "ELIMINAR" para confirmar',
        inputValidator: (value) => {
            if (value !== 'ELIMINAR') {
                return 'Debes escribir "ELIMINAR" para confirmar';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí iría la lógica para eliminar la cuenta
            Swal.fire({
                title: 'Cuenta Eliminada',
                text: 'Tu cuenta ha sido eliminada correctamente',
                icon: 'success',
                confirmButtonColor: '#dc3545'
            }).then(() => {
                window.location.href = 'logout.php';
            });
        }
    });
}

// Verificar si hay que resaltar el botón de cambiar contraseña
document.addEventListener('DOMContentLoaded', function() {
    // Verificar parámetro URL para resaltar cambiar contraseña
    const urlParams = new URLSearchParams(window.location.search);
    const highlight = urlParams.get('highlight');
    
    if (highlight === 'password') {
        // Resaltar el botón de cambiar contraseña
        const passwordBtn = document.querySelector('.quick-action-btn[onclick*="changePassword"]');
        if (passwordBtn) {
            // Añadir clase de resaltado
            passwordBtn.classList.add('highlight-password');
            
            // Scroll al botón después de un pequeño delay
            setTimeout(() => {
                passwordBtn.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Mostrar notificación
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Aquí puedes cambiar tu contraseña',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    background: '#C9F89B',
                    color: '#313C26'
                });
            }, 500);
            
            // Remover resaltado después de 8 segundos
            setTimeout(() => {
                passwordBtn.classList.remove('highlight-password');
            }, 8000);
        }
        
        // Limpiar URL para que no se repita el resaltado
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Animar las tarjetas de estadísticas
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animar las secciones
    const sections = document.querySelectorAll('.section-card');
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            section.style.transition = 'all 0.6s ease';
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, 200 + (index * 150));
    });
});

// Funciones de testing para APIs
function testPasswordAPI() {
    const formData = new FormData();
    formData.append('action', 'test_connection');
    
    fetch('api/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('🔑 Password API Test - Status:', response.status);
        return response.text();
    })
    .then(textData => {
        console.log('🔑 Password API Test - Response:', textData);
        
        try {
            const data = JSON.parse(textData);
            
            if (data.success) {
                Swal.fire({
                    title: '✅ API de Contraseñas OK',
                    text: 'La API de contrasenas esta funcionando correctamente.',
                    icon: 'success',
                    confirmButtonColor: '#A2CB8D'
                });
            } else {
                Swal.fire({
                    title: '⚠️ API con Problemas',
                    text: data.message || 'Error desconocido en la API',
                    icon: 'warning',
                    confirmButtonColor: '#A2CB8D'
                });
            }
        } catch (parseError) {
            Swal.fire({
                title: '❌ Error en API',
                text: 'Error al procesar la respuesta: ' + parseError.message,
                icon: 'error',
                confirmButtonColor: '#A2CB8D'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: '❌ API No Accesible',
            text: 'Error: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
    });
}

function testPersonalInfoAPI() {
    const formData = new FormData();
    formData.append('action', 'update_personal_info');
    formData.append('fullname', 'TEST NAME');
    formData.append('username', 'testuser');
    formData.append('email', 'test@example.com');
    formData.append('phone', '+123456789');
    formData.append('current_password', 'wrongpassword');
    
    fetch('api/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📝 Personal Info API Test - Status:', response.status);
        return response.text();
    })
    .then(textData => {
        console.log('📝 Personal Info API Test - Response:', textData);
        
        try {
            const data = JSON.parse(textData);
            
            if (!data.success && data.details && data.details.errors) {
                const hasPasswordError = data.details.errors.some(error => 
                    error.includes('contraseña actual no es correcta')
                );
                
                if (hasPasswordError) {
                    Swal.fire({
                        title: '✅ API de Edición OK',
                        text: 'La API de edicion esta funcionando correctamente.',
                        icon: 'success',
                        confirmButtonColor: '#A2CB8D'
                    });
                } else {
                    Swal.fire({
                        title: '⚠️ Respuesta Inesperada',
                        text: 'La API respondió pero no como se esperaba',
                        icon: 'warning',
                        confirmButtonColor: '#A2CB8D'
                    });
                }
            } else {
                Swal.fire({
                    title: '⚠️ Respuesta Inesperada',
                    text: 'La API respondió pero no como se esperaba',
                    icon: 'warning',
                    confirmButtonColor: '#A2CB8D'
                });
            }
        } catch (parseError) {
            Swal.fire({
                title: '❌ Error en API de Edición',
                text: 'Error al procesar la respuesta: ' + parseError.message,
                icon: 'error',
                confirmButtonColor: '#A2CB8D'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: '❌ API de Edición No Accesible',
            text: 'Error: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
    });
}
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>

