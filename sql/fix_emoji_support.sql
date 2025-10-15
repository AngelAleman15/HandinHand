-- Script para corregir el soporte de emojis en la tabla mensajes
-- Cambiar la collation a utf8mb4 para soportar emojis

-- Cambiar la base de datos a utf8mb4
ALTER DATABASE handinhand CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Cambiar la tabla mensajes
ALTER TABLE mensajes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Asegurarse de que la columna mensaje soporte utf8mb4
ALTER TABLE mensajes MODIFY COLUMN mensaje TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

-- También cambiar otras columnas de texto si existen
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE productos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE valoraciones CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
