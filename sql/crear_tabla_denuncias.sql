-- Tabla de denuncias de usuarios
DROP TABLE IF EXISTS `denuncias`;
CREATE TABLE IF NOT EXISTS `denuncias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `denunciante_id` int NOT NULL,
  `denunciado_id` int NOT NULL,
  `motivo` enum('spam','fraude','contenido_inapropiado','acoso','suplantacion','otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('pendiente','en_revision','resuelta','rechazada') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_denunciante` (`denunciante_id`),
  KEY `idx_denunciado` (`denunciado_id`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

