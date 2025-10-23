-- Mejoras para productos: GPS, múltiples categorías, varias imágenes
ALTER TABLE productos
    ADD COLUMN latitud DECIMAL(10,8) DEFAULT NULL,
    ADD COLUMN longitud DECIMAL(11,8) DEFAULT NULL,
    MODIFY COLUMN estado VARCHAR(50) DEFAULT 'disponible';

CREATE TABLE IF NOT EXISTS producto_categorias (
    producto_id INT NOT NULL,
    categoria_id INT NOT NULL,
    PRIMARY KEY (producto_id, categoria_id),
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

CREATE TABLE IF NOT EXISTS producto_imagenes (
    id INT NOT NULL AUTO_INCREMENT,
    producto_id INT NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);
-- Tabla de mensajes
CREATE TABLE IF NOT EXISTS mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES usuarios(id)
);

-- Índices para mejor rendimiento
ALTER TABLE mensajes ADD INDEX idx_sender (sender_id);
ALTER TABLE mensajes ADD INDEX idx_created (created_at);