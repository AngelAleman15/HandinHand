-- IF NOT EXISTS: Cláusula que evita errores si la base de datos ya existe
CREATE DATABASE IF NOT EXISTS handinhand;

-- USE: Comando que selecciona la base de datos a utilizar para las siguientes operaciones
-- Todas las tablas y consultas posteriores se ejecutarán en esta base de datos
USE handinhand;

CREATE TABLE usuarios (
    -- AUTO_INCREMENT: MySQL incrementa automáticamente este valor con cada nuevo registro
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    fullname VARCHAR(100) NOT NULL,

    -- UNIQUE: Garantiza que no haya dos usuarios con el mismo username
    -- NOT NULL: Campo obligatorio
    username VARCHAR(50) UNIQUE NOT NULL,

    email VARCHAR(100) UNIQUE NOT NULL,
    
    phone VARCHAR(20) NOT NULL,

    password VARCHAR(255) NOT NULL,

    birthdate DATE NOT NULL,
    
    -- created_at: Timestamp de cuando se creó el registro
    -- TIMESTAMP: Tipo de dato fecha y hora
    -- DEFAULT CURRENT_TIMESTAMP: Automáticamente establece la fecha/hora actual al crear el registro
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- updated_at: Timestamp de la última actualización del registro
    -- ON UPDATE CURRENT_TIMESTAMP: Actualiza automáticamente cuando se modifica el registro
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE productos (

    id INT PRIMARY KEY AUTO_INCREMENT,
    
    user_id INT NOT NULL,
    

    nombre VARCHAR(100) NOT NULL,
    
    descripcion TEXT,
    
    imagen VARCHAR(255) NOT NULL,

    categoria VARCHAR(50),
    
    -- estado: Estado actual del producto en el sistema de trueques
    -- ENUM: Tipo de dato que limita los valores a una lista específica
    -- 'disponible': Producto disponible para intercambio
    -- 'intercambiado': Producto ya intercambiado (historial)
    -- 'reservado': Producto reservado para intercambio pendiente
    -- DEFAULT 'disponible': Valor por defecto cuando se crea un producto
    estado ENUM('disponible', 'intercambiado', 'reservado') DEFAULT 'disponible',
    
    -- created_at: Timestamp de creación del producto
    -- TIMESTAMP: Fecha y hora de cuando se publicó el producto
    -- DEFAULT CURRENT_TIMESTAMP: Se establece automáticamente al crear
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- updated_at: Timestamp de última actualización
    -- ON UPDATE CURRENT_TIMESTAMP: Se actualiza automáticamente cuando se modifica
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- ON DELETE CASCADE: Si se elimina un usuario, se eliminan automáticamente todos sus productos
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE categorias (

    id INT PRIMARY KEY AUTO_INCREMENT,

    nombre VARCHAR(50) NOT NULL,

    descripcion TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE mensajes (

    id INT PRIMARY KEY AUTO_INCREMENT,

    producto_id INT NOT NULL,

    remitente_id INT NOT NULL,
    
    destinatario_id INT NOT NULL,

    mensaje TEXT NOT NULL,
    
    -- BOOLEAN: Tipo de dato verdadero/falso (1/0 en MySQL)
    -- DEFAULT FALSE: Por defecto, los mensajes nuevos no han sido leídos
    leido BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- CLAVES FORÁNEAS: Establecen relaciones con otras tablas
    -- ON DELETE CASCADE: Si se elimina el producto/usuario, se eliminan los mensajes relacionados
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE valoraciones (

    id INT PRIMARY KEY AUTO_INCREMENT,
    
    usuario_id INT NOT NULL,

    valorador_id INT NOT NULL,
    
    -- puntuacion: Puntuación numérica de la valoración
    -- CHECK: Restricción que valida que la puntuación esté entre 1 y 5
    -- >= 1 AND <= 5: Escala de 1 estrella (mínimo) a 5 estrellas (máximo)
    puntuacion INT CHECK (puntuacion >= 1 AND puntuacion <= 5),

    comentario TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (valorador_id) REFERENCES usuarios(id) ON DELETE CASCADE,

    UNIQUE KEY unique_valoracion (usuario_id, valorador_id)
);

-- Los datos de prueba permiten probar la funcionalidad sin tener que crear usuarios manualmente

-- USUARIOS DE PRUEBA CON CONTRASEÑAS CONOCIDAS:
-- Angel / password    (Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
-- Alejo / password    (mismo hash para pruebas)
-- Milagros / password (mismo hash para pruebas)  
-- test / 123456       (Hash: $2y$10$tSYWBxR9Im1bivzvfd0CmOTDBVp79ta/wc.tA86PbNSyfaSYcCYYa)

INSERT INTO usuarios (fullname, username, email, phone, password, birthdate) VALUES
-- Usuario 1: Angel Alemán
-- '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi': Hash bcrypt de "password"
-- '1995-05-15': Fecha de nacimiento en formato YYYY-MM-DD (mayo 15, 1995)
('Angel Alemán', 'Angel', 'angel@example.com', '+598123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1995-05-15'),

-- Usuario 2: Alejo García
-- Mismo hash de contraseña que Angel (ambos usan "password")
-- '1998-03-20': Nacido el 20 de marzo de 1998
('Alejo García', 'Alejo', 'alejo@example.com', '+598987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1998-03-20'),

-- Usuario 3: Milagros Pérez
-- '1997-08-10': Nacida el 10 de agosto de 1997
('Milagros Pérez', 'Milagros', 'milagros@example.com', '+598456789123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1997-08-10'),

-- Usuario 4: Usuario Test
-- '$2y$10$tSYWBxR9Im1bivzvfd0CmOTDBVp79ta/wc.tA86PbNSyfaSYcCYYa': Hash bcrypt de "123456"
-- '2000-01-01': Nacido el 1 de enero del 2000
('Usuario Test', 'test', 'test@example.com', '+598999999999', '$2y$10$tSYWBxR9Im1bivzvfd0CmOTDBVp79ta/wc.tA86PbNSyfaSYcCYYa', '2000-01-01');

INSERT INTO productos (user_id, nombre, descripcion, imagen, categoria) VALUES

(1, 'Zapatos Deportivos Nike', 'Zapatos deportivos en excelente estado, poco uso. Perfectos para correr o hacer ejercicio.', 'img/zapato.jpg', 'Calzado'),

(1, 'Guitarra Acústica', 'Guitarra acústica en buen estado, ideal para principiantes. Incluye funda protectora.', 'img/zapato.jpg', 'Música'),

(1, 'Libro "El Principito"', 'Clásico de la literatura en perfecto estado. Edición especial con ilustraciones.', 'img/zapato.jpg', 'Libros'),

(2, 'Smartphone Samsung', 'Samsung Galaxy en excelente estado, con cargador y protector. Funciona perfectamente.', 'img/zapato.jpg', 'Electrónicos'),

(2, 'Chaqueta de Cuero', 'Chaqueta de cuero genuino, talla M. Muy poco uso, perfecta para invierno.', 'img/zapato.jpg', 'Ropa'),

(2, 'Bicicleta de Montaña', 'Bicicleta en muy buen estado, ideal para aventuras al aire libre. Incluye casco.', 'img/zapato.jpg', 'Deportes'),

(3, 'Cafetera Express', 'Cafetera express automática, hace café delicioso. Incluye manual de uso.', 'img/zapato.jpg', 'Hogar'),

(3, 'Juego de Mesa Monopoly', 'Monopoly clásico en excelente estado, completo con todas las piezas.', 'img/zapato.jpg', 'Juguetes'),

(3, 'Taladro Eléctrico', 'Taladro eléctrico con set de brocas. Perfecto para proyectos de hogar.', 'img/zapato.jpg', 'Herramientas'),

(4, 'Reloj Vintage', 'Reloj de pulsera vintage en perfecto funcionamiento. Estilo clásico y elegante.', 'img/zapato.jpg', 'Accesorios');

INSERT INTO categorias (nombre, descripcion) VALUES
-- Categoría 1: Calzado
('Calzado', 'Zapatos, zapatillas y todo tipo de calzado'),

-- Categoría 2: Ropa
('Ropa', 'Vestimenta en general'),

-- Categoría 3: Electrónicos
('Electrónicos', 'Dispositivos electrónicos y tecnología'),

-- Categoría 4: Hogar
('Hogar', 'Artículos para el hogar y decoración'),

-- Categoría 5: Deportes
('Deportes', 'Artículos deportivos y fitness'),

-- Categoría 6: Libros
('Libros', 'Libros, revistas y material educativo'),

-- Categoría 7: Música
('Música', 'Instrumentos musicales y equipos de audio'),

-- Categoría 8: Juguetes
('Juguetes', 'Juguetes y juegos para todas las edades'),

-- Categoría 9: Herramientas
('Herramientas', 'Herramientas y equipos de trabajo'),

-- Categoría 10: Accesorios
('Accesorios', 'Accesorios y complementos diversos');

INSERT INTO valoraciones (usuario_id, valorador_id, puntuacion, comentario) VALUES

(1, 2, 5, 'Excelente persona para hacer trueques, muy recomendable'),

(1, 3, 4, 'Buen trato y productos de calidad para intercambiar'),

(2, 1, 5, 'Intercambio perfecto, muy confiable y puntual'),

(3, 4, 4, 'Gran experiencia de trueque, productos tal como se describían');
