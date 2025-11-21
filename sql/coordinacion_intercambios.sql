-- Tabla para coordinar los intercambios (lugar, fecha, confirmaciones)
CREATE TABLE IF NOT EXISTS coordinacion_intercambios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    propuesta_id INT NOT NULL,
    
    -- Información del punto de encuentro
    lugar_propuesto VARCHAR(500) DEFAULT NULL,
    latitud DECIMAL(10, 8) DEFAULT NULL,
    longitud DECIMAL(11, 8) DEFAULT NULL,
    fecha_hora_propuesta DATETIME DEFAULT NULL,
    
    -- Quién propuso el lugar
    propuesto_por_user_id INT NOT NULL,
    
    -- Confirmaciones
    confirmado_por_solicitante BOOLEAN DEFAULT FALSE,
    confirmado_por_receptor BOOLEAN DEFAULT FALSE,
    
    -- Estado del intercambio
    estado ENUM('coordinando', 'confirmado', 'realizado', 'cancelado') DEFAULT 'coordinando',
    
    -- Notas adicionales
    notas TEXT DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (propuesta_id) REFERENCES propuestas_intercambio(id) ON DELETE CASCADE,
    FOREIGN KEY (propuesto_por_user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Índices
    INDEX idx_propuesta (propuesta_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
