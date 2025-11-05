-- Script de corrección para productos
-- Corrige los nombres de imágenes incorrectos y ajusta la estructura

USE handinhand;

-- Corregir nombres de imágenes con errores de tipeo
UPDATE productos SET imagen = 'img/productos/smartphonesamsung.jpg' WHERE id = 4;
UPDATE productos SET imagen = 'img/productos/chaquetadecuero.jpg' WHERE id = 5;

-- Verificar que todos los productos estén disponibles
UPDATE productos SET estado = 'disponible' WHERE estado IS NULL OR estado = '';

-- Mostrar los productos actualizados
SELECT id, nombre, imagen, estado FROM productos ORDER BY id;
