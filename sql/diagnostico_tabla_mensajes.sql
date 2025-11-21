-- Script de diagnóstico: Ver exactamente qué columnas tiene la tabla mensajes

-- 1. Mostrar todas las columnas de la tabla mensajes
SELECT 
    COLUMN_NAME as 'Nombre de Columna',
    COLUMN_TYPE as 'Tipo',
    IS_NULLABLE as 'Permite NULL',
    COLUMN_DEFAULT as 'Valor por defecto',
    COLUMN_KEY as 'Clave'
FROM INFORMATION_SCHEMA.COLUMNS
WHERE 
    table_schema = DATABASE() 
    AND table_name = 'mensajes'
ORDER BY ORDINAL_POSITION;

-- 2. Verificar columnas específicas
SELECT 
    'Columnas NUEVAS' as Categoria,
    SUM(CASE WHEN COLUMN_NAME = 'sender_id' THEN 1 ELSE 0 END) as sender_id,
    SUM(CASE WHEN COLUMN_NAME = 'receiver_id' THEN 1 ELSE 0 END) as receiver_id,
    SUM(CASE WHEN COLUMN_NAME = 'message' THEN 1 ELSE 0 END) as message,
    SUM(CASE WHEN COLUMN_NAME = 'is_read' THEN 1 ELSE 0 END) as is_read
FROM INFORMATION_SCHEMA.COLUMNS
WHERE 
    table_schema = DATABASE() 
    AND table_name = 'mensajes'
UNION ALL
SELECT 
    'Columnas ANTIGUAS' as Categoria,
    SUM(CASE WHEN COLUMN_NAME = 'remitente_id' THEN 1 ELSE 0 END) as remitente_id,
    SUM(CASE WHEN COLUMN_NAME = 'destinatario_id' THEN 1 ELSE 0 END) as destinatario_id,
    SUM(CASE WHEN COLUMN_NAME = 'mensaje' THEN 1 ELSE 0 END) as mensaje,
    SUM(CASE WHEN COLUMN_NAME = 'leido' THEN 1 ELSE 0 END) as leido
FROM INFORMATION_SCHEMA.COLUMNS
WHERE 
    table_schema = DATABASE() 
    AND table_name = 'mensajes';

-- 3. Contar registros en la tabla
SELECT 
    COUNT(*) as total_mensajes,
    SUM(CASE WHEN sender_id IS NOT NULL THEN 1 ELSE 0 END) as con_sender_id,
    SUM(CASE WHEN receiver_id IS NOT NULL THEN 1 ELSE 0 END) as con_receiver_id,
    SUM(CASE WHEN message IS NOT NULL THEN 1 ELSE 0 END) as con_message
FROM mensajes;
