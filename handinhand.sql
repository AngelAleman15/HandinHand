-- Base de datos para HandinHand
CREATE DATABASE IF NOT EXISTS handinhand;
USE handinhand;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    birthdate DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de productos (SIN PRECIO - App de trueques)
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255) NOT NULL,
    categoria VARCHAR(50),
    estado ENUM('disponible', 'intercambiado', 'reservado') DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabla de categorías (opcional para futuro)
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de mensajes/contactos
CREATE TABLE mensajes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT NOT NULL,
    remitente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    leido BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabla de valoraciones
CREATE TABLE valoraciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    valorador_id INT NOT NULL,
    puntuacion INT CHECK (puntuacion >= 1 AND puntuacion <= 5),
    comentario TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (valorador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_valoracion (usuario_id, valorador_id)
);

-- Insertar datos de prueba
-- USUARIOS DE PRUEBA:
-- Angel / password
-- Alejo / password  
-- Milagros / password
-- test / 123456
INSERT INTO usuarios (fullname, username, email, phone, password, birthdate) VALUES
('Angel Alemán', 'Angel', 'angel@example.com', '+598123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1995-05-15'),
('Alejo García', 'Alejo', 'alejo@example.com', '+598987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1998-03-20'),
('Milagros Pérez', 'Milagros', 'milagros@example.com', '+598456789123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1997-08-10'),
('Usuario Test', 'test', 'test@example.com', '+598999999999', '$2y$10$tSYWBxR9Im1bivzvfd0CmOTDBVp79ta/wc.tA86PbNSyfaSYcCYYa', '2000-01-01');

-- Para hacer login usa:
-- Angel / password  (o Alejo / password, o Milagros / password)
-- test / 123456

-- Insertar productos de ejemplo (SIN PRECIOS - App de trueques)
INSERT INTO productos (user_id, nombre, descripcion, imagen, categoria) VALUES
-- Productos de Angel (user_id = 1)
(1, 'Zapatos Deportivos Nike', 'Zapatos deportivos en excelente estado, poco uso. Perfectos para correr o hacer ejercicio.', 'img/zapato.jpg', 'Calzado'),
(1, 'Guitarra Acústica', 'Guitarra acústica en buen estado, ideal para principiantes. Incluye funda protectora.', 'img/zapato.jpg', 'Música'),
(1, 'Libro "El Principito"', 'Clásico de la literatura en perfecto estado. Edición especial con ilustraciones.', 'img/zapato.jpg', 'Libros'),

-- Productos de Alejo (user_id = 2)
(2, 'Smartphone Samsung', 'Samsung Galaxy en excelente estado, con cargador y protector. Funciona perfectamente.', 'img/zapato.jpg', 'Electrónicos'),
(2, 'Chaqueta de Cuero', 'Chaqueta de cuero genuino, talla M. Muy poco uso, perfecta para invierno.', 'img/zapato.jpg', 'Ropa'),
(2, 'Bicicleta de Montaña', 'Bicicleta en muy buen estado, ideal para aventuras al aire libre. Incluye casco.', 'img/zapato.jpg', 'Deportes'),

-- Productos de Milagros (user_id = 3)
(3, 'Cafetera Express', 'Cafetera express automática, hace café delicioso. Incluye manual de uso.', 'img/zapato.jpg', 'Hogar'),
(3, 'Juego de Mesa Monopoly', 'Monopoly clásico en excelente estado, completo con todas las piezas.', 'img/zapato.jpg', 'Juguetes'),
(3, 'Taladro Eléctrico', 'Taladro eléctrico con set de brocas. Perfecto para proyectos de hogar.', 'img/zapato.jpg', 'Herramientas'),

-- Productos de Test (user_id = 4)
(4, 'Reloj Vintage', 'Reloj de pulsera vintage en perfecto funcionamiento. Estilo clásico y elegante.', 'img/zapato.jpg', 'Accesorios');

-- Insertar categorías
INSERT INTO categorias (nombre, descripcion) VALUES
('Calzado', 'Zapatos, zapatillas y todo tipo de calzado'),
('Ropa', 'Vestimenta en general'),
('Electrónicos', 'Dispositivos electrónicos y tecnología'),
('Hogar', 'Artículos para el hogar y decoración'),
('Deportes', 'Artículos deportivos y fitness'),
('Libros', 'Libros, revistas y material educativo'),
('Música', 'Instrumentos musicales y equipos de audio'),
('Juguetes', 'Juguetes y juegos para todas las edades'),
('Herramientas', 'Herramientas y equipos de trabajo'),
('Accesorios', 'Accesorios y complementos diversos');

-- Insertar valoraciones de ejemplo
INSERT INTO valoraciones (usuario_id, valorador_id, puntuacion, comentario) VALUES
(1, 2, 5, 'Excelente persona para hacer trueques, muy recomendable'),
(1, 3, 4, 'Buen trato y productos de calidad para intercambiar'),
(2, 1, 5, 'Intercambio perfecto, muy confiable y puntual'),
(3, 4, 4, 'Gran experiencia de trueque, productos tal como se describían');
