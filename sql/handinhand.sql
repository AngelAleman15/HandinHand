-- UNIFICACIÓN DE SCRIPTS SQL - HANDINHAND
-- Fecha de unificación: 2025-10-23
-- Este archivo contiene todos los scripts de migración, creación y alteración de tablas del sistema HandinHand.

-- ========== add_message_actions.sql ==========
-- ========== DEFINICIÓN DE TABLA PRINCIPAL: productos ========== 
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    estado VARCHAR(50) DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    latitud DECIMAL(10,8) DEFAULT NULL,
    longitud DECIMAL(11,8) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Agregar columnas para eliminar y editar mensajes
ALTER TABLE mensajes 
ADD COLUMN edited_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN deleted_for TEXT NULL DEFAULT NULL COMMENT 'JSON array de user IDs que eliminaron el mensaje para ellos',
ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE COMMENT 'True si el remitente eliminó el mensaje completamente';
ALTER TABLE mensajes ADD INDEX idx_is_deleted (is_deleted);

-- ========== add_perseo_auto_column.sql ==========
DELIMITER $$
DROP PROCEDURE IF EXISTS add_perseo_auto_column$$
CREATE PROCEDURE add_perseo_auto_column()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'is_perseo_auto'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN is_perseo_auto TINYINT(1) DEFAULT 0 AFTER message;
    END IF;
END$$
DELIMITER ;
CALL add_perseo_auto_column();
DROP PROCEDURE IF EXISTS add_perseo_auto_column;
CREATE INDEX IF NOT EXISTS idx_unread_messages ON mensajes(receiver_id, is_read);

-- ========== add_perseo_auto_column_simple.sql ==========
ALTER TABLE mensajes ADD COLUMN is_perseo_auto TINYINT(1) DEFAULT 0 AFTER message;
CREATE INDEX idx_unread_messages ON mensajes(receiver_id, is_read);

-- ========== add_reply_and_deleted_features.sql ==========
CREATE TABLE IF NOT EXISTS mensajes_eliminados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mensaje_id INT NOT NULL,
    eliminado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_mensaje (user_id, mensaje_id),
    INDEX idx_user_id (user_id),
    INDEX idx_mensaje_id (mensaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS chat_eliminado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    other_user_id INT NOT NULL,
    eliminado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat_deletion (user_id, other_user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_other_user_id (other_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== add_valoracion_delete_feature.sql ==========
USE handinhand;
SHOW CREATE TABLE valoraciones;

-- ========== chat.sql ==========
ALTER TABLE productos
    ADD COLUMN latitud DECIMAL(10,8) DEFAULT NULL,
    ADD COLUMN longitud DECIMAL(11,8) DEFAULT NULL,
    MODIFY COLUMN estado VARCHAR(50) DEFAULT 'disponible';
CREATE TABLE IF NOT EXISTS producto_categorias (
    producto_id INT NOT NULL,
    categoria_id INT NOT NULL,
    PRIMARY KEY (producto_id, categoria_id),
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);
CREATE TABLE IF NOT EXISTS producto_imagenes (
    id INT NOT NULL AUTO_INCREMENT,
    producto_id INT NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);
CREATE TABLE IF NOT EXISTS mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES usuarios(id)
);
ALTER TABLE mensajes ADD INDEX idx_sender (sender_id);
ALTER TABLE mensajes ADD INDEX idx_created (created_at);

-- ========== fix_emoji_support.sql ==========
ALTER DATABASE handinhand CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
ALTER TABLE mensajes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE mensajes MODIFY COLUMN mensaje TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE productos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE valoraciones CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ========== perfiles_sociales.sql ==========
ALTER TABLE usuarios ENGINE=InnoDB;
ALTER TABLE productos ENGINE=InnoDB;
ALTER TABLE valoraciones ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS denuncias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    denunciante_id INT NOT NULL,
    denunciado_id INT NOT NULL,
    motivo ENUM('spam', 'fraude', 'contenido_inapropiado', 'acoso', 'suplantacion', 'otro') NOT NULL,
    descripcion TEXT,
    estado ENUM('pendiente', 'en_revision', 'resuelta', 'rechazada') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (denunciante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (denunciado_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_denunciante (denunciante_id),
    INDEX idx_denunciado (denunciado_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS solicitudes_amistad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitante_id INT NOT NULL,
    receptor_id INT NOT NULL,
    estado ENUM('pendiente', 'aceptada', 'rechazada') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (receptor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_solicitud (solicitante_id, receptor_id),
    INDEX idx_solicitante (solicitante_id),
    INDEX idx_receptor (receptor_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS amistades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario1_id INT NOT NULL,
    usuario2_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario1_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario2_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_amistad (usuario1_id, usuario2_id),
    INDEX idx_usuario1 (usuario1_id),
    INDEX idx_usuario2 (usuario2_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE valoraciones 
MODIFY COLUMN puntuacion DECIMAL(2,1) DEFAULT NULL 
CHECK (puntuacion >= 0 AND puntuacion <= 5 AND MOD(puntuacion * 10, 5) = 0);
CREATE TABLE IF NOT EXISTS estadisticas_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    promedio_valoracion DECIMAL(2,1) DEFAULT 0,
    total_valoraciones INT DEFAULT 0,
    total_productos INT DEFAULT 0,
    total_amigos INT DEFAULT 0,
    total_intercambios INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_promedio (promedio_valoracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO estadisticas_usuario (usuario_id, promedio_valoracion, total_valoraciones, total_productos)
SELECT 
    u.id,
    COALESCE(AVG(v.puntuacion), 0) as promedio,
    COUNT(v.id) as total_val,
    (SELECT COUNT(*) FROM productos p WHERE p.user_id = u.id) as total_prod
FROM usuarios u
LEFT JOIN valoraciones v ON u.id = v.usuario_id
GROUP BY u.id
ON DUPLICATE KEY UPDATE
    promedio_valoracion = VALUES(promedio_valoracion),
    total_valoraciones = VALUES(total_valoraciones),
    total_productos = VALUES(total_productos);

-- ========== permitir_multiples_valoraciones.sql ==========
USE handinhand;
ALTER TABLE valoraciones DROP INDEX IF EXISTS unique_valoracion;
SHOW CREATE TABLE valoraciones;

-- ========== tablas_complementarias.sql ==========
USE handinhand;
SELECT CONCAT('✅ Base de datos actual: ', DATABASE()) AS status;
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
SELECT '✅ Tablas complementarias creadas exitosamente' as Status;
SHOW TABLES LIKE 'mensajes_eliminados';
SHOW TABLES LIKE 'chat_eliminado';
SHOW TABLES LIKE 'notificaciones';
SHOW TABLES LIKE 'sesiones_activas';
SHOW TABLES LIKE 'intercambios';
SHOW TABLES LIKE 'productos_favoritos';
SHOW TABLES LIKE 'reportes';

-- ========== tablas_complementarias_simple.sql ==========
CREATE TABLE IF NOT EXISTS mensajes_eliminados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mensaje_id INT NOT NULL,
    eliminado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_mensaje (user_id, mensaje_id),
    INDEX idx_user_id (user_id),
    INDEX idx_mensaje_id (mensaje_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS chat_eliminado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    other_user_id INT NOT NULL,
    eliminado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat_deletion (user_id, other_user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_other_user_id (other_user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
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
CREATE TABLE IF NOT EXISTS productos_favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    producto_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorito (user_id, producto_id),
    INDEX idx_user_id (user_id),
    INDEX idx_producto_id (producto_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
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

-- ========== unificar_mensajes.sql ==========
USE handinhand;
DELIMITER $$
DROP PROCEDURE IF EXISTS agregar_columnas_mensajes$$
CREATE PROCEDURE agregar_columnas_mensajes()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'sender_id'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN sender_id INT NOT NULL DEFAULT 0 AFTER id;
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'receiver_id'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN receiver_id INT NOT NULL DEFAULT 0 AFTER sender_id;
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'message'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN message TEXT NOT NULL AFTER receiver_id;
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'is_perseo_auto'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN is_perseo_auto TINYINT(1) DEFAULT 0 AFTER message;
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'is_read'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER is_perseo_auto;
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'read_at'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL AFTER is_read;
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'reply_to_message_id'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN reply_to_message_id INT NULL AFTER read_at;
    END IF;
END$$
DELIMITER ;
CALL agregar_columnas_mensajes();
DROP PROCEDURE IF EXISTS agregar_columnas_mensajes;
UPDATE mensajes 
SET 
    sender_id = COALESCE(remitente_id, sender_id),
    receiver_id = COALESCE(destinatario_id, receiver_id),
    message = COALESCE(mensaje, message),
    is_read = COALESCE(leido, is_read)
WHERE (remitente_id IS NOT NULL AND sender_id = 0)
   OR (destinatario_id IS NOT NULL AND receiver_id = 0)
   OR (mensaje IS NOT NULL AND message = '');
-- Paso 3: Eliminar columnas antiguas (OPCIONAL)
-- ALTER TABLE mensajes DROP COLUMN remitente_id;
-- ALTER TABLE mensajes DROP COLUMN destinatario_id;
-- ALTER TABLE mensajes DROP COLUMN mensaje;
-- ALTER TABLE mensajes DROP COLUMN leido;
-- ALTER TABLE mensajes DROP COLUMN producto_id;
DELIMITER $$
DROP PROCEDURE IF EXISTS crear_indices_mensajes$$
CREATE PROCEDURE crear_indices_mensajes()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_sender'
    ) THEN
        CREATE INDEX idx_sender ON mensajes(sender_id);
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_receiver'
    ) THEN
        CREATE INDEX idx_receiver ON mensajes(receiver_id);
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_unread_messages'
    ) THEN
        CREATE INDEX idx_unread_messages ON mensajes(receiver_id, is_read);
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_created_at'
    ) THEN
        CREATE INDEX idx_created_at ON mensajes(created_at);
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_perseo_auto'
    ) THEN
        CREATE INDEX idx_perseo_auto ON mensajes(is_perseo_auto);
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_reply_to'
    ) THEN
        CREATE INDEX idx_reply_to ON mensajes(reply_to_message_id);
    END IF;
END$$
DELIMITER ;
CALL crear_indices_mensajes();
DROP PROCEDURE IF EXISTS crear_indices_mensajes;
SELECT 
    COUNT(*) as total_mensajes,
    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as mensajes_leidos,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as mensajes_no_leidos,
    SUM(CASE WHEN is_perseo_auto = 1 THEN 1 ELSE 0 END) as mensajes_perseo
FROM mensajes;
SELECT '✅ Migración completada exitosamente' as Status;
