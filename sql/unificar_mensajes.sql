-- ============================================
-- SCRIPT DE UNIFICACIÓN DE TABLA MENSAJES
-- ============================================
-- Este script moderniza la tabla mensajes para que coincida con el código PHP
-- Ejecutar UNA SOLA VEZ

USE handinhand;

-- Paso 1: Agregar nuevas columnas (si no existen)
-- ------------------------------------------------
-- Usando procedimientos almacenados para verificar existencia

DELIMITER $$

-- Procedimiento para agregar columnas de forma segura
DROP PROCEDURE IF EXISTS agregar_columnas_mensajes$$

CREATE PROCEDURE agregar_columnas_mensajes()
BEGIN
    -- sender_id (reemplaza remitente_id)
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'sender_id'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN sender_id INT NOT NULL DEFAULT 0 AFTER id;
    END IF;
    
    -- receiver_id (reemplaza destinatario_id)
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'receiver_id'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN receiver_id INT NOT NULL DEFAULT 0 AFTER sender_id;
    END IF;
    
    -- message (reemplaza mensaje)
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'message'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN message TEXT NOT NULL AFTER receiver_id;
    END IF;
    
    -- is_perseo_auto (mensajes automáticos del chatbot)
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'is_perseo_auto'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN is_perseo_auto TINYINT(1) DEFAULT 0 AFTER message;
    END IF;
    
    -- is_read (reemplaza leido)
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'is_read'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER is_perseo_auto;
    END IF;
    
    -- read_at (timestamp de lectura)
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND COLUMN_NAME = 'read_at'
    ) THEN
        ALTER TABLE mensajes ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL AFTER is_read;
    END IF;
    
    -- reply_to_message_id (para responder mensajes)
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

-- Ejecutar el procedimiento
CALL agregar_columnas_mensajes();

-- Limpiar (eliminar el procedimiento)
DROP PROCEDURE IF EXISTS agregar_columnas_mensajes;

-- Paso 2: Migrar datos de columnas antiguas a nuevas
-- ---------------------------------------------------
UPDATE mensajes 
SET 
    sender_id = COALESCE(remitente_id, sender_id),
    receiver_id = COALESCE(destinatario_id, receiver_id),
    message = COALESCE(mensaje, message),
    is_read = COALESCE(leido, is_read)
WHERE (remitente_id IS NOT NULL AND sender_id = 0)
   OR (destinatario_id IS NOT NULL AND receiver_id = 0)
   OR (mensaje IS NOT NULL AND message = '');

-- Paso 3: Eliminar columnas antiguas (OPCIONAL - descomenta si estás seguro)
-- ---------------------------------------------------------------------------
-- ALTER TABLE mensajes DROP COLUMN remitente_id;
-- ALTER TABLE mensajes DROP COLUMN destinatario_id;
-- ALTER TABLE mensajes DROP COLUMN mensaje;
-- ALTER TABLE mensajes DROP COLUMN leido;
-- ALTER TABLE mensajes DROP COLUMN producto_id; -- Ya no se usa en el nuevo sistema

-- Paso 4: Crear índices para optimizar consultas
-- -----------------------------------------------

DELIMITER $$

-- Procedimiento para crear índices de forma segura
DROP PROCEDURE IF EXISTS crear_indices_mensajes$$

CREATE PROCEDURE crear_indices_mensajes()
BEGIN
    -- Índice para mensajes enviados
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_sender'
    ) THEN
        CREATE INDEX idx_sender ON mensajes(sender_id);
    END IF;
    
    -- Índice para mensajes recibidos
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_receiver'
    ) THEN
        CREATE INDEX idx_receiver ON mensajes(receiver_id);
    END IF;
    
    -- Índice para mensajes no leídos (IMPORTANTE para notificaciones)
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_unread_messages'
    ) THEN
        CREATE INDEX idx_unread_messages ON mensajes(receiver_id, is_read);
    END IF;
    
    -- Índice para búsqueda por fecha
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_created_at'
    ) THEN
        CREATE INDEX idx_created_at ON mensajes(created_at);
    END IF;
    
    -- Índice para mensajes de Perseo
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mensajes' 
        AND INDEX_NAME = 'idx_perseo_auto'
    ) THEN
        CREATE INDEX idx_perseo_auto ON mensajes(is_perseo_auto);
    END IF;
    
    -- Índice para respuestas a mensajes
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

-- Ejecutar el procedimiento
CALL crear_indices_mensajes();

-- Limpiar
DROP PROCEDURE IF EXISTS crear_indices_mensajes;

-- Paso 5: Verificar la migración
-- -------------------------------
SELECT 
    COUNT(*) as total_mensajes,
    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as mensajes_leidos,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as mensajes_no_leidos,
    SUM(CASE WHEN is_perseo_auto = 1 THEN 1 ELSE 0 END) as mensajes_perseo
FROM mensajes;

SELECT '✅ Migración completada exitosamente' as Status;
