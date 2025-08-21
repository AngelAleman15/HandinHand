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
                    <img src="img/usuario.png" alt="Avatar de <?php echo htmlspecialchars($user['fullname']); ?>">
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
                        <button class="btn btn-primary" onclick="editProfile()">
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
                            <span><?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : 'No especificado'; ?></span>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

function editProfile() {
    Swal.fire({
        title: 'Editar Perfil',
        html: `
            <div style="text-align: left;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nombre Completo:</label>
                    <input type="text" id="editFullname" class="swal2-input" placeholder="Nombre completo" value="<?php echo htmlspecialchars($user['fullname']); ?>">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email:</label>
                    <input type="email" id="editEmail" class="swal2-input" placeholder="Email" value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #dc3545;">Contraseña (requerida para cambiar email):</label>
                    <input type="password" id="editPassword" class="swal2-input" placeholder="Tu contraseña actual">
                </div>
            </div>
        `,
        focusConfirm: false,
        confirmButtonText: 'Guardar Cambios',
        confirmButtonColor: '#A2CB8D',
        cancelButtonText: 'Cancelar',
        showCancelButton: true,
        preConfirm: () => {
            const fullname = document.getElementById('editFullname').value;
            const email = document.getElementById('editEmail').value;
            const password = document.getElementById('editPassword').value;
            
            if (!fullname || !email) {
                Swal.showValidationMessage('El nombre y email son obligatorios');
                return false;
            }
            
            // Si el email cambió, requerir contraseña
            if (email !== '<?php echo htmlspecialchars($user['email']); ?>' && !password) {
                Swal.showValidationMessage('Se requiere contraseña para cambiar el email');
                return false;
            }
            
            return { fullname, email, password };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí iría la lógica para actualizar el perfil
            Swal.fire({
                title: '¡Perfil Actualizado!',
                text: 'Los cambios se han guardado correctamente',
                icon: 'success',
                confirmButtonColor: '#A2CB8D'
            });
        }
    });
}

function editPersonalInfo() {
    Swal.fire({
        title: 'Editar Información Personal',
        text: 'Esta funcionalidad estará disponible próximamente',
        icon: 'info',
        confirmButtonColor: '#A2CB8D'
    });
}

function editAvatar() {
    Swal.fire({
        title: 'Cambiar Avatar',
        text: 'Funcionalidad de carga de imágenes próximamente',
        icon: 'info',
        confirmButtonColor: '#A2CB8D'
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
                <p style="color: #666; margin-bottom: 20px;">Esta funcionalidad está en desarrollo. Por seguridad, ingresa tu contraseña:</p>
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
                html: `
                    <div style="text-align: center;">
                        <p>Tu solicitud de exportación de datos ha sido registrada.</p>
                        <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <strong>📋 Estado:</strong> En cola de procesamiento<br>
                            <strong>⏱️ Tiempo estimado:</strong> 24-48 horas<br>
                            <strong>📧 Notificación:</strong> Recibirás un email cuando esté listo
                        </div>
                        <small style="color: #666;">Ticket: #EXP-${Math.random().toString(36).substr(2, 9).toUpperCase()}</small>
                    </div>
                `,
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
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
