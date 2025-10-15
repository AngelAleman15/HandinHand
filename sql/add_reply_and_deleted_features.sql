-- La columna reply_to_message_id ya existe, comentamos esta línea:
-- ALTER TABLE mensajes ADD COLUMN reply_to_message_id INT NULL AFTER mensaje;

-- Crear tabla para mensajes eliminados (solo para el usuario que los eliminó)
CREATE TABLE IF NOT EXISTS mensajes_eliminados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mensaje_id INT NOT NULL,
    eliminado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_mensaje (user_id, mensaje_id),
    INDEX idx_user_id (user_id),
    INDEX idx_mensaje_id (mensaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla para historial de chat eliminado completamente
CREATE TABLE IF NOT EXISTS chat_eliminado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    other_user_id INT NOT NULL,
    eliminado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat_deletion (user_id, other_user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_other_user_id (other_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

