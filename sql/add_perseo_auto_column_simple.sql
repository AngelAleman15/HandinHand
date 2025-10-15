-- Versión simple: Agregar columna is_perseo_auto
-- NOTA: Dará error si la columna ya existe (es normal)

ALTER TABLE mensajes ADD COLUMN is_perseo_auto TINYINT(1) DEFAULT 0 AFTER message;

-- Crear índice para optimizar consultas de mensajes no leídos
CREATE INDEX idx_unread_messages ON mensajes(receiver_id, is_read);
