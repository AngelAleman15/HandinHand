-- Permitir múltiples valoraciones del mismo usuario
-- Fecha: 2025-10-15
-- Cambio: Eliminar restricción UNIQUE que impedía valoraciones múltiples

-- Este cambio permite que un usuario pueda recibir múltiples valoraciones
-- del mismo valorador en diferentes momentos

USE handinhand;

-- Eliminar la restricción UNIQUE si existe
ALTER TABLE valoraciones DROP INDEX IF EXISTS unique_valoracion;

-- Verificar el cambio
SHOW CREATE TABLE valoraciones;

-- Consulta de ejemplo: Ver todas las valoraciones de un usuario
-- SELECT 
--     v.*,
--     u.fullname as valorador_nombre,
--     u.username as valorador_username
-- FROM valoraciones v
-- JOIN usuarios u ON v.valorador_id = u.id
-- WHERE v.usuario_id = 2
-- ORDER BY v.created_at DESC;
