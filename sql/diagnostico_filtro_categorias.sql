-- Script de diagnóstico para filtro de categorías
-- Este script ayuda a identificar por qué el filtro no encuentra productos

-- 1. Ver todas las categorías tal como están guardadas (con comillas para ver espacios)
SELECT 
    id, 
    nombre,
    CONCAT('"', categoria, '"') as categoria_con_comillas,
    LENGTH(categoria) as longitud,
    categoria
FROM productos 
WHERE categoria IS NOT NULL AND categoria != ''
ORDER BY id;

-- 2. Buscar productos con una categoría específica (ejemplo: 'Tecnología')
-- CAMBIA 'Tecnología' por la categoría que estés probando
SET @categoria_buscar = 'Tecnología';

SELECT 
    id,
    nombre,
    categoria,
    -- Test 1: Coincidencia exacta
    (categoria = @categoria_buscar) as test_exacta,
    -- Test 2: Al inicio
    (categoria LIKE CONCAT(@categoria_buscar, ',%')) as test_inicio,
    -- Test 3: Al final  
    (categoria LIKE CONCAT('%,', @categoria_buscar)) as test_final,
    -- Test 4: En medio
    (categoria LIKE CONCAT('%,', @categoria_buscar, ',%')) as test_medio
FROM productos
WHERE categoria IS NOT NULL AND categoria != '';

-- 3. Buscar con TRIM (eliminar espacios)
SELECT 
    id,
    nombre,
    categoria,
    TRIM(categoria) as categoria_limpia
FROM productos
WHERE (
    TRIM(categoria) = @categoria_buscar 
    OR TRIM(categoria) LIKE CONCAT(@categoria_buscar, ',%')
    OR TRIM(categoria) LIKE CONCAT('%,', @categoria_buscar)
    OR TRIM(categoria) LIKE CONCAT('%,', @categoria_buscar, ',%')
    OR FIND_IN_SET(@categoria_buscar, REPLACE(categoria, ', ', ',')) > 0
);

-- 4. Mostrar qué productos deberían encontrarse
SELECT 
    id,
    nombre,
    categoria,
    'DEBERÍA APARECER' as resultado
FROM productos
WHERE categoria LIKE CONCAT('%', @categoria_buscar, '%');

-- 5. Verificar si hay espacios después de las comas
SELECT 
    id,
    nombre,
    categoria,
    CASE 
        WHEN categoria LIKE '%, %' THEN 'Tiene espacios después de comas'
        WHEN categoria LIKE ' %' THEN 'Tiene espacio al inicio'
        WHEN categoria LIKE '% ' THEN 'Tiene espacio al final'
        ELSE 'Sin espacios problemáticos'
    END as analisis_espacios
FROM productos
WHERE categoria IS NOT NULL AND categoria != '';
