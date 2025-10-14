-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 02-10-2025 a las 23:18:02
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.3.14

-- Crear y usar la base de datos
CREATE DATABASE IF NOT EXISTS handinhand;
USE handinhand;

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
-- Estructura de tabla para la tabla `mensajes`
--

DROP TABLE IF EXISTS `mensajes`;
CREATE TABLE IF NOT EXISTS `mensajes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `remitente_id` int NOT NULL,
  `destinatario_id` int NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  KEY `remitente_id` (`remitente_id`),
  KEY `destinatario_id` (`destinatario_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `user_id`, `nombre`, `descripcion`, `imagen`, `categoria`, `estado`, `created_at`, `updated_at`) VALUES
(1, 1, 'Zapatos Deportivos Nike', 'Zapatos deportivos en excelente estado, poco uso. Perfectos para correr o hacer ejercicio.', 'img/productos/Zapatosdeportivosnike.jpg', 'Calzado', 'disponible', '2025-10-02 19:10:12', '2025-10-02 23:11:14'),
(2, 1, 'Guitarra Acústica', 'Guitarra acústica en buen estado, ideal para principiantes. Incluye funda protectora.', 'img/productos/Guitarraacustica.jpg', 'Música', 'disponible', '2025-10-02 19:10:12', '2025-10-02 23:11:14'),
(3, 1, 'Libro \"El Principito\"', 'Clásico de la literatura en perfecto estado. Edición especial con ilustraciones.', 'img/productos/elprincipito.jpg', 'Libros', 'disponible', '2025-10-02 19:10:12', '2025-10-02 23:11:14'),
(4, 2, 'Smartphone Samsung', 'Samsung Galaxy en excelente estado, con cargador y protector. Funciona perfectamente.', 'img/productos/smartphonesamsungjpg.jpg', 'Electrónicos', 'disponible', '2025-10-02 19:10:12', '2025-10-02 23:11:14'),
(5, 2, 'Chaqueta de Cuero', 'Chaqueta de cuero genuino, talla M. Muy poco uso, perfecta para invierno.', 'img/productos/chaquetadecuerojpg.jpg', 'Ropa', 'disponible', '2025-10-02 19:10:12', '2025-10-02 23:11:14'),
(6, 2, 'Bicicleta de Montaña', 'Bicicleta en muy buen estado, ideal para aventuras al aire libre. Incluye casco.', 'img/productos/bicicletademontaña.jpg', 'Deportes', 'disponible', '2025-10-02 19:10:12', '2025-10-02 23:11:14'),
(7, 3, 'Cafetera Express', 'Cafetera express automática, hace café delicioso. Incluye manual de uso.', 'img/productos/cafeteraexpress.jpg', 'Hogar', 'disponible', '2025-10-02 19:10:12', '2025-10-02 23:11:14'),
(8, 3, 'Juego de Mesa Monopoly', 'Monopoly clásico en excelente estado, completo con todas las piezas.', 'img/productos/monopoly.jpg', 'Juguetes', 'disponible', '2025-10-02 19:10:12', '2025-10-02 23:11:14'),
(9, 3, 'Taladro Eléctrico', 'Taladro eléctrico con set de brocas. Perfecto para proyectos de hogar.', 'img/productos/taladroelectrico.jpg', 'Herramientas', 'disponible', '2025-10-02 19:10:12', '2025-10-02 23:11:14'),
(10, 4, 'Reloj Vintage', 'Reloj de pulsera vintage en perfecto funcionamiento. Estilo clásico y elegante.', 'img/productos/relojvintage.jpg', 'Accesorios', 'disponible', '2025-10-02 19:10:12', '2025-10-02 23:11:14');

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `fullname`, `username`, `email`, `phone`, `password`, `birthdate`, `created_at`, `updated_at`, `avatar_path`) VALUES
(1, 'Angel Alemán', 'Angel', 'angel@example.com', '+598123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1995-05-15', '2025-10-02 19:10:12', '2025-10-02 23:06:47', 'uploads/avatars/avatar_1_68df05875c7c9.jpg'),
(2, 'Alejo García', 'Alejo', 'alejo@example.com', '+598987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1998-03-20', '2025-10-02 19:10:12', '2025-10-02 19:10:12', NULL),
(3, 'Milagros Pérez', 'Milagros', 'milagros@example.com', '+598456789123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1997-08-10', '2025-10-02 19:10:12', '2025-10-02 19:10:12', NULL),
(4, 'Usuario Test', 'test', 'test@example.com', '+598999999999', '$2y$10$tSYWBxR9Im1bivzvfd0CmOTDBVp79ta/wc.tA86PbNSyfaSYcCYYa', '2000-01-01', '2025-10-02 19:10:12', '2025-10-02 19:10:12', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoraciones`
--

DROP TABLE IF EXISTS `valoraciones`;
CREATE TABLE IF NOT EXISTS `valoraciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `valorador_id` int NOT NULL,
  `puntuacion` int DEFAULT NULL,
  `comentario` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_valoracion` (`usuario_id`,`valorador_id`),
  KEY `valorador_id` (`valorador_id`)
) ;

--
-- Volcado de datos para la tabla `valoraciones`
--

INSERT INTO `valoraciones` (`id`, `usuario_id`, `valorador_id`, `puntuacion`, `comentario`, `created_at`) VALUES
(1, 1, 2, 5, 'Excelente persona para hacer trueques, muy recomendable', '2025-10-02 19:10:12'),
(2, 1, 3, 4, 'Buen trato y productos de calidad para intercambiar', '2025-10-02 19:10:12'),
(3, 2, 1, 5, 'Intercambio perfecto, muy confiable y puntual', '2025-10-02 19:10:12'),
(4, 3, 4, 4, 'Gran experiencia de trueque, productos tal como se describían', '2025-10-02 19:10:12');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
