-- Script de migración: Ejecutar en phpMyAdmin
-- Este script creará la tabla de valoraciones para productos
-- y migrará/actualizará la estructura existente

USE handinhand;

-- Paso 1: Crear la nueva tabla de valoraciones de productos
CREATE TABLE IF NOT EXISTS `valoraciones_productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL COMMENT 'Producto que se está valorando',
  `usuario_id` int NOT NULL COMMENT 'Usuario que hace la valoración',
  `puntuacion` int NOT NULL COMMENT 'Puntuación de 1 a 5 estrellas',
  `comentario` text COLLATE utf8mb4_unicode_ci COMMENT 'Comentario opcional del usuario',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_valoracion_producto` (`producto_id`, `usuario_id`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_valoracion_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_valoracion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paso 2: Verificar si las columnas ya existen antes de agregarlas
SET @dbname = DATABASE();
SET @tablename = 'productos';
SET @columnname1 = 'promedio_estrellas';
SET @columnname2 = 'total_valoraciones';

SET @preparedStatement1 = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname1) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname1, ' DECIMAL(2,1) DEFAULT 0.0 COMMENT ''Promedio de valoraciones (0.0 a 5.0)''')
));

SET @preparedStatement2 = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname2) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname2, ' INT DEFAULT 0 COMMENT ''Total de valoraciones recibidas''')
));

PREPARE alterStatement1 FROM @preparedStatement1;
EXECUTE alterStatement1;
DEALLOCATE PREPARE alterStatement1;

PREPARE alterStatement2 FROM @preparedStatement2;
EXECUTE alterStatement2;
DEALLOCATE PREPARE alterStatement2;

-- Paso 3: Insertar valoraciones de ejemplo para los productos existentes
INSERT IGNORE INTO `valoraciones_productos` (`producto_id`, `usuario_id`, `puntuacion`, `comentario`, `created_at`) VALUES
(1, 2, 5, 'Excelente smartphone, funciona perfectamente. ¡Muy recomendado!', '2025-11-01 10:30:00'),
(1, 3, 4, 'Buen estado general, solo algunas marcas mínimas de uso.', '2025-11-02 14:15:00'),
(1, 4, 5, 'Tal como se describe en la publicación, muy satisfecho.', '2025-11-03 09:45:00'),
(2, 1, 4, 'Zapatillas en buen estado, cómodas para correr.', '2025-11-01 16:20:00'),
(2, 3, 5, 'Perfectas para deportes, excelente calidad Nike.', '2025-11-02 11:00:00'),
(3, 2, 5, 'Guitarra con excelente sonido, ideal para principiantes.', '2025-11-01 13:45:00'),
(3, 4, 4, 'Buen instrumento, cuerdas en perfecto estado.', '2025-11-03 15:30:00');

-- Paso 4: Actualizar estadísticas de los productos basadas en las valoraciones
UPDATE productos p
SET 
    promedio_estrellas = COALESCE((
        SELECT ROUND(AVG(puntuacion), 1) 
        FROM valoraciones_productos 
        WHERE producto_id = p.id
    ), 0.0),
    total_valoraciones = COALESCE((
        SELECT COUNT(*) 
        FROM valoraciones_productos 
        WHERE producto_id = p.id
    ), 0);

-- Verificación: Mostrar resultados
SELECT 
    p.id,
    p.nombre,
    p.promedio_estrellas,
    p.total_valoraciones,
    COUNT(vp.id) as valoraciones_reales
FROM productos p
LEFT JOIN valoraciones_productos vp ON p.id = vp.producto_id
GROUP BY p.id
ORDER BY p.id;

SELECT 'Migración completada exitosamente!' as Resultado;
