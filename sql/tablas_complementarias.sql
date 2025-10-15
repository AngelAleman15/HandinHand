-- ============================================
-- TABLAS COMPLEMENTARIAS PARA HANDINHAND
-- ============================================
-- Ejecutar después de unificar_mensajes.sql

-- IMPORTANTE: Asegurarse de estar en la base de datos correcta
USE handinhand;

-- Verificar que estamos en la BD correcta
SELECT CONCAT('✅ Base de datos actual: ', DATABASE()) AS status;

-- ============================================
-- 1. Tabla para mensajes eliminados (por usuario)
-- ============================================
-- Permite que cada usuario elimine mensajes solo para sí mismo
CREATE TABLE IF NOT EXISTS mensajes_eliminados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Usuario que eliminó el mensaje',
    mensaje_id INT NOT NULL COMMENT 'ID del mensaje eliminado',
    eliminado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_mensaje (user_id, mensaje_id),
    INDEX idx_user_id (user_id),
    INDEX idx_mensaje_id (mensaje_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de mensajes eliminados por usuario';

-- ============================================
-- 2. Tabla para historial de chat eliminado
-- ============================================
-- Permite eliminar todo el historial de chat con un usuario
CREATE TABLE IF NOT EXISTS chat_eliminado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Usuario que eliminó el chat',
    other_user_id INT NOT NULL COMMENT 'Usuario con quien se eliminó el chat',
    eliminado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat_deletion (user_id, other_user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_other_user_id (other_user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de chats eliminados completamente';

-- ============================================
-- 3. Tabla para notificaciones
-- ============================================
-- Sistema de notificaciones push para eventos importantes
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Usuario que recibe la notificación',
    tipo ENUM('mensaje', 'producto', 'valoracion', 'sistema') NOT NULL DEFAULT 'mensaje',
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT,
    leida TINYINT(1) DEFAULT 0,
    url VARCHAR(255) COMMENT 'URL a donde redirigir al hacer clic',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    leida_en TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_leida (leida),
    INDEX idx_created (created_at)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sistema de notificaciones para usuarios';

-- ============================================
-- 4. Tabla para sesiones activas
-- ============================================
-- Control de sesiones de usuarios (para chat en tiempo real)
CREATE TABLE IF NOT EXISTS sesiones_activas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    socket_id VARCHAR(255) COMMENT 'ID del socket para WebSocket/Socket.io',
    ultima_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_ultima_actividad (ultima_actividad)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Control de sesiones activas para chat en tiempo real';

-- ============================================
-- 5. Tabla para intercambios/trueques
-- ============================================
-- Registro de intercambios realizados
CREATE TABLE IF NOT EXISTS intercambios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_ofrecido_id INT NOT NULL COMMENT 'Producto que se ofrece',
    producto_solicitado_id INT NOT NULL COMMENT 'Producto que se solicita',
    usuario_ofrecedor_id INT NOT NULL,
    usuario_solicitante_id INT NOT NULL,
    estado ENUM('pendiente', 'aceptado', 'rechazado', 'completado', 'cancelado') DEFAULT 'pendiente',
    mensaje_propuesta TEXT COMMENT 'Mensaje inicial de la propuesta',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_producto_ofrecido (producto_ofrecido_id),
    INDEX idx_producto_solicitado (producto_solicitado_id),
    INDEX idx_usuario_ofrecedor (usuario_ofrecedor_id),
    INDEX idx_usuario_solicitante (usuario_solicitante_id),
    INDEX idx_estado (estado)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de propuestas de intercambio';

-- ============================================
-- 6. Tabla para favoritos/productos guardados
-- ============================================
CREATE TABLE IF NOT EXISTS productos_favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    producto_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorito (user_id, producto_id),
    INDEX idx_user_id (user_id),
    INDEX idx_producto_id (producto_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Productos marcados como favoritos';

-- ============================================
-- 7. Tabla para reportes/denuncias
-- ============================================
CREATE TABLE IF NOT EXISTS reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reportador_id INT NOT NULL COMMENT 'Usuario que hace el reporte',
    tipo ENUM('usuario', 'producto', 'mensaje') NOT NULL,
    referencia_id INT NOT NULL COMMENT 'ID del usuario/producto/mensaje reportado',
    motivo ENUM('spam', 'contenido_inapropiado', 'estafa', 'otro') NOT NULL,
    descripcion TEXT,
    estado ENUM('pendiente', 'en_revision', 'resuelto', 'rechazado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revisado_en TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_reportador (reportador_id),
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sistema de reportes y denuncias';

-- ============================================
-- 8. Vista para estadísticas de usuarios
-- ============================================
CREATE OR REPLACE VIEW estadisticas_usuarios AS
SELECT 
    u.id,
    u.username,
    u.fullname,
    COUNT(DISTINCT p.id) as total_productos,
    COUNT(DISTINCT CASE WHEN p.estado = 'disponible' THEN p.id END) as productos_disponibles,
    COUNT(DISTINCT v.id) as total_valoraciones,
    COALESCE(AVG(v.puntuacion), 0) as promedio_valoracion,
    COUNT(DISTINCT i.id) as total_intercambios,
    u.created_at as miembro_desde
FROM usuarios u
LEFT JOIN productos p ON u.id = p.user_id
LEFT JOIN valoraciones v ON u.id = v.usuario_id
LEFT JOIN intercambios i ON (u.id = i.usuario_ofrecedor_id OR u.id = i.usuario_solicitante_id)
GROUP BY u.id;

-- Verificar creación de tablas
SELECT '✅ Tablas complementarias creadas exitosamente' as Status;

-- Listar las tablas creadas (versión compatible)
SHOW TABLES LIKE 'mensajes_eliminados';
SHOW TABLES LIKE 'chat_eliminado';
SHOW TABLES LIKE 'notificaciones';
SHOW TABLES LIKE 'sesiones_activas';
SHOW TABLES LIKE 'intercambios';
SHOW TABLES LIKE 'productos_favoritos';
SHOW TABLES LIKE 'reportes';
