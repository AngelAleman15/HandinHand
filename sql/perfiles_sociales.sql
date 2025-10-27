-- Script SQL para agregar funcionalidades sociales al sistema
-- Tabla de denuncias, solicitudes de amistad y mejoras en valoraciones

-- Primero convertir la tabla usuarios a InnoDB para soportar foreign keys
ALTER TABLE usuarios ENGINE=InnoDB;
ALTER TABLE productos ENGINE=InnoDB;
ALTER TABLE valoraciones ENGINE=InnoDB;

-- Tabla de denuncias de usuarios
CREATE TABLE IF NOT EXISTS denuncias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    denunciante_id INT NOT NULL,
    denunciado_id INT NOT NULL,
    motivo ENUM('spam', 'fraude', 'contenido_inapropiado', 'acoso', 'suplantacion', 'otro') NOT NULL,
    descripcion TEXT,
    estado ENUM('pendiente', 'en_revision', 'resuelta', 'rechazada') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (denunciante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (denunciado_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_denunciante (denunciante_id),
    INDEX idx_denunciado (denunciado_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de solicitudes de amistad
CREATE TABLE IF NOT EXISTS solicitudes_amistad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitante_id INT NOT NULL,
    receptor_id INT NOT NULL,
    estado ENUM('pendiente', 'aceptada', 'rechazada') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (receptor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_solicitud (solicitante_id, receptor_id),
    INDEX idx_solicitante (solicitante_id),
    INDEX idx_receptor (receptor_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de amistades (relación bidireccional)
CREATE TABLE IF NOT EXISTS amistades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario1_id INT NOT NULL,
    usuario2_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario1_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario2_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_amistad (usuario1_id, usuario2_id),
    INDEX idx_usuario1 (usuario1_id),
    INDEX idx_usuario2 (usuario2_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mejorar tabla de valoraciones para soportar medias estrellas (0.5)
-- La tabla ya existe, solo vamos a modificar la columna puntuacion
ALTER TABLE valoraciones 
MODIFY COLUMN puntuacion DECIMAL(2,1) DEFAULT NULL 
CHECK (puntuacion >= 0 AND puntuacion <= 5 AND MOD(puntuacion * 10, 5) = 0);

-- Tabla de estadísticas de usuario (para caché de datos agregados)
CREATE TABLE IF NOT EXISTS estadisticas_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    promedio_valoracion DECIMAL(2,1) DEFAULT 0,
    total_valoraciones INT DEFAULT 0,
    total_productos INT DEFAULT 0,
    total_amigos INT DEFAULT 0,
    total_intercambios INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_promedio (promedio_valoracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar estadísticas iniciales para usuarios existentes
INSERT INTO estadisticas_usuario (usuario_id, promedio_valoracion, total_valoraciones, total_productos)
SELECT 
    u.id,
    COALESCE(AVG(v.puntuacion), 0) as promedio,
    COUNT(v.id) as total_val,
    (SELECT COUNT(*) FROM productos p WHERE p.user_id = u.id) as total_prod
FROM usuarios u
LEFT JOIN valoraciones v ON u.id = v.usuario_id
GROUP BY u.id
ON DUPLICATE KEY UPDATE
    promedio_valoracion = VALUES(promedio_valoracion),
    total_valoraciones = VALUES(total_valoraciones),
    total_productos = VALUES(total_productos);
