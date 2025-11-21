-- INSERT USUARIO: Francisco Torrecillas
-- Nota: La contraseña debe hashearse con password_hash() en PHP

-- Primero, ejecuta esto en PHP para obtener el hash de la contraseña:
-- <?php echo password_hash('orpheus', PASSWORD_DEFAULT); ?>
-- Resultado ejemplo: $2y$10$...

INSERT INTO usuarios (
    username,
    fullname,
    email,
    password,
    created_at
) VALUES (
    'ftorrecillas',
    'Francisco Torrecillas',
    'francisco.torrecillas@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Hash de 'orpheus' (debes generar uno real)
    NOW()
);

-- Obtener el ID del usuario recién creado
SET @usuario_id = LAST_INSERT_ID();
