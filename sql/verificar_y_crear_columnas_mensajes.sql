-- Script simplificado: SOLO agregar columnas nuevas si no existen
-- Sin intentar copiar de columnas antiguas

-- Verificar si existe la columna sender_id
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
  'SELECT 1 as columna_ya_existe',
  'ALTER TABLE mensajes ADD COLUMN sender_id INT(11) NULL AFTER producto_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar si existe la columna receiver_id
SET @columnname = 'receiver_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1 as columna_ya_existe',
  'ALTER TABLE mensajes ADD COLUMN receiver_id INT(11) NULL AFTER sender_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar si existe la columna message
SET @columnname = 'message';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1 as columna_ya_existe',
  'ALTER TABLE mensajes ADD COLUMN message TEXT NULL AFTER receiver_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar si existe la columna is_read
SET @columnname = 'is_read';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1 as columna_ya_existe',
  'ALTER TABLE mensajes ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER message'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Mostrar resumen final
SELECT 
    'Verificación completada' as status,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'mensajes' AND column_name = 'sender_id') as tiene_sender_id,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'mensajes' AND column_name = 'receiver_id') as tiene_receiver_id,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'mensajes' AND column_name = 'message') as tiene_message,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'mensajes' AND column_name = 'is_read') as tiene_is_read;

-- Ver estructura completa de la tabla
DESCRIBE mensajes;
