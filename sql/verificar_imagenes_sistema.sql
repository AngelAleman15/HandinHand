-- Script de verificación del sistema de imágenes múltiples

-- 1. Verificar que la tabla existe
SHOW TABLES LIKE 'producto_imagenes';

-- 2. Ver estructura de la tabla
DESCRIBE producto_imagenes;

-- 3. Contar imágenes por producto
SELECT 
    producto_id,
    COUNT(*) as total_imagenes,
    SUM(es_principal) as imagenes_principales
FROM producto_imagenes
GROUP BY producto_id;

-- 4. Ver todas las imágenes con información del producto
SELECT 
    pi.id,
    pi.producto_id,
    p.nombre as producto_nombre,
    pi.imagen,
    pi.es_principal,
    pi.created_at
FROM producto_imagenes pi
LEFT JOIN productos p ON pi.producto_id = p.id
ORDER BY pi.producto_id, pi.es_principal DESC, pi.id ASC;

-- 5. Verificar que cada producto tiene solo 1 imagen principal
SELECT 
    producto_id,
    COUNT(*) as principales
FROM producto_imagenes
WHERE es_principal = 1
GROUP BY producto_id
HAVING COUNT(*) > 1;

-- 6. Buscar productos sin imagen principal
SELECT DISTINCT p.id, p.nombre
FROM productos p
LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = 1
WHERE p.id IN (SELECT DISTINCT producto_id FROM producto_imagenes)
AND pi.id IS NULL;

-- 7. Ver ejemplo de cómo la API carga las imágenes (simulación)
-- Para un producto específico (reemplaza 1 con el ID que quieras probar)
SELECT 
    'Producto ID 1 - Imágenes que verá el usuario:' as info,
    pi.imagen,
    pi.es_principal,
    CASE WHEN pi.es_principal = 1 THEN '← Principal' ELSE '' END as nota
FROM producto_imagenes pi
WHERE pi.producto_id = 1
ORDER BY pi.es_principal DESC, pi.id ASC;
