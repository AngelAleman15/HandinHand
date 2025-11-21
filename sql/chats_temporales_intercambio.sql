-- Tabla para chats temporales (cuando los usuarios no son amigos)
CREATE TABLE IF NOT EXISTS chats_temporales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario1_id INT NOT NULL,
    usuario2_id INT NOT NULL,
    producto_relacionado_id INT,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 7 DAY),
    FOREIGN KEY (usuario1_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario2_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_relacionado_id) REFERENCES productos(id) ON DELETE SET NULL,
    INDEX idx_usuarios (usuario1_id, usuario2_id),
    INDEX idx_activo (activo),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir columna tipo_mensaje a la tabla mensajes si no existe
ALTER TABLE mensajes 
ADD COLUMN IF NOT EXISTS tipo_mensaje VARCHAR(50) DEFAULT 'normal' 
AFTER contenido;

-- Añadir columna producto_relacionado_id a la tabla mensajes si no existe
ALTER TABLE mensajes 
ADD COLUMN IF NOT EXISTS producto_relacionado_id INT NULL 
AFTER tipo_mensaje,
ADD FOREIGN KEY (producto_relacionado_id) REFERENCES productos(id) ON DELETE SET NULL;

-- Tabla de notificaciones si no existe
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT,
    enlace VARCHAR(500),
    leida TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_leida (usuario_id, leida),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
