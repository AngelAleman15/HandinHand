-- Migración segura de tabla notificaciones
-- Renombrar tabla antigua y crear la nueva

-- 1. Renombrar tabla antigua (por si acaso necesitas los datos)
RENAME TABLE `notificaciones` TO `notificaciones_old`;

-- 2. Crear nueva tabla notificaciones con estructura correcta
CREATE TABLE `notificaciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL COMMENT 'Usuario que recibe la notificación',
  `tipo` ENUM('solicitud_amistad', 'amistad_aceptada', 'propuesta_intercambio', 'intercambio_aceptado', 'intercambio_rechazado', 'contraoferta', 'en_camino', 'demorado', 'entregado', 'intercambio_completado', 'denuncia', 'valoracion') NOT NULL,
  `de_usuario_id` INT DEFAULT NULL COMMENT 'Usuario que generó la notificación',
  `titulo` VARCHAR(255) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `icono` VARCHAR(100) DEFAULT 'fa-bell' COMMENT 'Clase de FontAwesome',
  `url` VARCHAR(500) DEFAULT NULL COMMENT 'URL a la que redirige al hacer clic',
  `metadata` JSON DEFAULT NULL COMMENT 'Datos adicionales (IDs, etc)',
  `leida` TINYINT(1) DEFAULT 0,
  `fecha_leida` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`de_usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  INDEX `idx_usuario_leida` (`usuario_id`, `leida`),
  INDEX `idx_tipo` (`tipo`),
  INDEX `idx_fecha` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Opcional: Migrar datos antiguos si los necesitas (comentado por defecto)
-- INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, leida, url, created_at)
-- SELECT 
--   user_id,
--   'sistema' as tipo,
--   titulo,
--   contenido as mensaje,
--   leida,
--   url,
--   created_at
-- FROM notificaciones_old;

-- 4. Si NO necesitas los datos antiguos, puedes borrar la tabla antigua:
-- DROP TABLE notificaciones_old;
