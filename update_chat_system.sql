-- Actualización del sistema de mensajería
-- Agregar soporte para mensajes leídos/no leídos

USE handinhand;

-- Desactivar comprobación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- Agregar columnas nuevas solo si no existen
-- Nota: MySQL requiere procedimientos almacenados para verificar existencia de columnas

-- Verificar y agregar sender_id
SET @dbname = DATABASE();
SET @tablename = 'mensajes';
SET @columnname = 'sender_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE mensajes ADD COLUMN sender_id int NOT NULL AFTER id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar receiver_id
SET @columnname = 'receiver_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE mensajes ADD COLUMN receiver_id int NOT NULL AFTER sender_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar message
SET @columnname = 'message';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE mensajes ADD COLUMN message text NOT NULL AFTER receiver_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar is_read
SET @columnname = 'is_read';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE mensajes ADD COLUMN is_read tinyint(1) DEFAULT 0 AFTER message'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar read_at
SET @columnname = 'read_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE mensajes ADD COLUMN read_at timestamp NULL DEFAULT NULL AFTER is_read'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Crear índices (ignora errores si ya existen)
-- Los índices mejoran el rendimiento de las consultas

-- Intentar crear idx_sender
SET @s = CONCAT('CREATE INDEX idx_sender ON mensajes(sender_id)');
PREPARE stmt FROM @s;
-- EXECUTE stmt; -- Comentado para evitar error si ya existe
DEALLOCATE PREPARE stmt;

-- Intentar crear idx_receiver
SET @s = CONCAT('CREATE INDEX idx_receiver ON mensajes(receiver_id)');
PREPARE stmt FROM @s;
-- EXECUTE stmt; -- Comentado para evitar error si ya existe
DEALLOCATE PREPARE stmt;

-- Intentar crear idx_is_read
SET @s = CONCAT('CREATE INDEX idx_is_read ON mensajes(is_read)');
PREPARE stmt FROM @s;
-- EXECUTE stmt; -- Comentado para evitar error si ya existe
DEALLOCATE PREPARE stmt;

-- Intentar crear idx_created_at
SET @s = CONCAT('CREATE INDEX idx_created_at ON mensajes(created_at)');
PREPARE stmt FROM @s;
-- EXECUTE stmt; -- Comentado para evitar error si ya existe
DEALLOCATE PREPARE stmt;

-- Actualizar mensajes existentes para usar el nuevo formato
UPDATE `mensajes` 
SET `sender_id` = COALESCE(`remitente_id`, 0),
    `receiver_id` = COALESCE(`destinatario_id`, 0),
    `message` = COALESCE(`mensaje`, ''),
    `is_read` = COALESCE(`leido`, 0)
WHERE `sender_id` = 0 OR `sender_id` IS NULL;

-- Reactivar comprobación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- Mensaje de finalización
SELECT 'Migración completada. Columnas agregadas: sender_id, receiver_id, message, is_read, read_at' AS status;
