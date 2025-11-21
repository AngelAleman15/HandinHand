-- Sistema de seguimiento de intercambios
-- Ejecutar este archivo en phpMyAdmin o MySQL

-- Tabla de seguimiento de intercambios activos
CREATE TABLE IF NOT EXISTS `seguimiento_intercambios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `propuesta_id` INT NOT NULL,
  `usuario1_id` INT NOT NULL COMMENT 'Usuario que hizo la propuesta',
  `usuario2_id` INT NOT NULL COMMENT 'Usuario que recibió la propuesta',
  `producto_ofrecido_id` INT NOT NULL,
  `producto_solicitado_id` INT NOT NULL,
  `estado` ENUM('coordinando', 'confirmado', 'en_camino_usuario1', 'en_camino_usuario2', 'en_camino_ambos', 'entregado_usuario1', 'entregado_usuario2', 'completado', 'cancelado', 'denunciado') DEFAULT 'coordinando',
  `lugar_encuentro` VARCHAR(500) DEFAULT NULL,
  `fecha_encuentro` DATETIME DEFAULT NULL,
  `lat` DECIMAL(10, 8) DEFAULT NULL,
  `lng` DECIMAL(11, 8) DEFAULT NULL,
  `usuario1_entregado` TINYINT(1) DEFAULT 0 COMMENT '1 si usuario1 marcó como entregado',
  `usuario2_entregado` TINYINT(1) DEFAULT 0 COMMENT '1 si usuario2 marcó como entregado',
  `fecha_aceptacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `fecha_completado` DATETIME DEFAULT NULL,
  `notas` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`propuesta_id`) REFERENCES `propuestas_intercambio`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario1_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario2_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_ofrecido_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_solicitado_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE,
  INDEX `idx_estado` (`estado`),
  INDEX `idx_usuarios` (`usuario1_id`, `usuario2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de acciones rápidas en el seguimiento
CREATE TABLE IF NOT EXISTS `acciones_seguimiento` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `seguimiento_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `tipo` ENUM('en_camino', 'demorado', 'cambio_ubicacion', 'mensaje_rapido', 'denuncia', 'entregado', 'cancelado') NOT NULL,
  `mensaje` TEXT DEFAULT NULL,
  `metadata` JSON DEFAULT NULL COMMENT 'Información adicional (nueva ubicación, etc)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`seguimiento_id`) REFERENCES `seguimiento_intercambios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  INDEX `idx_seguimiento` (`seguimiento_id`),
  INDEX `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de notificaciones (excluye mensajes de chat)
CREATE TABLE IF NOT EXISTS `notificaciones` (
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

-- Tabla de denuncias de intercambios
CREATE TABLE IF NOT EXISTS `denuncias_intercambio` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `seguimiento_id` INT NOT NULL,
  `denunciante_id` INT NOT NULL,
  `denunciado_id` INT NOT NULL,
  `motivo` ENUM('no_aparecio', 'producto_distinto', 'producto_danado', 'actitud_inapropiada', 'estafa', 'otro') NOT NULL,
  `descripcion` TEXT NOT NULL,
  `evidencias` JSON DEFAULT NULL COMMENT 'URLs de imágenes/capturas',
  `estado` ENUM('pendiente', 'en_revision', 'resuelta', 'rechazada') DEFAULT 'pendiente',
  `resolucion` TEXT DEFAULT NULL,
  `moderador_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`seguimiento_id`) REFERENCES `seguimiento_intercambios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`denunciante_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`denunciado_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`moderador_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  INDEX `idx_estado` (`estado`),
  INDEX `idx_denunciante` (`denunciante_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir columna a propuestas_intercambio
-- Si ya existe la columna, ignorar este bloque (ejecutar solo la primera vez)
ALTER TABLE `propuestas_intercambio` 
ADD COLUMN `seguimiento_id` INT DEFAULT NULL;

-- Añadir foreign key
ALTER TABLE `propuestas_intercambio`
ADD CONSTRAINT `fk_propuesta_seguimiento` 
FOREIGN KEY (`seguimiento_id`) REFERENCES `seguimiento_intercambios`(`id`) ON DELETE SET NULL;
