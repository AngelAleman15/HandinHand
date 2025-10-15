-- Agregar columnas para eliminar y editar mensajes
-- edited_at: timestamp de la última edición
-- deleted_for: JSON array de user IDs que han eliminado el mensaje para ellos
-- is_deleted: si el mensaje fue eliminado completamente por el remitente

ALTER TABLE mensajes 
ADD COLUMN edited_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN deleted_for TEXT NULL DEFAULT NULL COMMENT 'JSON array de user IDs que eliminaron el mensaje para ellos',
ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE COMMENT 'True si el remitente eliminó el mensaje completamente';

-- Índice para búsquedas rápidas
ALTER TABLE mensajes ADD INDEX idx_is_deleted (is_deleted);
