-- Script para añadir datos de prueba al sistema FYP
-- Simula interacciones de usuarios con productos

-- Obtener algunos productos y usuarios aleatorios
SET @producto1 = (SELECT id FROM productos LIMIT 1);
SET @producto2 = (SELECT id FROM productos LIMIT 1 OFFSET 1);
SET @producto3 = (SELECT id FROM productos LIMIT 1 OFFSET 2);
SET @usuario1 = (SELECT id FROM usuarios LIMIT 1);
SET @usuario2 = (SELECT id FROM usuarios LIMIT 1 OFFSET 1);

-- Simular vistas de productos
INSERT IGNORE INTO producto_vistas (producto_id, usuario_id, duracion_segundos, fecha_vista)
SELECT 
    p.id,
    u.id,
    FLOOR(5 + RAND() * 120),  -- Entre 5 y 125 segundos
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY)  -- Últimos 30 días
FROM productos p
CROSS JOIN usuarios u
WHERE RAND() < 0.3  -- 30% de probabilidad de vista
AND p.estado = 'disponible'
LIMIT 50;

-- Simular productos guardados
INSERT IGNORE INTO producto_guardados (producto_id, usuario_id)
SELECT DISTINCT
    pv.producto_id,
    pv.usuario_id
FROM producto_vistas pv
WHERE RAND() < 0.2  -- 20% de las vistas se convierten en guardados
LIMIT 15;

-- Recalcular scores
CALL actualizar_scores_productos();
CALL calcular_similitudes_productos();

-- Mostrar resumen
SELECT '✅ Datos de prueba creados' as Resultado;
SELECT 
    (SELECT COUNT(*) FROM producto_vistas) as total_vistas,
    (SELECT COUNT(*) FROM producto_guardados) as total_guardados,
    (SELECT COUNT(*) FROM producto_scores WHERE score_total > 0) as productos_con_score;
