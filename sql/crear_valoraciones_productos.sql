-- Crear tabla de valoraciones para PRODUCTOS
-- Las valoraciones son para los productos, no para los usuarios

CREATE TABLE IF NOT EXISTS `valoraciones_productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL COMMENT 'Producto que se está valorando',
  `usuario_id` int NOT NULL COMMENT 'Usuario que hace la valoración',
  `puntuacion` int NOT NULL COMMENT 'Puntuación de 1 a 5 estrellas',
  `comentario` text COMMENT 'Comentario opcional del usuario',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_valoracion_producto` (`producto_id`, `usuario_id`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_valoracion_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_valoracion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columnas de estadísticas a la tabla productos
ALTER TABLE `productos` 
ADD COLUMN IF NOT EXISTS `promedio_estrellas` decimal(2,1) DEFAULT 0.0 COMMENT 'Promedio de valoraciones (0.0 a 5.0)',
ADD COLUMN IF NOT EXISTS `total_valoraciones` int DEFAULT 0 COMMENT 'Total de valoraciones recibidas';

-- Insertar valoraciones de ejemplo para los productos existentes
INSERT INTO `valoraciones_productos` (`producto_id`, `usuario_id`, `puntuacion`, `comentario`, `created_at`) VALUES
(1, 2, 5, 'Excelente smartphone, funciona perfectamente. ¡Muy recomendado!', '2025-11-01 10:30:00'),
(1, 3, 4, 'Buen estado general, solo algunas marcas mínimas de uso.', '2025-11-02 14:15:00'),
(1, 4, 5, 'Tal como se describe en la publicación, muy satisfecho.', '2025-11-03 09:45:00'),
(2, 1, 4, 'Zapatillas en buen estado, cómodas para correr.', '2025-11-01 16:20:00'),
(2, 3, 5, 'Perfectas para deportes, excelente calidad Nike.', '2025-11-02 11:00:00'),
(3, 2, 5, 'Guitarra con excelente sonido, ideal para principiantes.', '2025-11-01 13:45:00'),
(3, 4, 4, 'Buen instrumento, cuerdas en perfecto estado.', '2025-11-03 15:30:00');

-- Actualizar estadísticas de los productos basadas en las valoraciones insertadas
UPDATE productos p
SET 
    promedio_estrellas = (
        SELECT COALESCE(ROUND(AVG(puntuacion), 1), 0.0) 
        FROM valoraciones_productos 
        WHERE producto_id = p.id
    ),
    total_valoraciones = (
        SELECT COUNT(*) 
        FROM valoraciones_productos 
        WHERE producto_id = p.id
    );
