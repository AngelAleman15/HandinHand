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
    $stmt = $pdo->prepare("SELECT COUNT(*) as mensajes FROM mensajes WHERE receiver_id = ?");
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

// Obtener promedio y total de valoraciones para mostrar en el perfil
$promedio_val = 0;
$total_val = 0;
try {
    require_once __DIR__ . '/config/database.php';
    $db = getConnection();
    $stmt = $db->prepare('SELECT AVG(puntuacion) as promedio, COUNT(*) as total FROM valoraciones WHERE usuario_id = ?');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $promedio_val = round(floatval($row['promedio']), 1);
        $total_val = intval($row['total']);
    }
} catch (Exception $e) {}

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
                    <img src="<?php echo isset($user['avatar_path']) && !empty($user['avatar_path']) ? htmlspecialchars($user['avatar_path']) : 'img/usuario.svg'; ?>" 
                         alt="Avatar de <?php echo htmlspecialchars($user['fullname']); ?>" 
                         onerror="this.src='img/usuario.svg'">
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
                        <?php
                        // Si el perfil es de otro usuario
                        $mi_id = $_SESSION['user_id'];
                        $perfil_id = $user['id'];
                        if ($mi_id !== $perfil_id) {
                            // Verificar amistad
                            $stmt = $pdo->prepare("SELECT id FROM amistades WHERE (usuario1_id = ? AND usuario2_id = ?) OR (usuario1_id = ? AND usuario2_id = ?)");
                            $stmt->execute([$mi_id, $perfil_id, $perfil_id, $mi_id]);
                            $es_amigo = $stmt->fetch();
                            // Verificar solicitud
                            $stmt = $pdo->prepare("SELECT id, estado FROM solicitudes_amistad WHERE (solicitante_id = ? AND receptor_id = ?) OR (solicitante_id = ? AND receptor_id = ?)");
                            $stmt->execute([$mi_id, $perfil_id, $perfil_id, $mi_id]);
                            $solicitud = $stmt->fetch();
                            if ($es_amigo) {
                                echo '<button class="btn btn-success" disabled><i class="fas fa-user-check"></i> Ya son amigos</button>';
                            } elseif ($solicitud && $solicitud['estado'] === 'pendiente') {
                                if ($solicitud['solicitante_id'] == $mi_id) {
                                    echo '<button class="btn btn-warning" onclick="cancelarSolicitud(' . $perfil_id . ')"><i class="fas fa-times"></i> Cancelar solicitud</button>';
                                } else {
                                    echo '<button class="btn btn-info" disabled><i class="fas fa-hourglass-half"></i> Solicitud pendiente</button>';
                                }
                            } elseif ($solicitud && $solicitud['estado'] === 'rechazada') {
                                echo '<button class="btn btn-secondary" disabled><i class="fas fa-ban"></i> Solicitud rechazada</button>';
                            } else {
                                echo '<button class="btn btn-primary" onclick="enviarSolicitud(' . $perfil_id . ')"><i class="fas fa-user-plus"></i> Enviar solicitud de amistad</button>';
                            }
                        } else {
                            // Perfil propio: mostrar botones de edición
                            echo '<button class="btn btn-primary" onclick="editPersonalInfo()"><i class="fas fa-edit"></i> Editar Perfil</button>';
                            echo '<a href="mis-productos.php" class="btn btn-primary"><i class="fas fa-box"></i> Mis Productos</a>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="profile-content">
        <!-- Tarjetas de estadísticas (solo una fila, sin duplicados) -->
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

        <!-- Tarjeta de promedio de valoraciones ocupando toda la fila -->
        <?php
        // Obtener las 3 mejores y más recientes valoraciones ANTES de cualquier salida
        $valoraciones_top = [];
        $valoraciones_debug = '';
        try {
            $db = getConnection();
            $stmt = $db->prepare('SELECT * FROM valoraciones WHERE usuario_id = ? ORDER BY puntuacion DESC, created_at DESC LIMIT 3');
            $stmt->execute([$user['id']]);
            $valoraciones_top = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Verificar si la tabla users existe
            $users_exists = false;
            try {
                $db->query("SELECT 1 FROM users LIMIT 1");
                $users_exists = true;
            } catch (Exception $e) {
                $users_exists = false;
            }
            $nombres_valoradores = [];
            $usernames_valoradores = [];
            $usuarios_map = [];
            error_log('VALORACIONES DEBUG: INICIO procesamiento valoraciones_top: ' . print_r($valoraciones_top, true));
            // Cambiar a tabla 'usuarios'
            $usuarios_exists = false;
            try {
                $db->query("SELECT 1 FROM usuarios LIMIT 1");
                $usuarios_exists = true;
            } catch (Exception $e) {
                $usuarios_exists = false;
            }
            $ids_valoradores = array_column($valoraciones_top, 'valorador_id');
            if ($usuarios_exists && !empty($ids_valoradores)) {
                $in = str_repeat('?,', count($ids_valoradores) - 1) . '?';
                $stmt2 = $db->prepare("SELECT id, fullname, username FROM usuarios WHERE id IN ($in)");
                $stmt2->execute($ids_valoradores);
                $usuarios_result = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                error_log('VALORACIONES DEBUG: resultado consulta usuarios=' . print_r($usuarios_result, true));
                foreach ($usuarios_result as $row) {
                    $usuarios_map[$row['id']] = $row;
                }
            }
            error_log('VALORACIONES DEBUG: usuarios_map=' . print_r($usuarios_map, true));
            foreach ($valoraciones_top as &$v) {
                $nombre = '';
                $username = '';
                if ($usuarios_exists && isset($v['valorador_id']) && isset($usuarios_map[$v['valorador_id']])) {
                    $nombre = $usuarios_map[$v['valorador_id']]['fullname'];
                    $username = $usuarios_map[$v['valorador_id']]['username'];
                }
                $v['valorador_nombre'] = ($nombre !== null && $nombre !== '') ? $nombre : (($username !== null && $username !== '') ? '@'.$username : 'Usuario');
                // Siempre asignar un string
                if (!isset($v['valorador_nombre']) || $v['valorador_nombre'] === null) {
                    $v['valorador_nombre'] = 'Usuario';
                }
                error_log('VALORACIONES DEBUG: valorador_id=' . (isset($v['valorador_id']) ? $v['valorador_id'] : 'NO_ID') . ' nombre=' . $nombre . ' username=' . $username . ' valorador_nombre=' . $v['valorador_nombre']);
            }
            unset($v);
            error_log('VALORACIONES DEBUG: valoraciones_top FINAL=' . print_r($valoraciones_top, true));
            $valoraciones_debug = '<!-- Valoraciones encontradas: '.count($valoraciones_top).'\n'.print_r($valoraciones_top, true).' -->';
        } catch (Exception $e) {
            $valoraciones_debug = '<!-- Error SQL: '.htmlspecialchars($e->getMessage()).' -->';
        }
        echo $valoraciones_debug;
        ?>
        <div style="width:100%; max-width:700px; margin:18px auto 0 auto;">
            <div style="background:#fff; border-radius:18px; box-shadow:0 2px 16px #313C2633; padding:32px 38px 18px 38px; display:flex; flex-direction:column; align-items:center; width:100%;">
                <div style="display:flex; align-items:center; gap:18px; margin-bottom:10px;">
                    <span style="background:#313C26; color:#FFD700; border-radius:12px; padding:10px 16px; font-size:2em; display:flex; align-items:center;"><i class="fas fa-star"></i></span>
                    <span style="font-size:2.2em; color:#3A5D1A; font-weight:700; letter-spacing:1px;"> <?php echo $promedio_val; ?> </span>
                    <span>
                        <?php
                        $rounded = round($promedio_val);
                        for ($i=1; $i<=5; $i++) {
                            echo "<i class='fas fa-star' style='color:".($i<=$rounded?'#FFD700':'#eee')."; font-size:1.5em;'></i>";
                        }
                        ?>
                    </span>
                </div>
                <div style="color:#888; font-size:1.1em; margin-bottom:10px;">Promedio (<?php echo $total_val; ?> valoraciones)</div>

                <!-- Mostrar las 3 mejores y más recientes valoraciones -->
                <div style="width:100%; display:flex; flex-direction:column; gap:12px; justify-content:center; align-items:center;">
                    <?php if (count($valoraciones_top) === 0): ?>
                        <div style="color:#bbb; font-size:1.1em; text-align:center; width:100%;">No hay valoraciones para mostrar.</div>
                    <?php else: ?>
                        <?php if (count($valoraciones_top) > 0): ?>
                            <?php $v =& $valoraciones_top[0]; // referencia para asegurar que tenga valorador_nombre ?>
                            <div style="background:#F8FFF2; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.07); padding:16px 24px 16px 24px; min-width:320px; max-width:700px; width:100%; display:flex; flex-direction:column; align-items:flex-start; gap:8px; margin-bottom:6px; min-height:70px;">
                                <div style="display:flex; align-items:center; gap:16px; margin-bottom:6px;">
                                    <img src='img/usuario.svg' style='width:54px; height:54px; border-radius:50%; object-fit:cover; border:2px solid #C9F89B;'>
                                    <div>
                                        <div style="font-weight:700; color:#3A5D1A; font-size:1.18em; margin-bottom:2px;"> <?php echo htmlspecialchars(isset($v['valorador_nombre']) && $v['valorador_nombre'] ? $v['valorador_nombre'] : 'Usuario'); ?> </div>
                                        <div style="font-size:1em; color:#888;"> <?php echo date('d/m/Y', strtotime($v['created_at'])); ?> </div>
                                    </div>
                                </div>
                                <div style="font-size:1.13em; color:#333; margin-bottom:8px; word-break:break-word; line-height:1.6; padding-left:4px;"> <?php echo $v['comentario'] ? htmlspecialchars($v['comentario']) : '<span style=\'color:#bbb\'>(Sin comentario)</span>'; ?> </div>
                                <div style="display:flex; align-items:center; gap:10px; margin-left:4px;">
                                    <span>
                                        <?php
                                        $p = intval(round($v['puntuacion']));
                                        for ($i=1; $i<=5; $i++) {
                                            echo "<i class='fas fa-star' style='color:".($i<=$p?'#FFD700':'#eee')."; font-size:1.3em;'></i>";
                                        }
                                        ?>
                                    </span>
                                    <span style="font-size:1.18em; color:#FFD700; font-weight:bold; margin-left:6px;"> <?php echo $v['puntuacion']; ?> </span>
                                </div>
                            </div>
                            <?php if (count($valoraciones_top) > 1): ?>
                                <button id="btn-mostrar-mas-valoraciones" onclick="document.getElementById('valoraciones-extra').style.display='block'; this.style.display='none';" style="margin:8px 0 0 0; background:#C9F89B; color:#313C26; border:none; border-radius:8px; padding:8px 18px; font-size:1em; cursor:pointer; font-weight:600;">Ver más valoraciones</button>
                                <div id="valoraciones-extra" style="display:none; width:100%; margin-top:8px;">
                                    <?php for ($i=1; $i<count($valoraciones_top); $i++): $v = $valoraciones_top[$i]; ?>
                                        <div style="background:#F8FFF2; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.07); padding:16px 24px 16px 24px; min-width:320px; max-width:700px; width:100%; display:flex; flex-direction:column; align-items:flex-start; gap:8px; margin-bottom:6px; min-height:70px;">
                                            <div style="display:flex; align-items:center; gap:16px; margin-bottom:6px;">
                                                <img src='img/usuario.svg' style='width:54px; height:54px; border-radius:50%; object-fit:cover; border:2px solid #C9F89B;'>
                                                <div>
                                                    <div style="font-weight:700; color:#3A5D1A; font-size:1.18em; margin-bottom:2px;"> <?php echo htmlspecialchars(isset($v['valorador_nombre']) && $v['valorador_nombre'] ? $v['valorador_nombre'] : 'Usuario'); ?> </div>
                                                    <div style="font-size:1em; color:#888;"> <?php echo date('d/m/Y', strtotime($v['created_at'])); ?> </div>
                                                </div>
                                            </div>
                                            <div style="font-size:1.13em; color:#333; margin-bottom:8px; word-break:break-word; line-height:1.6; padding-left:4px;"> <?php echo $v['comentario'] ? htmlspecialchars($v['comentario']) : '<span style=\'color:#bbb\'>(Sin comentario)</span>'; ?> </div>
                                            <div style="display:flex; align-items:center; gap:10px; margin-left:4px;">
                                                <span>
                                                    <?php
                                                    $p = intval(round($v['puntuacion']));
                                                    for ($j=1; $j<=5; $j++) {
                                                        echo "<i class='fas fa-star' style='color:".($j<=$p?'#FFD700':'#eee')."; font-size:1.3em;'></i>";
                                                    }
                                                    ?>
                                                </span>
                                                <span style="font-size:1.18em; color:#FFD700; font-weight:bold; margin-left:6px;"> <?php echo $v['puntuacion']; ?> </span>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                    <button id="btn-ocultar-valoraciones" onclick="document.getElementById('valoraciones-extra').style.display='none'; document.getElementById('btn-mostrar-mas-valoraciones').style.display='inline-block';" style="margin:8px 0 0 0; background:#eee; color:#313C26; border:none; border-radius:8px; padding:8px 18px; font-size:1em; cursor:pointer; font-weight:600; width:100%;">Ocultar valoraciones</button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
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
                    <a href="mis-productos.php" class="btn-view-all">Ver todos</a>
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
                        <button class="quick-action-btn" onclick="mostrarValoraciones()">
                            <i class="fas fa-star"></i>
                            <span>Valoraciones</span>
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

<!-- MODAL DE VALORACIONES: debe ir fuera de cualquier contenedor principal -->
<div id="valoraciones-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.32); z-index:99999; align-items:center; justify-content:center; transition:background 0.2s;">
    <div id="valoraciones-modal-content" style="background:white; max-width:700px; width:98vw; border-radius:22px; box-shadow:0 12px 40px rgba(0,0,0,0.22); padding:44px 36px 36px 36px; position:relative; min-height:350px; max-height:80vh; display:flex; flex-direction:column; justify-content:flex-start; animation:fadeInModal 0.35s cubic-bezier(.4,1.4,.6,1.0);">
        <button onclick="cerrarValoracionesModal()" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:28px; color:#888; cursor:pointer; transition:color 0.2s;" onmouseover="this.style.color='#313C26'" onmouseout="this.style.color='#888'"><i class='fas fa-times'></i></button>
        <h2 style="margin-top:0; color:#3A5D1A; font-size:2.1em; margin-bottom:18px; font-family:'Segoe UI',sans-serif; letter-spacing:0.5px;">Valoraciones recibidas</h2>
        <div id="valoraciones-list" style="flex:1; overflow-y:auto; margin-top:18px; padding-right:8px; min-height:120px;"></div>
    </div>
</div>

<style>
@keyframes fadeInModal {
  from { opacity:0; transform:scale(0.97); }
  to { opacity:1; transform:scale(1); }
}
</style>

<script>
function cerrarValoracionesModal() {
    document.getElementById('valoraciones-modal').style.display = 'none';
    document.removeEventListener('keydown', escListenerValoraciones);
}
function escListenerValoraciones(e) {
    if (e.key === 'Escape') cerrarValoracionesModal();
}
function mostrarValoraciones() {
    const modal = document.getElementById('valoraciones-modal');
    const list = document.getElementById('valoraciones-list');
    list.innerHTML = '<div style="color:#888; text-align:center; padding:20px;">Cargando valoraciones...</div>';
    modal.style.display = 'flex';
    setTimeout(() => document.addEventListener('keydown', escListenerValoraciones), 100);
    fetch('api/valoraciones.php?usuario_id=<?php echo $user['id']; ?>')
        .then(r => r.json())
        .then(res => {
            let valoraciones = [];
            let promedio = 0;
            let total = 0;
            if (Array.isArray(res)) {
                valoraciones = res;
            } else if (res.data && Array.isArray(res.data.valoraciones)) {
                valoraciones = res.data.valoraciones;
                promedio = res.data.promedio || 0;
                total = res.data.total || valoraciones.length;
            } else if (res.valoraciones && Array.isArray(res.valoraciones)) {
                valoraciones = res.valoraciones;
                promedio = res.promedio || 0;
                total = res.total || valoraciones.length;
            }
            if (valoraciones.length > 0) {
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    stars += `<i class='fas fa-star' style='color:${i <= Math.round(promedio) ? '#FFD700' : '#eee'}; font-size:1.5em;'></i>`;
                }
                list.innerHTML = `
                    <div style='text-align:center; margin-bottom:24px;'>
                        <div style='font-size:1.2em; color:#3A5D1A; font-weight:600; font-family:Segoe UI;'>Promedio: <span style='color:#FFD700; font-size:1.5em;'>${promedio.toFixed(1)}</span> ${stars}</div>
                        <div style='font-size:1em; color:#888; margin-bottom:10px;'>${total} valoraciones</div>
                    </div>
                    <div style='display:flex; flex-direction:column; gap:24px;'>
                        ${valoraciones.map(v => `
                            <div style="background:linear-gradient(90deg,#F8FFF2 80%,#F3F3F3 100%); border-radius:16px; box-shadow:0 2px 12px rgba(60,120,40,0.07); padding:22px 20px; display:flex; align-items:center; gap:22px; margin:0 8px; min-height:80px; border:1.5px solid #C9F89B; transition:box-shadow 0.2s;">
                                <img src="${v.valorador_avatar || 'img/usuario.svg'}" style="width:62px; height:62px; border-radius:50%; object-fit:cover; border:2.5px solid #C9F89B; box-shadow:0 2px 8px #C9F89B33;">
                                <div style="flex:1; min-width:0;">
                                    <div style="font-weight:600; color:#3A5D1A; font-size:1.15em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:Segoe UI;">${v.valorador_nombre || v.valorador_username || v.valorador_id || 'Usuario'}</div>
                                    <div style="font-size:14px; color:#888; margin-bottom:2px;">${v.created_at ? (new Date(v.created_at)).toLocaleDateString() : ''}</div>
                                    <div style="font-size:16px; color:#333; margin-top:8px; white-space:pre-line; word-break:break-word; font-family:Segoe UI;">${v.comentario ? v.comentario : '<span style=\'color:#bbb\'>(Sin comentario)</span>'}</div>
                                </div>
                                <div style="font-size:2.2em; color:#FFD700; font-weight:bold; min-width:62px; text-align:center; text-shadow:0 2px 8px #FFD70033;">${v.puntuacion} <i class="fas fa-star"></i></div>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                list.innerHTML = '<div style="color:#888; text-align:center; padding:20px;">No hay valoraciones aún.</div>';
            }
        })
        .catch(() => {
            list.innerHTML = '<div style="color:red; text-align:center; padding:20px;">Error al cargar valoraciones.</div>';
        });
}
</script>

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
    padding: 20px 30px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 1px solid rgba(0,0,0,0.05);
    display: flex;
    justify-content: flex-start;
    align-items: center;
    gap: 20px;
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
    flex-shrink: 0;
    margin: 0;
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
    outline: 3px solid #6a994e !important;
    outline-opacity: 0.75;
}

.cropper-face {
    background: rgba(106, 153, 78, 0.1) !important;
}

.cropper-line, .cropper-point {
    background: #6a994e !important;
}

.cropper-point.point-se {
    background: #C9F89B !important;
    width: 10px !important;
    height: 10px !important;
    border-radius: 50% !important;
}

/* Estilos personalizados para el modal de avatar */
.avatar-crop-modal {
    border-radius: 15px !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2) !important;
}

.avatar-crop-title {
    color: #2c3e50 !important;
    font-size: 22px !important;
    padding: 20px 20px 10px !important;
}

.avatar-crop-container {
    padding: 0 20px 20px !important;
}

.avatar-confirm-btn {
    background: linear-gradient(135deg, #6a994e 0%, #5a8442 100%) !important;
    border: none !important;
    padding: 12px 30px !important;
    font-weight: 600 !important;
    font-size: 15px !important;
    border-radius: 8px !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 15px rgba(106, 153, 78, 0.3) !important;
}

.avatar-confirm-btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(106, 153, 78, 0.4) !important;
}

.avatar-cancel-btn {
    padding: 12px 30px !important;
    font-weight: 600 !important;
    font-size: 15px !important;
    border-radius: 8px !important;
    transition: all 0.3s ease !important;
}

.avatar-cancel-btn:hover {
    background: #c82333 !important;
    transform: translateY(-2px) !important;
}
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
<script src="js/perfil-usuario.js?v=<?php echo time(); ?>"></script>

<script>
// === VARIABLES GLOBALES ===
window.IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
window.USER_ID = <?php echo isset($user['id']) ? intval($user['id']) : 'null'; ?>;
window.CURRENT_USER_ID = <?php echo isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'null'; ?>;

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
    console.log('Actualizando información personal:', userData);
    
    const formData = new FormData();
    formData.append('action', 'update_personal_info');
    formData.append('fullname', userData.fullname);
    formData.append('username', userData.username);
    formData.append('email', userData.email);
    formData.append('phone', userData.phone);
    formData.append('current_password', userData.currentPassword);
    
    // Validaciones del lado del cliente
    if (!userData.fullname || userData.fullname.trim().length < 2) {
        Swal.fire({
            title: '❌ Error de Validación',
            text: 'El nombre completo debe tener al menos 2 caracteres',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
        return;
    }

    if (!userData.username || userData.username.trim().length < 3) {
        Swal.fire({
            title: '❌ Error de Validación',
            text: 'El nombre de usuario debe tener al menos 3 caracteres',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
        return;
    }

    // Validar formato de username (solo letras, números y guiones bajos)
    if (!/^[a-zA-Z0-9_]+$/.test(userData.username)) {
        Swal.fire({
            title: '❌ Error de Validación',
            text: 'El nombre de usuario solo puede contener letras, números y guiones bajos',
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
        return;
    }

    console.log('Enviando petición al servidor...');
    
    fetch('api/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Respuesta del servidor:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text();
    })
    .then(textData => {
        console.log('Respuesta del servidor (texto):', textData);
        
        try {
            const data = JSON.parse(textData);
            console.log('Datos parseados:', data);
            
            if (data.success) {
                // Éxito: actualizar la página con la nueva información
                updatePageWithNewInfo(data.data);
                
                // Mostrar mensaje de éxito
                Swal.fire({
                    title: '✅ ¡Información Actualizada!',
                    html: `
                        <div style="text-align: left;">
                            <p>Tu información personal se ha actualizado correctamente:</p>
                            <ul style="margin-top: 10px;">
                                <li>Nombre: ${data.data.fullname}</li>
                                <li>Usuario: @${data.data.username}</li>
                                <li>Email: ${data.data.email}</li>
                            </ul>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonColor: '#A2CB8D',
                    confirmButtonText: 'Entendido'
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
                        return `<li style="margin: 8px 0; text-align: left; padding: 5px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 3px;">${error}</li>`;
                    }).join('');
                    
                    errorMessage = `
                        <div style="text-align: left;">
                            <p><strong>❌ Se encontraron ${data.details.errors.length} problema(s):</strong></p>
                            <ul style="margin: 15px 0; padding: 0; list-style: none;">
                                ${errorDetails}
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
                    `;
                } else {
                    // Error simple sin detalles
                    errorMessage = `
                        <div style="text-align: left;">
                            <p>${errorMessage}</p>
                            <div style="background: #ffebee; padding: 10px; border-radius: 4px; margin-top: 10px;">
                                <strong>🔍 Detalles técnicos:</strong><br>
                                <code style="font-size: 12px;">${JSON.stringify(data, null, 2)}</code>
                            </div>
                        </div>
                    `;
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
        
        // Actualizar título de la página
        if (newData.fullname) {
            document.title = `${newData.fullname} - Mi Perfil - HandinHand`;
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
                    console.error('Elemento connectivityResults no encontrado después del timeout');
                    // Intentar una vez más con un delay mayor
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
            text: 'No se pudo inicializar el sistema de diagnóstico. Revisa la consola para más detalles.',
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
            
            currentDiv.innerHTML = `
                <div style="color: green; padding: 5px;">✅ Test 1: Conectividad OK</div>
                <div style="margin: 10px 0; color: blue; padding: 5px;">🔄 Test 2: Probando update-profile.php...</div>
            `;
            
            // Test 2: API de update-profile con delay
            setTimeout(() => testUpdateProfileAPI(), 500);
            
        } catch (parseError) {
            console.error('Error parsing JSON:', parseError);
            currentDiv.innerHTML = `
                <div style="color: red; padding: 5px;">❌ Test 1: Error de JSON</div>
                <div style="background: #f8f8f8; padding: 10px; font-family: monospace; font-size: 12px; margin: 10px 0; max-height: 200px; overflow-y: auto; border-radius: 4px;">
                    <strong>Error:</strong> ${parseError.message}<br><br>
                    <strong>Respuesta:</strong><br>
                    ${textData.replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                </div>
                <button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Cerrar</button>
            `;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        
        const currentDiv = document.getElementById('connectivityResults');
        if (currentDiv) {
            currentDiv.innerHTML = `
                <div style="color: red; padding: 5px;">❌ Test 1: Error de conexión</div>
                <div style="margin: 10px 0; color: #666; padding: 5px;">
                    <strong>Error:</strong> ${error.message}<br>
                    <strong>Posibles causas:</strong>
                    <ul style="margin: 5px 0; padding-left: 20px;">
                        <li>Servidor web no está ejecutándose</li>
                        <li>Archivo test-connectivity.php no existe</li>
                        <li>Problema de permisos</li>
                    </ul>
                </div>
                <button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Cerrar</button>
            `;
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
                resultsDiv.innerHTML = `
                    <div style="color: green;">✅ Test 1: Conectividad OK</div>
                    <div style="color: orange;">⚠️ Test 2: Error en API</div>
                    <div style="margin: 10px 0; color: #666;">${data.message}</div>
                    <button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Cerrar</button>
                `;
            }
        } catch (parseError) {
            resultsDiv.innerHTML = `
                <div style="color: green;">✅ Test 1: Conectividad OK</div>
                <div style="color: red;">❌ Test 2: Error de JSON en update-profile.php</div>
                <div style="background: #f8f8f8; padding: 10px; font-family: monospace; font-size: 12px; margin: 10px 0; max-height: 200px; overflow-y: auto;">
                    <strong>Error de parsing:</strong> ${parseError.message}<br><br>
                    <strong>Respuesta cruda:</strong><br>
                    ${textData.replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                </div>
                <button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Cerrar</button>
            `;
        }
    })
    .catch(error => {
        resultsDiv.innerHTML = `
            <div style="color: green;">✅ Test 1: Conectividad OK</div>
            <div style="color: red;">❌ Test 2: Error de conexión en update-profile.php</div>
            <div style="margin: 10px 0; color: #666;">
                <strong>Error:</strong> ${error.message}<br>
                <strong>Posibles causas:</strong>
                <ul style="margin: 5px 0; padding-left: 20px;">
                    <li>Archivo update-profile.php no existe o no es accesible</li>
                    <li>Error de sintaxis en PHP</li>
                    <li>Problema con includes/functions.php</li>
                    <li>Error de base de datos</li>
                </ul>
            </div>
            <button onclick="Swal.close()" style="background: #A2CB8D; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Cerrar</button>
        `;
    });
}

// Función simple de test de conectividad (más confiable)
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
                html: `
                    <div style="text-align: left;">
                        <p><strong>✅ Servidor web:</strong> Funcionando</p>
                        <p><strong>✅ PHP:</strong> Funcionando</p>
                        <p><strong>✅ JSON:</strong> Válido</p>
                        <p><strong>📊 Respuesta:</strong></p>
                        <div style="background: #f8f8f8; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                            ${JSON.stringify(data, null, 2)}
                        </div>
                        <div style="margin-top: 15px; padding: 10px; background: #e8f5e8; border-radius: 4px;">
                            <strong>🎉 ¡Todo funciona!</strong> Puedes intentar cambiar la contraseña.
                        </div>
                    </div>
                `,
                icon: 'success',
                confirmButtonColor: '#A2CB8D',
                width: '500px'
            });
            
        } catch (parseError) {
            // Error de JSON
            Swal.fire({
                title: '⚠️ Error de JSON',
                html: `
                    <div style="text-align: left;">
                        <p><strong>✅ Servidor web:</strong> Funcionando</p>
                        <p><strong>❌ JSON:</strong> Inválido</p>
                        <p><strong>🐛 Error:</strong> ${parseError.message}</p>
                        <p><strong>📄 Respuesta raw:</strong></p>
                        <div style="background: #f8f8f8; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                            ${textData.replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                        </div>
                    </div>
                `,
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
            html: `
                <div style="text-align: left;">
                    <p><strong>❌ Servidor web:</strong> No responde</p>
                    <p><strong>🐛 Error:</strong> ${error.message}</p>
                    <p><strong>🔧 Posibles soluciones:</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Verificar que WAMP esté ejecutándose</li>
                        <li>Comprobar que el archivo api/test-connectivity.php existe</li>
                        <li>Revisar permisos de archivos</li>
                        <li>Verificar configuración del servidor</li>
                    </ul>
                </div>
            `,
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
                <div style="background: #fff3cd; padding: 10px; border-radius: 6px; border-left: 4px solid #ffc107;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #dc3545; font-size: 14px;">
                        <i class="fas fa-shield-alt"></i> Seguridad:
                    </label>
                    <small style="color: #856404; font-size: 11px;">
                        Por seguridad, deberás iniciar sesión nuevamente después del cambio.
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
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
