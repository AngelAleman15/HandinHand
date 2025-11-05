-- Sistema FYP (For You Page) - HandinHand
-- Rastreo de interacciones y recomendaciones personalizadas

USE handinhand;

-- Tabla para rastrear vistas de productos
CREATE TABLE IF NOT EXISTS producto_vistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT NULL, -- NULL si no está logueado
    session_id VARCHAR(100) NULL, -- Para usuarios no logueados
    fecha_vista TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duracion_segundos INT DEFAULT 0, -- Tiempo que pasó viendo el producto
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_producto (producto_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_session (session_id),
    INDEX idx_fecha (fecha_vista)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para productos guardados/favoritos
CREATE TABLE IF NOT EXISTS producto_guardados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_guardado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_guardado (producto_id, usuario_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para rastrear chats iniciados desde productos
CREATE TABLE IF NOT EXISTS producto_chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT NOT NULL, -- Usuario que inicia el chat
    vendedor_id INT NOT NULL, -- Dueño del producto
    fecha_chat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_chat (producto_id, usuario_id),
    INDEX idx_producto (producto_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para calcular scores de productos (se actualiza periódicamente)
CREATE TABLE IF NOT EXISTS producto_scores (
    producto_id INT PRIMARY KEY,
    total_vistas INT DEFAULT 0,
    total_guardados INT DEFAULT 0,
    total_chats INT DEFAULT 0,
    score_total DECIMAL(10,2) DEFAULT 0,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_score (score_total DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para recomendaciones colaborativas
-- "Usuarios que vieron X también vieron Y"
CREATE TABLE IF NOT EXISTS producto_similitudes (
    producto_a_id INT NOT NULL,
    producto_b_id INT NOT NULL,
    similitud_score DECIMAL(5,2) DEFAULT 0,
    veces_visto_juntos INT DEFAULT 0,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (producto_a_id, producto_b_id),
    FOREIGN KEY (producto_a_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_b_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_similitud (similitud_score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vista para obtener productos recomendados fácilmente
CREATE OR REPLACE VIEW productos_recomendados AS
SELECT 
    p.id,
    p.nombre,
    p.descripcion,
    p.imagen,
    p.categoria,
    p.estado,
    p.user_id,
    u.username as vendedor_username,
    u.fullname as vendedor_name,
    u.avatar_path,
    p.promedio_estrellas,
    p.total_valoraciones,
    ps.score_total,
    ps.total_vistas,
    ps.total_guardados,
    ps.total_chats,
    -- Calcular tendencia (más interacciones recientes = mayor peso)
    (SELECT COUNT(*) FROM producto_vistas pv 
     WHERE pv.producto_id = p.id 
     AND pv.fecha_vista >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as vistas_semana,
    (SELECT COUNT(*) FROM producto_guardados pg 
     WHERE pg.producto_id = p.id 
     AND pg.fecha_guardado >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as guardados_semana
FROM productos p
LEFT JOIN usuarios u ON p.user_id = u.id
LEFT JOIN producto_scores ps ON p.id = ps.producto_id
WHERE p.estado = 'disponible'
ORDER BY ps.score_total DESC, p.created_at DESC;

-- Procedimiento para actualizar scores de productos
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS actualizar_scores_productos()
BEGIN
    -- Limpiar tabla de scores
    TRUNCATE TABLE producto_scores;
    
    -- Calcular scores para todos los productos
    INSERT INTO producto_scores (producto_id, total_vistas, total_guardados, total_chats, score_total)
    SELECT 
        p.id,
        COALESCE(COUNT(DISTINCT pv.id), 0) as total_vistas,
        COALESCE(COUNT(DISTINCT pg.id), 0) as total_guardados,
        COALESCE(COUNT(DISTINCT pc.id), 0) as total_chats,
        -- Fórmula: vistas×1 + guardados×3 + chats×5 + valoraciones×2
        (COALESCE(COUNT(DISTINCT pv.id), 0) * 1) +
        (COALESCE(COUNT(DISTINCT pg.id), 0) * 3) +
        (COALESCE(COUNT(DISTINCT pc.id), 0) * 5) +
        (COALESCE(p.total_valoraciones, 0) * 2) as score_total
    FROM productos p
    LEFT JOIN producto_vistas pv ON p.id = pv.producto_id
    LEFT JOIN producto_guardados pg ON p.id = pg.producto_id
    LEFT JOIN producto_chats pc ON p.id = pc.producto_id
    WHERE p.estado = 'disponible'
    GROUP BY p.id;
END //
DELIMITER ;

-- Procedimiento para calcular similitudes entre productos
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS calcular_similitudes_productos()
BEGIN
    -- Limpiar tabla de similitudes
    TRUNCATE TABLE producto_similitudes;
    
    -- Calcular productos vistos juntos por los mismos usuarios
    INSERT INTO producto_similitudes (producto_a_id, producto_b_id, veces_visto_juntos, similitud_score)
    SELECT 
        pv1.producto_id as producto_a_id,
        pv2.producto_id as producto_b_id,
        COUNT(DISTINCT pv1.usuario_id) as veces_visto_juntos,
        -- Score de similitud basado en frecuencia
        COUNT(DISTINCT pv1.usuario_id) * 10 as similitud_score
    FROM producto_vistas pv1
    INNER JOIN producto_vistas pv2 
        ON pv1.usuario_id = pv2.usuario_id 
        AND pv1.producto_id < pv2.producto_id  -- Evitar duplicados
        AND pv1.usuario_id IS NOT NULL  -- Solo usuarios logueados
    GROUP BY pv1.producto_id, pv2.producto_id
    HAVING COUNT(DISTINCT pv1.usuario_id) >= 2;  -- Al menos 2 usuarios en común
END //
DELIMITER ;

-- Insertar datos de ejemplo para testing
INSERT INTO producto_scores (producto_id, total_vistas, total_guardados, total_chats, score_total)
SELECT id, 0, 0, 0, 0 FROM productos
ON DUPLICATE KEY UPDATE producto_id = producto_id;

-- Mensaje de confirmación
SELECT '✅ Sistema FYP creado exitosamente!' as Resultado;
SELECT 'Tablas creadas: producto_vistas, producto_guardados, producto_chats, producto_scores, producto_similitudes' as Info;
SELECT 'Ejecuta CALL actualizar_scores_productos(); para calcular scores iniciales' as ProximoPaso;
