-- Agregar columna de ubicación a la tabla usuarios
-- HandinHand Platform

ALTER TABLE usuarios
ADD COLUMN ubicacion VARCHAR(100) DEFAULT NULL COMMENT 'Ciudad/localidad del usuario' AFTER avatar_path;

-- Actualizar algunos usuarios con ubicaciones de ejemplo (Montevideo)
UPDATE usuarios SET ubicacion = 'Montevideo' WHERE id IN (1, 2, 3, 4);

-- Verificar cambios
-- SELECT id, username, fullname, ubicacion FROM usuarios;
