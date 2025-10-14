-- Agregar columna para identificar mensajes automáticos de Perseo
ALTER TABLE mensajes ADD COLUMN IF NOT EXISTS is_perseo_auto TINYINT(1) DEFAULT 0 AFTER message;

-- Crear índice para optimizar consultas de mensajes no leídos
CREATE INDEX IF NOT EXISTS idx_unread_messages ON mensajes(receiver_id, is_read);
