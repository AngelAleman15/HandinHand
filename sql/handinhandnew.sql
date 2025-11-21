-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 06-11-2025 a las 22:43:21
-- Versión del servidor: 8.3.0
-- Versión de PHP: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `handinhand`
--

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `actualizar_scores_productos`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_scores_productos` ()   BEGIN
    -- Limpiar tabla de scores
    TRUNCATE TABLE producto_scores;
    
    -- Calcular scores para todos los productos
    INSERT INTO producto_scores (producto_id, total_vistas, total_guardados, total_chats, score_total)
    SELECT 
        p.id,
        COALESCE(COUNT(DISTINCT pv.id), 0) as total_vistas,
        COALESCE(COUNT(DISTINCT pg.id), 0) as total_guardados,
        COALESCE(COUNT(DISTINCT pc.id), 0) as total_chats,
        -- F??rmula: vistas??1 + guardados??3 + chats??5 + valoraciones??2
        (COALESCE(COUNT(DISTINCT pv.id), 0) * 1) +
        (COALESCE(COUNT(DISTINCT pg.id), 0) * 3) +
        (COALESCE(COUNT(DISTINCT pc.id), 0) * 5) +
        (COALESCE(p.total_valoraciones, 0) * 2) as score_total
    FROM productos p
    LEFT JOIN producto_vistas pv ON p.id = pv.producto_id
    LEFT JOIN producto_guardados pg ON p.id = pg.producto_id
    LEFT JOIN producto_chats pc ON p.id = pc.producto_id
    WHERE p.estado = 'disponible'
    GROUP BY p.id;
END$$

DROP PROCEDURE IF EXISTS `calcular_similitudes_productos`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `calcular_similitudes_productos` ()   BEGIN
    -- Limpiar tabla de similitudes
    TRUNCATE TABLE producto_similitudes;
    
    -- Calcular productos vistos juntos por los mismos usuarios
    INSERT INTO producto_similitudes (producto_a_id, producto_b_id, veces_visto_juntos, similitud_score)
    SELECT 
        pv1.producto_id as producto_a_id,
        pv2.producto_id as producto_b_id,
        COUNT(DISTINCT pv1.usuario_id) as veces_visto_juntos,
        -- Score de similitud basado en frecuencia
        COUNT(DISTINCT pv1.usuario_id) * 10 as similitud_score
    FROM producto_vistas pv1
    INNER JOIN producto_vistas pv2 
        ON pv1.usuario_id = pv2.usuario_id 
        AND pv1.producto_id < pv2.producto_id  -- Evitar duplicados
        AND pv1.usuario_id IS NOT NULL  -- Solo usuarios logueados
    GROUP BY pv1.producto_id, pv2.producto_id
    HAVING COUNT(DISTINCT pv1.usuario_id) >= 2;  -- Al menos 2 usuarios en com??n
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `amistades`
--

DROP TABLE IF EXISTS `amistades`;
CREATE TABLE IF NOT EXISTS `amistades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario1_id` int NOT NULL,
  `usuario2_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_amistad` (`usuario1_id`,`usuario2_id`),
  KEY `idx_usuario1` (`usuario1_id`),
  KEY `idx_usuario2` (`usuario2_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `amistades`
--

INSERT INTO `amistades` (`id`, `usuario1_id`, `usuario2_id`, `created_at`) VALUES
(9, 1, 7, '2025-10-16 21:12:57'),
(10, 9, 10, '2025-10-16 22:56:57'),
(11, 3, 10, '2025-10-16 22:58:19'),
(12, 2, 3, '2025-10-16 23:27:53'),
(17, 1, 10, '2025-10-21 22:56:38'),
(18, 1, 8, '2025-10-21 23:56:41'),
(19, 2, 7, '2025-10-21 23:59:38'),
(20, 1, 3, '2025-11-04 22:58:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `created_at`) VALUES
(1, 'Calzado', 'Zapatos, zapatillas y todo tipo de calzado', '2025-10-02 19:10:12'),
(2, 'Ropa', 'Vestimenta en general', '2025-10-02 19:10:12'),
(3, 'Electrónicos', 'Dispositivos electrónicos y tecnología', '2025-10-02 19:10:12'),
(4, 'Hogar', 'Artículos para el hogar y decoración', '2025-10-02 19:10:12'),
(5, 'Deportes', 'Artículos deportivos y fitness', '2025-10-02 19:10:12'),
(6, 'Libros', 'Libros, revistas y material educativo', '2025-10-02 19:10:12'),
(7, 'Música', 'Instrumentos musicales y equipos de audio', '2025-10-02 19:10:12'),
(8, 'Juguetes', 'Juguetes y juegos para todas las edades', '2025-10-02 19:10:12'),
(9, 'Herramientas', 'Herramientas y equipos de trabajo', '2025-10-02 19:10:12'),
(10, 'Accesorios', 'Accesorios y complementos diversos', '2025-10-02 19:10:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chats_temporales`
--

DROP TABLE IF EXISTS `chats_temporales`;
CREATE TABLE IF NOT EXISTS `chats_temporales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario1_id` int NOT NULL,
  `usuario2_id` int NOT NULL,
  `producto_relacionado_id` int DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT ((now() + interval 7 day)),
  PRIMARY KEY (`id`),
  KEY `usuario2_id` (`usuario2_id`),
  KEY `producto_relacionado_id` (`producto_relacionado_id`),
  KEY `idx_usuarios` (`usuario1_id`,`usuario2_id`),
  KEY `idx_activo` (`activo`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chat_eliminado`
--

DROP TABLE IF EXISTS `chat_eliminado`;
CREATE TABLE IF NOT EXISTS `chat_eliminado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `other_user_id` int NOT NULL,
  `eliminado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_chat_deletion` (`user_id`,`other_user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_other_user_id` (`other_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `chat_eliminado`
--

INSERT INTO `chat_eliminado` (`id`, `user_id`, `other_user_id`, `eliminado_en`) VALUES
(1, 2, 1, '2025-10-15 01:58:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `denuncias`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `denuncias`
--

INSERT INTO `denuncias` (`id`, `denunciante_id`, `denunciado_id`, `motivo`, `descripcion`, `estado`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'suplantacion', 'ASDASDASDASD', 'pendiente', '2025-10-15 05:04:44', '2025-10-15 05:04:44'),
(2, 1, 2, 'acoso', 'ME CAE RE MAL, ME DIJO PUTITO PORQUE ESTABA VENDIENDO EL PRINCIPITO Y ME SIGUIÓ HASTA MI CASA DICIENDOME QUE ME PASE A MOVISTAR', 'pendiente', '2025-10-15 05:07:49', '2025-10-15 05:07:49'),
(3, 9, 1, 'fraude', 'es gay y dijo que era hetero', 'pendiente', '2025-10-16 22:49:31', '2025-10-16 22:49:31'),
(4, 8, 1, 'spam', 'Acoso sexual (me gusto)', 'pendiente', '2025-10-21 22:59:50', '2025-10-21 22:59:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estadisticas_usuario`
--

DROP TABLE IF EXISTS `estadisticas_usuario`;
CREATE TABLE IF NOT EXISTS `estadisticas_usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `promedio_valoracion` decimal(2,1) DEFAULT '0.0',
  `total_valoraciones` int DEFAULT '0',
  `total_productos` int DEFAULT '0',
  `total_amigos` int DEFAULT '0',
  `total_intercambios` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  KEY `idx_promedio` (`promedio_valoracion`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estadisticas_usuario`
--

INSERT INTO `estadisticas_usuario` (`id`, `usuario_id`, `promedio_valoracion`, `total_valoraciones`, `total_productos`, `total_amigos`, `total_intercambios`, `updated_at`) VALUES
(1, 2, 3.9, 6, 3, 3, 0, '2025-11-05 03:12:06'),
(2, 7, 0.5, 3, 0, 4, 0, '2025-10-21 23:59:38'),
(3, 1, 2.2, 15, 3, 5, 0, '2025-11-04 22:58:36'),
(4, 3, 2.3, 3, 3, 3, 0, '2025-11-04 22:58:36'),
(5, 4, 0.0, 0, 1, 0, 0, '2025-10-15 04:41:40'),
(6, 5, 0.0, 0, 0, 0, 0, '2025-10-15 04:41:40'),
(7, 6, 0.0, 0, 0, 0, 0, '2025-10-15 04:41:40');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `estadisticas_usuarios`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `estadisticas_usuarios`;
CREATE TABLE IF NOT EXISTS `estadisticas_usuarios` (
`fullname` varchar(100)
,`id` int
,`miembro_desde` timestamp
,`productos_disponibles` bigint
,`promedio_valoracion` decimal(6,5)
,`total_intercambios` bigint
,`total_productos` bigint
,`total_valoraciones` bigint
,`username` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intercambios`
--

DROP TABLE IF EXISTS `intercambios`;
CREATE TABLE IF NOT EXISTS `intercambios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_ofrecido_id` int NOT NULL COMMENT 'Producto que se ofrece',
  `producto_solicitado_id` int NOT NULL COMMENT 'Producto que se solicita',
  `usuario_ofrecedor_id` int NOT NULL,
  `usuario_solicitante_id` int NOT NULL,
  `estado` enum('pendiente','aceptado','rechazado','completado','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `mensaje_propuesta` text COLLATE utf8mb4_unicode_ci COMMENT 'Mensaje inicial de la propuesta',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_producto_ofrecido` (`producto_ofrecido_id`),
  KEY `idx_producto_solicitado` (`producto_solicitado_id`),
  KEY `idx_usuario_ofrecedor` (`usuario_ofrecedor_id`),
  KEY `idx_usuario_solicitante` (`usuario_solicitante_id`),
  KEY `idx_estado` (`estado`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de propuestas de intercambio';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

DROP TABLE IF EXISTS `mensajes`;
CREATE TABLE IF NOT EXISTS `mensajes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_perseo_auto` tinyint(1) DEFAULT '0',
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `reply_to_message_id` int DEFAULT NULL,
  `producto_id` int NOT NULL,
  `remitente_id` int NOT NULL,
  `destinatario_id` int NOT NULL,
  `mensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_mensaje` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `producto_relacionado_id` int DEFAULT NULL,
  `leido` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_at` timestamp NULL DEFAULT NULL,
  `deleted_for` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array de user IDs que eliminaron el mensaje para ellos',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT 'True si el remitente eliminó el mensaje completamente',
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  KEY `remitente_id` (`remitente_id`),
  KEY `destinatario_id` (`destinatario_id`),
  KEY `idx_unread_messages` (`receiver_id`,`is_read`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_receiver` (`receiver_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_perseo_auto` (`is_perseo_auto`),
  KEY `idx_reply_to` (`reply_to_message_id`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `producto_relacionado_id` (`producto_relacionado_id`)
) ENGINE=MyISAM AUTO_INCREMENT=878 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mensajes`
--

INSERT INTO `mensajes` (`id`, `sender_id`, `receiver_id`, `message`, `is_perseo_auto`, `is_read`, `read_at`, `reply_to_message_id`, `producto_id`, `remitente_id`, `destinatario_id`, `mensaje`, `tipo_mensaje`, `producto_relacionado_id`, `leido`, `created_at`, `edited_at`, `deleted_for`, `is_deleted`) VALUES
(289, 2, 1, '[Respuesta Automatica de Perseo]\n\nHola, Alejo no esta disponible en este momento. Tu mensaje ha sido recibido y sera respondido en breve. Gracias por tu paciencia!', 1, 1, '2025-10-15 23:14:30', NULL, 0, 0, 0, '', 'normal', NULL, 0, '2025-10-15 23:14:20', NULL, NULL, 0),
(288, 1, 2, '', 0, 1, '2025-10-15 23:14:25', NULL, 0, 0, 0, 'ola', 'normal', NULL, 0, '2025-10-15 23:13:39', NULL, NULL, 0),
(287, 2, 1, '', 0, 1, '2025-10-15 23:13:37', NULL, 0, 0, 0, 'hola gla', 'normal', NULL, 0, '2025-10-15 23:13:02', NULL, NULL, 0),
(286, 2, 1, '', 0, 1, '2025-10-15 23:13:37', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-15 23:12:59', NULL, NULL, 0),
(285, 2, 1, '', 0, 1, '2025-10-15 23:13:37', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-15 23:12:58', NULL, NULL, 0),
(284, 1, 2, '', 0, 1, '2025-10-15 23:09:46', NULL, 0, 0, 0, 'chi', 'normal', NULL, 0, '2025-10-15 23:09:46', NULL, NULL, 0),
(282, 2, 1, '', 0, 1, '2025-10-15 23:09:21', NULL, 0, 0, 0, 'que pushie', 'normal', NULL, 0, '2025-10-15 23:09:21', NULL, NULL, 0),
(283, 2, 1, '', 0, 1, '2025-10-15 23:09:45', NULL, 0, 0, 0, '0-O', 'normal', NULL, 0, '2025-10-15 23:09:45', NULL, NULL, 0),
(281, 2, 1, '', 0, 1, '2025-10-15 23:09:20', NULL, 0, 0, 0, 'o sea', 'normal', NULL, 0, '2025-10-15 23:09:20', NULL, NULL, 0),
(280, 1, 2, '', 0, 1, '2025-10-15 23:09:19', 275, 0, 0, 0, 'le da un beso }', 'normal', NULL, 0, '2025-10-15 23:09:19', NULL, NULL, 0),
(278, 1, 2, '', 0, 1, '2025-10-15 23:09:13', NULL, 0, 0, 0, '*se limpia la meada del pantalon*', 'normal', NULL, 0, '2025-10-15 23:09:13', NULL, NULL, 0),
(279, 2, 1, '', 0, 1, '2025-10-15 23:09:18', NULL, 0, 0, 0, 'te sirvio el js que te mande?', 'normal', NULL, 0, '2025-10-15 23:09:18', NULL, NULL, 0),
(277, 2, 1, '', 0, 1, '2025-10-15 23:09:10', NULL, 0, 0, 0, 'angel', 'normal', NULL, 0, '2025-10-15 23:09:10', NULL, NULL, 0),
(276, 2, 1, '', 0, 1, '2025-10-15 23:09:07', NULL, 0, 0, 0, 'dea', 'normal', NULL, 0, '2025-10-15 23:09:07', NULL, NULL, 0),
(275, 2, 1, '', 0, 1, '2025-10-15 23:09:05', NULL, 0, 0, 0, '*se pajia*', 'normal', NULL, 0, '2025-10-15 23:09:05', NULL, NULL, 0),
(274, 1, 2, '', 0, 1, '2025-10-15 23:09:02', NULL, 0, 0, 0, 'chi', 'normal', NULL, 0, '2025-10-15 23:09:02', NULL, NULL, 0),
(273, 1, 2, '', 0, 1, '2025-10-15 23:09:01', NULL, 0, 0, 0, 'como estas?', 'normal', NULL, 0, '2025-10-15 23:09:01', NULL, NULL, 0),
(272, 2, 1, '', 0, 1, '2025-10-15 23:09:00', NULL, 0, 0, 0, 'ahora siiii', 'normal', NULL, 0, '2025-10-15 23:09:00', NULL, NULL, 0),
(271, 1, 2, '', 0, 1, '2025-10-15 23:08:59', NULL, 0, 0, 0, 'hola bb', 'normal', NULL, 0, '2025-10-15 23:08:59', NULL, NULL, 0),
(270, 2, 1, '', 0, 1, '2025-10-15 23:08:57', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-15 23:07:43', NULL, NULL, 0),
(269, 2, 1, '[Respuesta Automatica de Perseo]\n\nAlejo esta ocupado/a ahora mismo. He guardado tu mensaje y te respondera lo antes posible. Gracias!', 1, 1, '2025-10-15 23:08:57', NULL, 0, 0, 0, '', 'normal', NULL, 0, '2025-10-15 23:07:27', NULL, NULL, 0),
(268, 1, 2, '', 0, 1, '2025-10-15 23:07:40', NULL, 0, 0, 0, '🥲🥲🥲🥲', 'normal', NULL, 0, '2025-10-15 23:07:26', NULL, NULL, 0),
(267, 1, 2, '', 0, 1, '2025-10-15 23:07:40', NULL, 0, 0, 0, '*llora y se mea encima*', 'normal', NULL, 0, '2025-10-15 23:07:20', NULL, NULL, 0),
(266, 1, 2, '', 0, 1, '2025-10-15 23:07:40', NULL, 0, 0, 0, 'noooooo', 'normal', NULL, 0, '2025-10-15 23:07:14', NULL, NULL, 0),
(265, 2, 1, '', 0, 1, '2025-10-15 23:07:10', NULL, 0, 0, 0, 'deja te borro', 'normal', NULL, 0, '2025-10-15 23:07:10', NULL, NULL, 0),
(264, 1, 2, '', 0, 1, '2025-10-15 23:07:08', NULL, 0, 0, 0, 'pero no se por que, en la bd se borra de un solo lado', 'normal', NULL, 0, '2025-10-15 23:07:08', NULL, NULL, 0),
(263, 2, 1, '', 0, 1, '2025-10-15 23:07:05', NULL, 0, 0, 0, 'A', 'normal', NULL, 0, '2025-10-15 23:07:05', NULL, NULL, 0),
(262, 1, 2, '', 0, 1, '2025-10-15 23:07:02', NULL, 0, 0, 0, 'es porque dejamos de ser amigos ya', 'normal', NULL, 0, '2025-10-15 23:07:01', NULL, NULL, 0),
(261, 1, 2, '[Respuesta Automatica de Perseo]\n\nHola, Angel no esta disponible en este momento. Tu mensaje ha sido recibido y sera respondido en breve. Gracias por tu paciencia!', 1, 1, '2025-10-15 23:07:02', NULL, 0, 0, 0, '', 'normal', NULL, 0, '2025-10-15 23:06:44', NULL, NULL, 0),
(260, 2, 1, '', 0, 1, '2025-10-15 23:06:53', NULL, 0, 0, 0, 'solo alexis', 'normal', NULL, 0, '2025-10-15 23:06:32', NULL, NULL, 0),
(259, 2, 1, '', 0, 1, '2025-10-15 23:06:53', NULL, 0, 0, 0, 'no me apareces en la lista de contactos', 'normal', NULL, 0, '2025-10-15 23:06:30', NULL, NULL, 0),
(258, 2, 1, '', 0, 1, '2025-10-15 23:06:53', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-15 23:06:20', NULL, NULL, 0),
(257, 2, 7, '', 0, 1, '2025-10-16 21:12:35', NULL, 0, 0, 0, 're gei, a vo seguro te gusta el chori podrido', 'normal', NULL, 0, '2025-10-15 22:44:41', NULL, NULL, 0),
(256, 1, 7, '', 0, 1, '2025-10-16 21:12:46', NULL, 0, 0, 0, 'deci \"tu mami\" si sos gay', 'normal', NULL, 0, '2025-10-15 21:44:01', '2025-10-16 23:24:31', NULL, 0),
(255, 1, 7, '', 0, 1, '2025-10-16 21:12:46', NULL, 0, 0, 0, 'sos re kaka', 'normal', NULL, 0, '2025-10-15 21:43:56', NULL, NULL, 0),
(254, 7, 1, '', 0, 1, '2025-10-15 21:21:36', NULL, 0, 0, 0, '´,señMSE', 'normal', NULL, 0, '2025-10-15 21:21:36', NULL, NULL, 0),
(253, 7, 1, '', 0, 1, '2025-10-15 21:21:35', NULL, 0, 0, 0, '´SEMB´LMSE,', 'normal', NULL, 0, '2025-10-15 21:21:35', NULL, NULL, 0),
(252, 7, 1, '', 0, 1, '2025-10-15 21:21:35', NULL, 0, 0, 0, '´SEMB´se;b¨sEMB´SEB', 'normal', NULL, 0, '2025-10-15 21:21:35', NULL, NULL, 0),
(251, 7, 1, '', 0, 1, '2025-10-15 21:21:34', NULL, 0, 0, 0, 'seb´PSE,.B', 'normal', NULL, 0, '2025-10-15 21:21:34', NULL, NULL, 0),
(250, 7, 1, '', 0, 1, '2025-10-15 21:21:34', NULL, 0, 0, 0, 'S.EV', 'normal', NULL, 0, '2025-10-15 21:21:33', NULL, NULL, 0),
(249, 7, 1, '', 0, 1, '2025-10-15 21:21:34', NULL, 0, 0, 0, 'SemV', 'normal', NULL, 0, '2025-10-15 21:21:33', NULL, NULL, 0),
(248, 7, 1, '', 0, 1, '2025-10-15 21:21:33', NULL, 0, 0, 0, 'B,,', 'normal', NULL, 0, '2025-10-15 21:21:33', NULL, NULL, 0),
(247, 7, 1, '', 0, 1, '2025-10-15 21:21:33', NULL, 0, 0, 0, '´V´sebBMS', 'normal', NULL, 0, '2025-10-15 21:21:33', NULL, NULL, 0),
(246, 7, 1, '', 0, 1, '2025-10-15 21:21:32', NULL, 0, 0, 0, '´s:bÑMSE,', 'normal', NULL, 0, '2025-10-15 21:21:32', NULL, NULL, 0),
(245, 7, 1, '', 0, 1, '2025-10-15 21:21:32', NULL, 0, 0, 0, 'SEV.SÑELV-seV.´Lse:', 'normal', NULL, 0, '2025-10-15 21:21:32', NULL, NULL, 0),
(244, 7, 1, '', 0, 1, '2025-10-15 21:21:31', NULL, 0, 0, 0, 'SG.ÑSéV', 'normal', NULL, 0, '2025-10-15 21:21:31', NULL, NULL, 0),
(243, 7, 1, '', 0, 1, '2025-10-15 21:21:31', NULL, 0, 0, 0, '´-SFLMEW', 'normal', NULL, 0, '2025-10-15 21:21:31', NULL, NULL, 0),
(242, 7, 1, '', 0, 1, '2025-10-15 21:21:30', NULL, 0, 0, 0, '-FAe`BNSE', 'normal', NULL, 0, '2025-10-15 21:21:30', NULL, NULL, 0),
(87, 7, 1, '', 0, 1, '2025-10-15 03:04:10', NULL, 0, 0, 0, 'gaya', 'normal', NULL, 0, '2025-10-15 03:04:04', NULL, NULL, 0),
(88, 1, 7, '', 0, 1, '2025-10-15 03:04:29', NULL, 0, 0, 0, 'que wea hacés aca', 'normal', NULL, 0, '2025-10-15 03:04:29', NULL, NULL, 0),
(89, 7, 1, '', 0, 1, '2025-10-15 03:04:58', NULL, 0, 0, 0, 'cosas', 'normal', NULL, 0, '2025-10-15 03:04:57', NULL, NULL, 0),
(90, 1, 7, '', 0, 1, '2025-10-15 03:05:01', NULL, 0, 0, 0, 'apoco', 'normal', NULL, 0, '2025-10-15 03:05:01', NULL, NULL, 0),
(91, 1, 7, '', 0, 1, '2025-10-15 03:05:03', NULL, 0, 0, 0, 'mandame otro mensaje', 'normal', NULL, 0, '2025-10-15 03:05:03', NULL, NULL, 0),
(92, 7, 1, '', 0, 1, '2025-10-15 03:05:05', NULL, 0, 0, 0, 'ugunt', 'normal', NULL, 0, '2025-10-15 03:05:05', NULL, NULL, 0),
(93, 1, 7, '', 0, 1, '2025-10-15 03:05:10', NULL, 0, 0, 0, 'que me mandaste justo cuando recargue', 'normal', NULL, 0, '2025-10-15 03:05:10', NULL, NULL, 0),
(94, 1, 7, '', 0, 1, '2025-10-15 03:05:11', NULL, 0, 0, 0, 'ahi', 'normal', NULL, 0, '2025-10-15 03:05:10', NULL, NULL, 0),
(95, 1, 7, '', 0, 1, '2025-10-15 03:05:12', NULL, 0, 0, 0, 'anda bien', 'normal', NULL, 0, '2025-10-15 03:05:12', NULL, NULL, 0),
(97, 1, 7, '', 0, 1, '2025-10-15 03:05:13', NULL, 0, 0, 0, 'ya esta bro', 'normal', NULL, 0, '2025-10-15 03:05:13', NULL, NULL, 0),
(98, 7, 1, '', 0, 1, '2025-10-15 03:05:13', NULL, 0, 0, 0, 'kya', 'normal', NULL, 0, '2025-10-15 03:05:13', NULL, NULL, 0),
(241, 7, 1, '', 0, 1, '2025-10-15 21:21:30', NULL, 0, 0, 0, 'VELM,S', 'normal', NULL, 0, '2025-10-15 21:21:30', NULL, NULL, 0),
(100, 7, 1, '', 0, 1, '2025-10-15 03:05:14', NULL, 0, 0, 0, 'kya', 'normal', NULL, 0, '2025-10-15 03:05:14', NULL, NULL, 0),
(102, 1, 7, '', 0, 1, '2025-10-15 03:05:48', NULL, 0, 0, 0, 'que sepas que ya se lo mande a todos', 'normal', NULL, 0, '2025-10-15 03:05:48', NULL, NULL, 0),
(103, 1, 7, '', 0, 1, '2025-10-15 03:05:50', NULL, 0, 0, 0, 'hasta a sofia', 'normal', NULL, 0, '2025-10-15 03:05:50', NULL, NULL, 0),
(104, 7, 1, '', 0, 1, '2025-10-15 03:06:06', NULL, 0, 0, 0, 'jaja', 'normal', NULL, 0, '2025-10-15 03:06:00', NULL, NULL, 0),
(240, 7, 1, '', 0, 1, '2025-10-15 21:21:29', NULL, 0, 0, 0, '´d+çW,duiN DÑ´S-aoisnfiuaeh08uksç', 'normal', NULL, 0, '2025-10-15 21:21:29', NULL, NULL, 0),
(106, 2, 7, '', 0, 1, '2025-10-15 03:07:25', NULL, 0, 0, 0, 'que hace un mono truequeando', 'normal', NULL, 0, '2025-10-15 03:06:37', NULL, NULL, 0),
(107, 1, 7, '', 0, 1, '2025-10-15 03:07:15', NULL, 0, 0, 0, 'es re epico', 'normal', NULL, 0, '2025-10-15 03:07:15', NULL, NULL, 0),
(108, 7, 1, '', 0, 1, '2025-10-15 03:07:21', NULL, 0, 0, 0, 'porque bro', 'normal', NULL, 0, '2025-10-15 03:07:21', NULL, NULL, 0),
(109, 1, 7, '', 0, 1, '2025-10-15 03:07:21', NULL, 0, 0, 0, '❤+ç', 'normal', NULL, 0, '2025-10-15 03:07:21', NULL, NULL, 0),
(110, 7, 1, '', 0, 1, '2025-10-15 03:07:25', NULL, 0, 0, 0, 'porque me expones', 'normal', NULL, 0, '2025-10-15 03:07:25', NULL, NULL, 0),
(111, 1, 7, '', 0, 1, '2025-10-15 03:07:40', NULL, 0, 0, 0, 'JA', 'normal', NULL, 0, '2025-10-15 03:07:28', NULL, NULL, 0),
(112, 7, 2, '', 0, 1, '2025-10-15 03:07:31', NULL, 0, 0, 0, 'no me hables vos', 'normal', NULL, 0, '2025-10-15 03:07:31', NULL, NULL, 0),
(113, 7, 2, '', 0, 1, '2025-10-15 03:07:39', NULL, 0, 0, 0, 'andate con la mujer esa', 'normal', NULL, 0, '2025-10-15 03:07:39', NULL, NULL, 0),
(114, 7, 1, '', 0, 1, '2025-10-15 03:07:51', NULL, 0, 0, 0, 'quien es Zami?', 'normal', NULL, 0, '2025-10-15 03:07:51', NULL, NULL, 0),
(115, 1, 7, '', 0, 1, '2025-10-15 03:07:56', NULL, 0, 0, 0, 'mi hermana', 'normal', NULL, 0, '2025-10-15 03:07:56', NULL, NULL, 0),
(116, 1, 7, '', 0, 1, '2025-10-15 03:08:05', NULL, 0, 0, 0, 'porque le hice que se meta para probar el chat', 'normal', NULL, 0, '2025-10-15 03:08:05', NULL, NULL, 0),
(117, 7, 1, '', 0, 1, '2025-10-15 03:08:08', NULL, 0, 0, 0, 'y porque no esta Zami2,3,4,5,6,7,8,9,10,11', 'normal', NULL, 0, '2025-10-15 03:08:08', NULL, NULL, 0),
(118, 1, 7, '', 0, 1, '2025-10-15 03:08:19', NULL, 0, 0, 0, 'porque los borré', 'normal', NULL, 0, '2025-10-15 03:08:19', NULL, NULL, 0),
(119, 1, 7, '', 0, 1, '2025-10-15 03:08:20', NULL, 0, 0, 0, 'u', 'normal', NULL, 0, '2025-10-15 03:08:20', NULL, NULL, 0),
(120, 1, 7, '', 0, 1, '2025-10-15 03:08:21', NULL, 0, 0, 0, 'bro', 'normal', NULL, 0, '2025-10-15 03:08:21', NULL, NULL, 0),
(121, 1, 7, '', 0, 1, '2025-10-15 03:08:26', NULL, 0, 0, 0, 'te animas a crearte una cuenta mas', 'normal', NULL, 0, '2025-10-15 03:08:26', NULL, NULL, 0),
(122, 2, 7, '', 0, 1, '2025-10-15 03:10:08', NULL, 0, 0, 0, 'XD', 'normal', NULL, 0, '2025-10-15 03:08:31', NULL, NULL, 0),
(123, 2, 7, '', 0, 1, '2025-10-15 03:10:08', NULL, 0, 0, 0, '?????', 'normal', NULL, 0, '2025-10-15 03:08:32', NULL, NULL, 0),
(124, 2, 7, '', 0, 1, '2025-10-15 03:10:08', NULL, 0, 0, 0, 'que dicia', 'normal', NULL, 0, '2025-10-15 03:08:35', NULL, NULL, 0),
(125, 1, 7, '', 0, 1, '2025-10-15 03:08:35', NULL, 0, 0, 0, 'a ver que pasa si se crea una cuenta mas y se va para abajo?', 'normal', NULL, 0, '2025-10-15 03:08:35', NULL, NULL, 0),
(127, 2, 7, '', 0, 1, '2025-10-15 03:10:08', NULL, 0, 0, 0, 'eso te tendria que decir yo a vos', 'normal', NULL, 0, '2025-10-15 03:08:43', NULL, NULL, 0),
(239, 1, 7, '', 0, 1, '2025-10-15 21:21:26', NULL, 0, 0, 0, 'a ver bro', 'normal', NULL, 0, '2025-10-15 21:21:26', NULL, NULL, 0),
(238, 7, 1, '', 0, 1, '2025-10-15 21:21:26', NULL, 0, 0, 0, 'gayç', 'normal', NULL, 0, '2025-10-15 21:21:26', NULL, NULL, 0),
(237, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'apoco', 'normal', NULL, 0, '2025-10-15 21:17:40', NULL, NULL, 0),
(236, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'ad', 'normal', NULL, 0, '2025-10-15 21:16:32', NULL, NULL, 0),
(132, 7, 1, '', 0, 1, '2025-10-15 03:09:25', NULL, 0, 0, 0, 'que paja bro', 'normal', NULL, 0, '2025-10-15 03:09:02', NULL, NULL, 0),
(235, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'd', 'normal', NULL, 0, '2025-10-15 21:16:32', NULL, NULL, 0),
(134, 2, 7, '', 0, 1, '2025-10-15 03:10:08', NULL, 0, 0, 0, 'jei', 'normal', NULL, 0, '2025-10-15 03:09:03', NULL, NULL, 0),
(135, 7, 1, '', 0, 1, '2025-10-15 03:09:25', NULL, 0, 0, 0, 'create vos', 'normal', NULL, 0, '2025-10-15 03:09:05', NULL, NULL, 0),
(233, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'as', 'normal', NULL, 0, '2025-10-15 21:16:32', NULL, NULL, 0),
(234, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'das', 'normal', NULL, 0, '2025-10-15 21:16:32', NULL, NULL, 0),
(137, 1, 7, '', 0, 1, '2025-10-15 03:09:40', NULL, 0, 0, 0, 'ya le dije al chancho y a la enana', 'normal', NULL, 0, '2025-10-15 03:09:40', NULL, NULL, 0),
(138, 7, 2, '', 0, 1, '2025-10-15 03:10:38', NULL, 0, 0, 0, 'como que a mi', 'normal', NULL, 0, '2025-10-15 03:10:16', NULL, NULL, 0),
(232, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'd', 'normal', NULL, 0, '2025-10-15 21:16:31', NULL, NULL, 0),
(231, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'a', 'normal', NULL, 0, '2025-10-15 21:16:31', NULL, NULL, 0),
(230, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'asd', 'normal', NULL, 0, '2025-10-15 21:16:31', NULL, NULL, 0),
(229, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'd', 'normal', NULL, 0, '2025-10-15 21:16:31', NULL, NULL, 0),
(143, 2, 7, '', 0, 1, '2025-10-15 03:10:49', NULL, 0, 0, 0, 'no me digas nada', 'normal', NULL, 0, '2025-10-15 03:10:49', NULL, NULL, 0),
(144, 2, 7, '', 0, 1, '2025-10-15 03:10:52', NULL, 0, 0, 0, 'sh', 'normal', NULL, 0, '2025-10-15 03:10:52', NULL, NULL, 0),
(145, 2, 7, '', 0, 1, '2025-10-15 03:10:57', NULL, 0, 0, 0, 'andateeeeeeee', 'normal', NULL, 0, '2025-10-15 03:10:57', NULL, NULL, 0),
(228, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'as', 'normal', NULL, 0, '2025-10-15 21:16:31', NULL, NULL, 0),
(147, 7, 2, '', 0, 1, '2025-10-15 03:12:00', NULL, 0, 0, 0, 'yo no tengo contacto femenino', 'normal', NULL, 0, '2025-10-15 03:11:12', NULL, NULL, 0),
(148, 7, 2, '', 0, 1, '2025-10-15 03:12:00', NULL, 0, 0, 0, 'es pura falacia', 'normal', NULL, 0, '2025-10-15 03:11:16', NULL, NULL, 0),
(149, 7, 1, '', 0, 1, '2025-10-15 03:11:57', NULL, 0, 0, 0, 'hace que cuando le doy al escape en un chat me saque', 'normal', NULL, 0, '2025-10-15 03:11:53', NULL, NULL, 0),
(150, 7, 1, '', 0, 1, '2025-10-15 03:11:58', NULL, 0, 0, 0, 'me da toc sino', 'normal', NULL, 0, '2025-10-15 03:11:58', NULL, NULL, 0),
(151, 1, 7, '', 0, 1, '2025-10-15 03:12:03', NULL, 0, 0, 0, 'ok', 'normal', NULL, 0, '2025-10-15 03:12:03', NULL, NULL, 0),
(227, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'sd', 'normal', NULL, 0, '2025-10-15 21:16:31', NULL, NULL, 0),
(226, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'd', 'normal', NULL, 0, '2025-10-15 21:16:31', NULL, NULL, 0),
(225, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'as', 'normal', NULL, 0, '2025-10-15 21:16:31', NULL, NULL, 0),
(224, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'sd', 'normal', NULL, 0, '2025-10-15 21:16:31', NULL, NULL, 0),
(223, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'a', 'normal', NULL, 0, '2025-10-15 21:16:30', NULL, NULL, 0),
(222, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'asd', 'normal', NULL, 0, '2025-10-15 21:16:30', NULL, NULL, 0),
(221, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'd', 'normal', NULL, 0, '2025-10-15 21:16:30', NULL, NULL, 0),
(220, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'as', 'normal', NULL, 0, '2025-10-15 21:16:30', NULL, NULL, 0),
(219, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'asd', 'normal', NULL, 0, '2025-10-15 21:16:30', NULL, NULL, 0),
(218, 1, 7, '', 0, 1, '2025-10-15 21:21:23', NULL, 0, 0, 0, 'asd', 'normal', NULL, 0, '2025-10-15 21:16:30', NULL, NULL, 0),
(217, 7, 1, '', 0, 1, '2025-10-15 21:16:13', NULL, 0, 0, 0, 'jj', 'normal', NULL, 0, '2025-10-15 20:53:07', NULL, NULL, 0),
(216, 1, 7, '', 0, 1, '2025-10-15 20:43:19', NULL, 0, 0, 0, 'chilito de cojones', 'normal', NULL, 0, '2025-10-15 20:42:56', NULL, NULL, 0),
(166, 1, 7, '', 0, 1, '2025-10-15 03:51:29', NULL, 0, 0, 0, 'ya lo agregué', 'normal', NULL, 0, '2025-10-15 03:51:29', NULL, NULL, 0),
(167, 1, 7, '', 0, 1, '2025-10-15 04:03:22', NULL, 0, 0, 0, 'listo bro', 'normal', NULL, 0, '2025-10-15 04:01:54', NULL, NULL, 0),
(214, 1, 7, '', 0, 1, '2025-10-15 20:41:57', NULL, 0, 0, 0, 'te quiero intercambiar algo', 'normal', NULL, 0, '2025-10-15 20:41:43', NULL, NULL, 0),
(215, 1, 7, '', 0, 1, '2025-10-15 20:41:57', NULL, 0, 0, 0, '🤭🤭🤭🤭🤭🤭🤭🤭🤭🤭🤭', 'normal', NULL, 0, '2025-10-15 20:41:47', NULL, NULL, 0),
(169, 7, 1, '', 0, 1, '2025-10-15 04:04:54', NULL, 0, 0, 0, 'bien bro', 'normal', NULL, 0, '2025-10-15 04:03:28', NULL, NULL, 0),
(170, 1, 7, '', 0, 1, '2025-10-15 04:05:36', NULL, 0, 0, 0, 'si bro', 'normal', NULL, 0, '2025-10-15 04:04:57', NULL, NULL, 0),
(171, 7, 1, '', 0, 1, '2025-10-15 04:06:00', NULL, 0, 0, 0, 'mandame 1 mensaje', 'normal', NULL, 0, '2025-10-15 04:05:44', NULL, NULL, 0),
(172, 1, 7, '', 0, 1, '2025-10-15 04:06:25', NULL, 0, 0, 0, '67', 'normal', NULL, 0, '2025-10-15 04:06:03', NULL, NULL, 0),
(173, 7, 1, '', 0, 1, '2025-10-15 04:06:44', NULL, 0, 0, 0, 'me sale como si tuviera 3 notis cuando me mandas 1', 'normal', NULL, 0, '2025-10-15 04:06:42', NULL, NULL, 0),
(174, 1, 7, '', 0, 1, '2025-10-15 04:07:00', NULL, 0, 0, 0, 'recargá página', 'normal', NULL, 0, '2025-10-15 04:06:52', NULL, NULL, 0),
(175, 1, 7, '', 0, 1, '2025-10-15 04:07:00', NULL, 0, 0, 0, 'sos el unico con el error', 'normal', NULL, 0, '2025-10-15 04:06:57', NULL, NULL, 0),
(176, 7, 1, '', 0, 1, '2025-10-15 04:07:13', NULL, 0, 0, 0, 'madname otr', 'normal', NULL, 0, '2025-10-15 04:07:11', NULL, NULL, 0),
(177, 1, 7, '', 0, 1, '2025-10-15 04:07:19', NULL, 0, 0, 0, 'no quiero', 'normal', NULL, 0, '2025-10-15 04:07:15', NULL, NULL, 0),
(178, 7, 1, '', 0, 1, '2025-10-15 04:07:21', NULL, 0, 0, 0, 'otro', 'normal', NULL, 0, '2025-10-15 04:07:21', NULL, NULL, 0),
(179, 1, 7, '', 0, 1, '2025-10-15 04:07:34', NULL, 0, 0, 0, 'ctrl + shift + r', 'normal', NULL, 0, '2025-10-15 04:07:33', NULL, NULL, 0),
(180, 7, 1, '', 0, 1, '2025-10-15 04:08:25', NULL, 0, 0, 0, 'ya lo hice mogolico', 'normal', NULL, 0, '2025-10-15 04:07:59', NULL, NULL, 0),
(181, 7, 1, '', 0, 1, '2025-10-15 04:08:25', NULL, 0, 0, 0, 'las notificaciones no se reinician', 'normal', NULL, 0, '2025-10-15 04:08:11', NULL, NULL, 0),
(182, 7, 1, '', 0, 1, '2025-10-15 04:08:31', NULL, 0, 0, 0, 'tipo me mandas 1 y me aparece 1, la leo, me mandas otro y sale 2 en vez de 1', 'normal', NULL, 0, '2025-10-15 04:08:31', NULL, NULL, 0),
(183, 1, 7, '', 0, 1, '2025-10-15 04:08:36', NULL, 0, 0, 0, 'sos el unico', 'normal', NULL, 0, '2025-10-15 04:08:36', NULL, NULL, 0),
(184, 7, 1, '', 0, 1, '2025-10-15 04:09:01', NULL, 0, 0, 0, 'a y cuando le doy al escape en la parte izquierda no se quita que el chat se vea verde', 'normal', NULL, 0, '2025-10-15 04:09:00', NULL, NULL, 0),
(185, 1, 7, '', 0, 1, '2025-10-15 04:09:26', NULL, 0, 0, 0, 'decí \"aprende a leer\" si sos gay', 'normal', NULL, 0, '2025-10-15 04:09:22', '2025-10-15 04:09:55', NULL, 0),
(186, 7, 1, '', 0, 1, '2025-10-15 04:09:35', NULL, 0, 0, 0, 'aprende a leer', 'normal', NULL, 0, '2025-10-15 04:09:35', NULL, NULL, 0),
(187, 1, 7, '', 0, 1, '2025-10-15 04:10:08', NULL, 0, 0, 0, 'bro?', 'normal', NULL, 0, '2025-10-15 04:10:07', NULL, NULL, 0),
(188, 1, 7, '', 0, 1, '2025-10-15 04:16:47', NULL, 0, 0, 0, 'buena foto de perfil', 'normal', NULL, 0, '2025-10-15 04:15:15', NULL, NULL, 0),
(189, 7, 1, '', 0, 1, '2025-10-15 04:16:54', NULL, 0, 0, 0, 'bro', 'normal', NULL, 0, '2025-10-15 04:16:52', NULL, NULL, 0),
(190, 7, 1, '', 0, 1, '2025-10-15 04:16:58', NULL, 0, 0, 0, 'ya se que me la pusiste vos', 'normal', NULL, 0, '2025-10-15 04:16:58', NULL, NULL, 0),
(191, 1, 7, '', 0, 1, '2025-10-15 04:17:02', NULL, 0, 0, 0, 'six seven', 'normal', NULL, 0, '2025-10-15 04:17:02', NULL, NULL, 0),
(192, 7, 1, '', 0, 1, '2025-10-15 04:17:07', NULL, 0, 0, 0, 'no te hagas el bobo', 'normal', NULL, 0, '2025-10-15 04:17:07', NULL, NULL, 0),
(193, 1, 7, '', 0, 1, '2025-10-15 04:17:09', 190, 0, 0, 0, 'yo no puedo hacer eso', 'normal', NULL, 0, '2025-10-15 04:17:09', NULL, NULL, 0),
(194, 7, 1, '', 0, 1, '2025-10-15 04:19:27', NULL, 0, 0, 0, 'apoco', 'normal', NULL, 0, '2025-10-15 04:18:54', NULL, NULL, 0),
(195, 1, 7, '', 0, 1, '2025-10-15 04:22:20', NULL, 0, 0, 0, '✨', 'normal', NULL, 0, '2025-10-15 04:20:30', NULL, NULL, 0),
(196, 1, 7, '', 0, 1, '2025-10-15 04:22:20', NULL, 0, 0, 0, 'te fijaste que no fuera video ni gift?', 'normal', NULL, 0, '2025-10-15 04:20:45', NULL, NULL, 0),
(197, 7, 1, '', 0, 1, '2025-10-15 04:24:19', NULL, 0, 0, 0, 'es un .png', 'normal', NULL, 0, '2025-10-15 04:22:28', NULL, NULL, 0),
(198, 7, 1, '', 0, 1, '2025-10-15 04:24:19', NULL, 0, 0, 0, 'de un objeto de minecraft', 'normal', NULL, 0, '2025-10-15 04:22:35', NULL, NULL, 0),
(199, 1, 7, '', 0, 1, '2025-10-15 04:24:34', NULL, 0, 0, 0, 'a', 'normal', NULL, 0, '2025-10-15 04:24:23', NULL, NULL, 0),
(200, 7, 1, '[Respuesta Automatica de Perseo]\n\nAlexis8090 esta ocupado/a ahora mismo. He guardado tu mensaje y te respondera lo antes posible. Gracias!', 1, 1, '2025-10-15 04:25:15', NULL, 0, 0, 0, '', 'normal', NULL, 0, '2025-10-15 04:24:29', NULL, NULL, 0),
(201, 1, 7, '', 0, 1, '2025-10-15 04:25:18', NULL, 0, 0, 0, '?', 'normal', NULL, 0, '2025-10-15 04:25:18', NULL, NULL, 0),
(213, 7, 1, '', 0, 1, '2025-10-15 20:41:25', NULL, 0, 0, 0, 'sexo', 'normal', NULL, 0, '2025-10-15 20:34:27', NULL, NULL, 0),
(212, 7, 2, '', 0, 1, '2025-10-15 20:34:29', NULL, 0, 0, 0, 'ugu', 'normal', NULL, 0, '2025-10-15 20:34:22', NULL, NULL, 0),
(210, 2, 7, '', 0, 1, '2025-10-15 20:34:02', NULL, 0, 0, 0, 'ugu', 'normal', NULL, 0, '2025-10-15 20:21:51', NULL, NULL, 0),
(209, 2, 7, '', 0, 1, '2025-10-15 20:34:02', NULL, 0, 0, 0, 'regalame una plei', 'normal', NULL, 0, '2025-10-15 20:21:50', NULL, NULL, 0),
(211, 7, 2, '', 0, 1, '2025-10-15 20:34:29', NULL, 0, 0, 0, 'no', 'normal', NULL, 0, '2025-10-15 20:34:21', NULL, NULL, 0),
(208, 2, 7, '', 0, 1, '2025-10-15 20:34:02', NULL, 0, 0, 0, 'porque te tengo agregado a vos? re loco', 'normal', NULL, 0, '2025-10-15 20:21:46', NULL, NULL, 0),
(290, 2, 1, '', 0, 1, '2025-10-15 23:14:30', NULL, 0, 0, 0, 'an fojaf', 'normal', NULL, 0, '2025-10-15 23:14:30', NULL, NULL, 0),
(291, 2, 1, '', 0, 1, '2025-10-15 23:14:30', NULL, 0, 0, 0, 'k nsfs', 'normal', NULL, 0, '2025-10-15 23:14:30', NULL, NULL, 0),
(292, 2, 1, '', 0, 1, '2025-10-15 23:14:34', NULL, 0, 0, 0, 'n ds', 'normal', NULL, 0, '2025-10-15 23:14:34', NULL, NULL, 0),
(293, 2, 1, '', 0, 1, '2025-10-15 23:14:35', NULL, 0, 0, 0, '\\', 'normal', NULL, 0, '2025-10-15 23:14:35', NULL, NULL, 0),
(294, 1, 2, '', 0, 1, '2025-10-15 23:14:41', NULL, 0, 0, 0, 'dejame', 'normal', NULL, 0, '2025-10-15 23:14:41', NULL, NULL, 0),
(295, 2, 1, '', 0, 1, '2025-10-15 23:16:02', NULL, 0, 0, 0, 's', 'normal', NULL, 0, '2025-10-15 23:16:02', NULL, NULL, 0),
(296, 2, 1, '', 0, 1, '2025-10-15 23:16:04', NULL, 0, 0, 0, 'listo', 'normal', NULL, 0, '2025-10-15 23:16:04', NULL, NULL, 0),
(297, 2, 1, '', 0, 1, '2025-10-15 23:16:08', NULL, 0, 0, 0, 'no juego lol contigo', 'normal', NULL, 0, '2025-10-15 23:16:08', NULL, NULL, 0),
(298, 2, 1, '', 0, 1, '2025-10-15 23:16:10', NULL, 0, 0, 0, 'malvada', 'normal', NULL, 0, '2025-10-15 23:16:10', NULL, NULL, 0),
(299, 1, 2, '', 0, 1, '2025-10-15 23:22:45', NULL, 0, 0, 0, 're mal', 'normal', NULL, 0, '2025-10-15 23:17:49', NULL, NULL, 0),
(300, 1, 2, '', 0, 1, '2025-10-15 23:22:45', 299, 0, 0, 0, 'o sea', 'normal', NULL, 0, '2025-10-15 23:18:14', NULL, NULL, 0),
(301, 2, 1, '[Respuesta Automatica de Perseo]\n\nActualmente Alejo no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 1, 1, '2025-10-15 23:19:07', NULL, 0, 0, 0, '', 'normal', NULL, 0, '2025-10-15 23:18:16', NULL, NULL, 0),
(302, 1, 2, '[Respuesta Automatica de Perseo]\n\nAngel esta ocupado/a ahora mismo. He guardado tu mensaje y te respondera lo antes posible. Gracias!', 1, 1, '2025-10-15 23:22:45', NULL, 0, 0, 0, 'apoco', 'normal', NULL, 0, '2025-10-15 23:19:05', '2025-10-15 23:28:27', NULL, 0),
(303, 2, 1, '[Respuesta Automatica de Perseo]\n\nActualmente Alejo no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 1, 1, '2025-10-15 23:22:26', NULL, 0, 0, 0, '', 'normal', NULL, 0, '2025-10-15 23:22:22', NULL, NULL, 0),
(304, 2, 1, '', 0, 1, '2025-10-15 23:22:53', NULL, 0, 0, 0, 'xd', 'normal', NULL, 0, '2025-10-15 23:22:53', NULL, NULL, 0),
(305, 2, 1, '', 0, 1, '2025-10-15 23:22:54', NULL, 0, 0, 0, 'wn', 'normal', NULL, 0, '2025-10-15 23:22:54', NULL, NULL, 0),
(306, 1, 2, '', 0, 1, '2025-10-15 23:23:20', NULL, 0, 0, 0, 'que bro', 'normal', NULL, 0, '2025-10-15 23:23:20', NULL, '[1]', 0),
(307, 2, 1, '', 0, 1, '2025-10-15 23:23:42', NULL, 0, 0, 0, 'pudiste usar el php', 'normal', NULL, 0, '2025-10-15 23:23:42', NULL, NULL, 0),
(308, 1, 2, '', 0, 1, '2025-10-15 23:23:55', NULL, 0, 0, 0, 'que php', 'normal', NULL, 0, '2025-10-15 23:23:55', NULL, '[1]', 0),
(309, 2, 1, '', 0, 1, '2025-10-15 23:31:32', NULL, 0, 0, 0, 'el busquestia-chat.php', 'normal', NULL, 0, '2025-10-15 23:30:27', NULL, NULL, 0),
(310, 1, 2, '[Respuesta Automatica de Perseo]\n\nActualmente Angel no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 1, 1, '2025-10-15 23:33:07', NULL, 0, 0, 0, '', 'normal', NULL, 0, '2025-10-15 23:31:28', NULL, NULL, 0),
(311, 1, 2, '', 0, 1, '2025-10-15 23:33:07', 309, 0, 0, 0, 'ugunt', 'normal', NULL, 0, '2025-10-15 23:33:07', NULL, NULL, 0),
(312, 1, 2, '', 0, 1, '2025-10-15 23:33:11', NULL, 0, 0, 0, 'nao nao', 'normal', NULL, 0, '2025-10-15 23:33:11', NULL, NULL, 0),
(313, 1, 2, '', 0, 1, '2025-10-15 23:34:13', 309, 0, 0, 0, 'a tu mami yo le hago cositas', 'normal', NULL, 0, '2025-10-15 23:34:13', NULL, NULL, 0),
(314, 1, 2, '', 0, 1, '2025-10-15 23:34:14', NULL, 0, 0, 0, 'ugu', 'normal', NULL, 0, '2025-10-15 23:34:14', NULL, NULL, 0),
(315, 2, 1, '', 0, 1, '2025-10-15 23:37:35', NULL, 0, 0, 0, 'ctmr', 'normal', NULL, 0, '2025-10-15 23:37:35', NULL, NULL, 0),
(316, 2, 1, '', 0, 1, '2025-10-15 23:43:23', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-15 23:42:54', NULL, NULL, 0),
(317, 2, 1, '', 0, 1, '2025-10-15 23:43:23', NULL, 0, 0, 0, 'gei', 'normal', NULL, 0, '2025-10-15 23:42:56', NULL, NULL, 0),
(318, 1, 2, '', 1, 1, '2025-10-15 23:56:06', NULL, 0, 0, 0, '[Respuesta Automatica de Perseo]\n\nActualmente Angel no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 'normal', NULL, 0, '2025-10-15 23:43:06', NULL, NULL, 0),
(319, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 'xzfdfds', 'normal', NULL, 0, '2025-10-15 23:44:33', NULL, NULL, 0),
(320, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 'sdf', 'normal', NULL, 0, '2025-10-15 23:44:35', NULL, NULL, 0),
(321, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 'f', 'normal', NULL, 0, '2025-10-15 23:44:35', NULL, NULL, 0),
(322, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 'sf', 'normal', NULL, 0, '2025-10-15 23:44:36', NULL, NULL, 0),
(323, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 's', 'normal', NULL, 0, '2025-10-15 23:44:36', NULL, NULL, 0),
(324, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 'f', 'normal', NULL, 0, '2025-10-15 23:44:36', NULL, NULL, 0),
(325, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 's', 'normal', NULL, 0, '2025-10-15 23:44:37', NULL, NULL, 0),
(326, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 'dssd', 'normal', NULL, 0, '2025-10-15 23:44:37', NULL, NULL, 0),
(327, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 'dsfs', 'normal', NULL, 0, '2025-10-15 23:44:37', NULL, NULL, 0),
(328, 1, 2, '', 1, 1, '2025-10-15 23:56:06', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nAngel esta ocupado/a ahora mismo. He guardado tu mensaje y te respondera lo antes posible. Gracias!', 'normal', NULL, 0, '2025-10-15 23:44:55', NULL, NULL, 0),
(329, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 'dsfdgfdgfd', 'normal', NULL, 0, '2025-10-15 23:44:57', NULL, NULL, 0),
(330, 2, 1, '', 0, 1, '2025-10-15 23:45:02', NULL, 0, 0, 0, 'dfdgfdg', 'normal', NULL, 0, '2025-10-15 23:44:58', NULL, NULL, 0),
(331, 8, 1, '', 0, 1, '2025-10-15 23:45:45', NULL, 0, 0, 0, 'causinia', 'normal', NULL, 0, '2025-10-15 23:45:32', NULL, NULL, 0),
(332, 2, 1, '', 0, 1, '2025-10-15 23:46:37', NULL, 0, 0, 0, 'fdsf', 'normal', NULL, 0, '2025-10-15 23:46:37', NULL, NULL, 0),
(333, 2, 1, '', 0, 1, '2025-10-15 23:46:37', NULL, 0, 0, 0, 'g', 'normal', NULL, 0, '2025-10-15 23:46:37', NULL, NULL, 0),
(334, 2, 1, '', 0, 1, '2025-10-15 23:46:37', NULL, 0, 0, 0, 'd', 'normal', NULL, 0, '2025-10-15 23:46:37', NULL, NULL, 0),
(335, 2, 1, '', 0, 1, '2025-10-15 23:46:37', NULL, 0, 0, 0, 'd', 'normal', NULL, 0, '2025-10-15 23:46:37', NULL, NULL, 0),
(336, 2, 1, '', 0, 1, '2025-10-15 23:46:38', NULL, 0, 0, 0, 'gfd', 'normal', NULL, 0, '2025-10-15 23:46:38', NULL, NULL, 0),
(337, 2, 1, '', 0, 1, '2025-10-15 23:46:38', NULL, 0, 0, 0, 'fd', 'normal', NULL, 0, '2025-10-15 23:46:38', NULL, NULL, 0),
(338, 2, 1, '', 0, 1, '2025-10-15 23:46:38', NULL, 0, 0, 0, 'fdg', 'normal', NULL, 0, '2025-10-15 23:46:38', NULL, NULL, 0),
(339, 2, 1, '', 0, 1, '2025-10-15 23:46:38', NULL, 0, 0, 0, 'fd', 'normal', NULL, 0, '2025-10-15 23:46:38', NULL, NULL, 0),
(340, 2, 1, '', 0, 1, '2025-10-15 23:46:38', NULL, 0, 0, 0, 'fd', 'normal', NULL, 0, '2025-10-15 23:46:38', NULL, NULL, 0),
(341, 2, 1, '', 0, 1, '2025-10-15 23:46:38', NULL, 0, 0, 0, 'fd', 'normal', NULL, 0, '2025-10-15 23:46:38', NULL, NULL, 0),
(342, 2, 1, '', 0, 1, '2025-10-15 23:46:39', NULL, 0, 0, 0, 'fdg', 'normal', NULL, 0, '2025-10-15 23:46:39', NULL, NULL, 0),
(343, 2, 1, '', 0, 1, '2025-10-15 23:46:39', NULL, 0, 0, 0, 'fd', 'normal', NULL, 0, '2025-10-15 23:46:39', NULL, NULL, 0),
(344, 8, 1, '', 0, 1, '2025-10-15 23:46:58', NULL, 0, 0, 0, 'UwU', 'normal', NULL, 0, '2025-10-15 23:46:48', NULL, NULL, 0),
(345, 1, 8, '', 0, 1, '2025-10-15 23:48:43', NULL, 0, 0, 0, 'me caes re mal', 'normal', NULL, 0, '2025-10-15 23:48:39', NULL, NULL, 0),
(346, 8, 1, '', 0, 1, '2025-10-15 23:48:48', NULL, 0, 0, 0, 'si?', 'normal', NULL, 0, '2025-10-15 23:48:48', NULL, NULL, 0),
(347, 8, 1, '', 0, 1, '2025-10-15 23:48:59', NULL, 0, 0, 0, 'cuanto por el Principito?', 'normal', NULL, 0, '2025-10-15 23:48:59', NULL, NULL, 0),
(348, 8, 1, '', 0, 1, '2025-10-15 23:49:06', NULL, 0, 0, 0, 'osea', 'normal', NULL, 0, '2025-10-15 23:49:06', NULL, NULL, 0),
(349, 8, 1, '', 0, 1, '2025-10-15 23:49:14', NULL, 0, 0, 0, 'que cosa por el principito', 'normal', NULL, 0, '2025-10-15 23:49:14', NULL, NULL, 0),
(350, 1, 8, '', 0, 1, '2025-10-15 23:49:28', NULL, 0, 0, 0, 'no', 'normal', NULL, 0, '2025-10-15 23:49:28', NULL, NULL, 0),
(351, 8, 1, '', 0, 1, '2025-10-15 23:49:34', NULL, 0, 0, 0, 'como que no?', 'normal', NULL, 0, '2025-10-15 23:49:34', NULL, NULL, 0),
(352, 8, 1, '', 0, 1, '2025-10-15 23:49:39', NULL, 0, 0, 0, 'te ofrezco lo que sea', 'normal', NULL, 0, '2025-10-15 23:49:39', NULL, NULL, 0),
(353, 8, 1, '', 0, 1, '2025-10-15 23:49:47', NULL, 0, 0, 0, '💦💦', 'normal', NULL, 0, '2025-10-15 23:49:47', NULL, NULL, 0),
(354, 1, 8, '', 0, 1, '2025-10-15 23:49:48', NULL, 0, 0, 0, 'si buscás pito en el index aparece el principito', 'normal', NULL, 0, '2025-10-15 23:49:48', NULL, NULL, 0),
(355, 1, 8, '', 0, 1, '2025-10-15 23:49:51', NULL, 0, 0, 0, '🤭🤭🤭🤭', 'normal', NULL, 0, '2025-10-15 23:49:51', NULL, NULL, 0),
(356, 8, 1, '', 0, 1, '2025-10-15 23:50:23', NULL, 0, 0, 0, '😋', 'normal', NULL, 0, '2025-10-15 23:50:01', NULL, NULL, 0),
(357, 1, 8, '', 1, 1, '2025-10-15 23:50:45', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nActualmente Angel no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 'normal', NULL, 0, '2025-10-15 23:50:10', NULL, NULL, 0),
(358, 1, 8, '', 1, 1, '2025-10-15 23:50:45', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nActualmente Angel no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 'normal', NULL, 0, '2025-10-15 23:50:18', NULL, NULL, 0),
(359, 8, 2, '', 0, 1, '2025-10-15 23:52:26', NULL, 0, 0, 0, 'no banco esa', 'normal', NULL, 0, '2025-10-15 23:51:45', NULL, NULL, 0),
(360, 8, 2, '', 0, 1, '2025-10-15 23:52:26', NULL, 0, 0, 0, 'tenes una BMX?', 'normal', NULL, 0, '2025-10-15 23:51:50', NULL, NULL, 0),
(361, 8, 1, '', 0, 1, '2025-10-15 23:55:18', NULL, 0, 0, 0, 'holi UwU rica cola', 'normal', NULL, 0, '2025-10-15 23:52:10', NULL, NULL, 0),
(362, 2, 8, '', 0, 1, '2025-10-15 23:52:35', NULL, 0, 0, 0, 'Uhh', 'normal', NULL, 0, '2025-10-15 23:52:31', NULL, NULL, 0),
(363, 2, 8, '', 0, 1, '2025-10-15 23:52:35', NULL, 0, 0, 0, 'si', 'normal', NULL, 0, '2025-10-15 23:52:32', NULL, NULL, 0),
(364, 2, 8, '', 0, 1, '2025-10-15 23:52:35', NULL, 0, 0, 0, 'pero le faltan las ruedas', 'normal', NULL, 0, '2025-10-15 23:52:35', NULL, NULL, 0),
(365, 1, 8, '', 1, 1, '2025-10-15 23:53:49', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nAngel esta ocupado/a ahora mismo. He guardado tu mensaje y te respondera lo antes posible. Gracias!', 'normal', NULL, 0, '2025-10-15 23:52:37', NULL, NULL, 0),
(366, 8, 2, '', 0, 1, '2025-10-15 23:53:42', NULL, 0, 0, 0, 'uh, mal ahi, pero te puedo dar unos zapatos sin suela, no te protegen del suelo pero simulan que tenes zapatos', 'normal', NULL, 0, '2025-10-15 23:53:42', NULL, NULL, 0),
(367, 2, 8, '', 0, 1, '2025-10-15 23:53:54', NULL, 0, 0, 0, 'uff', 'normal', NULL, 0, '2025-10-15 23:53:53', NULL, NULL, 0),
(368, 2, 8, '', 0, 1, '2025-10-15 23:53:54', NULL, 0, 0, 0, 'mesis', 'normal', NULL, 0, '2025-10-15 23:53:54', NULL, NULL, 0),
(369, 1, 8, '', 1, 1, '2025-10-15 23:54:54', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nHola, Angel no esta disponible en este momento. Tu mensaje ha sido recibido y sera respondido en breve. Gracias por tu paciencia!', 'normal', NULL, 0, '2025-10-15 23:54:16', NULL, NULL, 0),
(370, 8, 2, '', 0, 1, '2025-10-15 23:54:26', NULL, 0, 0, 0, 'va, te sirve hacer el tradeo en un callejon oscuro alejado de la ciudad?', 'normal', NULL, 0, '2025-10-15 23:54:26', NULL, NULL, 0),
(371, 1, 8, '', 1, 1, '2025-10-15 23:54:54', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nHola, Angel no esta disponible en este momento. Tu mensaje ha sido recibido y sera respondido en breve. Gracias por tu paciencia!', 'normal', NULL, 0, '2025-10-15 23:54:54', NULL, NULL, 0),
(372, 1, 8, '', 1, 1, '2025-10-15 23:55:18', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nActualmente Angel no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 'normal', NULL, 0, '2025-10-15 23:55:07', NULL, NULL, 0),
(373, 2, 8, '', 0, 1, '2025-10-15 23:55:21', NULL, 0, 0, 0, 'si', 'normal', NULL, 0, '2025-10-15 23:55:10', NULL, NULL, 0),
(374, 2, 8, '', 0, 1, '2025-10-15 23:55:21', NULL, 0, 0, 0, 'llevo globos para celerar?', 'normal', NULL, 0, '2025-10-15 23:55:16', NULL, NULL, 0),
(375, 8, 1, '', 0, 1, '2025-10-15 23:56:04', NULL, 0, 0, 0, 'ando hot', 'normal', NULL, 0, '2025-10-15 23:55:46', NULL, NULL, 0),
(376, 2, 1, '', 0, 1, '2025-10-15 23:57:09', NULL, 0, 0, 0, 'angel', 'normal', NULL, 0, '2025-10-15 23:56:13', NULL, NULL, 0),
(377, 1, 2, '', 1, 1, '2025-10-15 23:59:04', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nHola, Angel no esta disponible en este momento. Tu mensaje ha sido recibido y sera respondido en breve. Gracias por tu paciencia!', 'normal', NULL, 0, '2025-10-15 23:56:26', NULL, NULL, 0),
(378, 8, 2, '', 0, 1, '2025-10-15 23:59:34', NULL, 0, 0, 0, 'banco', 'normal', NULL, 0, '2025-10-15 23:56:48', NULL, NULL, 0),
(379, 8, 2, '', 0, 1, '2025-10-15 23:59:34', NULL, 0, 0, 0, 'sabes que podemos hacer?', 'normal', NULL, 0, '2025-10-15 23:56:53', NULL, NULL, 0),
(380, 8, 2, '', 0, 1, '2025-10-15 23:59:34', NULL, 0, 0, 0, 'fabricar las ruedas', 'normal', NULL, 0, '2025-10-15 23:56:57', NULL, NULL, 0),
(381, 8, 2, '', 0, 1, '2025-10-15 23:59:34', NULL, 0, 0, 0, 'a mano', 'normal', NULL, 0, '2025-10-15 23:56:59', NULL, NULL, 0),
(382, 8, 1, '', 0, 1, '2025-10-15 23:57:56', NULL, 0, 0, 0, 'mucho 🥵', 'normal', NULL, 0, '2025-10-15 23:57:41', NULL, NULL, 0),
(383, 1, 8, '', 0, 1, '2025-10-15 23:58:04', NULL, 0, 0, 0, '🤫', 'normal', NULL, 0, '2025-10-15 23:57:59', NULL, NULL, 0),
(384, 8, 1, '', 0, 1, '2025-10-15 23:58:08', NULL, 0, 0, 0, '😇', 'normal', NULL, 0, '2025-10-15 23:58:08', NULL, NULL, 0),
(385, 8, 1, '', 0, 1, '2025-10-15 23:58:54', NULL, 0, 0, 0, 'oli Uwu', 'normal', NULL, 0, '2025-10-15 23:58:25', NULL, NULL, 0),
(386, 2, 1, '', 0, 1, '2025-10-15 23:58:54', NULL, 0, 0, 0, 'hacemos lo del php', 'normal', NULL, 0, '2025-10-15 23:58:28', NULL, NULL, 0),
(387, 2, 1, '', 0, 1, '2025-10-15 23:58:54', NULL, 0, 0, 0, 'de la busqueda de mensajes?', 'normal', NULL, 0, '2025-10-15 23:58:32', NULL, NULL, 0),
(388, 1, 2, '', 1, 1, '2025-10-15 23:59:04', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nActualmente Angel no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 'normal', NULL, 0, '2025-10-15 23:58:38', NULL, NULL, 0),
(389, 1, 8, '', 1, 1, '2025-10-15 23:58:50', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nAngel esta ocupado/a ahora mismo. He guardado tu mensaje y te respondera lo antes posible. Gracias!', 'normal', NULL, 0, '2025-10-15 23:58:38', NULL, NULL, 0),
(390, 1, 2, '', 0, 1, '2025-10-15 23:59:04', NULL, 0, 0, 0, 'pera', 'normal', NULL, 0, '2025-10-15 23:59:04', NULL, NULL, 0),
(391, 2, 1, '', 0, 1, '2025-10-15 23:59:22', NULL, 0, 0, 0, 'avisa', 'normal', NULL, 0, '2025-10-15 23:59:22', NULL, NULL, 0),
(392, 2, 1, '', 0, 1, '2025-10-15 23:59:24', NULL, 0, 0, 0, 'uwu', 'normal', NULL, 0, '2025-10-15 23:59:23', NULL, NULL, 0),
(393, 2, 8, '', 0, 1, '2025-10-16 00:00:01', NULL, 0, 0, 0, 'uuuh', 'normal', NULL, 0, '2025-10-15 23:59:39', NULL, NULL, 0),
(394, 2, 8, '', 0, 1, '2025-10-16 00:00:01', NULL, 0, 0, 0, 'cielto', 'normal', NULL, 0, '2025-10-15 23:59:40', NULL, NULL, 0),
(395, 2, 1, '', 0, 1, '2025-10-16 00:00:05', NULL, 0, 0, 0, 'angel', 'normal', NULL, 0, '2025-10-16 00:00:05', NULL, NULL, 0),
(396, 2, 1, '', 0, 1, '2025-10-16 00:00:08', NULL, 0, 0, 0, 'sos celosa?', 'normal', NULL, 0, '2025-10-16 00:00:08', NULL, NULL, 0),
(397, 8, 2, '', 0, 1, '2025-10-16 00:00:19', NULL, 0, 0, 0, 'claro, como no se te ocurrio?', 'normal', NULL, 0, '2025-10-16 00:00:11', NULL, NULL, 0),
(398, 2, 1, '', 0, 1, '2025-10-16 00:00:15', NULL, 0, 0, 0, 'o porque no puedo eliminarte de amigos?', 'normal', NULL, 0, '2025-10-16 00:00:15', NULL, NULL, 0),
(399, 2, 8, '', 0, 1, '2025-10-16 00:00:32', NULL, 0, 0, 0, 'es que no pense fuera de la caja', 'normal', NULL, 0, '2025-10-16 00:00:32', NULL, NULL, 0),
(400, 1, 2, '', 0, 1, '2025-10-16 00:01:08', NULL, 0, 0, 0, 'no te dejo', 'normal', NULL, 0, '2025-10-16 00:01:02', NULL, NULL, 0),
(401, 1, 2, '', 0, 1, '2025-10-16 00:01:08', NULL, 0, 0, 0, 'no te deja?', 'normal', NULL, 0, '2025-10-16 00:01:08', NULL, NULL, 0),
(402, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, 'no', 'normal', NULL, 0, '2025-10-16 00:01:11', NULL, NULL, 0),
(403, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, 'xd', 'normal', NULL, 0, '2025-10-16 00:01:11', NULL, NULL, 0),
(404, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, 'en vos no me aparece', 'normal', NULL, 0, '2025-10-16 00:01:20', NULL, NULL, 0),
(405, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, 'ef', 'normal', NULL, 0, '2025-10-16 00:01:22', NULL, NULL, 0),
(406, 1, 2, '', 1, 1, '2025-10-16 00:03:00', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nHola, Angel no esta disponible en este momento. Tu mensaje ha sido recibido y sera respondido en breve. Gracias por tu paciencia!', 'normal', NULL, 0, '2025-10-16 00:01:23', NULL, NULL, 0),
(407, 8, 2, '', 0, 1, '2025-10-16 21:42:25', NULL, 0, 0, 0, 'muy mal eso', 'normal', NULL, 0, '2025-10-16 00:02:06', NULL, NULL, 0),
(408, 8, 2, '', 0, 1, '2025-10-16 21:42:25', NULL, 0, 0, 0, 'nonono', 'normal', NULL, 0, '2025-10-16 00:02:07', NULL, NULL, 0),
(409, 8, 2, '', 0, 1, '2025-10-16 21:42:25', NULL, 0, 0, 0, 'pensa que sos la caja', 'normal', NULL, 0, '2025-10-16 00:02:12', NULL, NULL, 0),
(410, 8, 1, '', 0, 1, '2025-10-16 00:03:02', NULL, 0, 0, 0, 'no', 'normal', NULL, 0, '2025-10-16 00:02:25', NULL, NULL, 0),
(411, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, '????', 'normal', NULL, 0, '2025-10-16 00:02:53', NULL, NULL, 0),
(412, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, 'sx', 'normal', NULL, 0, '2025-10-16 00:02:54', NULL, NULL, 0),
(413, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, 'x', 'normal', NULL, 0, '2025-10-16 00:02:54', NULL, NULL, 0),
(414, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, 's', 'normal', NULL, 0, '2025-10-16 00:02:54', NULL, NULL, 0),
(415, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, 's', 'normal', NULL, 0, '2025-10-16 00:02:54', NULL, NULL, 0),
(416, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, 's', 'normal', NULL, 0, '2025-10-16 00:02:54', NULL, NULL, 0),
(417, 2, 1, '', 0, 1, '2025-10-16 00:02:56', NULL, 0, 0, 0, 'x', 'normal', NULL, 0, '2025-10-16 00:02:54', NULL, NULL, 0),
(418, 1, 2, '', 0, 1, '2025-10-16 00:03:00', NULL, 0, 0, 0, 'te voy a reportar', 'normal', NULL, 0, '2025-10-16 00:03:00', NULL, NULL, 0),
(419, 1, 8, '', 0, 1, '2025-10-16 00:03:11', NULL, 0, 0, 0, 'que buena foto de perfil', 'normal', NULL, 0, '2025-10-16 00:03:11', NULL, NULL, 0),
(420, 8, 1, '', 0, 1, '2025-10-16 00:03:18', NULL, 0, 0, 0, 'muy buena', 'normal', NULL, 0, '2025-10-16 00:03:18', NULL, NULL, 0),
(421, 1, 8, '', 0, 1, '2025-10-16 00:03:27', NULL, 0, 0, 0, 'si', 'normal', NULL, 0, '2025-10-16 00:03:27', NULL, NULL, 0),
(422, 8, 1, '', 0, 1, '2025-10-16 00:03:28', NULL, 0, 0, 0, 'obvio', 'normal', NULL, 0, '2025-10-16 00:03:28', NULL, NULL, 0),
(423, 8, 1, '', 0, 1, '2025-10-16 00:03:31', NULL, 0, 0, 0, 'absolute cinema', 'normal', NULL, 0, '2025-10-16 00:03:31', NULL, NULL, 0),
(424, 1, 8, '', 0, 1, '2025-10-16 00:03:33', NULL, 0, 0, 0, 'pasame una por discord para ponerme', 'normal', NULL, 0, '2025-10-16 00:03:33', NULL, NULL, 0),
(425, 8, 1, '', 0, 1, '2025-10-16 00:03:42', NULL, 0, 0, 0, 'como?', 'normal', NULL, 0, '2025-10-16 00:03:42', NULL, NULL, 0),
(426, 1, 8, '', 0, 1, '2025-10-16 00:03:42', NULL, 0, 0, 0, 'una que sea de absolute cinema pero de alguna mona china', 'normal', NULL, 0, '2025-10-16 00:03:42', NULL, NULL, 0),
(427, 8, 1, '', 0, 1, '2025-10-16 00:03:47', NULL, 0, 0, 0, 'uh', 'normal', NULL, 0, '2025-10-16 00:03:47', NULL, NULL, 0),
(428, 1, 8, '', 0, 1, '2025-10-16 00:03:51', NULL, 0, 0, 0, 'asi abriendo los brazos', 'normal', NULL, 0, '2025-10-16 00:03:51', NULL, NULL, 0),
(429, 8, 1, '', 0, 1, '2025-10-16 00:03:53', NULL, 0, 0, 0, 'entonces mi pinterest es cine', 'normal', NULL, 0, '2025-10-16 00:03:53', NULL, NULL, 0),
(430, 1, 8, '', 0, 1, '2025-10-16 00:03:54', NULL, 0, 0, 0, 'pero de alguna mona china', 'normal', NULL, 0, '2025-10-16 00:03:54', NULL, NULL, 0),
(431, 2, 1, '', 0, 1, '2025-10-16 00:05:50', NULL, 0, 0, 0, 'a', 'normal', NULL, 0, '2025-10-16 00:04:18', NULL, NULL, 0),
(432, 2, 1, '', 0, 1, '2025-10-16 00:05:50', NULL, 0, 0, 0, 'pero es para llamarte la atencion', 'normal', NULL, 0, '2025-10-16 00:04:25', NULL, NULL, 0),
(433, 2, 1, '', 0, 1, '2025-10-16 00:05:50', NULL, 0, 0, 0, 'lo hacemos?', 'normal', NULL, 0, '2025-10-16 00:05:36', NULL, NULL, 0),
(434, 2, 1, '', 0, 1, '2025-10-16 00:05:50', NULL, 0, 0, 0, 'o andas en algo?', 'normal', NULL, 0, '2025-10-16 00:05:41', NULL, NULL, 0),
(435, 1, 2, '', 0, 1, '2025-10-16 00:05:57', NULL, 0, 0, 0, 'ando viendo', 'normal', NULL, 0, '2025-10-16 00:05:57', NULL, NULL, 0),
(436, 1, 2, '', 0, 1, '2025-10-16 00:05:58', NULL, 0, 0, 0, 'algo', 'normal', NULL, 0, '2025-10-16 00:05:58', NULL, NULL, 0),
(437, 1, 2, '', 0, 1, '2025-10-16 00:05:59', NULL, 0, 0, 0, 'o sea', 'normal', NULL, 0, '2025-10-16 00:05:59', NULL, NULL, 0),
(438, 1, 2, '', 0, 1, '2025-10-16 00:06:02', NULL, 0, 0, 0, 'aparecieron mas errores que ayer', 'normal', NULL, 0, '2025-10-16 00:06:02', NULL, NULL, 0),
(439, 2, 1, '', 0, 1, '2025-10-16 00:06:37', NULL, 0, 0, 0, 'en que parte', 'normal', NULL, 0, '2025-10-16 00:06:37', NULL, NULL, 0),
(440, 2, 1, '', 0, 1, '2025-10-16 00:06:38', NULL, 0, 0, 0, '?', 'normal', NULL, 0, '2025-10-16 00:06:38', NULL, NULL, 0),
(441, 1, 2, '', 0, 1, '2025-10-16 00:07:40', NULL, 0, 0, 0, 'mensajeria', 'normal', NULL, 0, '2025-10-16 00:07:40', NULL, NULL, 0),
(442, 2, 1, '', 0, 1, '2025-10-16 00:12:56', NULL, 0, 0, 0, 'pero que parte tiene error', 'normal', NULL, 0, '2025-10-16 00:08:56', NULL, NULL, 0),
(443, 2, 1, '', 0, 1, '2025-10-16 00:12:56', NULL, 0, 0, 0, ';3', 'normal', NULL, 0, '2025-10-16 00:09:02', NULL, NULL, 0),
(444, 1, 2, '', 1, 1, '2025-10-16 21:15:17', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nActualmente Angel no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 'normal', NULL, 0, '2025-10-16 00:09:06', NULL, NULL, 0),
(445, 2, 1, '', 0, 1, '2025-10-16 00:12:56', NULL, 0, 0, 0, '<3', 'normal', NULL, 0, '2025-10-16 00:10:32', NULL, NULL, 0),
(446, 1, 2, '', 1, 1, '2025-10-16 21:15:17', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nAngel esta ocupado/a ahora mismo. He guardado tu mensaje y te respondera lo antes posible. Gracias!', 'normal', NULL, 0, '2025-10-16 00:12:30', NULL, NULL, 0),
(447, 1, 2, '', 1, 1, '2025-10-16 21:15:17', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nHola, Angel no esta disponible en este momento. Tu mensaje ha sido recibido y sera respondido en breve. Gracias por tu paciencia!', 'normal', NULL, 0, '2025-10-16 00:12:32', NULL, NULL, 0),
(448, 7, 2, '', 0, 1, '2025-10-16 21:44:14', NULL, 0, 0, 0, 'no', 'normal', NULL, 0, '2025-10-16 21:12:38', NULL, NULL, 0),
(449, 7, 2, '', 0, 1, '2025-10-16 21:44:14', NULL, 0, 0, 0, 'a vo te guta el penesito', 'normal', NULL, 0, '2025-10-16 21:12:45', NULL, NULL, 0),
(450, 7, 1, '', 0, 1, '2025-10-16 21:13:33', NULL, 0, 0, 0, 'tu mami', 'normal', NULL, 0, '2025-10-16 21:12:51', NULL, NULL, 0),
(451, 7, 1, '', 0, 1, '2025-10-16 21:13:33', NULL, 0, 0, 0, 'awjofnjkawfj4', 'normal', NULL, 0, '2025-10-16 21:12:52', NULL, NULL, 0),
(452, 1, 7, '', 1, 1, '2025-11-06 21:04:25', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nActualmente Angel no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 'normal', NULL, 0, '2025-10-16 21:13:03', NULL, NULL, 0),
(453, 2, 1, '', 0, 1, '2025-10-16 22:41:21', NULL, 0, 0, 0, 'dejame en paz', 'normal', NULL, 0, '2025-10-16 21:15:21', NULL, NULL, 0),
(454, 2, 7, '', 0, 1, '2025-11-06 21:04:19', NULL, 0, 0, 0, 'soy espejo y me reflejo', 'normal', NULL, 0, '2025-10-16 21:44:20', NULL, NULL, 0),
(455, 2, 1, '', 0, 1, '2025-10-16 22:41:21', NULL, 0, 0, 0, 'invierno del 92', 'normal', NULL, 0, '2025-10-16 22:40:42', NULL, NULL, 0),
(456, 2, 1, '', 0, 1, '2025-10-16 22:41:21', NULL, 0, 0, 0, 'es la unica que me se', 'normal', NULL, 0, '2025-10-16 22:40:57', NULL, NULL, 0),
(457, 2, 1, '', 0, 1, '2025-10-16 22:41:21', NULL, 0, 0, 0, 'del cuarteto', 'normal', NULL, 0, '2025-10-16 22:40:59', NULL, NULL, 0),
(458, 2, 1, '', 0, 1, '2025-10-16 22:41:21', NULL, 0, 0, 0, 'porque no lo escucho mucho', 'normal', NULL, 0, '2025-10-16 22:41:04', NULL, NULL, 0),
(459, 2, 1, '', 0, 1, '2025-10-16 22:41:21', NULL, 0, 0, 0, 'y de persona, jugue un poco del 3 de psp', 'normal', NULL, 0, '2025-10-16 22:41:14', NULL, NULL, 0),
(460, 1, 2, '', 0, 1, '2025-10-16 22:44:46', NULL, 0, 0, 0, 'por que me decís que te deje en paz', 'normal', NULL, 0, '2025-10-16 22:44:46', NULL, NULL, 0),
(461, 1, 2, '', 0, 1, '2025-10-16 22:44:51', NULL, 0, 0, 0, 'yo te trato re bien', 'normal', NULL, 0, '2025-10-16 22:44:51', NULL, NULL, 0),
(462, 1, 2, '', 0, 1, '2025-10-16 22:45:00', NULL, 0, 0, 0, '🥲🥲🥲🥲🥲', 'normal', NULL, 0, '2025-10-16 22:45:00', NULL, NULL, 0),
(463, 2, 1, '', 0, 1, '2025-10-16 22:50:01', NULL, 0, 0, 0, 'mentirosa', 'normal', NULL, 0, '2025-10-16 22:47:25', NULL, NULL, 0),
(464, 2, 1, '', 0, 1, '2025-10-16 22:50:01', NULL, 0, 0, 0, 'no me importa que de amor te mueras', 'normal', NULL, 0, '2025-10-16 22:47:36', NULL, NULL, 0),
(465, 2, 1, '', 0, 1, '2025-10-16 22:50:01', NULL, 0, 0, 0, 'ooooooooooooooo', 'normal', NULL, 0, '2025-10-16 22:47:39', NULL, NULL, 0),
(466, 2, 1, '', 0, 1, '2025-10-16 22:50:01', NULL, 0, 0, 0, 'o oooo', 'normal', NULL, 0, '2025-10-16 22:47:41', NULL, NULL, 0),
(467, 2, 1, '', 0, 1, '2025-10-16 22:50:01', NULL, 0, 0, 0, 'o o o o o o', 'normal', NULL, 0, '2025-10-16 22:47:47', NULL, NULL, 0),
(468, 2, 1, '', 0, 1, '2025-10-16 22:50:01', NULL, 0, 0, 0, 'men', 'normal', NULL, 0, '2025-10-16 22:47:49', NULL, NULL, 0),
(469, 2, 1, '', 0, 1, '2025-10-16 22:50:01', NULL, 0, 0, 0, 'ti', 'normal', NULL, 0, '2025-10-16 22:47:50', NULL, NULL, 0),
(470, 2, 1, '', 0, 1, '2025-10-16 22:50:01', NULL, 0, 0, 0, 'rosa', 'normal', NULL, 0, '2025-10-16 22:47:51', NULL, NULL, 0),
(471, 2, 1, '', 0, 1, '2025-10-16 22:50:01', NULL, 0, 0, 0, 'no me importa que de amor te mueras', 'normal', NULL, 0, '2025-10-16 22:47:56', NULL, NULL, 0),
(472, 2, 8, '', 0, 1, '2025-10-21 22:56:19', NULL, 0, 0, 0, 'tenes', 'normal', NULL, 0, '2025-10-16 22:51:36', NULL, NULL, 0),
(473, 2, 8, '', 0, 1, '2025-10-21 22:56:19', NULL, 0, 0, 0, 'razon', 'normal', NULL, 0, '2025-10-16 22:51:38', NULL, NULL, 0);
INSERT INTO `mensajes` (`id`, `sender_id`, `receiver_id`, `message`, `is_perseo_auto`, `is_read`, `read_at`, `reply_to_message_id`, `producto_id`, `remitente_id`, `destinatario_id`, `mensaje`, `tipo_mensaje`, `producto_relacionado_id`, `leido`, `created_at`, `edited_at`, `deleted_for`, `is_deleted`) VALUES
(474, 2, 8, '', 0, 1, '2025-10-21 22:56:19', NULL, 0, 0, 0, 'yo soy la caja', 'normal', NULL, 0, '2025-10-16 22:51:43', NULL, NULL, 0),
(475, 2, 8, '', 0, 1, '2025-10-21 22:56:19', NULL, 0, 0, 0, 'vagabundos se pelean por mi para dormir', 'normal', NULL, 0, '2025-10-16 22:51:52', NULL, NULL, 0),
(476, 1, 2, '', 0, 1, '2025-10-16 22:52:02', NULL, 0, 0, 0, 'por que me tratas asi', 'normal', NULL, 0, '2025-10-16 22:52:02', NULL, NULL, 0),
(477, 1, 2, '', 0, 1, '2025-10-16 22:52:08', NULL, 0, 0, 0, 'que te hice', 'normal', NULL, 0, '2025-10-16 22:52:08', NULL, NULL, 0),
(478, 2, 1, '', 0, 1, '2025-10-16 22:52:16', NULL, 0, 0, 0, 'no juguaste LoL', 'normal', NULL, 0, '2025-10-16 22:52:16', NULL, NULL, 0),
(479, 2, 1, '', 0, 1, '2025-10-16 22:52:20', NULL, 0, 0, 0, 'y no me pasaste el repo', 'normal', NULL, 0, '2025-10-16 22:52:20', NULL, NULL, 0),
(480, 2, 1, '', 0, 1, '2025-10-16 22:52:36', NULL, 0, 0, 0, 'angel', 'normal', NULL, 0, '2025-10-16 22:52:36', NULL, NULL, 0),
(481, 2, 1, '', 0, 1, '2025-10-16 22:52:43', NULL, 0, 0, 0, 'lo del botoncito de la lupa', 'normal', NULL, 0, '2025-10-16 22:52:43', NULL, NULL, 0),
(482, 9, 10, '', 0, 1, '2025-10-16 22:57:06', NULL, 0, 0, 0, 'holaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'normal', NULL, 0, '2025-10-16 22:57:06', NULL, NULL, 0),
(483, 10, 9, '', 0, 1, '2025-10-16 22:57:06', NULL, 0, 0, 0, 'Hola', 'normal', NULL, 0, '2025-10-16 22:57:06', NULL, NULL, 0),
(484, 9, 10, '', 0, 1, '2025-10-16 22:57:12', NULL, 0, 0, 0, 'mi primer amigo', 'normal', NULL, 0, '2025-10-16 22:57:11', NULL, NULL, 0),
(485, 9, 10, '', 0, 1, '2025-10-16 22:57:12', NULL, 0, 0, 0, 'gracias', 'normal', NULL, 0, '2025-10-16 22:57:12', NULL, NULL, 0),
(486, 10, 9, '', 0, 1, '2025-10-16 22:57:16', NULL, 0, 0, 0, 'Que dice?', 'normal', NULL, 0, '2025-10-16 22:57:16', NULL, NULL, 0),
(487, 10, 9, '', 0, 1, '2025-10-16 22:57:23', NULL, 0, 0, 0, 'Voy a hacer un trueque', 'normal', NULL, 0, '2025-10-16 22:57:23', NULL, NULL, 0),
(488, 10, 9, '', 0, 1, '2025-10-16 22:57:31', NULL, 0, 0, 0, '😏😏', 'normal', NULL, 0, '2025-10-16 22:57:31', NULL, NULL, 0),
(489, 9, 10, '', 0, 1, '2025-10-16 22:59:17', NULL, 0, 0, 0, '🍆🍆🍆🍆🍆🍆🍑👌👈😉😉🤭🤫', 'normal', NULL, 0, '2025-10-16 22:58:02', NULL, NULL, 0),
(490, 10, 9, '', 0, 1, '2025-10-16 23:00:59', NULL, 0, 0, 0, 'OK', 'normal', NULL, 0, '2025-10-16 22:59:19', NULL, NULL, 0),
(491, 1, 2, '', 0, 1, '2025-10-16 22:59:44', NULL, 0, 0, 0, 'pera que estoy haciendo otra wwa', 'normal', NULL, 0, '2025-10-16 22:59:44', NULL, NULL, 0),
(492, 1, 2, '', 0, 1, '2025-10-16 22:59:47', NULL, 0, 0, 0, 'wea', 'normal', NULL, 0, '2025-10-16 22:59:47', NULL, NULL, 0),
(493, 2, 1, '', 0, 1, '2025-10-16 22:59:55', NULL, 0, 0, 0, 'oka', 'normal', NULL, 0, '2025-10-16 22:59:54', NULL, NULL, 0),
(494, 2, 1, '', 0, 1, '2025-10-16 22:59:57', NULL, 0, 0, 0, 'por cielto', 'normal', NULL, 0, '2025-10-16 22:59:56', NULL, NULL, 0),
(495, 2, 1, '', 0, 1, '2025-10-16 23:00:02', NULL, 0, 0, 0, 'a no nada', 'normal', NULL, 0, '2025-10-16 23:00:01', NULL, NULL, 0),
(496, 10, 3, '', 0, 1, '2025-10-16 23:00:02', NULL, 0, 0, 0, 'Hola', 'normal', NULL, 0, '2025-10-16 23:00:02', NULL, NULL, 0),
(497, 2, 1, '', 0, 1, '2025-10-16 23:00:06', NULL, 0, 0, 0, 'iba a decirte algo de responsividad', 'normal', NULL, 0, '2025-10-16 23:00:05', NULL, NULL, 0),
(498, 2, 1, '', 0, 1, '2025-10-16 23:00:11', NULL, 0, 0, 0, 'pero esto no lo va a notar nadie', 'normal', NULL, 0, '2025-10-16 23:00:11', NULL, NULL, 0),
(499, 2, 1, '', 0, 1, '2025-10-16 23:00:18', NULL, 0, 0, 0, 'ya que pasa seguramente solo en mi pantalla', 'normal', NULL, 0, '2025-10-16 23:00:18', NULL, NULL, 0),
(500, 3, 10, '', 0, 1, '2025-10-16 23:00:21', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-16 23:00:21', NULL, NULL, 0),
(501, 10, 3, '', 0, 1, '2025-10-16 23:00:49', NULL, 0, 0, 0, 'Todo bien?', 'normal', NULL, 0, '2025-10-16 23:00:30', NULL, NULL, 0),
(502, 9, 10, '', 1, 1, '2025-10-16 23:00:44', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nguillesito esta ocupado/a ahora mismo. He guardado tu mensaje y te respondera lo antes posible. Gracias!', 'normal', NULL, 0, '2025-10-16 23:00:42', NULL, NULL, 0),
(503, 3, 10, '', 1, 1, '2025-10-16 23:00:48', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nHola, Milagros no esta disponible en este momento. Tu mensaje ha sido recibido y sera respondido en breve. Gracias por tu paciencia!', 'normal', NULL, 0, '2025-10-16 23:00:44', NULL, NULL, 0),
(504, 10, 3, '', 0, 1, '2025-10-16 23:00:55', NULL, 0, 0, 0, 'Uh bno', 'normal', NULL, 0, '2025-10-16 23:00:55', NULL, NULL, 0),
(505, 10, 3, '', 0, 1, '2025-10-16 23:00:57', NULL, 0, 0, 0, 'Esperare', 'normal', NULL, 0, '2025-10-16 23:00:57', NULL, NULL, 0),
(506, 3, 10, '', 0, 1, '2025-10-16 23:00:59', NULL, 0, 0, 0, 'bien y vos', 'normal', NULL, 0, '2025-10-16 23:00:59', NULL, NULL, 0),
(507, 9, 10, '', 0, 1, '2025-10-16 23:02:26', NULL, 0, 0, 0, 'xd', 'normal', NULL, 0, '2025-10-16 23:01:05', NULL, NULL, 0),
(508, 10, 3, '', 0, 1, '2025-10-16 23:01:06', NULL, 0, 0, 0, 'Ah que estas si?', 'normal', NULL, 0, '2025-10-16 23:01:06', NULL, NULL, 0),
(509, 10, 3, '', 0, 1, '2025-10-16 23:01:14', NULL, 0, 0, 0, 'Tenes el monopoly aun?', 'normal', NULL, 0, '2025-10-16 23:01:14', NULL, NULL, 0),
(510, 3, 10, '', 0, 1, '2025-10-16 23:01:16', NULL, 0, 0, 0, 'recien volvi', 'normal', NULL, 0, '2025-10-16 23:01:15', NULL, NULL, 0),
(511, 3, 10, '', 0, 1, '2025-10-16 23:01:19', NULL, 0, 0, 0, 'sipi', 'normal', NULL, 0, '2025-10-16 23:01:19', NULL, NULL, 0),
(512, 3, 10, '', 0, 1, '2025-10-16 23:01:26', NULL, 0, 0, 0, 'lo guarde solo para vos', 'normal', NULL, 0, '2025-10-16 23:01:25', NULL, NULL, 0),
(513, 10, 3, '', 0, 1, '2025-10-16 23:01:26', NULL, 0, 0, 0, 'Te tengo otra oferta', 'normal', NULL, 0, '2025-10-16 23:01:26', NULL, NULL, 0),
(514, 3, 10, '', 0, 1, '2025-10-16 23:01:36', NULL, 0, 0, 0, 'a ver contame', 'normal', NULL, 0, '2025-10-16 23:01:36', NULL, NULL, 0),
(515, 10, 3, '', 0, 1, '2025-10-16 23:01:45', NULL, 0, 0, 0, 'Me das el monopoly', 'normal', NULL, 0, '2025-10-16 23:01:45', NULL, NULL, 0),
(516, 10, 3, '', 0, 1, '2025-10-16 23:01:54', NULL, 0, 0, 0, 'Y te doy el taladro', 'normal', NULL, 0, '2025-10-16 23:01:54', NULL, NULL, 0),
(517, 3, 10, '', 0, 1, '2025-10-16 23:02:02', NULL, 0, 0, 0, 'ya tengo taladro', 'normal', NULL, 0, '2025-10-16 23:02:02', NULL, NULL, 0),
(518, 10, 3, '', 0, 1, '2025-10-16 23:02:12', NULL, 0, 0, 0, 'Pero lo sabes usar?', 'normal', NULL, 0, '2025-10-16 23:02:11', NULL, NULL, 0),
(519, 10, 9, '', 0, 1, '2025-10-16 23:04:50', NULL, 0, 0, 0, 'Bno ta bien', 'normal', NULL, 0, '2025-10-16 23:02:32', NULL, NULL, 0),
(520, 3, 10, '', 0, 1, '2025-10-16 23:03:44', NULL, 0, 0, 0, 'si', 'normal', NULL, 0, '2025-10-16 23:02:51', NULL, NULL, 0),
(521, 3, 10, '', 0, 1, '2025-10-16 23:03:44', NULL, 0, 0, 0, '😁', 'normal', NULL, 0, '2025-10-16 23:03:18', NULL, NULL, 0),
(522, 10, 3, '', 0, 1, '2025-10-16 23:03:53', NULL, 0, 0, 0, 'Y no queres a alguien para eso?', 'normal', NULL, 0, '2025-10-16 23:03:53', NULL, NULL, 0),
(523, 3, 10, '', 0, 1, '2025-10-16 23:04:05', NULL, 0, 0, 0, 'para taladrar?', 'normal', NULL, 0, '2025-10-16 23:04:05', NULL, NULL, 0),
(524, 10, 3, '', 0, 1, '2025-10-16 23:04:10', NULL, 0, 0, 0, 'te', 'normal', NULL, 0, '2025-10-16 23:04:10', NULL, NULL, 0),
(525, 3, 10, '', 0, 1, '2025-10-16 23:04:16', NULL, 0, 0, 0, 'si tu te queres morir asi...', 'normal', NULL, 0, '2025-10-16 23:04:15', NULL, NULL, 0),
(526, 10, 3, '', 0, 1, '2025-10-16 23:04:29', NULL, 0, 0, 0, 'Como?', 'normal', NULL, 0, '2025-10-16 23:04:29', NULL, NULL, 0),
(527, 10, 3, '', 0, 1, '2025-10-16 23:04:56', NULL, 0, 0, 0, 'Para taladrar', 'normal', NULL, 0, '2025-10-16 23:04:56', NULL, NULL, 0),
(528, 10, 3, '', 0, 1, '2025-10-16 23:04:56', NULL, 0, 0, 0, 'e', 'normal', NULL, 0, '2025-10-16 23:04:56', NULL, NULL, 1),
(529, 10, 3, '', 0, 1, '2025-10-16 23:05:10', 524, 0, 0, 0, 'te', 'normal', NULL, 0, '2025-10-16 23:05:10', NULL, NULL, 0),
(530, 3, 10, '', 0, 1, '2025-10-16 23:07:26', NULL, 0, 0, 0, 'cusndo nos vemos?', 'normal', NULL, 0, '2025-10-16 23:07:06', NULL, NULL, 0),
(531, 10, 3, '', 0, 1, '2025-10-16 23:08:22', NULL, 0, 0, 0, 'Aceptas entonces?', 'normal', NULL, 0, '2025-10-16 23:07:38', NULL, NULL, 0),
(532, 1, 2, '', 0, 1, '2025-10-16 23:13:53', NULL, 0, 0, 0, 'ok', 'normal', NULL, 0, '2025-10-16 23:08:04', NULL, NULL, 0),
(533, 1, 2, '', 0, 1, '2025-10-16 23:13:53', NULL, 0, 0, 0, 'ok', 'normal', NULL, 0, '2025-10-16 23:08:04', NULL, NULL, 0),
(534, 1, 2, '', 0, 1, '2025-10-16 23:13:53', NULL, 0, 0, 0, 'k', 'normal', NULL, 0, '2025-10-16 23:08:05', NULL, NULL, 0),
(535, 1, 2, '', 0, 1, '2025-10-16 23:13:53', NULL, 0, 0, 0, 'k', 'normal', NULL, 0, '2025-10-16 23:08:06', NULL, NULL, 0),
(536, 1, 2, '', 0, 1, '2025-10-16 23:13:53', NULL, 0, 0, 0, 'k', 'normal', NULL, 0, '2025-10-16 23:08:06', NULL, NULL, 0),
(537, 1, 2, '', 0, 1, '2025-10-16 23:13:53', NULL, 0, 0, 0, 'kkkkk', 'normal', NULL, 0, '2025-10-16 23:08:07', NULL, NULL, 0),
(538, 3, 10, '', 0, 1, '2025-10-16 23:09:02', NULL, 0, 0, 0, 'pues si', 'normal', NULL, 0, '2025-10-16 23:09:02', NULL, NULL, 0),
(539, 3, 10, '', 0, 1, '2025-10-16 23:09:10', NULL, 0, 0, 0, 'cuando nos vemos?', 'normal', NULL, 0, '2025-10-16 23:09:10', NULL, NULL, 0),
(540, 10, 3, '', 0, 1, '2025-10-16 23:09:10', NULL, 0, 0, 0, 'Bueno esta bien', 'normal', NULL, 0, '2025-10-16 23:09:10', NULL, NULL, 0),
(541, 10, 3, '', 0, 1, '2025-10-16 23:15:15', NULL, 0, 0, 0, 'Mañana de noche', 'normal', NULL, 0, '2025-10-16 23:09:27', NULL, NULL, 0),
(542, 9, 10, '', 0, 1, '2025-10-16 23:09:50', NULL, 0, 0, 0, 'deja de hablarte con esa', 'normal', NULL, 0, '2025-10-16 23:09:49', NULL, NULL, 0),
(543, 9, 10, '', 0, 1, '2025-10-16 23:09:53', NULL, 0, 0, 0, 'que sus petes no son buenos', 'normal', NULL, 0, '2025-10-16 23:09:53', NULL, NULL, 0),
(544, 10, 9, '', 0, 1, '2025-10-16 23:11:33', 543, 0, 0, 0, 'Como sabes?', 'normal', NULL, 0, '2025-10-16 23:10:37', NULL, NULL, 0),
(545, 1, 2, '', 0, 1, '2025-10-16 23:13:53', NULL, 0, 0, 0, 'yo hice pull y la lupa no anda', 'normal', NULL, 0, '2025-10-16 23:13:42', NULL, NULL, 0),
(546, 2, 1, '', 0, 1, '2025-10-16 23:14:01', NULL, 0, 0, 0, 'es que no tiene weas piuestas', 'normal', NULL, 0, '2025-10-16 23:14:00', NULL, NULL, 0),
(547, 2, 1, '', 0, 1, '2025-10-16 23:14:02', NULL, 0, 0, 0, 'por eso te dije', 'normal', NULL, 0, '2025-10-16 23:14:02', NULL, NULL, 0),
(548, 2, 1, '', 0, 1, '2025-10-16 23:14:08', NULL, 0, 0, 0, 'como vos le sabes mas al js', 'normal', NULL, 0, '2025-10-16 23:14:08', NULL, NULL, 0),
(549, 2, 1, '', 0, 1, '2025-10-16 23:14:14', NULL, 0, 0, 0, 'que vos le metieras el nombre de las variables', 'normal', NULL, 0, '2025-10-16 23:14:14', NULL, NULL, 0),
(550, 2, 1, '', 0, 1, '2025-10-16 23:14:17', NULL, 0, 0, 0, 'ademas', 'normal', NULL, 0, '2025-10-16 23:14:17', NULL, NULL, 0),
(551, 2, 1, '', 0, 1, '2025-10-16 23:14:38', NULL, 0, 0, 0, 'desde que converti busquesita-chat.php en php me dio errores que no entendi y me dio miedo', 'normal', NULL, 0, '2025-10-16 23:14:37', NULL, NULL, 0),
(552, 1, 2, '', 0, 1, '2025-10-16 23:14:40', NULL, 0, 0, 0, 'ahh', 'normal', NULL, 0, '2025-10-16 23:14:40', NULL, NULL, 0),
(553, 1, 2, '', 0, 1, '2025-10-16 23:14:41', NULL, 0, 0, 0, 'o sea', 'normal', NULL, 0, '2025-10-16 23:14:41', NULL, NULL, 0),
(554, 2, 1, '', 0, 1, '2025-10-16 23:14:44', NULL, 0, 0, 0, 'esta hecho', 'normal', NULL, 0, '2025-10-16 23:14:44', NULL, NULL, 0),
(555, 2, 1, '', 0, 1, '2025-10-16 23:14:47', NULL, 0, 0, 0, 'pero no puesto', 'normal', NULL, 0, '2025-10-16 23:14:47', NULL, NULL, 0),
(556, 1, 2, '', 0, 1, '2025-10-16 23:15:17', NULL, 0, 0, 0, 'queres que lo configure o que lo implemente?', 'normal', NULL, 0, '2025-10-16 23:15:17', NULL, NULL, 0),
(557, 3, 10, '', 0, 1, '2025-10-16 23:21:59', NULL, 0, 0, 0, 'de noche pillo jajja', 'normal', NULL, 0, '2025-10-16 23:15:28', NULL, NULL, 0),
(558, 1, 2, '', 0, 1, '2025-10-16 23:15:30', NULL, 0, 0, 0, 'la logica o que si apreto el boto aparezca lo que haces vos', 'normal', NULL, 0, '2025-10-16 23:15:30', NULL, NULL, 0),
(559, 1, 2, '', 0, 1, '2025-10-16 23:15:33', NULL, 0, 0, 0, '?', 'normal', NULL, 0, '2025-10-16 23:15:32', NULL, NULL, 1),
(560, 3, 10, '', 0, 1, '2025-10-16 23:21:59', NULL, 0, 0, 0, 'quien a quien?', 'normal', NULL, 0, '2025-10-16 23:15:39', NULL, NULL, 0),
(561, 10, 3, '', 0, 1, '2025-10-16 23:22:36', 557, 0, 0, 0, 'Claro', 'normal', NULL, 0, '2025-10-16 23:22:07', NULL, NULL, 0),
(562, 10, 3, '', 0, 1, '2025-10-16 23:22:36', NULL, 0, 0, 0, 'Es mejor', 'normal', NULL, 0, '2025-10-16 23:22:09', NULL, NULL, 0),
(563, 10, 3, '', 0, 1, '2025-10-16 23:22:36', NULL, 0, 0, 0, 'Mas comodo', 'normal', NULL, 0, '2025-10-16 23:22:11', NULL, NULL, 0),
(564, 10, 3, '', 0, 1, '2025-10-16 23:22:36', 560, 0, 0, 0, 'Y ns, yo soy hetero', 'normal', NULL, 0, '2025-10-16 23:22:26', NULL, NULL, 0),
(565, 3, 10, '', 0, 1, '2025-10-16 23:22:52', NULL, 0, 0, 0, 'TAN SEGURO ESTAS DE ESO?', 'normal', NULL, 0, '2025-10-16 23:22:52', NULL, NULL, 0),
(566, 3, 10, '', 0, 1, '2025-10-16 23:23:05', NULL, 0, 0, 0, 'mas como porque? porque no hay luz?', 'normal', NULL, 0, '2025-10-16 23:23:05', NULL, NULL, 0),
(567, 3, 10, '', 0, 1, '2025-10-16 23:23:14', NULL, 0, 0, 0, 'mira si aparece la llorona', 'normal', NULL, 0, '2025-10-16 23:23:14', NULL, NULL, 0),
(568, 10, 3, '', 0, 1, '2025-10-16 23:25:22', 567, 0, 0, 0, 'Llorona va a quedar otra cosa', 'normal', NULL, 0, '2025-10-16 23:24:42', NULL, NULL, 0),
(569, 10, 3, '', 0, 1, '2025-10-16 23:25:22', 566, 0, 0, 0, 'Porque estoy libre al 100x100', 'normal', NULL, 0, '2025-10-16 23:25:00', NULL, NULL, 0),
(570, 3, 10, '', 0, 1, '2025-10-16 23:26:24', NULL, 0, 0, 0, 'como asi? de dia no?', 'normal', NULL, 0, '2025-10-16 23:26:24', NULL, NULL, 0),
(571, 3, 10, '', 0, 1, '2025-10-16 23:26:41', NULL, 0, 0, 0, 'contame que va a quedar llorona..', 'normal', NULL, 0, '2025-10-16 23:26:41', NULL, NULL, 0),
(572, 10, 3, '', 0, 1, '2025-10-16 23:30:57', 570, 0, 0, 0, 'De noche estoy mas libre', 'normal', NULL, 0, '2025-10-16 23:27:45', NULL, NULL, 0),
(573, 10, 3, '', 0, 1, '2025-10-16 23:30:57', NULL, 0, 0, 0, 'Por el dia ando muy ocupado', 'normal', NULL, 0, '2025-10-16 23:27:50', '2025-10-16 23:27:57', NULL, 0),
(574, 2, 3, '', 0, 1, '2025-10-16 23:30:45', NULL, 0, 0, 0, 'listo', 'normal', NULL, 0, '2025-10-16 23:28:02', NULL, NULL, 0),
(575, 10, 3, '', 0, 1, '2025-10-16 23:30:57', 571, 0, 0, 0, 'Donde uno taladra no?', 'normal', NULL, 0, '2025-10-16 23:28:08', NULL, NULL, 0),
(576, 2, 3, '', 0, 1, '2025-10-16 23:30:45', NULL, 0, 0, 0, '😄', 'normal', NULL, 0, '2025-10-16 23:28:11', NULL, NULL, 0),
(577, 3, 2, '', 0, 1, '2025-10-16 23:30:55', NULL, 0, 0, 0, 'ya quedo la lupa??', 'normal', NULL, 0, '2025-10-16 23:30:55', NULL, NULL, 0),
(578, 3, 10, '', 0, 1, '2025-10-16 23:31:16', 573, 0, 0, 0, 'escuchalo chico acupado', 'normal', NULL, 0, '2025-10-16 23:31:16', NULL, NULL, 0),
(579, 10, 3, '', 0, 1, '2025-10-16 23:31:31', NULL, 0, 0, 0, 'Ocupado querida', 'normal', NULL, 0, '2025-10-16 23:31:31', NULL, NULL, 0),
(580, 3, 10, '', 0, 1, '2025-10-16 23:31:43', 575, 0, 0, 0, 'pero vos no hablabas de taladrar una madera..', 'normal', NULL, 0, '2025-10-16 23:31:43', NULL, NULL, 0),
(581, 3, 10, '', 0, 1, '2025-10-16 23:32:19', 579, 0, 0, 0, 'como asi que querida, mil disculpas rey no sabia que andabas oocupado', 'normal', NULL, 0, '2025-10-16 23:32:19', NULL, NULL, 0),
(582, 2, 3, '', 0, 1, '2025-10-16 23:32:39', NULL, 0, 0, 0, 'tamo en eso', 'normal', NULL, 0, '2025-10-16 23:32:39', NULL, NULL, 0),
(583, 3, 2, '', 0, 1, '2025-10-16 23:32:55', NULL, 0, 0, 0, 'pero precisas ayuda en eso? o estas bien?', 'normal', NULL, 0, '2025-10-16 23:32:55', NULL, NULL, 0),
(584, 10, 3, '', 0, 1, '2025-10-16 23:33:12', 580, 0, 0, 0, 'Madera? nunca dije eso yo', 'normal', NULL, 0, '2025-10-16 23:33:06', NULL, NULL, 0),
(585, 10, 3, '', 0, 1, '2025-10-16 23:33:14', 581, 0, 0, 0, 'Ahora lo sabes reina', 'normal', NULL, 0, '2025-10-16 23:33:14', NULL, NULL, 0),
(586, 3, 10, '', 0, 1, '2025-10-16 23:33:23', NULL, 0, 0, 0, 'fue un ejemplo rey', 'normal', NULL, 0, '2025-10-16 23:33:23', NULL, NULL, 0),
(587, 10, 3, '', 0, 1, '2025-10-16 23:42:30', NULL, 0, 0, 0, 'Bno pero los dos sabemos que tengo que taladrar', 'normal', NULL, 0, '2025-10-16 23:41:56', NULL, NULL, 0),
(588, 3, 10, '', 0, 1, '2025-10-16 23:43:07', NULL, 0, 0, 0, 'entoces yo te taladro?', 'normal', NULL, 0, '2025-10-16 23:43:06', NULL, NULL, 0),
(589, 10, 3, '', 0, 1, '2025-10-16 23:44:03', NULL, 0, 0, 0, 'Nah no me gusta eso a mi', 'normal', NULL, 0, '2025-10-16 23:43:38', NULL, NULL, 0),
(590, 3, 10, '', 0, 1, '2025-10-16 23:44:13', NULL, 0, 0, 0, 'quien dijo que a mi si?', 'normal', NULL, 0, '2025-10-16 23:44:13', NULL, NULL, 0),
(591, 1, 3, '', 0, 1, '2025-10-16 23:44:24', NULL, 0, 0, 0, 'El tema era que estaba codificando todo dentro de un visual que no era', 'normal', NULL, 0, '2025-10-16 23:44:16', NULL, NULL, 0),
(592, 3, 10, '', 0, 1, '2025-10-16 23:44:30', NULL, 0, 0, 0, '🤔', 'normal', NULL, 0, '2025-10-16 23:44:22', NULL, NULL, 0),
(593, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Ah', 'normal', NULL, 0, '2025-10-16 23:44:27', NULL, NULL, 0),
(594, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Ah', 'normal', NULL, 0, '2025-10-16 23:44:27', NULL, NULL, 0),
(595, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Ah', 'normal', NULL, 0, '2025-10-16 23:44:27', NULL, NULL, 0),
(596, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Ah', 'normal', NULL, 0, '2025-10-16 23:44:27', NULL, NULL, 0),
(597, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Ah', 'normal', NULL, 0, '2025-10-16 23:44:31', NULL, NULL, 0),
(598, 1, 3, '', 0, 1, '2025-10-16 23:44:36', NULL, 0, 0, 0, 'como el servidor lo tengo en mi computadora de casa', 'normal', NULL, 0, '2025-10-16 23:44:36', NULL, NULL, 0),
(599, 1, 3, '', 0, 1, '2025-10-16 23:44:36', NULL, 0, 0, 0, 'como el servidor lo tengo en mi computadora de casa', 'normal', NULL, 0, '2025-10-16 23:44:36', NULL, NULL, 0),
(600, 1, 3, '', 0, 1, '2025-10-16 23:44:36', NULL, 0, 0, 0, 'como el servidor lo tengo en mi computadora de casa', 'normal', NULL, 0, '2025-10-16 23:44:36', NULL, NULL, 0),
(601, 1, 3, '', 0, 1, '2025-10-16 23:44:36', NULL, 0, 0, 0, 'como el servidor lo tengo en mi computadora de casa', 'normal', NULL, 0, '2025-10-16 23:44:36', NULL, NULL, 0),
(602, 1, 3, '', 0, 1, '2025-10-16 23:44:36', NULL, 0, 0, 0, 'como el servidor lo tengo en mi computadora de casa', 'normal', NULL, 0, '2025-10-16 23:44:36', NULL, NULL, 0),
(603, 1, 3, '', 0, 1, '2025-10-16 23:44:49', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-16 23:44:48', NULL, NULL, 0),
(604, 1, 3, '', 0, 1, '2025-10-16 23:44:49', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-16 23:44:49', NULL, NULL, 0),
(605, 1, 3, '', 0, 1, '2025-10-16 23:44:49', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-16 23:44:49', NULL, NULL, 0),
(606, 1, 3, '', 0, 1, '2025-10-16 23:44:49', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-16 23:44:49', NULL, NULL, 0),
(607, 1, 3, '', 0, 1, '2025-10-16 23:44:49', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-16 23:44:49', NULL, NULL, 0),
(608, 1, 3, '', 0, 1, '2025-10-16 23:44:49', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-16 23:44:49', NULL, NULL, 0),
(609, 1, 3, '', 0, 1, '2025-10-16 23:44:49', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-16 23:44:49', NULL, NULL, 1),
(610, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:50', NULL, NULL, 0),
(611, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:50', NULL, NULL, 0),
(612, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:50', NULL, NULL, 0),
(613, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:50', NULL, NULL, 0),
(614, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:50', NULL, NULL, 0),
(615, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:50', NULL, NULL, 0),
(616, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:50', NULL, NULL, 0),
(617, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:51', NULL, NULL, 0),
(618, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:51', NULL, NULL, 0),
(619, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:51', NULL, NULL, 0),
(620, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:51', NULL, NULL, 0),
(621, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:51', NULL, NULL, 0),
(622, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:51', NULL, NULL, 0),
(623, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Perdon', 'normal', NULL, 0, '2025-10-16 23:44:51', NULL, NULL, 0),
(624, 10, 3, '', 0, 1, '2025-10-16 23:45:11', NULL, 0, 0, 0, 'Hola?', 'normal', NULL, 0, '2025-10-16 23:45:01', NULL, NULL, 0),
(625, 1, 3, '', 0, 1, '2025-10-16 23:45:06', NULL, 0, 0, 0, 'gai', 'normal', NULL, 0, '2025-10-16 23:45:06', NULL, NULL, 0),
(626, 1, 3, '', 0, 1, '2025-10-16 23:45:06', NULL, 0, 0, 0, 'gai', 'normal', NULL, 0, '2025-10-16 23:45:06', NULL, NULL, 0),
(627, 1, 3, '', 0, 1, '2025-10-16 23:45:06', NULL, 0, 0, 0, 'gai', 'normal', NULL, 0, '2025-10-16 23:45:06', NULL, NULL, 0),
(628, 1, 3, '', 0, 1, '2025-10-16 23:45:06', NULL, 0, 0, 0, 'gai', 'normal', NULL, 0, '2025-10-16 23:45:06', NULL, NULL, 0),
(629, 1, 3, '', 0, 1, '2025-10-16 23:45:06', NULL, 0, 0, 0, 'gai', 'normal', NULL, 0, '2025-10-16 23:45:06', NULL, NULL, 0),
(630, 1, 3, '', 0, 1, '2025-10-16 23:45:06', NULL, 0, 0, 0, 'gai', 'normal', NULL, 0, '2025-10-16 23:45:06', NULL, NULL, 0),
(631, 1, 3, '', 0, 1, '2025-10-16 23:45:06', NULL, 0, 0, 0, 'gai', 'normal', NULL, 0, '2025-10-16 23:45:06', NULL, NULL, 0),
(632, 1, 3, '', 0, 1, '2025-10-16 23:45:07', NULL, 0, 0, 0, 'gai', 'normal', NULL, 0, '2025-10-16 23:45:07', NULL, NULL, 0),
(633, 1, 3, '', 0, 1, '2025-10-16 23:45:07', NULL, 0, 0, 0, 'gai', 'normal', NULL, 0, '2025-10-16 23:45:07', NULL, NULL, 0),
(634, 3, 1, '', 0, 1, '2025-10-16 23:45:10', NULL, 0, 0, 0, 'entonces ahora arreglaste de esto', 'normal', NULL, 0, '2025-10-16 23:45:09', NULL, NULL, 0),
(635, 10, 3, '', 0, 1, '2025-10-16 23:45:11', 590, 0, 0, 0, 'Uh', 'normal', NULL, 0, '2025-10-16 23:45:09', NULL, NULL, 0),
(636, 10, 3, '', 0, 1, '2025-10-16 23:45:14', NULL, 0, 0, 0, 'Si no te gusta entonces no', 'normal', NULL, 0, '2025-10-16 23:45:14', NULL, NULL, 0),
(637, 1, 3, '', 0, 1, '2025-10-16 23:45:56', NULL, 0, 0, 0, 'eh?', 'normal', NULL, 0, '2025-10-16 23:45:15', NULL, NULL, 0),
(638, 3, 10, '', 0, 1, '2025-10-16 23:45:33', NULL, 0, 0, 0, 'yo solo pregunte quien dijo que si', 'normal', NULL, 0, '2025-10-16 23:45:33', NULL, NULL, 0),
(639, 3, 10, '', 0, 1, '2025-10-16 23:45:46', NULL, 0, 0, 0, 'vos lo tomaste como echo', 'normal', NULL, 0, '2025-10-16 23:45:46', NULL, NULL, 0),
(640, 3, 10, '', 0, 1, '2025-10-16 23:45:54', NULL, 0, 0, 0, 'yo quiero ser monja', 'normal', NULL, 0, '2025-10-16 23:45:54', NULL, NULL, 0),
(641, 3, 1, '', 0, 1, '2025-10-16 23:46:21', NULL, 0, 0, 0, 'arreglaste en el archivo original y resolvisste', 'normal', NULL, 0, '2025-10-16 23:46:21', NULL, NULL, 0),
(642, 3, 1, '', 0, 1, '2025-10-16 23:46:23', NULL, 0, 0, 0, 'ademas', 'normal', NULL, 0, '2025-10-16 23:46:23', NULL, NULL, 0),
(643, 3, 1, '', 0, 1, '2025-10-16 23:46:31', NULL, 0, 0, 0, 'resolve le boton de enviar', 'normal', NULL, 0, '2025-10-16 23:46:30', NULL, NULL, 0),
(644, 10, 3, '', 0, 1, '2025-10-16 23:47:12', NULL, 0, 0, 0, 'Monja?', 'normal', NULL, 0, '2025-10-16 23:46:49', NULL, NULL, 0),
(645, 10, 3, '', 0, 1, '2025-10-16 23:47:12', NULL, 0, 0, 0, 'Y eso que?', 'normal', NULL, 0, '2025-10-16 23:46:52', NULL, NULL, 0),
(646, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:46:59', NULL, NULL, 0),
(647, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:46:59', NULL, NULL, 0),
(648, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:46:59', NULL, NULL, 0),
(649, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:46:59', NULL, NULL, 0),
(650, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:46:59', NULL, NULL, 0),
(651, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:46:59', NULL, NULL, 0),
(652, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:46:59', NULL, NULL, 0),
(653, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:46:59', NULL, NULL, 0),
(654, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:46:59', NULL, NULL, 0),
(655, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:46:59', NULL, NULL, 0),
(656, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(657, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(658, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(659, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(660, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(661, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(662, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(663, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(664, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(665, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(666, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(667, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(668, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(669, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(670, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(671, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(672, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(673, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(674, 3, 1, '', 0, 1, '2025-10-16 23:47:00', NULL, 0, 0, 0, 'ah', 'normal', NULL, 0, '2025-10-16 23:47:00', NULL, NULL, 0),
(675, 3, 10, '', 0, 1, '2025-10-16 23:47:28', NULL, 0, 0, 0, 'que no puedo ser taladrada jaj', 'normal', NULL, 0, '2025-10-16 23:47:28', NULL, NULL, 0),
(676, 10, 3, '', 0, 1, '2025-10-16 23:48:35', NULL, 0, 0, 0, 'Uh que mal entonces', 'normal', NULL, 0, '2025-10-16 23:48:04', NULL, NULL, 0),
(677, 2, 3, '', 0, 1, '2025-10-16 23:48:23', NULL, 0, 0, 0, 'esta bien', 'normal', NULL, 0, '2025-10-16 23:48:16', NULL, NULL, 0),
(678, 2, 3, '', 0, 1, '2025-10-16 23:48:23', NULL, 0, 0, 0, 'nos falta', 'normal', NULL, 0, '2025-10-16 23:48:22', NULL, NULL, 0),
(679, 2, 3, '', 0, 1, '2025-10-16 23:48:24', NULL, 0, 0, 0, 'ponerlo', 'normal', NULL, 0, '2025-10-16 23:48:24', NULL, NULL, 0),
(680, 2, 3, '', 0, 1, '2025-10-16 23:48:25', NULL, 0, 0, 0, 'y ya', 'normal', NULL, 0, '2025-10-16 23:48:25', NULL, NULL, 0),
(681, 3, 2, '', 0, 1, '2025-10-16 23:48:33', NULL, 0, 0, 0, 'perfecto', 'normal', NULL, 0, '2025-10-16 23:48:33', NULL, NULL, 0),
(682, 2, 3, '', 0, 1, '2025-10-16 23:49:27', NULL, 0, 0, 0, 'ya que angel tiene todo el codigo voy a hablar con el para empezar maquetacion yo', 'normal', NULL, 0, '2025-10-16 23:48:46', NULL, NULL, 0),
(683, 2, 3, '', 0, 1, '2025-10-16 23:49:27', NULL, 0, 0, 0, 'asi aligero', 'normal', NULL, 0, '2025-10-16 23:48:48', NULL, NULL, 0),
(684, 3, 10, '', 0, 1, '2025-10-16 23:49:21', NULL, 0, 0, 0, 'na pero contigo puedo hacer una excepción', 'normal', NULL, 0, '2025-10-16 23:49:20', NULL, NULL, 0),
(685, 3, 2, '', 0, 1, '2025-10-16 23:49:49', NULL, 0, 0, 0, 'muy bien alejo', 'normal', NULL, 0, '2025-10-16 23:49:49', NULL, NULL, 0),
(686, 3, 2, '', 0, 1, '2025-10-16 23:49:54', NULL, 0, 0, 0, 'segui asi', 'normal', NULL, 0, '2025-10-16 23:49:54', NULL, NULL, 0),
(687, 1, 10, '', 0, 0, NULL, NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-16 23:54:46', NULL, NULL, 0),
(688, 2, 1, '', 0, 1, '2025-10-18 00:01:13', NULL, 0, 0, 0, 'es que eso', 'normal', NULL, 0, '2025-10-17 12:55:52', NULL, NULL, 0),
(689, 2, 1, '', 0, 1, '2025-10-18 00:01:13', NULL, 0, 0, 0, 'ya esta hecho', 'normal', NULL, 0, '2025-10-17 12:55:55', NULL, NULL, 0),
(690, 2, 1, '', 0, 1, '2025-10-18 00:01:13', NULL, 0, 0, 0, 'lo de que el boton lo cliquees y aparezca', 'normal', NULL, 0, '2025-10-17 12:56:08', NULL, NULL, 0),
(691, 2, 1, '', 0, 1, '2025-10-18 00:01:13', NULL, 0, 0, 0, 'en teoria', 'normal', NULL, 0, '2025-10-17 12:56:10', NULL, NULL, 0),
(692, 2, 1, '', 0, 1, '2025-10-18 00:01:13', NULL, 0, 0, 0, 'ya esta', 'normal', NULL, 0, '2025-10-17 12:56:11', NULL, NULL, 0),
(693, 2, 1, '', 0, 1, '2025-10-18 00:01:13', NULL, 0, 0, 0, 'te pido que lo implementes, que lo pongas, que lo metas en el codigo', 'normal', NULL, 0, '2025-10-17 12:56:45', NULL, NULL, 0),
(694, 2, 1, '', 0, 1, '2025-10-18 00:01:13', NULL, 0, 0, 0, 'y que me ayudes con el tema de las variables con valores del php', 'normal', NULL, 0, '2025-10-17 12:57:10', NULL, NULL, 0),
(695, 2, 1, '', 0, 1, '2025-10-18 00:01:13', NULL, 0, 0, 0, 'porque yo no le se', 'normal', NULL, 0, '2025-10-17 12:57:19', NULL, NULL, 0),
(696, 2, 1, '', 0, 1, '2025-10-18 00:01:13', NULL, 0, 0, 0, 'ademas de que hay que copiar y pegar el codigo de busquesita-chat.php en el mensajeria.php', 'normal', NULL, 0, '2025-10-17 17:05:37', NULL, NULL, 0),
(697, 1, 2, '', 0, 1, '2025-10-20 21:10:26', NULL, 0, 0, 0, 'o qué', 'normal', NULL, 0, '2025-10-18 00:01:28', NULL, NULL, 0),
(698, 2, 1, '', 1, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, '🤖 [Respuesta Automatica de Perseo]\n\nActualmente Alejo no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.', 'normal', NULL, 0, '2025-10-20 21:10:15', NULL, NULL, 0),
(699, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'hola amor', 'normal', NULL, 0, '2025-10-21 00:02:08', NULL, NULL, 0),
(700, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'listo para lo de esta noche?', 'normal', NULL, 0, '2025-10-21 00:02:15', NULL, NULL, 0),
(701, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'dea', 'normal', NULL, 0, '2025-10-21 00:02:16', NULL, NULL, 0),
(702, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'No te llega?', 'normal', NULL, 0, '2025-10-21 00:06:48', NULL, NULL, 0),
(703, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'WEONA', 'normal', NULL, 0, '2025-10-21 00:06:53', NULL, NULL, 0),
(704, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'SuLoL?', 'normal', NULL, 0, '2025-10-21 00:06:58', NULL, NULL, 0),
(705, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'mas', 'normal', NULL, 0, '2025-10-21 00:11:43', NULL, NULL, 0),
(706, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'mensajes', 'normal', NULL, 0, '2025-10-21 00:11:44', NULL, NULL, 0),
(707, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'para', 'normal', NULL, 0, '2025-10-21 00:11:45', NULL, NULL, 0),
(708, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'vos', 'normal', NULL, 0, '2025-10-21 00:11:47', NULL, NULL, 0),
(709, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'angel', 'normal', NULL, 0, '2025-10-21 00:11:48', NULL, NULL, 0),
(710, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'ef', 'normal', NULL, 0, '2025-10-21 00:11:50', NULL, NULL, 0),
(711, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'sd', 'normal', NULL, 0, '2025-10-21 00:11:50', NULL, NULL, 0),
(712, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'sd', 'normal', NULL, 0, '2025-10-21 00:11:51', NULL, NULL, 0),
(713, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'sdf', 'normal', NULL, 0, '2025-10-21 00:11:51', NULL, NULL, 0),
(714, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'd', 'normal', NULL, 0, '2025-10-21 00:11:51', NULL, NULL, 0),
(715, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'sd', 'normal', NULL, 0, '2025-10-21 00:11:51', NULL, NULL, 0),
(716, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'sf', 'normal', NULL, 0, '2025-10-21 00:11:51', NULL, NULL, 0),
(717, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'fdsdsfsegundo', 'normal', NULL, 0, '2025-10-21 00:13:33', NULL, NULL, 0),
(718, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'mensaje', 'normal', NULL, 0, '2025-10-21 00:13:35', NULL, NULL, 0),
(719, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'para', 'normal', NULL, 0, '2025-10-21 00:13:36', NULL, NULL, 0),
(720, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'vos', 'normal', NULL, 0, '2025-10-21 00:13:37', NULL, NULL, 0),
(721, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'qliao', 'normal', NULL, 0, '2025-10-21 00:13:38', NULL, NULL, 0),
(722, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'loco', 'normal', NULL, 0, '2025-10-21 00:13:41', NULL, NULL, 0),
(723, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'sulol o te tiemblan los panes?', 'normal', NULL, 0, '2025-10-21 00:13:50', NULL, NULL, 0),
(724, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'ola', 'normal', NULL, 0, '2025-10-21 02:05:21', NULL, NULL, 0),
(725, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'aaa', 'normal', NULL, 0, '2025-10-21 02:19:13', NULL, NULL, 0),
(726, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, '111', 'normal', NULL, 0, '2025-10-21 02:19:24', NULL, NULL, 0),
(727, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'A', 'normal', NULL, 0, '2025-10-21 02:32:53', NULL, NULL, 0),
(728, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'a', 'normal', NULL, 0, '2025-10-21 02:57:45', NULL, NULL, 0),
(729, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'aa', 'normal', NULL, 0, '2025-10-21 03:08:55', NULL, NULL, 0),
(730, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'sovovobo', 'normal', NULL, 0, '2025-10-21 03:09:05', NULL, NULL, 0),
(731, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'ahira=', 'normal', NULL, 0, '2025-10-21 03:11:18', NULL, NULL, 0),
(732, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'aa', 'normal', NULL, 0, '2025-10-21 03:25:22', NULL, NULL, 0),
(733, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'ya?', 'normal', NULL, 0, '2025-10-21 03:27:23', NULL, NULL, 0),
(734, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'y ahora?', 'normal', NULL, 0, '2025-10-21 03:30:17', NULL, NULL, 0),
(735, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'tamare', 'normal', NULL, 0, '2025-10-21 03:30:23', NULL, NULL, 0),
(736, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'hablame', 'normal', NULL, 0, '2025-10-21 03:36:45', NULL, NULL, 0),
(737, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'aaa', 'normal', NULL, 0, '2025-10-21 03:36:51', NULL, NULL, 0),
(738, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'aaa', 'normal', NULL, 0, '2025-10-21 03:40:52', NULL, NULL, 0),
(739, 2, 1, '', 0, 1, '2025-10-21 03:44:03', NULL, 0, 0, 0, 'dale man', 'normal', NULL, 0, '2025-10-21 03:43:34', NULL, NULL, 0),
(740, 1, 2, '', 0, 1, '2025-10-21 03:44:05', NULL, 0, 0, 0, 'abusivo', 'normal', NULL, 0, '2025-10-21 03:44:05', NULL, NULL, 0),
(741, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'Olaaaa', 'normal', NULL, 0, '2025-10-21 03:47:14', NULL, NULL, 0),
(742, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'Dale mN', 'normal', NULL, 0, '2025-10-21 03:47:50', NULL, NULL, 0),
(743, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'Dale', 'normal', NULL, 0, '2025-10-21 03:49:00', NULL, NULL, 0),
(744, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'Necesito que sea en tiempo real', 'normal', NULL, 0, '2025-10-21 03:49:06', NULL, NULL, 0),
(745, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'O', 'normal', NULL, 0, '2025-10-21 03:51:01', NULL, NULL, 0),
(746, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'yap', 'normal', NULL, 0, '2025-10-21 03:52:43', NULL, NULL, 0),
(747, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'sexo', 'normal', NULL, 0, '2025-10-21 03:55:40', NULL, NULL, 0),
(748, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'wa', 'normal', NULL, 0, '2025-10-21 03:57:22', NULL, NULL, 0),
(749, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'dale mano', 'normal', NULL, 0, '2025-10-21 04:00:25', NULL, NULL, 0),
(750, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'y ahora?', 'normal', NULL, 0, '2025-10-21 04:00:43', NULL, NULL, 0),
(751, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'aw', 'normal', NULL, 0, '2025-10-21 04:05:48', NULL, NULL, 0),
(752, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'a', 'normal', NULL, 0, '2025-10-21 04:05:52', NULL, NULL, 0),
(753, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'dale porfa', 'normal', NULL, 0, '2025-10-21 04:09:14', NULL, NULL, 0),
(754, 2, 1, '', 0, 1, '2025-10-21 04:13:39', NULL, 0, 0, 0, 'pucha', 'normal', NULL, 0, '2025-10-21 04:13:23', NULL, NULL, 0),
(755, 2, 1, '', 0, 1, '2025-10-21 18:15:00', NULL, 0, 0, 0, 'hola bb', 'normal', NULL, 0, '2025-10-21 18:14:17', NULL, NULL, 0),
(756, 2, 1, '', 0, 1, '2025-10-21 18:15:00', NULL, 0, 0, 0, 'te amo', 'normal', NULL, 0, '2025-10-21 18:14:19', NULL, NULL, 0),
(757, 2, 1, '', 0, 1, '2025-10-21 18:15:00', NULL, 0, 0, 0, 'volve', 'normal', NULL, 0, '2025-10-21 18:14:20', NULL, NULL, 0),
(758, 1, 2, '', 0, 1, '2025-10-21 19:11:31', NULL, 0, 0, 0, 'no quiero', 'normal', NULL, 0, '2025-10-21 18:15:09', NULL, NULL, 0),
(759, 1, 2, '', 0, 1, '2025-10-21 19:11:31', NULL, 0, 0, 0, 'hola mi amor', 'normal', NULL, 0, '2025-10-21 18:53:53', NULL, NULL, 0),
(760, 1, 3, '', 0, 0, NULL, NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-21 18:54:11', NULL, NULL, 0),
(761, 1, 3, '', 0, 0, NULL, NULL, 0, 0, 0, 'pop', 'normal', NULL, 0, '2025-10-21 18:54:15', NULL, NULL, 0),
(762, 1, 3, '', 0, 0, NULL, NULL, 0, 0, 0, 'pop', 'normal', NULL, 0, '2025-10-21 18:54:15', NULL, NULL, 0),
(763, 1, 3, '', 0, 0, NULL, NULL, 0, 0, 0, 'pop', 'normal', NULL, 0, '2025-10-21 18:54:18', NULL, NULL, 0),
(764, 1, 3, '', 0, 0, NULL, NULL, 0, 0, 0, 'pop', 'normal', NULL, 0, '2025-10-21 18:54:20', NULL, NULL, 0),
(765, 1, 2, '', 0, 1, '2025-10-21 19:11:31', NULL, 0, 0, 0, 'rekaka', 'normal', NULL, 0, '2025-10-21 18:55:18', NULL, NULL, 0),
(766, 1, 2, '', 0, 1, '2025-10-21 19:11:31', NULL, 0, 0, 0, 'pipipupu', 'normal', NULL, 0, '2025-10-21 18:55:33', NULL, NULL, 0),
(767, 1, 2, '', 0, 1, '2025-10-21 19:11:31', NULL, 0, 0, 0, 'uuuh', 'normal', NULL, 0, '2025-10-21 18:55:35', NULL, NULL, 0),
(768, 1, 2, '', 0, 1, '2025-10-21 19:11:31', NULL, 0, 0, 0, 'pipi', 'normal', NULL, 0, '2025-10-21 18:55:36', NULL, NULL, 0),
(769, 1, 2, '', 0, 1, '2025-10-21 19:11:31', NULL, 0, 0, 0, 'pupu', 'normal', NULL, 0, '2025-10-21 18:55:38', NULL, NULL, 0),
(770, 1, 2, '', 0, 1, '2025-10-21 19:11:31', NULL, 0, 0, 0, 'no seas gay', 'normal', NULL, 0, '2025-10-21 19:10:32', NULL, NULL, 0),
(771, 2, 1, '', 0, 1, '2025-10-21 19:11:50', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-21 19:11:42', NULL, NULL, 0),
(772, 2, 1, '', 0, 1, '2025-10-21 19:11:50', NULL, 0, 0, 0, 'amigo', 'normal', NULL, 0, '2025-10-21 19:11:46', NULL, NULL, 0),
(773, 2, 1, '', 0, 1, '2025-10-21 19:11:50', NULL, 0, 0, 0, 'sulol?', 'normal', NULL, 0, '2025-10-21 19:11:49', NULL, NULL, 0),
(774, 2, 1, '', 0, 1, '2025-10-21 19:12:09', NULL, 0, 0, 0, 'loco', 'normal', NULL, 0, '2025-10-21 19:11:54', NULL, NULL, 0),
(775, 2, 1, '', 0, 1, '2025-10-21 19:12:09', NULL, 0, 0, 0, 'crep qie amda rarp', 'normal', NULL, 0, '2025-10-21 19:11:57', NULL, NULL, 0),
(776, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'hola´', 'normal', NULL, 0, '2025-10-21 19:20:42', NULL, NULL, 0),
(777, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'mensaje nuevo', 'normal', NULL, 0, '2025-10-21 19:20:44', NULL, NULL, 0),
(778, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'uwu', 'normal', NULL, 0, '2025-10-21 19:20:45', NULL, NULL, 0),
(779, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'estoy escribiendo angel', 'normal', NULL, 0, '2025-10-21 19:20:48', NULL, NULL, 0),
(780, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'mira loco', 'normal', NULL, 0, '2025-10-21 19:20:52', NULL, NULL, 0),
(781, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'porfa', 'normal', NULL, 0, '2025-10-21 19:20:53', NULL, NULL, 0),
(782, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'te estoy mandando mensajes', 'normal', NULL, 0, '2025-10-21 19:20:58', NULL, NULL, 0),
(783, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'para que veas que funca', 'normal', NULL, 0, '2025-10-21 19:21:01', NULL, NULL, 0),
(784, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'porfavor', 'normal', NULL, 0, '2025-10-21 19:21:02', NULL, NULL, 0),
(785, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'g', 'normal', NULL, 0, '2025-10-21 19:21:29', NULL, NULL, 0),
(786, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'd', 'normal', NULL, 0, '2025-10-21 19:21:29', NULL, NULL, 0),
(787, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'fg', 'normal', NULL, 0, '2025-10-21 19:21:30', NULL, NULL, 0),
(788, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 's', 'normal', NULL, 0, '2025-10-21 19:21:30', NULL, NULL, 0),
(789, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'sd', 'normal', NULL, 0, '2025-10-21 19:21:31', NULL, NULL, 0),
(790, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'f', 'normal', NULL, 0, '2025-10-21 19:21:31', NULL, NULL, 0),
(791, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'sd', 'normal', NULL, 0, '2025-10-21 19:21:31', NULL, NULL, 0),
(792, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'sd', 'normal', NULL, 0, '2025-10-21 19:21:32', NULL, NULL, 0),
(793, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'sdf', 'normal', NULL, 0, '2025-10-21 19:21:32', NULL, NULL, 0),
(794, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'sd', 'normal', NULL, 0, '2025-10-21 19:21:33', NULL, NULL, 0),
(795, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'fs', 'normal', NULL, 0, '2025-10-21 19:21:33', NULL, NULL, 0),
(796, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'fsd', 'normal', NULL, 0, '2025-10-21 19:21:34', NULL, NULL, 0),
(797, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'sf', 'normal', NULL, 0, '2025-10-21 19:21:34', NULL, NULL, 0),
(798, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'ff', 'normal', NULL, 0, '2025-10-21 19:22:50', NULL, NULL, 0),
(799, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'f', 'normal', NULL, 0, '2025-10-21 19:22:50', NULL, NULL, 0),
(800, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'sf', 'normal', NULL, 0, '2025-10-21 19:22:50', NULL, NULL, 0),
(801, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'f', 'normal', NULL, 0, '2025-10-21 19:22:50', NULL, NULL, 0),
(802, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'f', 'normal', NULL, 0, '2025-10-21 19:22:51', NULL, NULL, 0),
(803, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'f', 'normal', NULL, 0, '2025-10-21 19:22:51', NULL, NULL, 0),
(804, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'sf', 'normal', NULL, 0, '2025-10-21 19:22:52', NULL, NULL, 0),
(805, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 'dfdsfs', 'normal', NULL, 0, '2025-10-21 19:22:52', NULL, NULL, 0),
(806, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 's', 'normal', NULL, 0, '2025-10-21 19:22:52', NULL, NULL, 0),
(807, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 's', 'normal', NULL, 0, '2025-10-21 19:22:53', NULL, NULL, 0),
(808, 2, 1, '', 0, 1, '2025-10-21 19:23:58', NULL, 0, 0, 0, 's', 'normal', NULL, 0, '2025-10-21 19:22:53', NULL, NULL, 0),
(809, 2, 1, '', 0, 1, '2025-10-21 19:24:06', NULL, 0, 0, 0, 'este mensaje lo mande ahora', 'normal', NULL, 0, '2025-10-21 19:24:02', NULL, NULL, 0),
(810, 2, 1, '', 0, 1, '2025-10-21 19:24:26', NULL, 0, 0, 0, 'este mensaje lo volvi a mandar', 'normal', NULL, 0, '2025-10-21 19:24:13', NULL, NULL, 0);
INSERT INTO `mensajes` (`id`, `sender_id`, `receiver_id`, `message`, `is_perseo_auto`, `is_read`, `read_at`, `reply_to_message_id`, `producto_id`, `remitente_id`, `destinatario_id`, `mensaje`, `tipo_mensaje`, `producto_relacionado_id`, `leido`, `created_at`, `edited_at`, `deleted_for`, `is_deleted`) VALUES
(811, 2, 1, '', 0, 1, '2025-10-21 19:25:23', NULL, 0, 0, 0, 'Ha fallado la carga del <script> con origen \"http://186.54.83.245:3001/socket.io/socket.io.js\". mensajeria.php:23:72 downloadable font: Glyph bbox was incorrect (glyph ids 1 2 3 4 5 8 9 10 11 12 13 14 16 17 19 22 24 28 32 34 35 38 39 40 43 44 45 46 47 50 51 52 53 55 56 58 60 61 62 64 67 68 70 71 72 73 74 78 79 80 81 83 90 96 101 103 104 105 108 109 115 116 117 118 120 123 125 135 138 139 140 142 143 144 145 146 148 149 154 155 157 161 162 163 164 165 169 170 171 173 179 181 193 195 203 207 208 210 211 214 218 219 223 225 227 228 229 230 235 236 237 238 239 240 245 246 247 248 249 250 251 252 253 254 255 256 257 263 264 266 268 271 275 278 279 280 281 282 283 284 285 286 287 288 291 292 293 294 295 296 297 298 299 300 301 302 303 304 305 306 307 308 309 310 311 312 313 314 315 316 321 335 338 339 340 342 344 345 346 353 354 356 357 358 359 362 363 365 366 371 373 374 379 381 382 383 386 389 390 391 393 394 406 407 412 413 418 419 420 424 432 433 439 448 449 450 451 454 455 456 457 472 479 480 481 482 485 486 490 491 493 499 500 501 503 508 509 513 515 516 525 527 528 532 535 541 542 543 549 550 551 552 554 555 556 558 560 569 571 593 602 603 604 607 608 609 614 615 617 618 623 626 627 643 644 645 647 650 651 654 655 656 657 662 663 664 665 670 671 672 674 675 679 680 681 682 683 698 699 708 712 714 717 718 729 730 732 735 736 739 746 747 752 761 762 767 774 776 777 778 779 788 789 790 794 796 798 799 800 801 803 804 806 826 828 829 831 835 836 838 839 840 841 842 843 844 845 848 849 856 857 861 862 863 871 873 874 880 882 892 895 900 908 911 913 925 928 929 930 933 936 937 938 941 942 943 944 945 948 949 950 952 958 960 961 962 964 966 967 969 973 974 978 979 980 981 982 989 998 1000 1001 1005 1006 1008 1009 1011 1012 1013 1016 1020 1026 1027 1031 1036 1037 1042 1045 1048 1050 1052 1053 1057 1058 1060 1063 1072 1073 1076 1084 1087 1099 1104 1110 1111 1112 1116 1117 1121 1122 1124 1131 1136 1140 1141 1142 1147 1148 1151 1157 1163 1167 1168 1170 1177 1186 1187 1193 1196 1199 1200 1201 1204 1205 1208 1211 1212 1217 1218 1220 1224 1226 1228 1230 1231 1232 1233 1235 1236 1237 1238 1243 1246 1247 1249 1251 1256 1258 1259 1260 1261 1265 1268 1269 1271 1272 1273 1275 1276 1279 1285 1289 1290 1291 1292 1296 1297 1303 1304 1305 1309 1310 1311 1312 1317 1319 1320 1324 1325 1328 1329 1330 1331 1334 1335 1337 1339 1341 1343 1356 1357 1363 1369 1371 1372 1375 1376 1377 1382 1384 1387) (font-family: \"Font Awesome 6 Free\" style:normal weight:900 stretch:100 src index:0) source: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2 Solicitud de origen cruzado bloqueada: La política de mismo origen no permite la lectura de recursos remotos en http://186.54.83.245:3001/socket.io/?EIO=4&transport=polling&t=Pe8Dc30. (Razón: Solicitud CORS sin éxito). Código de estado: (null). 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 461, message: \"yo te trato re bien\", timestamp: \"2025-10-16 19:44:51\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 462, message: \"🥲🥲🥲🥲🥲\", timestamp: \"2025-10-16 19:45:00\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 463, message: \"mentirosa\", timestamp: \"2025-10-16 19:47:25\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 464, message: \"no me importa que de amor te mueras\", timestamp: \"2025-10-16 19:47:36\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 465, message: \"ooooooooooooooo\", timestamp: \"2025-10-16 19:47:39\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 466, message: \"o oooo\", timestamp: \"2025-10-16 19:47:41\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 467, message: \"o o o o o o\", timestamp: \"2025-10-16 19:47:47\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 468, message: \"men\", timestamp: \"2025-10-16 19:47:49\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 469, message: \"ti\", timestamp: \"2025-10-16 19:47:50\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 470, message: \"rosa\", timestamp: \"2025-10-16 19:47:51\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 471, message: \"no me importa que de amor te mueras\", timestamp: \"2025-10-16 19:47:56\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 476, message: \"por que me tratas asi\", timestamp: \"2025-10-16 19:52:02\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 477, message: \"que te hice\", timestamp: \"2025-10-16 19:52:08\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 478, message: \"no juguaste LoL\", timestamp: \"2025-10-16 19:52:16\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 479, message: \"y no me pasaste el repo\", timestamp: \"2025-10-16 19:52:20\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 480, message: \"angel\", timestamp: \"2025-10-16 19:52:36\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 481, message: \"lo del botoncito de la lupa\", timestamp: \"2025-10-16 19:52:43\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 491, message: \"pera que estoy haciendo otra wwa\", timestamp: \"2025-10-16 19:59:44\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 492, message: \"wea\", timestamp: \"2025-10-16 19:59:47\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 493, message: \"oka\", timestamp: \"2025-10-16 19:59:54\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 494, message: \"por cielto\", timestamp: \"2025-10-16 19:59:56\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 495, message: \"a no nada\", timestamp: \"2025-10-16 20:00:01\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 497, message: \"iba a decirte algo de responsividad\", timestamp: \"2025-10-16 20:00:05\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 498, message: \"pero esto no lo va a notar nadie\", timestamp: \"2025-10-16 20:00:11\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 499, message: \"ya que pasa seguramente solo en mi pantalla\", timestamp: \"2025-10-16 20:00:18\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 532, message: \"ok\", timestamp: \"2025-10-16 20:08:04\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 533, message: \"ok\", timestamp: \"2025-10-16 20:08:04\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 534, message: \"k\", timestamp: \"2025-10-16 20:08:05\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 535, message: \"k\", timestamp: \"2025-10-16 20:08:06\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 536, message: \"k\", timestamp: \"2025-10-16 20:08:06\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 537, message: \"kkkkk\", timestamp: \"2025-10-16 20:08:07\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 545, message: \"yo hice pull y la lupa no anda\", timestamp: \"2025-10-16 20:13:42\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 546, message: \"es que no tiene weas piuestas\", timestamp: \"2025-10-16 20:14:00\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 547, message: \"por eso te dije\", timestamp: \"2025-10-16 20:14:02\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 548, message: \"como vos le sabes mas al js\", timestamp: \"2025-10-16 20:14:08\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 549, message: \"que vos le metieras el nombre de las variables\", timestamp: \"2025-10-16 20:14:14\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 550, message: \"ademas\", timestamp: \"2025-10-16 20:14:17\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 551, message: \"desde que converti busquesita-chat.php en php me dio errores que no entendi y me dio miedo\", timestamp: \"2025-10-16 20:14:37\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 552, message: \"ahh\", timestamp: \"2025-10-16 20:14:40\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 553, message: \"o sea\", timestamp: \"2025-10-16 20:14:41\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 554, message: \"esta hecho\", timestamp: \"2025-10-16 20:14:44\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 555, message: \"pero no puesto\", timestamp: \"2025-10-16 20:14:47\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 556, message: \"queres que lo configure o que lo implemente?\", timestamp: \"2025-10-16 20:15:17\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 558, message: \"la logica o que si apreto el boto aparezca lo que haces vos\", timestamp: \"2025-10-16 20:15:30\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 688, message: \"es que eso\", timestamp: \"2025-10-17 09:55:52\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 689, message: \"ya esta hecho\", timestamp: \"2025-10-17 09:55:55\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 690, message: \"lo de que el boton lo cliquees y aparezca\", timestamp: \"2025-10-17 09:56:08\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 691, message: \"en teoria\", timestamp: \"2025-10-17 09:56:10\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 692, message: \"ya esta\", timestamp: \"2025-10-17 09:56:11\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 693, message: \"te pido que lo implementes, que lo pongas, que lo metas en el codigo\", timestamp: \"2025-10-17 09:56:45\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 694, message: \"y que me ayudes con el tema de las variables con valores del php\", timestamp: \"2025-10-17 09:57:10\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 695, message: \"porque yo no le se\", timestamp: \"2025-10-17 09:57:19\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 696, message: \"ademas de que hay que copiar y pegar el codigo de busquesita-chat.php en el mensajeria.php\", timestamp: \"2025-10-17 14:05:37\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 697, message: \"o qué\", timestamp: \"2025-10-17 21:01:28\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 698, message: \"🤖 [Respuesta Automatica de Perseo]\\n\\nActualmente Alejo no puede responder. Tu mensaje es importante y sera atendido en cuanto sea posible.\", timestamp: \"2025-10-20 18:10:15\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: true, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 699, message: \"hola amor\", timestamp: \"2025-10-20 21:02:08\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 700, message: \"listo para lo de esta noche?\", timestamp: \"2025-10-20 21:02:15\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 701, message: \"dea\", timestamp: \"2025-10-20 21:02:16\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 702, message: \"No te llega?\", timestamp: \"2025-10-20 21:06:48\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 703, message: \"WEONA\", timestamp: \"2025-10-20 21:06:53\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 704, message: \"SuLoL?\", timestamp: \"2025-10-20 21:06:58\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 705, message: \"mas\", timestamp: \"2025-10-20 21:11:43\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 706, message: \"mensajes\", timestamp: \"2025-10-20 21:11:44\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 707, message: \"para\", timestamp: \"2025-10-20 21:11:45\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 708, message: \"vos\", timestamp: \"2025-10-20 21:11:47\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 709, message: \"angel\", timestamp: \"2025-10-20 21:11:48\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 710, message: \"ef\", timestamp: \"2025-10-20 21:11:50\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 711, message: \"sd\", timestamp: \"2025-10-20 21:11:50\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 712, message: \"sd\", timestamp: \"2025-10-20 21:11:51\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 713, message: \"sdf\", timestamp: \"2025-10-20 21:11:51\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 714, message: \"d\", timestamp: \"2025-10-20 21:11:51\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 715, message: \"sd\", timestamp: \"2025-10-20 21:11:51\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 716, message: \"sf\", timestamp: \"2025-10-20 21:11:51\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 717, message: \"fdsdsfsegundo\", timestamp: \"2025-10-20 21:13:33\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 718, message: \"mensaje\", timestamp: \"2025-10-20 21:13:35\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 719, message: \"para\", timestamp: \"2025-10-20 21:13:36\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 720, message: \"vos\", timestamp: \"2025-10-20 21:13:37\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 721, message: \"qliao\", timestamp: \"2025-10-20 21:13:38\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 722, message: \"loco\", timestamp: \"2025-10-20 21:13:41\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 723, message: \"sulol o te tiemblan los panes?\", timestamp: \"2025-10-20 21:13:50\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 724, message: \"ola\", timestamp: \"2025-10-20 23:05:21\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 725, message: \"aaa\", timestamp: \"2025-10-20 23:19:13\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 726, message: \"111\", timestamp: \"2025-10-20 23:19:24\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 727, message: \"A\", timestamp: \"2025-10-20 23:32:53\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 728, message: \"a\", timestamp: \"2025-10-20 23:57:45\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 729, message: \"aa\", timestamp: \"2025-10-21 00:08:55\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 730, message: \"sovovobo\", timestamp: \"2025-10-21 00:09:05\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 731, message: \"ahira=\", timestamp: \"2025-10-21 00:11:18\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 732, message: \"aa\", timestamp: \"2025-10-21 00:25:22\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 733, message: \"ya?\", timestamp: \"2025-10-21 00:27:23\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 734, message: \"y ahora?\", timestamp: \"2025-10-21 00:30:17\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 735, message: \"tamare\", timestamp: \"2025-10-21 00:30:23\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 736, message: \"hablame\", timestamp: \"2025-10-21 00:36:45\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 737, message: \"aaa\", timestamp: \"2025-10-21 00:36:51\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 738, message: \"aaa\", timestamp: \"2025-10-21 00:40:52\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 739, message: \"dale man\", timestamp: \"2025-10-21 00:43:34\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 740, message: \"abusivo\", timestamp: \"2025-10-21 00:44:05\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 741, message: \"Olaaaa\", timestamp: \"2025-10-21 00:47:14\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 742, message: \"Dale mN\", timestamp: \"2025-10-21 00:47:50\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 743, message: \"Dale\", timestamp: \"2025-10-21 00:49:00\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 744, message: \"Necesito que sea en tiempo real\", timestamp: \"2025-10-21 00:49:06\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 745, message: \"O\", timestamp: \"2025-10-21 00:51:01\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 746, message: \"yap\", timestamp: \"2025-10-21 00:52:43\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 747, message: \"sexo\", timestamp: \"2025-10-21 00:55:40\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 748, message: \"wa\", timestamp: \"2025-10-21 00:57:22\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 749, message: \"dale mano\", timestamp: \"2025-10-21 01:00:25\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 750, message: \"y ahora?\", timestamp: \"2025-10-21 01:00:43\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 751, message: \"aw\", timestamp: \"2025-10-21 01:05:48\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 752, message: \"a\", timestamp: \"2025-10-21 01:05:52\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 753, message: \"dale porfa\", timestamp: \"2025-10-21 01:09:14\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 754, message: \"pucha\", timestamp: \"2025-10-21 01:13:23\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 755, message: \"hola bb\", timestamp: \"2025-10-21 15:14:17\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 756, message: \"te amo\", timestamp: \"2025-10-21 15:14:19\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 757, message: \"volve\", timestamp: \"2025-10-21 15:14:20\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 758, message: \"no quiero\", timestamp: \"2025-10-21 15:15:09\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 759, message: \"hola mi amor\", timestamp: \"2025-10-21 15:53:53\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 765, message: \"rekaka\", timestamp: \"2025-10-21 15:55:18\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 766, message: \"pipipupu\", timestamp: \"2025-10-21 15:55:33\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 767, message: \"uuuh\", timestamp: \"2025-10-21 15:55:35\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 768, message: \"pipi\", timestamp: \"2025-10-21 15:55:36\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 769, message: \"pupu\", timestamp: \"2025-10-21 15:55:38\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 770, message: \"no seas gay\", timestamp: \"2025-10-21 16:10:32\", sender_id: 1, receiver_id: 2, is_own_message: false, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 771, message: \"hola\", timestamp: \"2025-10-21 16:11:42\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 772, message: \"amigo\", timestamp: \"2025-10-21 16:11:46\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 773, message: \"sulol?\", timestamp: \"2025-10-21 16:11:49\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 774, message: \"loco\", timestamp: \"2025-10-21 16:11:54\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { id: 775, message: \"crep qie amda rarp\", timestamp: \"2025-10-21 16:11:57\", sender_id: 2, receiver_id: 1, is_own_message: true, is_read: true, is_perseo_auto: false, reply_to_message_id: null, reply_to_message: null, … } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 Solicitud de origen cruzado bloqueada: La política de mismo origen no permite la lectura de recursos remotos en http://186.54.83.245:3001/socket.io/?EIO=4&transport=polling&t=Pe8DhIx. (Razón: Solicitud CORS sin éxito). Código de estado: (null). 📤 sendMessage() llamado chat.js:861:17    messageInput:  <input id=\"message-input\" class=\"chat-input\" type=\"text\" placeholder=\"Escribe un mensaje para Angel...\"> chat.js:862:17    currentChatUserId: 1 chat.js:863:17    Mensaje a enviar: hola´ chat.js:871:17 ⚠️ Socket.IO NO está conectado chat.js:892:21 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { message: \"hola´\", sender_id: \"2\", receiver_id: \"1\", timestamp: \"2025-10-21T19:20:42.355Z\", reply_to_message_id: null, id: \"776\" } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 💾 Guardando mensaje en BD... chat.js:898:17 📥 Respuesta de save-message.php:  Object { status: \"success\", message_id: \"776\", timestamp: \"2025-10-21 19:20:42\" } chat.js:912:21 🆔 ID del mensaje: 776 chat.js:922:25 📤 sendMessage() llamado chat.js:861:17    messageInput:  <input id=\"message-input\" class=\"chat-input\" type=\"text\" placeholder=\"Escribe un mensaje para Angel...\"> chat.js:862:17    currentChatUserId: 1 chat.js:863:17    Mensaje a enviar: mensaje nuevo chat.js:871:17 ⚠️ Socket.IO NO está conectado chat.js:892:21 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { message: \"mensaje nuevo\", sender_id: \"2\", receiver_id: \"1\", timestamp: \"2025-10-21T19:20:44.374Z\", reply_to_message_id: null, id: \"777\" } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 💾 Guardando mensaje en BD... chat.js:898:17 📥 Respuesta de save-message.php:  Object { status: \"success\", message_id: \"777\", timestamp: \"2025-10-21 19:20:44\" } chat.js:912:21 🆔 ID del mensaje: 777 chat.js:922:25 📤 sendMessage() llamado chat.js:861:17    messageInput:  <input id=\"message-input\" class=\"chat-input\" type=\"text\" placeholder=\"Escribe un mensaje para Angel...\"> chat.js:862:17    currentChatUserId: 1 chat.js:863:17    Mensaje a enviar: uwu chat.js:871:17 ⚠️ Socket.IO NO está conectado chat.js:892:21 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { message: \"uwu\", sender_id: \"2\", receiver_id: \"1\", timestamp: \"2025-10-21T19:20:45.918Z\", reply_to_message_id: null, id: \"778\" } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 💾 Guardando mensaje en BD... chat.js:898:17 📥 Respuesta de save-message.php:  Object { status: \"success\", message_id: \"778\", timestamp: \"2025-10-21 19:20:45\" } chat.js:912:21 🆔 ID del mensaje: 778 chat.js:922:25 📤 sendMessage() llamado chat.js:861:17    messageInput:  <input id=\"message-input\" class=\"chat-input\" type=\"text\" placeholder=\"Escribe un mensaje para Angel...\"> chat.js:862:17    currentChatUserId: 1 chat.js:863:17    Mensaje a enviar: estoy escribiendo angel chat.js:871:17 ⚠️ Socket.IO NO está conectado chat.js:892:21 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { message: \"estoy escribiendo angel\", sender_id: \"2\", receiver_id: \"1\", timestamp: \"2025-10-21T19:20:49.197Z\", reply_to_message_id: null, id: \"779\" } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 💾 Guardando mensaje en BD... chat.js:898:17 📥 Respuesta de save-message.php:  Object { status: \"success\", message_id: \"779\", timestamp: \"2025-10-21 19:20:48\" } chat.js:912:21 🆔 ID del mensaje: 779 chat.js:922:25 📤 sendMessage() llamado chat.js:861:17    messageInput:  <input id=\"message-input\" class=\"chat-input\" type=\"text\" placeholder=\"Escribe un mensaje para Angel...\"> chat.js:862:17    currentChatUserId: 1 chat.js:863:17    Mensaje a enviar: mira loco chat.js:871:17 ⚠️ Socket.IO NO está conectado chat.js:892:21 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { message: \"mira loco\", sender_id: \"2\", receiver_id: \"1\", timestamp: \"2025-10-21T19:20:52.358Z\", reply_to_message_id: null, id: \"780\" } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 💾 Guardando mensaje en BD... chat.js:898:17 📥 Respuesta de save-message.php:  Object { status: \"success\", message_id: \"780\", timestamp: \"2025-10-21 19:20:52\" } chat.js:912:21 🆔 ID del mensaje: 780 chat.js:922:25 📤 sendMessage() llamado chat.js:861:17    messageInput:  <input id=\"message-input\" class=\"chat-input\" type=\"text\" placeholder=\"Escribe un mensaje para Angel...\"> chat.js:862:17    currentChatUserId: 1 chat.js:863:17    Mensaje a enviar: porfa chat.js:871:17 ⚠️ Socket.IO NO está conectado chat.js:892:21 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { message: \"porfa\", sender_id: \"2\", receiver_id: \"1\", timestamp: \"2025-10-21T19:20:53.293Z\", reply_to_message_id: null, id: \"781\" } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 💾 Guardando mensaje en BD... chat.js:898:17 📥 Respuesta de save-message.php:  Object { status: \"success\", message_id: \"781\", timestamp: \"2025-10-21 19:20:53\" } chat.js:912:21 🆔 ID del mensaje: 781 chat.js:922:25 📤 sendMessage() llamado chat.js:861:17    messageInput:  <input id=\"message-input\" class=\"chat-input\" type=\"text\" placeholder=\"Escribe un mensaje para Angel...\"> chat.js:862:17    currentChatUserId: 1 chat.js:863:17    Mensaje a enviar: te estoy mandando mensajes chat.js:871:17 ⚠️ Socket.IO NO está conectado chat.js:892:21 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { message: \"te estoy mandando mensajes\", sender_id: \"2\", receiver_id: \"1\", timestamp: \"2025-10-21T19:20:58.603Z\", reply_to_message_id: null, id: \"782\" } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 💾 Guardando mensaje en BD... chat.js:898:17 📥 Respuesta de save-message.php:  Object { status: \"success\", message_id: \"782\", timestamp: \"2025-10-21 19:20:58\" } chat.js:912:21 🆔 ID del mensaje: 782 chat.js:922:25 Solicitud de origen cruzado bloqueada: La política de mismo origen no permite la lectura de recursos remotos en http://186.54.83.245:3001/socket.io/?EIO=4&transport=polling&t=Pe8Dmsn. (Razón: Solicitud CORS sin éxito). Código de estado: (null). 📤 sendMessage() llamado chat.js:861:17    messageInput:  <input id=\"message-input\" class=\"chat-input\" type=\"text\" placeholder=\"Escribe un mensaje para Angel...\"> chat.js:862:17    currentChatUserId: 1 chat.js:863:17    Mensaje a enviar: para que veas que funca chat.js:871:17 ⚠️ Socket.IO NO está conectado chat.js:892:21 📝 appendMessage llamado chat.js:756:17    chatMessages element:  <div id=\"chat-messages\" class=\"chat-messages\"> chat.js:757:17    messageData:  Object { message: \"para que veas que funca\", sender_id: \"2\", receiver_id: \"1\", timestamp: \"2025-10-21T19:21:01.639Z\", reply_to_message_id: null, id: \"783\" } chat.js:758:17    ✅ Agregando mensaje al DOM chat.js:854:17 💾 Gua', 'normal', NULL, 0, '2025-10-21 19:25:06', NULL, NULL, 0);
INSERT INTO `mensajes` (`id`, `sender_id`, `receiver_id`, `message`, `is_perseo_auto`, `is_read`, `read_at`, `reply_to_message_id`, `producto_id`, `remitente_id`, `destinatario_id`, `mensaje`, `tipo_mensaje`, `producto_relacionado_id`, `leido`, `created_at`, `edited_at`, `deleted_for`, `is_deleted`) VALUES
(812, 2, 1, '', 0, 1, '2025-10-21 20:09:01', NULL, 0, 0, 0, 'tu mama', 'normal', NULL, 0, '2025-10-21 19:36:15', NULL, NULL, 0),
(813, 2, 1, '', 0, 1, '2025-10-21 20:09:01', NULL, 0, 0, 0, 'La tuya', 'normal', NULL, 0, '2025-10-21 20:07:44', NULL, NULL, 0),
(814, 1, 2, '', 0, 1, '2025-10-21 20:10:25', NULL, 0, 0, 0, 'la tuya', 'normal', NULL, 0, '2025-10-21 20:09:07', NULL, NULL, 0),
(815, 2, 1, '', 0, 1, '2025-10-21 20:24:14', NULL, 0, 0, 0, 'Ok e', 'normal', NULL, 0, '2025-10-21 20:09:25', NULL, NULL, 0),
(816, 2, 1, '', 0, 1, '2025-10-21 20:24:14', NULL, 0, 0, 0, 'hola papi', 'normal', NULL, 0, '2025-10-21 20:11:48', NULL, NULL, 0),
(817, 2, 1, '', 0, 1, '2025-10-21 20:24:14', NULL, 0, 0, 0, 'dale bro', 'normal', NULL, 0, '2025-10-21 20:13:20', NULL, NULL, 0),
(818, 2, 1, '', 0, 1, '2025-10-21 20:24:14', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-10-21 20:14:36', NULL, NULL, 0),
(819, 2, 1, '', 0, 1, '2025-10-21 20:24:14', NULL, 0, 0, 0, 'dale', 'normal', NULL, 0, '2025-10-21 20:16:03', NULL, NULL, 0),
(820, 2, 1, '', 0, 1, '2025-10-21 20:24:14', NULL, 0, 0, 0, 'y ahora?4', 'normal', NULL, 0, '2025-10-21 20:17:04', NULL, NULL, 0),
(821, 2, 1, '', 0, 1, '2025-10-21 20:24:14', NULL, 0, 0, 0, 'Pepe', 'normal', NULL, 0, '2025-10-21 20:20:43', NULL, NULL, 0),
(822, 2, 1, '', 0, 1, '2025-10-21 20:24:14', NULL, 0, 0, 0, 'un re lore', 'normal', NULL, 0, '2025-10-21 20:22:21', NULL, NULL, 0),
(823, 2, 1, '', 0, 1, '2025-10-21 20:24:14', NULL, 0, 0, 0, 'Holi', 'normal', NULL, 0, '2025-10-21 20:24:14', NULL, NULL, 0),
(824, 2, 1, '', 0, 1, '2025-10-21 20:24:28', NULL, 0, 0, 0, 'hola bebe', 'normal', NULL, 0, '2025-10-21 20:24:28', NULL, NULL, 0),
(825, 1, 2, '', 0, 1, '2025-10-21 20:25:09', NULL, 0, 0, 0, 'holi', 'normal', NULL, 0, '2025-10-21 20:25:09', NULL, NULL, 0),
(826, 1, 2, '', 0, 1, '2025-10-22 00:06:37', NULL, 0, 0, 0, 'pepe', 'normal', NULL, 0, '2025-10-21 20:25:14', NULL, NULL, 0),
(827, 1, 2, '', 0, 1, '2025-10-22 00:06:37', NULL, 0, 0, 0, 're sexy todo no?', 'normal', NULL, 0, '2025-10-21 22:23:40', NULL, NULL, 0),
(828, 8, 1, '', 0, 1, '2025-10-21 22:23:52', NULL, 0, 0, 0, 'peru', 'normal', NULL, 0, '2025-10-21 22:23:47', NULL, NULL, 0),
(829, 1, 2, '', 0, 1, '2025-10-22 00:06:37', NULL, 0, 0, 0, 'pepe', 'normal', NULL, 0, '2025-10-21 22:23:48', NULL, NULL, 0),
(830, 1, 8, '', 0, 1, '2025-10-21 22:23:54', NULL, 0, 0, 0, 'peru', 'normal', NULL, 0, '2025-10-21 22:23:53', NULL, NULL, 0),
(831, 1, 8, '', 0, 1, '2025-10-21 22:24:03', NULL, 0, 0, 0, 'sos re ugu', 'normal', NULL, 0, '2025-10-21 22:23:57', NULL, NULL, 0),
(832, 1, 8, '', 0, 1, '2025-10-21 22:24:03', NULL, 0, 0, 0, 'no?', 'normal', NULL, 0, '2025-10-21 22:23:59', NULL, NULL, 0),
(833, 1, 8, '', 0, 1, '2025-10-21 22:24:06', NULL, 0, 0, 0, 'hay que poner las fotos de perfiles de nuevo', 'normal', NULL, 0, '2025-10-21 22:24:05', NULL, NULL, 0),
(834, 8, 1, '', 0, 1, '2025-10-21 22:24:56', NULL, 0, 0, 0, 'tengo que mostrarte algo', 'normal', NULL, 0, '2025-10-21 22:24:06', NULL, NULL, 0),
(835, 8, 1, '', 0, 1, '2025-10-21 22:24:56', NULL, 0, 0, 0, 'si, soy muy ugu', 'normal', NULL, 0, '2025-10-21 22:24:15', NULL, NULL, 0),
(836, 8, 1, '', 0, 1, '2025-10-21 22:24:56', NULL, 0, 0, 0, 'si, soy muy ugu', 'normal', NULL, 0, '2025-10-21 22:24:17', NULL, NULL, 0),
(837, 1, 8, '', 0, 1, '2025-10-21 22:24:53', NULL, 0, 0, 0, 'porque cambie la manera en la que se guardan y tuve que eliminarlas todas porque me daba pereza cambiar la url de todo', 'normal', NULL, 0, '2025-10-21 22:24:52', NULL, NULL, 0),
(838, 8, 1, '', 0, 1, '2025-10-21 22:25:06', NULL, 0, 0, 0, 'ugu', 'normal', NULL, 0, '2025-10-21 22:25:06', NULL, NULL, 0),
(839, 1, 8, '', 0, 1, '2025-10-21 22:25:11', NULL, 0, 0, 0, 'ahora si', 'normal', NULL, 0, '2025-10-21 22:25:09', NULL, NULL, 0),
(840, 8, 1, '', 0, 1, '2025-10-22 00:03:11', NULL, 0, 0, 0, 'En las aguas profundas se esconde, un susurro lejano, un eco vibrante, como el canto ancestral de un alma errante, es el ugu, guardián del horizonte.  Con su voz callada se enreda en el viento, dibujando en las sombras su suave tormento. Entre las ramas, en el eco del tiempo, se funden sus palabras con el firmamento.  Es un río sin cauce, un fuego apagado, un misterio guardado en un rincón olvidado. Su poder es silente, su nombre sagrado, en cada respiro, el ugu es invocado.  Es alma de los bosques, el brillo escondido, la paz que se alcanza y el sueño perdido. En la quietud del alma, su canto es oído, y el ugu se convierte en lo nunca olvidado.', 'normal', NULL, 0, '2025-10-21 22:27:32', NULL, NULL, 0),
(841, 8, 1, '', 0, 1, '2025-10-22 00:03:11', NULL, 0, 0, 0, '(¡Yeah! ¡Es el Ugu en la casa! Siente el flow, escucha el ritmo…)  Verso 1 Desde el barrio se escucha un grito, ¡Ugu! con estilo nunca compito. Rápido como el viento, firme como el sol, Le pongo fuego a la pista, suena el control.  Con mi gente siempre fiel, no hay traición, En el camino voy dejando mi canción. El futuro es mi meta, ¡no hay freno! Siempre rompo el juego, y yo me mantengo.  Estribillo ¡Ugu, Ugu, en la movida! Siempre con la mente encendida, Rompo barreras, nada me frena, Este es mi momento, Ugu me suena.  Verso 2 No me importa lo que digan, yo voy a mi ritmo, Con cada paso, subo más, ¡síguelo, primo! La vida es dura, pero yo la afronto, Cada batalla me hace más fuerte, pronto.  De la calle al escenario, soy un soldado, Y aunque el camino sea largo, nunca he tropezado. Me caí, me levanté, y lo grité, ¡Ugu está aquí, y ahora todo lo daré!  Estribillo ¡Ugu, Ugu, en la movida! Siempre con la mente encendida, Rompo barreras, nada me frena, Este es mi momento, Ugu me suena.', 'normal', NULL, 0, '2025-10-21 22:38:56', NULL, NULL, 0),
(842, 8, 1, '', 0, 1, '2025-10-22 00:03:11', NULL, 0, 0, 0, 'uf', 'normal', NULL, 0, '2025-10-21 22:39:00', NULL, NULL, 0),
(843, 8, 1, '', 0, 1, '2025-10-22 00:03:11', NULL, 0, 0, 0, 'ese le sabe', 'normal', NULL, 0, '2025-10-21 22:39:10', NULL, NULL, 0),
(844, 8, 1, '', 0, 1, '2025-10-22 00:03:11', NULL, 0, 0, 0, 'ese le sabe', 'normal', NULL, 0, '2025-10-21 22:39:10', NULL, NULL, 0),
(845, 8, 1, '', 0, 1, '2025-10-22 00:03:11', NULL, 0, 0, 0, 'ese le sabe', 'normal', NULL, 0, '2025-10-21 22:39:10', NULL, NULL, 0),
(846, 8, 1, '', 0, 1, '2025-10-22 00:03:11', NULL, 0, 0, 0, 'ese le sabe', 'normal', NULL, 0, '2025-10-21 22:39:10', NULL, NULL, 0),
(847, 8, 1, '', 0, 1, '2025-10-22 00:03:11', NULL, 0, 0, 0, 'ese le sabe', 'normal', NULL, 0, '2025-10-21 22:39:10', NULL, NULL, 0),
(848, 8, 1, '', 0, 1, '2025-10-22 00:03:11', NULL, 0, 0, 0, 'ese le sabe', 'normal', NULL, 0, '2025-10-21 22:39:31', NULL, NULL, 0),
(849, 1, 8, '', 0, 1, '2025-11-05 19:06:24', NULL, 0, 0, 0, 'sda', 'normal', NULL, 0, '2025-10-22 01:45:36', NULL, NULL, 0),
(850, 2, 1, '', 0, 1, '2025-10-22 01:49:59', NULL, 0, 0, 0, 'Kqkqk', 'normal', NULL, 0, '2025-10-22 01:47:00', NULL, NULL, 0),
(851, 2, 1, '', 0, 1, '2025-10-22 01:49:59', NULL, 0, 0, 0, 'Uuu', 'normal', NULL, 0, '2025-10-22 01:49:57', NULL, NULL, 0),
(852, 1, 2, '', 0, 1, '2025-10-22 01:50:02', NULL, 0, 0, 0, 'si bro', 'normal', NULL, 0, '2025-10-22 01:50:01', NULL, NULL, 0),
(853, 1, 8, '', 0, 1, '2025-11-05 19:06:24', NULL, 0, 0, 0, 'a', 'normal', NULL, 0, '2025-10-22 01:50:04', NULL, NULL, 0),
(854, 2, 1, '', 0, 1, '2025-10-22 01:50:53', NULL, 0, 0, 0, 'Jwjwjwjw', 'normal', NULL, 0, '2025-10-22 01:50:48', NULL, NULL, 0),
(855, 2, 1, '', 0, 1, '2025-10-22 01:51:51', NULL, 0, 0, 0, 'Renhioos', 'normal', NULL, 0, '2025-10-22 01:51:46', NULL, NULL, 0),
(856, 1, 8, '', 0, 1, '2025-11-05 19:06:24', NULL, 0, 0, 0, 'si', 'normal', NULL, 0, '2025-10-22 01:51:49', NULL, NULL, 0),
(857, 1, 2, '', 0, 1, '2025-10-22 01:52:28', NULL, 0, 0, 0, 'ufa', 'normal', NULL, 0, '2025-10-22 01:51:53', NULL, NULL, 0),
(858, 2, 1, '', 0, 1, '2025-10-22 01:54:18', NULL, 0, 0, 0, 'Hwjbwjbjbekxbibeefibkb3fibkb3fibib3fibib2fibib3fibhhucyc', 'normal', NULL, 0, '2025-10-22 01:53:05', NULL, NULL, 0),
(859, 1, 3, '', 0, 0, NULL, NULL, 0, 0, 0, 'hola compañera', 'normal', NULL, 0, '2025-11-04 22:59:02', NULL, NULL, 0),
(860, 1, 2, '', 0, 1, '2025-11-05 04:16:18', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-11-05 04:16:18', NULL, NULL, 0),
(861, 2, 1, '', 0, 1, '2025-11-05 04:16:22', NULL, 0, 0, 0, 'hola bb', 'normal', NULL, 0, '2025-11-05 04:16:22', NULL, NULL, 0),
(862, 8, 1, '', 0, 1, '2025-11-05 22:34:45', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-11-05 19:06:26', NULL, NULL, 0),
(863, 8, 1, '', 0, 1, '2025-11-05 22:34:45', NULL, 0, 0, 0, 'quiero la guitarra', 'normal', NULL, 0, '2025-11-05 19:06:32', NULL, NULL, 0),
(864, 7, 8, '', 0, 1, '2025-11-06 21:04:28', NULL, 0, 0, 0, 'sisissisiis', 'normal', NULL, 0, '2025-11-06 21:04:14', NULL, NULL, 0),
(865, 7, 2, '', 0, 1, '2025-11-06 21:04:43', NULL, 0, 0, 0, 'tu mami', 'normal', NULL, 0, '2025-11-06 21:04:23', NULL, NULL, 0),
(866, 8, 7, '', 0, 0, NULL, NULL, 0, 0, 0, 'non', 'normal', NULL, 0, '2025-11-06 21:04:30', NULL, NULL, 0),
(867, 7, 1, '', 0, 1, '2025-11-06 21:05:02', NULL, 0, 0, 0, 'gay', 'normal', NULL, 0, '2025-11-06 21:04:30', NULL, NULL, 0),
(868, 1, 7, '', 0, 0, NULL, NULL, 0, 0, 0, 'dejame', 'normal', NULL, 0, '2025-11-06 21:05:05', NULL, NULL, 0),
(869, 1, 7, '', 0, 0, NULL, NULL, 0, 0, 0, 'gay tu mamá', 'normal', NULL, 0, '2025-11-06 21:05:09', NULL, NULL, 0),
(870, 8, 7, '', 0, 0, NULL, NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-11-06 21:06:39', NULL, NULL, 0),
(871, 8, 2, '', 0, 1, '2025-11-06 21:07:21', NULL, 0, 0, 0, 'hola', 'normal', NULL, 0, '2025-11-06 21:06:46', NULL, NULL, 0),
(872, 2, 8, '', 0, 0, NULL, NULL, 0, 0, 0, 'hola  b', 'normal', NULL, 0, '2025-11-06 21:07:24', NULL, NULL, 0),
(873, 2, 8, '', 0, 0, NULL, NULL, 0, 0, 0, 'en que andas', 'normal', NULL, 0, '2025-11-06 21:07:40', NULL, NULL, 0),
(874, 2, 8, '', 0, 0, NULL, NULL, 0, 0, 0, 'unu', 'normal', NULL, 0, '2025-11-06 21:07:43', NULL, NULL, 0),
(875, 0, 0, '', 0, 0, NULL, NULL, 34, 1, 13, '🔄 **PROPUESTA DE INTERCAMBIO**\n\nQuiero intercambiar mi producto:\n📦 **Zapato kike**\n\nPor tu producto:\n📦 **Motocicleta Tsukiyumi MotorSola Edición especial modificada para carreras**\n\n---\nMi producto ha sido marcado como RESERVADO para este intercambio.\n¿Estás interesado en realizar el intercambio?', 'propuesta_intercambio', 1, 0, '2025-11-06 22:38:19', NULL, NULL, 0),
(876, 0, 0, '', 0, 0, NULL, NULL, 34, 1, 13, '🔄 **PROPUESTA DE INTERCAMBIO**\n\nQuiero intercambiar mi producto:\n📦 **Zapato kike**\n\nPor tu producto:\n📦 **Motocicleta Tsukiyumi MotorSola Edición especial modificada para carreras**\n\n---\nMi producto ha sido marcado como RESERVADO para este intercambio.\n¿Estás interesado en realizar el intercambio?', 'propuesta_intercambio', 1, 0, '2025-11-06 22:40:05', NULL, NULL, 0),
(877, 1, 13, '🔄 **PROPUESTA DE INTERCAMBIO**\n\nQuiero intercambiar mi producto:\n📦 **Zapato kike**\n\nPor tu producto:\n📦 **Motocicleta Tsukiyumi MotorSola Edición especial modificada para carreras**\n\n---\nMi producto ha sido marcado como RESERVADO para este intercambio.\n¿Estás interesado en realizar el intercambio?', 0, 0, NULL, NULL, 34, 0, 0, '', 'propuesta_intercambio', 1, 0, '2025-11-06 22:42:14', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_eliminados`
--

DROP TABLE IF EXISTS `mensajes_eliminados`;
CREATE TABLE IF NOT EXISTS `mensajes_eliminados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `mensaje_id` int NOT NULL,
  `eliminado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_mensaje` (`user_id`,`mensaje_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_mensaje_id` (`mensaje_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'Usuario que recibe la notificación',
  `tipo` enum('mensaje','producto','valoracion','sistema') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mensaje',
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenido` text COLLATE utf8mb4_unicode_ci,
  `leida` tinyint(1) DEFAULT '0',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL a donde redirigir al hacer clic',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `leida_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_leida` (`leida`),
  KEY `idx_created` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sistema de notificaciones para usuarios';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `imagen` varchar(255) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `estado` enum('disponible','intercambiado','reservado') DEFAULT 'disponible',
  `condicion` enum('nuevo','como nuevo','poco uso','usado','muy desgastado') DEFAULT 'usado',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `promedio_estrellas` decimal(2,1) DEFAULT '0.0' COMMENT 'Promedio de valoraciones (0.0 a 5.0)',
  `total_valoraciones` int DEFAULT '0' COMMENT 'Total de valoraciones recibidas',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `user_id`, `nombre`, `descripcion`, `imagen`, `categoria`, `estado`, `condicion`, `created_at`, `updated_at`, `promedio_estrellas`, `total_valoraciones`) VALUES
(1, 1, 'Zapato kike', 'El Zapato Kike es un calzado de uso urbano diseñado para ofrecer ergonomía, durabilidad y soporte estructural durante actividades cotidianas. Fabricado con materiales sintéticos de alta resistencia y acabado hidrorrepelente, garantiza protección ante condiciones de uso moderadas en interiores y exteriores.\r\nFicha técnica resumida\r\n• Uso: Urbano / diario\r\n• Material del corte: Sintético hidrorrepelente\r\n• Forro interno: Textil respirable\r\n• Suela: TPR o EVA con diseño antideslizante', 'img/productos/zapatosdeportivosnike.jpg', 'Ropa', 'disponible', 'usado', '2025-10-02 19:10:12', '2025-11-05 03:54:24', 4.7, 3),
(2, 1, 'Guitarra Acústica', 'Guitarra acústica en buen estado, ideal para principiantes. Incluye funda protectora.', 'img/productos/guitarraacustica.jpg', 'Música', 'disponible', 'usado', '2025-10-02 19:10:12', '2025-11-05 03:54:24', 4.5, 2),
(3, 1, 'Libro \"El Principito\"', 'Clásico de la literatura en perfecto estado. Edición especial con ilustraciones.', 'uploads/productos/prod_3_1762382104_690bd118a3b3e.png', 'Libros', 'disponible', 'usado', '2025-10-02 19:10:12', '2025-11-05 22:35:04', 4.5, 2),
(4, 2, 'Smartphone Samsung', 'Samsung Galaxy en excelente estado, con cargador y protector. Funciona perfectamente.', 'img/productos/smartphonesamsung.jpg', 'Electrónicos', 'disponible', 'usado', '2025-10-02 19:10:12', '2025-11-05 14:55:47', 5.0, 1),
(5, 2, 'Chaqueta de Cuero', 'Chaqueta de cuero genuino, talla M. Muy poco uso, perfecta para invierno.', 'img/productos/chaquetadecuero.jpg', 'Ropa', 'disponible', 'usado', '2025-10-02 19:10:12', '2025-11-04 23:55:44', 0.0, 0),
(6, 2, 'Bicicleta de Montaña', 'Bicicleta en muy buen estado, ideal para aventuras al aire libre. Incluye casco.', 'img/productos/bicicletademontana.jpg', 'Deportes', 'disponible', 'usado', '2025-10-02 19:10:12', '2025-11-04 23:55:44', 0.0, 0),
(7, 9, 'Cafetera Express', 'Cafetera express automática, hace café delicioso. Incluye manual de uso.', 'img/productos/cafeteraexpress.jpg', 'Hogar', 'disponible', 'usado', '2025-10-02 19:10:12', '2025-10-16 22:55:18', 0.0, 0),
(8, 3, 'Juego de Mesa Monopoly', 'Monopoly clásico en excelente estado, completo con todas las piezas.', 'img/productos/monopoly.jpg', 'Juguetes', 'disponible', 'usado', '2025-10-02 19:10:12', '2025-10-16 22:54:02', 0.0, 0),
(9, 10, 'Taladro Eléctrico', 'Taladro eléctrico con set de brocas. Perfecto para proyectos de hogar.', 'img/productos/taladroelectrico.jpg', 'Herramientas', 'disponible', 'usado', '2025-10-02 19:10:12', '2025-10-16 22:55:26', 0.0, 0),
(10, 4, 'Reloj Vintage', 'Reloj de pulsera vintage en perfecto funcionamiento. Estilo clásico y elegante.', 'img/productos/relojvintage.jpg', 'Accesorios', 'disponible', 'usado', '2025-10-02 19:10:12', '2025-10-02 23:11:14', 0.0, 0),
(11, 12, 'Persona 5 Royal - Edición Deluxe', 'Juego completo de Persona 5 Royal con todos los DLCs incluidos. Perfecto estado, casi sin usar. Incluye caja original y manual.', 'uploads/productos/prod_11_1762368966.jpg', 'Electrónicos', 'disponible', 'usado', '2025-11-05 17:43:04', '2025-11-05 18:56:29', 0.0, 0),
(12, 12, 'Evoker de Persona 3 (Réplica)', 'Réplica oficial del Evoker de Persona 3. Coleccionable de alta calidad, material resistente. Ideal para fans de la saga Shin Megami Tensei.', 'uploads/productos/prod_12_1762379459.jpg', 'Juguetes', 'disponible', 'usado', '2025-11-05 17:43:04', '2025-11-05 21:50:59', 0.0, 0),
(13, 12, 'Soundtrack Persona 3 FES - Vinilo', 'Edición limitada en vinilo del soundtrack de Persona 3 FES. Incluye temas icónicos como \"Burn My Dread\" y \"Mass Destruction\". Estado impecable.', 'uploads/productos/prod_13_1762368926.jpg', 'Música, Saga Persona, Persona 3', 'disponible', 'usado', '2025-11-05 17:43:04', '2025-11-06 01:36:56', 3.0, 1),
(14, 12, 'Figura Orpheus Telos', 'Figura articulada de Orpheus Telos de 25cm de altura. Pintado a mano, detalles increíbles. Incluye base y accesorios intercambiables.', 'uploads/productos/prod_14_1762368915.jpg', 'Juguetes', 'disponible', 'usado', '2025-11-05 17:43:04', '2025-11-05 18:55:15', 0.0, 0),
(15, 12, 'Manga Persona 3 - Colección Completa', 'Colección completa del manga de Persona 3 (6 tomos). En español, estado excelente. Incluye páginas a color y portadas alternativas.', 'uploads/productos/prod_15_1762368897.png', 'Libros', 'disponible', 'usado', '2025-11-05 17:43:04', '2025-11-05 18:54:57', 2.0, 1),
(29, 2, 'Carta Pokemon TCG', 'Carta de Pokemon Caterpie PSA10 verificado por profesionales de Lima Peru', 'img/productos/prod_29_1762387733_690be715aea5e.webp', 'Juguetes', 'disponible', 'usado', '2025-11-06 00:08:53', '2025-11-06 00:08:53', 0.0, 0),
(30, 2, 'Mouse gamer 8000dpi', 'Mouse gamer para entorno competitivo con estética de Dragon Ball para gamers fan de la saga Dragon Ball.\r\nPosee 8000dpi y un sensor gamer de la marca kinkenyo.', 'img/productos/prod_30_1762462232_690d0a1849db7.webp', 'Herramientas, Gamer, Dragon Ball, Mouse, Periféric', 'disponible', 'usado', '2025-11-06 20:50:32', '2025-11-06 21:25:33', 1.0, 1),
(31, 2, 'Tarjeta gráfica USUS FUT GaCorce CTX 4090 Ti', 'Gráfica de video dedicada para gamers de entorno competitivo de la marca USUS y de la poderosa línea de gráficas FUT (Fatiguin Under Traction) con 128 GB de VRAM a 10hz, ventiladores de plástico antideslizante con a 2.5 RPM.\r\nEsta gráfica posee, a su vez, chips gráficos de la marca MVDIYA, perfectos para tareas pesadas como abrir Excel, Word o bloc de notas, pero sin dejar de lado el gaming, siendo capaz de de ejecutar Buscaminas a 3FPS.', 'img/productos/prod_31_1762462906_690d0cbaaf567.webp', 'Tarjeta Gráfica, Electrónicos, Hardware, USUS, Gam', 'disponible', 'usado', '2025-11-06 21:01:46', '2025-11-06 21:03:20', 1.0, 1),
(32, 8, 'Teto', 'Peluche Teto', 'img/productos/prod_32_1762462986_690d0d0a6ced4.jpeg', 'Juguetes', 'disponible', 'usado', '2025-11-06 21:03:06', '2025-11-06 21:46:27', 5.0, 1),
(33, 13, 'Ferrirri Mini-E0.1', 'Vehículo de la alta gamma de la marca Ferrirri.\r\nPieza única creada por el fundador Pienzo Ferrirri para sentir el lujo de la marca.\r\nPintura de ferreteria, rines de plomo, neumáticos de la excelente marca JOTO y frenos de la famosa y confiable marca Bambi.\r\nSu motor V20 alcanza los 100cm/h en 2 horas y posee un tanque de diesel de 3ml.', 'img/productos/prod_33_1762464161_690d11a12b04c.webp', 'Vehículo, Edición Especial, Una persona, 2 puertas', 'disponible', 'usado', '2025-11-06 21:22:41', '2025-11-06 21:22:46', 0.0, 0),
(34, 13, 'Motocicleta Tsukiyumi MotorSola Edición especial modificada para carreras', 'Motocicleta tipo Tsukiyumi de la marca MotorSola modificada especialmente para carreras de formula 1 con diseño especial de la cantante famosa de Vocaloid, Hatsune Miku.\r\nPosee un motor de gas con una capacidad de medio ml, carcasa de plástico, frenos de la marca TOE y neumatios de la marca JOTO', 'img/productos/prod_34_1762464910_690d148eaad56.webp', 'Vehículo, Moto, MotorSola', 'disponible', 'usado', '2025-11-06 21:35:10', '2025-11-06 21:35:16', 0.0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_favoritos`
--

DROP TABLE IF EXISTS `productos_favoritos`;
CREATE TABLE IF NOT EXISTS `productos_favoritos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorito` (`user_id`,`producto_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_producto_id` (`producto_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Productos marcados como favoritos';

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `productos_recomendados`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `productos_recomendados`;
CREATE TABLE IF NOT EXISTS `productos_recomendados` (
`avatar_path` varchar(255)
,`categoria` varchar(50)
,`descripcion` text
,`estado` enum('disponible','intercambiado','reservado')
,`guardados_semana` bigint
,`id` int
,`imagen` varchar(255)
,`nombre` varchar(100)
,`promedio_estrellas` decimal(2,1)
,`score_total` decimal(10,2)
,`total_chats` int
,`total_guardados` int
,`total_valoraciones` int
,`total_vistas` int
,`user_id` int
,`vendedor_name` varchar(100)
,`vendedor_username` varchar(50)
,`vistas_semana` bigint
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_chats`
--

DROP TABLE IF EXISTS `producto_chats`;
CREATE TABLE IF NOT EXISTS `producto_chats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `vendedor_id` int NOT NULL,
  `fecha_chat` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_chat` (`producto_id`,`usuario_id`),
  KEY `vendedor_id` (`vendedor_id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_chats`
--

INSERT INTO `producto_chats` (`id`, `producto_id`, `usuario_id`, `vendedor_id`, `fecha_chat`) VALUES
(1, 2, 8, 1, '2025-11-05 19:06:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_guardados`
--

DROP TABLE IF EXISTS `producto_guardados`;
CREATE TABLE IF NOT EXISTS `producto_guardados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `fecha_guardado` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_guardado` (`producto_id`,`usuario_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_producto` (`producto_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_guardados`
--

INSERT INTO `producto_guardados` (`id`, `producto_id`, `usuario_id`, `fecha_guardado`) VALUES
(2, 9, 11, '2025-11-05 12:29:07'),
(3, 9, 3, '2025-11-05 12:29:07'),
(4, 7, 3, '2025-11-05 12:29:07'),
(5, 2, 3, '2025-11-05 12:29:07'),
(6, 8, 5, '2025-11-05 12:29:07'),
(11, 4, 1, '2025-11-05 16:50:11'),
(12, 8, 1, '2025-11-05 17:24:43'),
(14, 10, 1, '2025-11-05 17:24:56'),
(15, 13, 1, '2025-11-06 01:37:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_imagenes`
--

DROP TABLE IF EXISTS `producto_imagenes`;
CREATE TABLE IF NOT EXISTS `producto_imagenes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_principal` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_producto_principal` (`producto_id`,`es_principal`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_imagenes`
--

INSERT INTO `producto_imagenes` (`id`, `producto_id`, `imagen`, `es_principal`, `created_at`) VALUES
(1, 3, 'uploads/productos/prod_3_1762382074_690bd0fa90d2e.png', 0, '2025-11-05 22:34:34'),
(2, 3, 'uploads/productos/prod_3_1762382074_690bd0fa9627d.jpg', 0, '2025-11-05 22:34:34'),
(3, 3, 'uploads/productos/prod_3_1762382104_690bd118a3b3e.png', 1, '2025-11-05 22:35:04'),
(4, 3, 'uploads/productos/prod_3_1762382104_690bd118c668e.jpg', 0, '2025-11-05 22:35:04'),
(5, 3, 'uploads/productos/prod_3_1762382104_690bd118cd354.jpg', 0, '2025-11-05 22:35:04'),
(15, 29, 'img/productos/prod_29_1762387733_690be715aea5e.webp', 1, '2025-11-06 00:08:53'),
(16, 30, 'img/productos/prod_30_1762462232_690d0a1849db7.webp', 1, '2025-11-06 20:50:32'),
(17, 31, 'img/productos/prod_31_1762462906_690d0cbaaf567.webp', 1, '2025-11-06 21:01:46'),
(18, 32, 'img/productos/prod_32_1762462986_690d0d0a6ced4.jpeg', 1, '2025-11-06 21:03:06'),
(19, 33, 'img/productos/prod_33_1762464161_690d11a12b04c.webp', 1, '2025-11-06 21:22:41'),
(20, 34, 'img/productos/prod_34_1762464910_690d148eaad56.webp', 1, '2025-11-06 21:35:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_scores`
--

DROP TABLE IF EXISTS `producto_scores`;
CREATE TABLE IF NOT EXISTS `producto_scores` (
  `producto_id` int NOT NULL,
  `total_vistas` int DEFAULT '0',
  `total_guardados` int DEFAULT '0',
  `total_chats` int DEFAULT '0',
  `score_total` decimal(10,2) DEFAULT '0.00',
  `ultima_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`producto_id`),
  KEY `idx_score` (`score_total` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_scores`
--

INSERT INTO `producto_scores` (`producto_id`, `total_vistas`, `total_guardados`, `total_chats`, `score_total`, `ultima_actualizacion`) VALUES
(1, 3, 0, 0, 9.00, '2025-11-06 22:42:09'),
(2, 23, 1, 1, 35.00, '2025-11-06 22:42:09'),
(3, 22, 0, 0, 26.00, '2025-11-06 22:42:09'),
(4, 26, 1, 0, 31.00, '2025-11-06 22:42:09'),
(5, 4, 0, 0, 4.00, '2025-11-06 22:42:09'),
(6, 4, 0, 0, 4.00, '2025-11-06 22:42:09'),
(7, 7, 1, 0, 10.00, '2025-11-06 22:42:09'),
(8, 9, 2, 0, 15.00, '2025-11-06 22:42:09'),
(9, 11, 2, 0, 17.00, '2025-11-06 22:42:09'),
(10, 8, 1, 0, 11.00, '2025-11-06 22:42:09'),
(11, 5, 0, 0, 5.00, '2025-11-06 22:42:09'),
(12, 8, 0, 0, 8.00, '2025-11-06 22:42:09'),
(13, 18, 1, 0, 23.00, '2025-11-06 22:42:09'),
(14, 4, 0, 0, 4.00, '2025-11-06 22:42:09'),
(15, 7, 0, 0, 9.00, '2025-11-06 22:42:09'),
(29, 5, 0, 0, 5.00, '2025-11-06 22:42:09'),
(30, 21, 0, 0, 23.00, '2025-11-06 22:42:09'),
(31, 12, 0, 0, 14.00, '2025-11-06 22:42:09'),
(32, 24, 0, 0, 26.00, '2025-11-06 22:42:09'),
(33, 5, 0, 0, 5.00, '2025-11-06 22:42:09'),
(34, 16, 0, 0, 16.00, '2025-11-06 22:42:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_similitudes`
--

DROP TABLE IF EXISTS `producto_similitudes`;
CREATE TABLE IF NOT EXISTS `producto_similitudes` (
  `producto_a_id` int NOT NULL,
  `producto_b_id` int NOT NULL,
  `similitud_score` decimal(5,2) DEFAULT '0.00',
  `veces_visto_juntos` int DEFAULT '0',
  `ultima_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`producto_a_id`,`producto_b_id`),
  KEY `producto_b_id` (`producto_b_id`),
  KEY `idx_similitud` (`similitud_score` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_similitudes`
--

INSERT INTO `producto_similitudes` (`producto_a_id`, `producto_b_id`, `similitud_score`, `veces_visto_juntos`, `ultima_actualizacion`) VALUES
(1, 4, 10.00, 1, '2025-11-05 16:27:44'),
(1, 6, 20.00, 2, '2025-11-05 16:27:44'),
(1, 8, 20.00, 2, '2025-11-05 16:27:44'),
(1, 9, 10.00, 1, '2025-11-05 16:27:44'),
(1, 10, 20.00, 2, '2025-11-05 16:27:44'),
(2, 3, 10.00, 1, '2025-11-05 16:27:44'),
(2, 5, 10.00, 1, '2025-11-05 16:27:44'),
(2, 7, 10.00, 1, '2025-11-05 16:27:44'),
(2, 8, 10.00, 1, '2025-11-05 16:27:44'),
(2, 9, 10.00, 1, '2025-11-05 16:27:44'),
(2, 10, 20.00, 2, '2025-11-05 16:27:44'),
(3, 4, 20.00, 2, '2025-11-05 16:27:44'),
(3, 5, 20.00, 2, '2025-11-05 16:27:44'),
(3, 6, 10.00, 1, '2025-11-05 16:27:44'),
(3, 7, 20.00, 2, '2025-11-05 16:27:44'),
(3, 8, 10.00, 1, '2025-11-05 16:27:44'),
(3, 9, 20.00, 2, '2025-11-05 16:27:44'),
(3, 10, 10.00, 1, '2025-11-05 16:27:44'),
(4, 5, 10.00, 1, '2025-11-05 16:27:44'),
(4, 6, 20.00, 2, '2025-11-05 16:27:44'),
(4, 7, 10.00, 1, '2025-11-05 16:27:44'),
(4, 8, 10.00, 1, '2025-11-05 16:27:44'),
(4, 9, 20.00, 2, '2025-11-05 16:27:44'),
(4, 10, 20.00, 2, '2025-11-05 16:27:44'),
(5, 6, 10.00, 1, '2025-11-05 16:27:44'),
(5, 7, 20.00, 2, '2025-11-05 16:27:44'),
(5, 9, 20.00, 2, '2025-11-05 16:27:44'),
(5, 10, 10.00, 1, '2025-11-05 16:27:44'),
(6, 7, 10.00, 1, '2025-11-05 16:27:44'),
(6, 8, 10.00, 1, '2025-11-05 16:27:44'),
(6, 9, 20.00, 2, '2025-11-05 16:27:44'),
(6, 10, 20.00, 2, '2025-11-05 16:27:44'),
(7, 9, 20.00, 2, '2025-11-05 16:27:44'),
(7, 10, 10.00, 1, '2025-11-05 16:27:44'),
(8, 10, 20.00, 2, '2025-11-05 16:27:44'),
(9, 10, 20.00, 2, '2025-11-05 16:27:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_vistas`
--

DROP TABLE IF EXISTS `producto_vistas`;
CREATE TABLE IF NOT EXISTS `producto_vistas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_vista` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `duracion_segundos` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_fecha` (`fecha_vista`)
) ENGINE=InnoDB AUTO_INCREMENT=256 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_vistas`
--

INSERT INTO `producto_vistas` (`id`, `producto_id`, `usuario_id`, `session_id`, `fecha_vista`, `duracion_segundos`) VALUES
(1, 4, 8, NULL, '2025-10-11 12:29:07', 115),
(2, 10, 2, NULL, '2025-10-23 12:29:07', 46),
(3, 9, 2, NULL, '2025-10-30 12:29:07', 71),
(4, 6, 2, NULL, '2025-10-18 12:29:07', 22),
(5, 4, 2, NULL, '2025-10-21 12:29:07', 5),
(6, 1, 2, NULL, '2025-10-14 12:29:07', 68),
(7, 10, 7, NULL, '2025-10-20 12:29:07', 79),
(8, 8, 7, NULL, '2025-11-02 12:29:07', 116),
(9, 1, 7, NULL, '2025-11-03 12:29:07', 58),
(10, 10, 1, NULL, '2025-10-29 12:29:07', 107),
(11, 3, 1, NULL, '2025-10-08 12:29:07', 86),
(12, 9, 11, NULL, '2025-10-10 12:29:07', 83),
(13, 10, 9, NULL, '2025-10-26 12:29:07', 5),
(14, 2, 9, NULL, '2025-10-15 12:29:07', 83),
(15, 9, 3, NULL, '2025-11-02 12:29:07', 41),
(16, 7, 3, NULL, '2025-10-31 12:29:07', 65),
(17, 5, 3, NULL, '2025-10-07 12:29:07', 21),
(18, 3, 3, NULL, '2025-10-08 12:29:07', 25),
(19, 2, 3, NULL, '2025-10-24 12:29:07', 30),
(20, 10, 4, NULL, '2025-10-10 12:29:07', 64),
(21, 8, 4, NULL, '2025-10-22 12:29:07', 61),
(22, 2, 4, NULL, '2025-10-13 12:29:07', 55),
(23, 8, 5, NULL, '2025-10-12 12:29:07', 61),
(24, 6, 5, NULL, '2025-10-31 12:29:07', 12),
(25, 1, 5, NULL, '2025-10-19 12:29:07', 110),
(26, 8, 6, NULL, '2025-11-02 12:29:07', 70),
(27, 4, 6, NULL, '2025-10-23 12:29:07', 88),
(28, 3, 6, NULL, '2025-10-25 12:29:07', 20),
(32, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 14:53:58', 0),
(33, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 14:54:07', 0),
(34, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 14:55:39', 0),
(35, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 15:12:55', 0),
(36, 7, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:03:29', 0),
(37, 6, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:03:31', 0),
(38, 9, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:03:34', 0),
(39, 9, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:03:46', 0),
(40, 4, 1, 'm82mdgrrgemd7qa0vipaiuqkd6', '2025-11-05 16:22:32', 0),
(41, 5, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:25:13', 0),
(42, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:37:45', 0),
(43, 6, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:37:50', 0),
(44, 9, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:37:55', 0),
(45, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:38:10', 0),
(46, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:47:21', 0),
(47, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:47:25', 0),
(48, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:47:27', 0),
(49, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:47:29', 0),
(50, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:47:33', 0),
(51, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:47:54', 0),
(52, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:50:08', 0),
(53, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:50:11', 0),
(54, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:51:00', 0),
(55, 8, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:51:03', 0),
(56, 8, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:51:07', 0),
(57, 9, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:51:13', 0),
(58, 9, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:51:17', 0),
(59, 9, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:51:21', 0),
(60, 9, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:51:27', 0),
(61, 9, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:55:08', 0),
(62, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 16:55:17', 0),
(63, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:24:36', 0),
(64, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:24:39', 0),
(65, 8, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:24:42', 0),
(66, 8, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:24:45', 0),
(67, 8, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:24:48', 0),
(68, 10, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:24:51', 0),
(69, 10, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:24:54', 0),
(70, 10, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:25:00', 0),
(71, 7, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:25:56', 0),
(72, 5, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:28:44', 0),
(73, 5, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 17:36:15', 0),
(74, 13, NULL, '131mokimho4crhf1kjveuv73b3', '2025-11-05 17:52:01', 0),
(75, 13, NULL, '131mokimho4crhf1kjveuv73b3', '2025-11-05 17:52:55', 0),
(76, 4, NULL, 'l7fncuv4gg03a2ecrks6b1gnvo', '2025-11-05 18:36:08', 0),
(77, 4, NULL, 'l7fncuv4gg03a2ecrks6b1gnvo', '2025-11-05 18:36:12', 0),
(78, 2, NULL, 'l7fncuv4gg03a2ecrks6b1gnvo', '2025-11-05 18:36:14', 0),
(79, 2, NULL, 'l7fncuv4gg03a2ecrks6b1gnvo', '2025-11-05 18:36:17', 0),
(80, 2, NULL, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-05 18:39:47', 0),
(81, 2, NULL, 'l7fncuv4gg03a2ecrks6b1gnvo', '2025-11-05 18:42:00', 0),
(82, 2, NULL, 'l7fncuv4gg03a2ecrks6b1gnvo', '2025-11-05 18:42:04', 0),
(83, 2, NULL, 'l7fncuv4gg03a2ecrks6b1gnvo', '2025-11-05 18:42:21', 0),
(84, 2, NULL, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-05 18:42:33', 0),
(85, 14, NULL, 'l7fncuv4gg03a2ecrks6b1gnvo', '2025-11-05 18:45:27', 0),
(86, 2, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 18:51:00', 0),
(87, 2, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 18:51:05', 0),
(88, 2, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 18:51:06', 0),
(89, 15, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 18:52:18', 0),
(90, 15, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 18:52:21', 0),
(91, 15, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 18:52:38', 0),
(92, 15, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 18:52:41', 0),
(93, 15, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 18:52:47', 0),
(94, 3, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 18:58:58', 0),
(95, 3, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 18:59:02', 0),
(96, 4, 1, 'h3dsvttfh7ur2tkavf77q8bohb', '2025-11-05 19:01:35', 0),
(97, 3, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 19:03:38', 0),
(98, 12, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 19:03:45', 0),
(99, 12, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 19:03:49', 0),
(100, 2, NULL, 'jntar7mns34mdjno3s31fv65oo', '2025-11-05 19:06:03', 0),
(101, 2, 8, 'jntar7mns34mdjno3s31fv65oo', '2025-11-05 19:06:20', 0),
(102, 13, 8, 'jntar7mns34mdjno3s31fv65oo', '2025-11-05 19:06:57', 0),
(103, 13, 8, 'jntar7mns34mdjno3s31fv65oo', '2025-11-05 19:07:00', 0),
(104, 13, 8, 'jntar7mns34mdjno3s31fv65oo', '2025-11-05 19:07:20', 0),
(105, 12, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 19:07:20', 0),
(106, 12, 2, '131mokimho4crhf1kjveuv73b3', '2025-11-05 20:15:15', 0),
(107, 13, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 21:34:04', 0),
(108, 12, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 21:34:44', 0),
(109, 2, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 21:49:23', 0),
(110, 2, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 21:49:27', 0),
(111, 2, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 21:49:28', 0),
(112, 12, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 21:50:05', 0),
(113, 12, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 21:50:08', 0),
(114, 12, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 21:51:34', 0),
(115, 13, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 22:25:54', 0),
(116, 13, 12, 'e3703psucfncdu325rfo3eu1qq', '2025-11-05 22:25:57', 0),
(117, 11, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:38:25', 0),
(118, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:39:26', 0),
(119, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:39:29', 0),
(120, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:40:16', 0),
(121, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:40:20', 0),
(122, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:41:17', 0),
(123, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:41:21', 0),
(124, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:41:40', 0),
(125, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:41:44', 0),
(126, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:41:47', 0),
(127, 4, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:42:11', 0),
(128, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:43:00', 0),
(129, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:43:04', 0),
(130, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:43:14', 0),
(131, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:47:23', 0),
(132, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:47:26', 0),
(133, 3, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:47:33', 0),
(134, 13, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:54:25', 0),
(135, 13, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 22:54:28', 0),
(146, 2, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-05 23:59:59', 0),
(147, 2, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 00:00:00', 0),
(148, 11, NULL, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:27:27', 0),
(149, 11, NULL, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:27:30', 0),
(150, 11, NULL, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:27:45', 0),
(151, 2, NULL, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:27:52', 0),
(152, 2, NULL, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:27:56', 0),
(153, 2, NULL, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:28:51', 0),
(154, 13, NULL, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:36:02', 0),
(155, 13, NULL, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:36:05', 0),
(156, 13, NULL, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:36:20', 0),
(157, 13, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:36:36', 0),
(158, 13, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:36:39', 0),
(159, 13, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:36:58', 0),
(160, 13, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:37:01', 0),
(161, 13, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:37:03', 0),
(162, 29, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:37:07', 0),
(163, 29, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:37:10', 0),
(164, 29, 1, '2fofdj3f9biv09illjglbdaptn', '2025-11-06 01:37:14', 0),
(165, 29, 2, '131mokimho4crhf1kjveuv73b3', '2025-11-06 20:50:47', 0),
(166, 29, 2, '131mokimho4crhf1kjveuv73b3', '2025-11-06 20:51:19', 0),
(167, 31, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:02:20', 0),
(168, 31, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:02:24', 0),
(169, 31, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:03:22', 0),
(170, 32, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:03:30', 0),
(171, 32, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:03:34', 0),
(172, 31, 2, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:03:35', 0),
(173, 32, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:03:41', 0),
(174, 31, 2, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:04:08', 0),
(175, 31, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:04:35', 0),
(176, 31, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:04:38', 0),
(177, 31, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:05:00', 0),
(178, 32, 8, 'dmab4j8ba4okl1qto0ltr902oh', '2025-11-06 21:05:00', 0),
(179, 32, 8, 'dmab4j8ba4okl1qto0ltr902oh', '2025-11-06 21:05:04', 0),
(180, 31, 2, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:05:24', 0),
(181, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:05:37', 0),
(182, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:05:41', 0),
(183, 31, 2, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:06:09', 0),
(184, 31, 2, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:06:12', 0),
(185, 32, 8, 'dmab4j8ba4okl1qto0ltr902oh', '2025-11-06 21:06:13', 0),
(186, 32, 8, 'dmab4j8ba4okl1qto0ltr902oh', '2025-11-06 21:06:16', 0),
(187, 32, 8, 'dmab4j8ba4okl1qto0ltr902oh', '2025-11-06 21:06:29', 0),
(188, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:06:51', 0),
(189, 31, 2, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:07:19', 0),
(190, 32, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:07:34', 0),
(191, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:08:17', 0),
(192, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:08:21', 0),
(193, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:11:37', 0),
(194, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:11:41', 0),
(195, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:11:52', 0),
(196, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:11:55', 0),
(197, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:12:23', 0),
(198, 32, 8, 'dmab4j8ba4okl1qto0ltr902oh', '2025-11-06 21:20:04', 0),
(199, 32, 8, 'dmab4j8ba4okl1qto0ltr902oh', '2025-11-06 21:20:07', 0),
(200, 32, 8, 'dmab4j8ba4okl1qto0ltr902oh', '2025-11-06 21:20:09', 0),
(201, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:21:56', 0),
(202, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:21:59', 0),
(203, 32, 13, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:23:01', 0),
(204, 32, 13, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:23:05', 0),
(205, 30, 13, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:23:10', 0),
(206, 30, 13, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:23:35', 0),
(207, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:25:22', 0),
(208, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:25:25', 0),
(209, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:26:04', 0),
(210, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:26:07', 0),
(211, 30, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:26:10', 0),
(212, 33, 2, 'kmr2kjib8th7vbsloobidg3mek', '2025-11-06 21:26:34', 0),
(213, 32, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:27:31', 0),
(214, 32, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:27:35', 0),
(215, 32, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:27:40', 0),
(216, 14, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:33:17', 0),
(217, 14, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:33:21', 0),
(218, 14, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:34:12', 0),
(219, 30, 13, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:35:33', 0),
(220, 30, 13, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:35:44', 0),
(221, 11, 13, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:36:02', 0),
(222, 15, 13, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:36:08', 0),
(223, 15, 13, '131mokimho4crhf1kjveuv73b3', '2025-11-06 21:36:11', 0),
(224, 33, 2, 'kmr2kjib8th7vbsloobidg3mek', '2025-11-06 21:36:20', 0),
(225, 33, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:38:57', 0),
(226, 33, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:39:00', 0),
(227, 33, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:40:30', 0),
(228, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:40:32', 0),
(229, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:40:35', 0),
(230, 4, NULL, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:44:57', 0),
(231, 7, NULL, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:45:14', 0),
(232, 7, NULL, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:45:17', 0),
(233, 7, NULL, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:45:36', 0),
(234, 32, 3, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:45:54', 0),
(235, 32, 3, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:45:58', 0),
(236, 32, 3, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:46:28', 0),
(237, 32, 3, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:46:28', 0),
(238, 32, 3, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:46:28', 0),
(239, 32, 3, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:46:32', 0),
(240, 32, 3, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:47:03', 0),
(241, 7, 3, 'pejlkcv6cj3llcfdguqhifqmgp', '2025-11-06 21:48:41', 0),
(242, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:55:08', 0),
(243, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:55:12', 0),
(244, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:55:45', 0),
(245, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 21:55:49', 0),
(246, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 22:27:17', 0),
(247, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 22:27:20', 0),
(248, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 22:28:26', 0),
(249, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 22:28:30', 0),
(250, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 22:38:04', 0),
(251, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 22:38:08', 0),
(252, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 22:39:58', 0),
(253, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 22:40:02', 0),
(254, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 22:42:05', 0),
(255, 34, 1, 'ckiq4iebmkmamgkrgvvctka4ug', '2025-11-06 22:42:09', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos_encuentro`
--

DROP TABLE IF EXISTS `puntos_encuentro`;
CREATE TABLE IF NOT EXISTS `puntos_encuentro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL COMMENT 'Producto al que pertenece el punto de encuentro',
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre del lugar (ej: "Starbucks Centro", "Plaza Principal")',
  `descripcion` text COLLATE utf8mb4_unicode_ci COMMENT 'Descripción adicional del lugar',
  `direccion` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Dirección completa',
  `latitud` decimal(10,8) NOT NULL COMMENT 'Latitud GPS',
  `longitud` decimal(11,8) NOT NULL COMMENT 'Longitud GPS',
  `referencia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Punto de referencia (ej: "Frente al banco")',
  `horario_sugerido` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Horario sugerido (ej: "Lun-Vie 9am-6pm")',
  `es_principal` tinyint(1) DEFAULT '0' COMMENT '1 si es el punto principal/preferido',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `puntos_encuentro`
--

INSERT INTO `puntos_encuentro` (`id`, `producto_id`, `nombre`, `descripcion`, `direccion`, `latitud`, `longitud`, `referencia`, `horario_sugerido`, `es_principal`, `created_at`, `updated_at`) VALUES
(1, 1, 'Starbucks Ágora Mall', 'Punto céntrico y seguro, con cámaras de seguridad', 'Ágora Mall, Av. John F. Kennedy, Santo Domingo', 18.47155600, -69.94044400, 'Primer nivel, cerca de la entrada principal', 'Lun-Dom 10am-8pm', 1, '2025-11-05 04:03:22', '2025-11-05 04:03:22'),
(2, 1, 'McDonald\'s Blue Mall', 'Zona segura con bastante tráfico', 'Blue Mall, Av. Winston Churchill, Santo Domingo', 18.47832800, -69.95706700, 'Food court, segundo nivel', 'Lun-Dom 11am-9pm', 0, '2025-11-05 04:03:22', '2025-11-05 04:03:22'),
(3, 2, 'Parque Mirador Sur', 'Área pública y segura', 'Av. Mirador Sur, Santo Domingo', 18.46205900, -69.95437300, 'Entrada principal, junto a los kioscos', 'Lun-Dom 6am-6pm', 1, '2025-11-05 04:03:22', '2025-11-05 04:03:22'),
(4, 2, 'Plaza Central', 'Centro comercial con seguridad', 'Av. 27 de Febrero, Santo Domingo', 18.48332200, -69.93097800, 'Lobby principal', 'Lun-Dom 10am-8pm', 0, '2025-11-05 04:03:22', '2025-11-05 04:03:22'),
(5, 3, 'Sambil Santo Domingo', 'Centro comercial seguro', 'Av. John F. Kennedy, Santo Domingo', 18.46969400, -69.93921700, 'Food court, tercer nivel', 'Lun-Dom 10am-9pm', 1, '2025-11-05 04:03:22', '2025-11-05 04:03:22'),
(6, 3, 'Malecón de Santo Domingo', 'Zona turística con vigilancia', 'Av. George Washington, Santo Domingo', 18.46369400, -69.89225000, 'Frente al Obelisco Macho', 'Lun-Dom 8am-6pm (solo de día)', 0, '2025-11-05 04:03:22', '2025-11-05 04:03:22'),
(7, 1, 'Starbucks Montevideo Shopping', 'Centro comercial céntrico y seguro con cámaras', 'Montevideo Shopping, Av. Luis Alberto de Herrera 1290, Montevideo', -34.90166700, -56.16277800, 'Planta baja, cerca de la entrada principal', 'Lun-Dom 10am-9pm', 1, '2025-11-05 04:05:20', '2025-11-05 04:05:20'),
(8, 1, 'Plaza Independencia', 'Plaza céntrica con mucha vigilancia policial', 'Plaza Independencia, Ciudad Vieja, Montevideo', -34.90694400, -56.20138900, 'Frente a la Torre Ejecutiva', 'Lun-Dom 9am-7pm (solo de día)', 0, '2025-11-05 04:05:20', '2025-11-05 04:05:20'),
(9, 1, 'McDonald\'s Tres Cruces', 'Terminal de ómnibus con alta seguridad', 'Terminal Tres Cruces, Bulevar Artigas 1825, Montevideo', -34.89444400, -56.16666700, 'Food court, segundo nivel', 'Lun-Dom 7am-11pm', 0, '2025-11-05 04:05:20', '2025-11-05 04:05:20'),
(10, 2, 'Rambla de Montevideo', 'Paseo costero seguro y público', 'Rambla República del Perú, Pocitos, Montevideo', -34.91166700, -56.15833300, 'Altura del Puertito del Buceo', 'Lun-Dom 8am-7pm', 1, '2025-11-05 04:05:20', '2025-11-05 04:05:20'),
(11, 2, 'Punta Carretas Shopping', 'Centro comercial de alto nivel con seguridad', 'Punta Carretas Shopping, Ellauri 350, Montevideo', -34.91944400, -56.16111100, 'Patio de comidas, planta alta', 'Lun-Dom 10am-10pm', 0, '2025-11-05 04:05:20', '2025-11-05 04:05:20'),
(13, 3, 'Parque Rodó', 'Parque público con buena iluminación y tránsito', 'Parque Rodó, Av. Sarmiento, Montevideo', -34.91527800, -56.16583300, 'Entrada principal, junto al Teatro de Verano', 'Lun-Dom 9am-7pm (solo de día)', 1, '2025-11-05 04:05:20', '2025-11-05 04:05:20'),
(14, 3, 'Nuevocentro Shopping', 'Centro comercial en zona norte de Montevideo', 'Nuevocentro Shopping, Av. Luis Alberto de Herrera 3365, Montevideo', -34.88305600, -56.13472200, 'Food court, planta baja', 'Lun-Dom 10am-10pm', 0, '2025-11-05 04:05:20', '2025-11-05 04:05:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

DROP TABLE IF EXISTS `reportes`;
CREATE TABLE IF NOT EXISTS `reportes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reportador_id` int NOT NULL COMMENT 'Usuario que hace el reporte',
  `tipo` enum('usuario','producto','mensaje') COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia_id` int NOT NULL COMMENT 'ID del usuario/producto/mensaje reportado',
  `motivo` enum('spam','contenido_inapropiado','estafa','otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('pendiente','en_revision','resuelto','rechazado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `revisado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reportador` (`reportador_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_estado` (`estado`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sistema de reportes y denuncias';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_activas`
--

DROP TABLE IF EXISTS `sesiones_activas`;
CREATE TABLE IF NOT EXISTS `sesiones_activas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `socket_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID del socket para WebSocket/Socket.io',
  `ultima_actividad` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`(250)),
  KEY `idx_ultima_actividad` (`ultima_actividad`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Control de sesiones activas para chat en tiempo real';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_amistad`
--

DROP TABLE IF EXISTS `solicitudes_amistad`;
CREATE TABLE IF NOT EXISTS `solicitudes_amistad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `solicitante_id` int NOT NULL,
  `receptor_id` int NOT NULL,
  `estado` enum('pendiente','aceptada','rechazada') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_solicitud` (`solicitante_id`,`receptor_id`),
  KEY `idx_solicitante` (`solicitante_id`),
  KEY `idx_receptor` (`receptor_id`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes_amistad`
--

INSERT INTO `solicitudes_amistad` (`id`, `solicitante_id`, `receptor_id`, `estado`, `created_at`, `updated_at`) VALUES
(13, 1, 8, 'aceptada', '2025-10-15 23:45:59', '2025-10-15 23:47:25'),
(14, 1, 7, 'aceptada', '2025-10-16 21:12:42', '2025-10-16 21:12:57'),
(15, 7, 2, 'aceptada', '2025-10-16 21:13:03', '2025-10-21 23:59:38'),
(16, 9, 1, 'rechazada', '2025-10-16 22:48:24', '2025-10-21 23:31:34'),
(17, 10, 1, 'aceptada', '2025-10-16 22:50:57', '2025-10-21 22:56:38'),
(18, 9, 10, 'aceptada', '2025-10-16 22:56:27', '2025-10-16 22:56:57'),
(19, 10, 3, 'aceptada', '2025-10-16 22:57:49', '2025-10-16 22:58:19'),
(20, 3, 9, 'pendiente', '2025-10-16 23:06:19', '2025-10-16 23:06:19'),
(23, 3, 2, 'aceptada', '2025-10-16 23:26:55', '2025-10-16 23:27:53'),
(29, 2, 1, 'aceptada', '2025-10-21 17:57:20', '2025-10-21 22:54:36'),
(30, 8, 1, 'aceptada', '2025-10-21 23:50:47', '2025-10-21 23:56:41'),
(32, 1, 3, 'aceptada', '2025-11-04 22:58:02', '2025-11-04 22:58:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `avatar_path` varchar(255) DEFAULT NULL COMMENT 'Ruta del archivo de avatar del usuario. NULL = usar avatar por defecto',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `fullname`, `username`, `email`, `phone`, `password`, `birthdate`, `created_at`, `updated_at`, `avatar_path`) VALUES
(1, 'Angel Alemán', 'Angel', 'angel@example.com', '+598123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1995-05-15', '2025-10-02 19:10:12', '2025-10-20 23:19:36', 'uploads/avatars/avatar_Angel_Angel_20251020_231936_1_68f6c38803c7f.jpg'),
(2, 'Alejo García', 'Alejo', 'alejo@example.com', '+598987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1998-03-20', '2025-10-02 19:10:12', '2025-11-05 22:38:29', 'uploads/avatars/avatar_Alejo_Alejo_20251105_223829_2_690bd1e5899ba.jpg'),
(3, 'Milagros Pérez', 'Milagros', 'milagros@example.com', '+598456789123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1997-08-10', '2025-10-02 19:10:12', '2025-11-06 21:49:27', 'uploads/avatars/avatar_Milagros_Milagros_20251106_214927_3_690d17e740a1e.jpg'),
(4, 'Usuario Test', 'test', 'test@example.com', '+598999999999', '$2y$10$tSYWBxR9Im1bivzvfd0CmOTDBVp79ta/wc.tA86PbNSyfaSYcCYYa', '2000-01-01', '2025-10-02 19:10:12', '2025-10-02 19:10:12', NULL),
(5, 'Zamira Valentina Alemán Sanchéz', 'Zami', 'zaaleman@impulso.edu.uy', '095651678', 'Zami', '2006-12-14', '2025-10-15 01:09:07', '2025-10-15 01:12:33', NULL),
(6, 'Zamira Aleman', 'Zami12', 'mongolia323efe@gmail.com', '095651678', '$2y$10$ze0/fdMdxzH6Aw6yuIGNDeY27cnFils9TcwTwwSpF2.9Asn35NI.q', '2006-12-12', '2025-10-15 01:14:27', '2025-10-15 01:14:27', NULL),
(7, 'Alexis Sosa', 'Alexis8090', 'jorgealexissosaojeda@gmail.com', '096422782', '$2y$10$bo.nWC80YA4pgLpZtcJQ1ulqYjfoBsPEIBd9OF1NGLAKoDtdn4E66', '2006-09-22', '2025-10-15 03:03:42', '2025-10-21 23:48:33', NULL),
(8, 'Agustin Anselmi', 'agusro', 'agusro220182@gmail.com', '091793907', '$2y$10$phvgP.DAWI7vrnEidoQEvuciDXQZ5atq1WC.t83Z2QU3rgD0yXAqS', '2007-08-18', '2025-10-15 23:44:03', '2025-10-20 23:01:21', NULL),
(9, 'Guillermo Reherman', 'guillesito', 'greherman@impulso.edu.uy', '1111111', '$2y$10$Mhpse4W8Ub1k2j6mV7tXy.DOywtEOJ61fe41AmpCc1qmOpDB1nqai', '2007-08-04', '2025-10-16 22:47:20', '2025-10-20 23:01:19', NULL),
(10, 'Lucas Larraura', 'LucasLR2', 'lucaslr2@gmail.com', '123123123', '$2y$10$V4Y/xpRf5m/CoNmoT8A/le4aZcvLG8N1XokXadrRmkXSgtpbsndKe', '1111-11-11', '2025-10-16 22:50:33', '2025-10-20 23:01:18', NULL),
(11, 'Sabrina Evelyn', 'García Aguiar', 'sabrgar82@gmail.com', '098841897', '$2y$10$Hmk5IU1mRINKaQz93q2kEu9hSCqUvosImBY.hUpT4/3psvkNN0h36', '2006-11-03', '2025-10-21 18:32:07', '2025-10-21 18:32:07', NULL),
(12, 'Francisco Torrecillas', 'PanchiTorre', 'francisco.torrecillas@example.com', '', '$2y$10$j/f/pC18v6Rd4a65sl3gnO4KRSEkUmSORfVMombcdbxRz621jxvfy', '0000-00-00', '2025-11-05 17:43:04', '2025-11-05 18:17:28', 'uploads/avatars/avatar_Francisco_PanchiTorre_20251105_181728_12_690b94b82269c.jpg'),
(13, 'Tommy Vercetti', 'Tommmafy', 'TommyVerc@gmail.com', '091 732 890', '$2y$10$GyJPPWmN73gscGbGa8zImerhM93PclsHiUH3bu5PlM8MBZ1gChHti', '1958-11-21', '2025-11-06 21:12:53', '2025-11-06 21:28:37', 'uploads/avatars/avatar_Tommy_Tommmafy_20251106_212837_13_690d130566eb9.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoraciones`
--

DROP TABLE IF EXISTS `valoraciones`;
CREATE TABLE IF NOT EXISTS `valoraciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `valorador_id` int NOT NULL,
  `puntuacion` decimal(2,1) DEFAULT NULL,
  `comentario` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `valorador_id` (`valorador_id`)
) ;

--
-- Volcado de datos para la tabla `valoraciones`
--

INSERT INTO `valoraciones` (`id`, `usuario_id`, `valorador_id`, `puntuacion`, `comentario`, `created_at`) VALUES
(1, 1, 2, 5.0, 'Excelente persona para hacer trueques, muy recomendable', '2000-10-02 19:10:12'),
(2, 1, 3, 4.0, 'Buen trato y productos de calidad para intercambiar', '2025-10-02 19:10:12'),
(4, 3, 4, 4.0, 'Gran experiencia de trueque, productos tal como se describían', '2025-10-02 19:10:12'),
(11, 7, 1, 0.5, 'Mono 🤬😡', '2025-10-15 05:47:50'),
(12, 7, 2, 0.5, 'Me quiso vender una piedra de mi casa como una piedra magica a cambio de mi ferrari.', '2025-10-15 20:29:07'),
(13, 7, 2, 0.5, 'Me dijo que le mostrara mi chilito', '2025-10-15 20:35:14'),
(14, 2, 7, 0.5, 'Me quiso intercambiar una pc gamer que tenia 2gb de RAM ddr2 por una laptop con un I5 de octava', '2025-10-15 20:36:12'),
(15, 2, 1, 5.0, 'Alejo García = 💯/10 + premio Nobel del Trueque 💼✨\nBro... ¿cómo explicarlo? Hacer un intercambio con Alejo fue como si el universo alineara sus chakras y me dijera: &quot;este es el elegido&quot;. Llegó puntual, con la energía de un anime protagonista y una sonrisa que podría firmar Colgate. 😁\nEl objeto estaba tan perfecto que casi le propongo matrimonio (spoiler: no lo hice, pero ganas no faltaron).\nSi todos fueran como Alejo, el mundo no tendría guerras, solo intercambios épicos. 🙌💖', '2025-10-15 22:39:49'),
(16, 2, 1, 5.0, 'Recomendado hasta por mi tía la desconfiada.', '2025-10-15 22:39:59'),
(17, 2, 1, 5.0, '¡Alejo García es un crack del trueque!\nIntercambiar con Alejo fue tan fácil como quitarle un dulce a un unicornio (o algo así). Puntual, simpático y con más honestidad que una abuela contando chismes. El objeto estaba tan impecable que pensé que era nuevo o bendecido por algún monje tibetano.\n¡Si todos los intercambios fueran así, el mundo sería un lugar mejor y con menos cosas acumuladas! 🔄✨', '2025-10-15 22:40:19'),
(18, 2, 1, 5.0, '¡Todo perfecto con Alejo García!\nUna experiencia de intercambio excelente. Alejo fue puntual, claro en la comunicación y súper amable. El artículo estaba en perfectas condiciones, tal como lo había descrito. ¡Así da gusto hacer intercambios! 100% recomendado, sin duda repetiría con él.', '2025-10-15 22:41:07'),
(19, 1, 8, 0.5, 'no puedo poner 0, F', '2025-10-15 23:47:37'),
(20, 1, 9, 0.5, 'es re puto', '2025-10-16 22:48:43'),
(21, 1, 10, 5.0, 'Le doy lo máximo a Ángel Alemán, me cae re bien y siempre es súper generoso en los trueques, incluso regalándome cosas. ¡Recomendadísimo para intercambios!', '2025-10-16 22:52:58'),
(22, 10, 3, 5.0, '', '2025-10-16 22:58:33'),
(24, 3, 10, 1.0, 'No me quiere intercambiar el monopoly una desubicada total. 🙄', '2025-10-16 22:59:08'),
(25, 10, 9, 5.0, 'la chupa re bien', '2025-10-16 22:59:15'),
(26, 9, 3, 0.5, 'es muy gay, que asco', '2025-10-16 23:06:32'),
(28, 9, 3, 0.5, 'quiere cambiar su cuerpo por objetos, ademas re peludo tenia el culo', '2025-10-16 23:09:48'),
(29, 1, 8, 5.0, '', '2025-10-21 22:57:56'),
(30, 1, 8, 0.5, '', '2025-10-21 22:58:32'),
(31, 1, 8, 0.5, '', '2025-10-21 22:58:36'),
(32, 1, 8, 0.5, '', '2025-10-21 22:58:42'),
(33, 1, 8, 0.5, '', '2025-10-21 22:58:47'),
(34, 1, 8, 0.5, '', '2025-10-21 22:58:55'),
(35, 1, 8, 0.5, '', '2025-10-21 22:59:00'),
(36, 1, 8, 0.5, '', '2025-10-21 22:59:04'),
(37, 1, 8, 5.0, 'chi ugu', '2025-10-21 23:46:55'),
(38, 1, 8, 5.0, 'skibidi', '2025-10-21 23:47:03'),
(39, 2, 1, 3.0, 'me guta', '2025-11-05 03:12:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoraciones_productos`
--

DROP TABLE IF EXISTS `valoraciones_productos`;
CREATE TABLE IF NOT EXISTS `valoraciones_productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL COMMENT 'Producto que se está valorando',
  `usuario_id` int NOT NULL COMMENT 'Usuario que hace la valoración',
  `puntuacion` int NOT NULL COMMENT 'Puntuación de 1 a 5 estrellas',
  `comentario` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Comentario opcional del usuario',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_valoracion_producto` (`producto_id`,`usuario_id`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `valoraciones_productos`
--

INSERT INTO `valoraciones_productos` (`id`, `producto_id`, `usuario_id`, `puntuacion`, `comentario`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 5, 'Excelente smartphone, funciona perfectamente. ¡Muy recomendado!', '2025-11-01 13:30:00', '2025-11-05 03:54:23'),
(2, 1, 3, 4, 'Buen estado general, solo algunas marcas mínimas de uso.', '2025-11-02 17:15:00', '2025-11-05 03:54:23'),
(3, 1, 4, 5, 'Tal como se describe en la publicación, muy satisfecho.', '2025-11-03 12:45:00', '2025-11-05 03:54:23'),
(4, 2, 1, 4, 'Zapatillas en buen estado, cómodas para correr.', '2025-11-01 19:20:00', '2025-11-05 03:54:23'),
(5, 2, 3, 5, 'Perfectas para deportes, excelente calidad Nike.', '2025-11-02 14:00:00', '2025-11-05 03:54:23'),
(6, 3, 2, 5, 'Guitarra con excelente sonido, ideal para principiantes.', '2025-11-01 16:45:00', '2025-11-05 03:54:23'),
(7, 3, 4, 4, 'Buen instrumento, cuerdas en perfecto estado.', '2025-11-03 18:30:00', '2025-11-05 03:54:23'),
(8, 4, 1, 5, 'Ta bien', '2025-11-05 03:54:39', '2025-11-05 14:55:47'),
(9, 15, 1, 2, 'Re rico ugu', '2025-11-05 18:52:37', '2025-11-05 18:52:37'),
(10, 13, 1, 3, 'Me gustó, pero de vez en cuando se raya ://', '2025-11-06 01:36:56', '2025-11-06 01:36:56'),
(11, 31, 1, 1, '3FPS, Fotogramas por semana 😡🤬', '2025-11-06 21:03:20', '2025-11-06 21:03:20'),
(12, 30, 1, 1, '4,000000dpi 🤬', '2025-11-06 21:06:49', '2025-11-06 21:06:49'),
(13, 32, 3, 5, NULL, '2025-11-06 21:46:27', '2025-11-06 21:46:27');

-- --------------------------------------------------------

--
-- Estructura para la vista `estadisticas_usuarios`
--
DROP TABLE IF EXISTS `estadisticas_usuarios`;

DROP VIEW IF EXISTS `estadisticas_usuarios`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `estadisticas_usuarios`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`fullname` AS `fullname`, count(distinct `p`.`id`) AS `total_productos`, count(distinct (case when (`p`.`estado` = 'disponible') then `p`.`id` end)) AS `productos_disponibles`, count(distinct `v`.`id`) AS `total_valoraciones`, coalesce(avg(`v`.`puntuacion`),0) AS `promedio_valoracion`, count(distinct `i`.`id`) AS `total_intercambios`, `u`.`created_at` AS `miembro_desde` FROM (((`usuarios` `u` left join `productos` `p` on((`u`.`id` = `p`.`user_id`))) left join `valoraciones` `v` on((`u`.`id` = `v`.`usuario_id`))) left join `intercambios` `i` on(((`u`.`id` = `i`.`usuario_ofrecedor_id`) or (`u`.`id` = `i`.`usuario_solicitante_id`)))) GROUP BY `u`.`id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `productos_recomendados`
--
DROP TABLE IF EXISTS `productos_recomendados`;

DROP VIEW IF EXISTS `productos_recomendados`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `productos_recomendados`  AS SELECT `p`.`id` AS `id`, `p`.`nombre` AS `nombre`, `p`.`descripcion` AS `descripcion`, `p`.`imagen` AS `imagen`, `p`.`categoria` AS `categoria`, `p`.`estado` AS `estado`, `p`.`user_id` AS `user_id`, `u`.`username` AS `vendedor_username`, `u`.`fullname` AS `vendedor_name`, `u`.`avatar_path` AS `avatar_path`, `p`.`promedio_estrellas` AS `promedio_estrellas`, `p`.`total_valoraciones` AS `total_valoraciones`, `ps`.`score_total` AS `score_total`, `ps`.`total_vistas` AS `total_vistas`, `ps`.`total_guardados` AS `total_guardados`, `ps`.`total_chats` AS `total_chats`, (select count(0) from `producto_vistas` `pv` where ((`pv`.`producto_id` = `p`.`id`) and (`pv`.`fecha_vista` >= (now() - interval 7 day)))) AS `vistas_semana`, (select count(0) from `producto_guardados` `pg` where ((`pg`.`producto_id` = `p`.`id`) and (`pg`.`fecha_guardado` >= (now() - interval 7 day)))) AS `guardados_semana` FROM ((`productos` `p` left join `usuarios` `u` on((`p`.`user_id` = `u`.`id`))) left join `producto_scores` `ps` on((`p`.`id` = `ps`.`producto_id`))) WHERE (`p`.`estado` = 'disponible') ORDER BY `ps`.`score_total` DESC, `p`.`created_at` DESC ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `amistades`
--
ALTER TABLE `amistades`
  ADD CONSTRAINT `amistades_ibfk_1` FOREIGN KEY (`usuario1_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `amistades_ibfk_2` FOREIGN KEY (`usuario2_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `chats_temporales`
--
ALTER TABLE `chats_temporales`
  ADD CONSTRAINT `chats_temporales_ibfk_1` FOREIGN KEY (`usuario1_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chats_temporales_ibfk_2` FOREIGN KEY (`usuario2_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chats_temporales_ibfk_3` FOREIGN KEY (`producto_relacionado_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `denuncias`
--
ALTER TABLE `denuncias`
  ADD CONSTRAINT `denuncias_ibfk_1` FOREIGN KEY (`denunciante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `denuncias_ibfk_2` FOREIGN KEY (`denunciado_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estadisticas_usuario`
--
ALTER TABLE `estadisticas_usuario`
  ADD CONSTRAINT `estadisticas_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto_chats`
--
ALTER TABLE `producto_chats`
  ADD CONSTRAINT `producto_chats_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `producto_chats_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `producto_chats_ibfk_3` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto_guardados`
--
ALTER TABLE `producto_guardados`
  ADD CONSTRAINT `producto_guardados_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `producto_guardados_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD CONSTRAINT `producto_imagenes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto_scores`
--
ALTER TABLE `producto_scores`
  ADD CONSTRAINT `producto_scores_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto_similitudes`
--
ALTER TABLE `producto_similitudes`
  ADD CONSTRAINT `producto_similitudes_ibfk_1` FOREIGN KEY (`producto_a_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `producto_similitudes_ibfk_2` FOREIGN KEY (`producto_b_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto_vistas`
--
ALTER TABLE `producto_vistas`
  ADD CONSTRAINT `producto_vistas_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `producto_vistas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `puntos_encuentro`
--
ALTER TABLE `puntos_encuentro`
  ADD CONSTRAINT `fk_punto_encuentro_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitudes_amistad`
--
ALTER TABLE `solicitudes_amistad`
  ADD CONSTRAINT `solicitudes_amistad_ibfk_1` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitudes_amistad_ibfk_2` FOREIGN KEY (`receptor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `valoraciones_productos`
--
ALTER TABLE `valoraciones_productos`
  ADD CONSTRAINT `fk_valoracion_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_valoracion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
