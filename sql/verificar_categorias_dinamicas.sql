-- Script para verificar categorías únicas de productos
-- Este script muestra cómo se extraen las categorías de productos que pueden tener múltiples categorías separadas por comas

-- 1. Ver todos los productos con sus categorías
SELECT id, nombre, categoria 
FROM productos 
WHERE categoria IS NOT NULL AND categoria != '' 
ORDER BY id;

-- 2. Extraer todas las categorías únicas (incluyendo las separadas por comas)
SELECT DISTINCT 
    TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.categoria, ',', numbers.n), ',', -1)) as categoria
FROM productos p
CROSS JOIN (
    SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL 
    SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL 
    SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) numbers
WHERE p.categoria IS NOT NULL 
AND p.categoria != ''
AND CHAR_LENGTH(p.categoria) - CHAR_LENGTH(REPLACE(p.categoria, ',', '')) >= numbers.n - 1
ORDER BY categoria ASC;

-- 3. Contar productos por categoría (categorías individuales)
SELECT 
    TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.categoria, ',', numbers.n), ',', -1)) as categoria,
    COUNT(*) as total_productos
FROM productos p
CROSS JOIN (
    SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL 
    SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL 
    SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) numbers
WHERE p.categoria IS NOT NULL 
AND p.categoria != ''
AND CHAR_LENGTH(p.categoria) - CHAR_LENGTH(REPLACE(p.categoria, ',', '')) >= numbers.n - 1
GROUP BY categoria
ORDER BY total_productos DESC, categoria ASC;

-- 4. Ejemplo de búsqueda por categoría (simula el filtro)
-- Cambia 'Electrónicos' por la categoría que quieras buscar
SET @categoria_buscar = 'Electrónicos';

SELECT id, nombre, categoria, estado
FROM productos
WHERE (
    categoria = @categoria_buscar 
    OR categoria LIKE CONCAT(@categoria_buscar, ',%')
    OR categoria LIKE CONCAT('%,', @categoria_buscar)
    OR categoria LIKE CONCAT('%,', @categoria_buscar, ',%')
)
ORDER BY created_at DESC;
