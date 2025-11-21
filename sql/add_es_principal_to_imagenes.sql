-- Crear tabla producto_imagenes si no existe
CREATE TABLE IF NOT EXISTS producto_imagenes (
    id INT NOT NULL AUTO_INCREMENT,
    producto_id INT NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    es_principal TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_producto_principal (producto_id, es_principal),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
