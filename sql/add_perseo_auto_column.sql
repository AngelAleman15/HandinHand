-- Agregar columna para identificar mensajes automáticos de Perseo
-- Usar procedimiento para evitar error si la columna ya existe

DELIMITER $$

DROP PROCEDURE IF EXISTS add_perseo_auto_column$$

CREATE PROCEDURE add_perseo_auto_column()
BEGIN
    -- Verificar si la columna existe
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

-- Ejecutar el procedimiento
CALL add_perseo_auto_column();

-- Eliminar el procedimiento (limpieza)
DROP PROCEDURE IF EXISTS add_perseo_auto_column;

-- Crear índice para optimizar consultas de mensajes no leídos
-- MySQL 5.7+ soporta IF NOT EXISTS en CREATE INDEX
CREATE INDEX IF NOT EXISTS idx_unread_messages ON mensajes(receiver_id, is_read);
