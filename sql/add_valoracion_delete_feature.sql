-- Funcionalidad de eliminación de valoraciones
-- Fecha: 2025-10-15
-- Cambio: Permitir que los usuarios eliminen sus propias valoraciones

-- No se requieren cambios en la base de datos para esta funcionalidad
-- La tabla valoraciones ya tiene la estructura necesaria:
-- - id (PRIMARY KEY)
-- - usuario_id (usuario que recibe la valoración)
-- - valorador_id (usuario que hace la valoración)
-- - puntuacion
-- - comentario
-- - created_at

-- La lógica de permisos se maneja en el código PHP:
-- Solo el valorador_id puede eliminar su propia valoración

-- Ejemplo de consulta para verificar propietario antes de eliminar:
-- SELECT usuario_id, valorador_id FROM valoraciones WHERE id = ? AND valorador_id = ?

-- Después de eliminar, se actualizan las estadísticas:
-- UPDATE estadisticas_usuario 
-- SET promedio_valoracion = (SELECT COALESCE(AVG(puntuacion), 0) FROM valoraciones WHERE usuario_id = ?),
--     total_valoraciones = (SELECT COUNT(*) FROM valoraciones WHERE usuario_id = ?)
-- WHERE usuario_id = ?

USE handinhand;

-- Verificar estructura actual
SHOW CREATE TABLE valoraciones;

-- Ver todas las valoraciones (para testing)
-- SELECT 
--     v.*,
--     u1.fullname as usuario_valorado,
--     u2.fullname as valorador
-- FROM valoraciones v
-- JOIN usuarios u1 ON v.usuario_id = u1.id
-- JOIN usuarios u2 ON v.valorador_id = u2.id
-- ORDER BY v.created_at DESC;
