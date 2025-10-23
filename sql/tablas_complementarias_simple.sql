-- ============================================
-- SCRIPT SIMPLE: TABLAS COMPLEMENTARIAS
-- ============================================
-- Ejecutar DESPUÉS de seleccionar la base de datos handinhand en phpMyAdmin

-- Tabla 1: Mensajes eliminados
CREATE TABLE IF NOT EXISTS mensajes_eliminados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mensaje_id INT NOT NULL,
    eliminado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_mensaje (user_id, mensaje_id),
    INDEX idx_user_id (user_id),
    INDEX idx_mensaje_id (mensaje_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- Tabla 2: Chat eliminado
CREATE TABLE IF NOT EXISTS chat_eliminado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    other_user_id INT NOT NULL,
    eliminado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat_deletion (user_id, other_user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_other_user_id (other_user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- Tabla 3: Notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tipo ENUM('mensaje', 'producto', 'valoracion', 'sistema') NOT NULL DEFAULT 'mensaje',
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT,
    leida TINYINT(1) DEFAULT 0,
    url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    leida_en TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_leida (leida),
    INDEX idx_created (created_at)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- Tabla 4: Sesiones activas
CREATE TABLE IF NOT EXISTS sesiones_activas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    socket_id VARCHAR(255),
    ultima_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_ultima_actividad (ultima_actividad)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- Tabla 5: Intercambios
CREATE TABLE IF NOT EXISTS intercambios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_ofrecido_id INT NOT NULL,
    producto_solicitado_id INT NOT NULL,
    usuario_ofrecedor_id INT NOT NULL,
    usuario_solicitante_id INT NOT NULL,
    estado ENUM('pendiente', 'aceptado', 'rechazado', 'completado', 'cancelado') DEFAULT 'pendiente',
    mensaje_propuesta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_producto_ofrecido (producto_ofrecido_id),
    INDEX idx_producto_solicitado (producto_solicitado_id),
    INDEX idx_usuario_ofrecedor (usuario_ofrecedor_id),
    INDEX idx_usuario_solicitante (usuario_solicitante_id),
    INDEX idx_estado (estado)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- Tabla 6: Productos favoritos
CREATE TABLE IF NOT EXISTS productos_favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    producto_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorito (user_id, producto_id),
    INDEX idx_user_id (user_id),
    INDEX idx_producto_id (producto_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- Tabla 7: Reportes
CREATE TABLE IF NOT EXISTS reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reportador_id INT NOT NULL,
    tipo ENUM('usuario', 'producto', 'mensaje') NOT NULL,
    referencia_id INT NOT NULL,
    motivo ENUM('spam', 'contenido_inapropiado', 'estafa', 'otro') NOT NULL,
    descripcion TEXT,
    estado ENUM('pendiente', 'en_revision', 'resuelto', 'rechazado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revisado_en TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_reportador (reportador_id),
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

SELECT '✅ Todas las tablas creadas exitosamente' AS Resultado;
