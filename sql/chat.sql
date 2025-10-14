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