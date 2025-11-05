-- Eliminar puntos de encuentro que NO son de Montevideo
-- Este script elimina ubicaciones de Punta del Este y Colonia del Sacramento

USE handinhand;

-- Eliminar punto de Punta del Este (Producto 2)
DELETE FROM puntos_encuentro 
WHERE nombre = 'Gorlero - Punta del Este' 
  AND direccion LIKE '%Punta del Este%';

-- Eliminar punto de Colonia del Sacramento (Producto 3)
DELETE FROM puntos_encuentro 
WHERE nombre = 'Plaza Artigas - Colonia' 
  AND direccion LIKE '%Colonia del Sacramento%';

-- Verificar puntos restantes (solo Montevideo)
SELECT 
    pe.id,
    p.nombre as producto,
    pe.nombre as punto_encuentro,
    pe.direccion,
    pe.es_principal
FROM puntos_encuentro pe
JOIN productos p ON pe.producto_id = p.id
ORDER BY pe.producto_id, pe.es_principal DESC;

SELECT 'Puntos fuera de Montevideo eliminados exitosamente!' as Resultado;
