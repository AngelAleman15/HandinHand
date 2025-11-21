-- Tabla para gestionar propuestas de intercambio
CREATE TABLE IF NOT EXISTS propuestas_intercambio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_solicitado_id INT NOT NULL,
    producto_ofrecido_id INT NOT NULL,
    solicitante_id INT NOT NULL,
    receptor_id INT NOT NULL,
    mensaje_id INT NULL,
    mensaje TEXT,
    estado ENUM('pendiente', 'aceptada', 'rechazada', 'contraoferta', 'cancelada') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_solicitado_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_ofrecido_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (solicitante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (receptor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_solicitante (solicitante_id),
    INDEX idx_receptor (receptor_id),
    INDEX idx_estado (estado),
    INDEX idx_productos (producto_solicitado_id, producto_ofrecido_id),
    INDEX idx_mensaje (mensaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
