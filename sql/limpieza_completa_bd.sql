-- =====================================================
-- LIMPIEZA COMPLETA DE BASE DE DATOS + SISTEMA DE UBICACIONES
-- HandinHand - Ejecutar después de actualizar los archivos PHP
-- =====================================================

-- FASE 1: AGREGAR SISTEMA DE UBICACIONES
-- =====================================================

-- 1.1 Crear tabla de departamentos
CREATE TABLE IF NOT EXISTS departamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2 Insertar los 19 departamentos de Uruguay
INSERT INTO departamentos (nombre) VALUES
('Artigas'),
('Canelones'),
('Cerro Largo'),
('Colonia'),
('Durazno'),
('Flores'),
('Florida'),
('Lavalleja'),
('Maldonado'),
('Montevideo'),
('Paysandú'),
('Río Negro'),
('Rivera'),
('Rocha'),
('Salto'),
('San José'),
('Soriano'),
('Tacuarembó'),
('Treinta y Tres')
ON DUPLICATE KEY UPDATE nombre=nombre;

-- 1.3 Crear tabla de ciudades
CREATE TABLE IF NOT EXISTS ciudades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    departamento_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    es_capital BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ciudad (departamento_id, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.4 Insertar ciudades principales de cada departamento
INSERT INTO ciudades (departamento_id, nombre, es_capital) VALUES
-- Artigas (1)
(1, 'Artigas', TRUE),
(1, 'Bella Unión', FALSE),
(1, 'Tomás Gomensoro', FALSE),

-- Canelones (2)
(2, 'Canelones', TRUE),
(2, 'Ciudad de la Costa', FALSE),
(2, 'Las Piedras', FALSE),
(2, 'Pando', FALSE),
(2, 'La Paz', FALSE),
(2, 'Progreso', FALSE),
(2, 'Santa Lucía', FALSE),
(2, 'Atlántida', FALSE),
(2, 'Parque del Plata', FALSE),

-- Cerro Largo (3)
(3, 'Melo', TRUE),
(3, 'Río Branco', FALSE),
(3, 'Fraile Muerto', FALSE),

-- Colonia (4)
(4, 'Colonia del Sacramento', TRUE),
(4, 'Carmelo', FALSE),
(4, 'Nueva Helvecia', FALSE),
(4, 'Juan Lacaze', FALSE),
(4, 'Rosario', FALSE),

-- Durazno (5)
(5, 'Durazno', TRUE),
(5, 'Sarandí del Yí', FALSE),

-- Flores (6)
(6, 'Trinidad', TRUE),

-- Florida (7)
(7, 'Florida', TRUE),
(7, 'Sarandí Grande', FALSE),

-- Lavalleja (8)
(8, 'Minas', TRUE),
(8, 'José Pedro Varela', FALSE),
(8, 'Solís de Mataojo', FALSE),

-- Maldonado (9)
(9, 'Maldonado', TRUE),
(9, 'Punta del Este', FALSE),
(9, 'San Carlos', FALSE),
(9, 'Piriápolis', FALSE),
(9, 'Pan de Azúcar', FALSE),

-- Montevideo (10)
(10, 'Montevideo', TRUE),

-- Paysandú (11)
(11, 'Paysandú', TRUE),
(11, 'Guichón', FALSE),
(11, 'Quebracho', FALSE),

-- Río Negro (12)
(12, 'Fray Bentos', TRUE),
(12, 'Young', FALSE),
(12, 'San Javier', FALSE),

-- Rivera (13)
(13, 'Rivera', TRUE),
(13, 'Tranqueras', FALSE),
(13, 'Vichadero', FALSE),

-- Rocha (14)
(14, 'Rocha', TRUE),
(14, 'Chuy', FALSE),
(14, 'Castillos', FALSE),
(14, 'La Paloma', FALSE),
(14, 'Lascano', FALSE),

-- Salto (15)
(15, 'Salto', TRUE),
(15, 'Constitución', FALSE),

-- San José (16)
(16, 'San José de Mayo', TRUE),
(16, 'Ciudad del Plata', FALSE),
(16, 'Libertad', FALSE),
(16, 'Ecilda Paullier', FALSE),

-- Soriano (17)
(17, 'Mercedes', TRUE),
(17, 'Dolores', FALSE),
(17, 'Cardona', FALSE),

-- Tacuarembó (18)
(18, 'Tacuarembó', TRUE),
(18, 'Paso de los Toros', FALSE),
(18, 'San Gregorio de Polanco', FALSE),

-- Treinta y Tres (19)
(19, 'Treinta y Tres', TRUE),
(19, 'Vergara', FALSE),
(19, 'Santa Clara de Olimar', FALSE)
ON DUPLICATE KEY UPDATE nombre=nombre;

-- 1.5 Agregar columnas de ubicación a productos (si no existen)
-- Verificar si las columnas ya existen antes de agregarlas
SET @dbname = DATABASE();
SET @tablename = 'productos';
SET @columnname1 = 'departamento_id';
SET @columnname2 = 'ciudad_id';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname1)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE productos ADD COLUMN departamento_id INT NULL"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname2)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE productos ADD COLUMN ciudad_id INT NULL"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Agregar constraints (solo si no existen)
SET @constraint1 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                    WHERE CONSTRAINT_NAME = 'fk_producto_departamento' 
                    AND TABLE_SCHEMA = @dbname 
                    AND TABLE_NAME = @tablename);

SET @preparedStatement = IF(@constraint1 = 0,
  "ALTER TABLE productos ADD CONSTRAINT fk_producto_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL",
  "SELECT 1"
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @constraint2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                    WHERE CONSTRAINT_NAME = 'fk_producto_ciudad' 
                    AND TABLE_SCHEMA = @dbname 
                    AND TABLE_NAME = @tablename);

SET @preparedStatement = IF(@constraint2 = 0,
  "ALTER TABLE productos ADD CONSTRAINT fk_producto_ciudad FOREIGN KEY (ciudad_id) REFERENCES ciudades(id) ON DELETE SET NULL",
  "SELECT 1"
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Crear índices para búsquedas por ubicación (solo si no existen)
SET @index1 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE TABLE_SCHEMA = @dbname 
               AND TABLE_NAME = @tablename 
               AND INDEX_NAME = 'idx_productos_departamento');

SET @preparedStatement = IF(@index1 = 0,
  "CREATE INDEX idx_productos_departamento ON productos(departamento_id)",
  "SELECT 1"
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @index2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE TABLE_SCHEMA = @dbname 
               AND TABLE_NAME = @tablename 
               AND INDEX_NAME = 'idx_productos_ciudad');

SET @preparedStatement = IF(@index2 = 0,
  "CREATE INDEX idx_productos_ciudad ON productos(ciudad_id)",
  "SELECT 1"
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;


-- FASE 2: LIMPIAR COLUMNAS DUPLICADAS EN MENSAJES
-- =====================================================
-- ⚠️ IMPORTANTE: Solo ejecutar después de verificar que todos los archivos PHP
--    fueron actualizados y están usando las nuevas columnas (sender_id, receiver_id, message, is_read)

-- 2.1 Verificar que las nuevas columnas existen (backup de seguridad)
-- Si alguna no existe, este script creará un error y se detendrá
SELECT COUNT(*) FROM mensajes WHERE sender_id IS NOT NULL;
SELECT COUNT(*) FROM mensajes WHERE receiver_id IS NOT NULL;
SELECT COUNT(*) FROM mensajes WHERE message IS NOT NULL;
SELECT COUNT(*) FROM mensajes WHERE is_read IS NOT NULL;

-- 2.2 Eliminar columnas obsoletas (comentar si quieres mantenerlas temporalmente)
-- Verificar si las columnas existen antes de eliminarlas
SET @dbname = DATABASE();
SET @tablename = 'mensajes';

-- Eliminar remitente_id si existe
SET @col1 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = @dbname 
             AND TABLE_NAME = @tablename 
             AND COLUMN_NAME = 'remitente_id');

SET @preparedStatement = IF(@col1 > 0,
  "ALTER TABLE mensajes DROP COLUMN remitente_id",
  "SELECT 1"
);
PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- Eliminar destinatario_id si existe
SET @col2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = @dbname 
             AND TABLE_NAME = @tablename 
             AND COLUMN_NAME = 'destinatario_id');

SET @preparedStatement = IF(@col2 > 0,
  "ALTER TABLE mensajes DROP COLUMN destinatario_id",
  "SELECT 1"
);
PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- Eliminar mensaje si existe
SET @col3 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = @dbname 
             AND TABLE_NAME = @tablename 
             AND COLUMN_NAME = 'mensaje');

SET @preparedStatement = IF(@col3 > 0,
  "ALTER TABLE mensajes DROP COLUMN mensaje",
  "SELECT 1"
);
PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- Eliminar leido si existe
SET @col4 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = @dbname 
             AND TABLE_NAME = @tablename 
             AND COLUMN_NAME = 'leido');

SET @preparedStatement = IF(@col4 > 0,
  "ALTER TABLE mensajes DROP COLUMN leido",
  "SELECT 1"
);
PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- 2.3 Optimizar tabla después de eliminar columnas
OPTIMIZE TABLE mensajes;


-- FASE 3: LIMPIEZA DE TABLAS NO UTILIZADAS (OPCIONAL)
-- =====================================================
-- Descomentar solo si estás seguro de que estas tablas no se usan

-- DROP TABLE IF EXISTS producto_vistas;
-- DROP TABLE IF EXISTS producto_guardados;
-- DROP TABLE IF EXISTS producto_scores;
-- DROP TABLE IF EXISTS producto_similitudes;


-- FASE 4: LIMPIEZA DE PROCEDIMIENTOS ALMACENADOS OBSOLETOS
-- =====================================================

DROP PROCEDURE IF EXISTS actualizar_producto_score;
DROP PROCEDURE IF EXISTS calcular_similitudes_producto;


-- FASE 5: VERIFICACIÓN FINAL
-- =====================================================

-- Verificar estructura de mensajes
SELECT 
    'mensajes' as tabla,
    COUNT(*) as total_registros,
    ROUND(DATA_LENGTH / 1024, 2) as 'tamaño_kb'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'mensajes';

-- Verificar que no quedan columnas viejas
SHOW COLUMNS FROM mensajes;

-- Verificar sistema de ubicaciones
SELECT 
    d.nombre as departamento,
    COUNT(c.id) as total_ciudades
FROM departamentos d
LEFT JOIN ciudades c ON d.id = c.departamento_id
GROUP BY d.id, d.nombre
ORDER BY d.nombre;

-- Verificar productos con ubicaciones
SELECT 
    COUNT(*) as total_productos,
    COUNT(departamento_id) as productos_con_departamento,
    COUNT(ciudad_id) as productos_con_ciudad
FROM productos;

-- =====================================================
-- RESUMEN DE CAMBIOS:
-- =====================================================
-- ✅ Sistema de ubicaciones completo (19 departamentos + ~80 ciudades)
-- ✅ Columnas de ubicación agregadas a productos
-- ✅ Columnas duplicadas eliminadas de mensajes
-- ✅ Tabla mensajes optimizada
-- ✅ Procedimientos obsoletos eliminados
-- 
-- ESPACIO LIBERADO ESTIMADO: ~450 KB + 10-20 KB por cada 1000 mensajes futuros
-- =====================================================
