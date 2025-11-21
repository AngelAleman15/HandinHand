-- Script para agregar columnas nuevas a la tabla mensajes
-- VERSIÓN SIMPLIFICADA - Sin intentar copiar de columnas antiguas
-- Este script es seguro de ejecutar múltiples veces (idempotente)

-- Agregar columna sender_id si no existe
ALTER TABLE mensajes ADD COLUMN IF NOT EXISTS sender_id INT(11) NULL AFTER producto_id;

-- Agregar columna receiver_id si no existe  
ALTER TABLE mensajes ADD COLUMN IF NOT EXISTS receiver_id INT(11) NULL AFTER sender_id;

-- Agregar columna message si no existe
ALTER TABLE mensajes ADD COLUMN IF NOT EXISTS message TEXT NULL AFTER receiver_id;

-- Agregar columna is_read si no existe
ALTER TABLE mensajes ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0 AFTER message;

-- Agregar índices para mejorar rendimiento
ALTER TABLE mensajes ADD INDEX IF NOT EXISTS idx_sender (sender_id);
ALTER TABLE mensajes ADD INDEX IF NOT EXISTS idx_receiver (receiver_id);

-- Mostrar resumen
SELECT 
    'Columnas creadas correctamente' as status,
    COUNT(*) as total_mensajes
FROM mensajes;

-- Ver estructura actualizada
DESCRIBE mensajes;
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
  'SELECT 1',
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
  'SELECT 1',
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
  'SELECT 1',
  'ALTER TABLE mensajes ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER message'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ahora copiar datos de las columnas antiguas a las nuevas (si existen ambas)
UPDATE mensajes 
SET 
    sender_id = remitente_id,
    receiver_id = destinatario_id,
    message = mensaje,
    is_read = leido
WHERE 
    sender_id IS NULL 
    AND remitente_id IS NOT NULL;

-- Mostrar resumen
SELECT 
    'Migración completada' as status,
    COUNT(*) as total_mensajes,
    SUM(CASE WHEN sender_id IS NOT NULL THEN 1 ELSE 0 END) as con_sender_id,
    SUM(CASE WHEN receiver_id IS NOT NULL THEN 1 ELSE 0 END) as con_receiver_id,
    SUM(CASE WHEN message IS NOT NULL THEN 1 ELSE 0 END) as con_message,
    SUM(CASE WHEN is_read IS NOT NULL THEN 1 ELSE 0 END) as con_is_read
FROM mensajes;
