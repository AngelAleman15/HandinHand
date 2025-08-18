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

-- Tabla de productos
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    categoria VARCHAR(50),
    estado ENUM('disponible', 'vendido', 'reservado') DEFAULT 'disponible',
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

-- Insertar productos de ejemplo
INSERT INTO productos (user_id, nombre, descripcion, precio, imagen, categoria) VALUES
(1, 'Zapatos Deportivos', 'Zapatos deportivos en excelente estado, poco uso', 50.00, 'img/zapato.jpg', 'Calzado'),
(1, 'Zapatillas Running', 'Zapatillas ideales para running, marca reconocida', 75.00, 'img/zapato.jpg', 'Calzado'),
(2, 'Zapatos Casuales', 'Zapatos casuales cómodos para uso diario, marca reconocida', 40.00, 'img/zapato.jpg', 'Calzado');

-- Insertar categorías
INSERT INTO categorias (nombre, descripcion) VALUES
('Calzado', 'Zapatos, zapatillas y todo tipo de calzado'),
('Ropa', 'Vestimenta en general'),
('Electrónicos', 'Dispositivos electrónicos y tecnología'),
('Hogar', 'Artículos para el hogar y decoración'),
('Deportes', 'Artículos deportivos y fitness');

-- Insertar valoraciones de ejemplo
INSERT INTO valoraciones (usuario_id, valorador_id, puntuacion, comentario) VALUES
(1, 2, 5, 'Excelente vendedor, muy recomendable'),
(1, 3, 4, 'Buen trato y productos de calidad');
