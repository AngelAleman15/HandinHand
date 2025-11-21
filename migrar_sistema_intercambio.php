<?php
require_once 'config/database.php';

try {
    $pdo = getConnection();
    
    echo "Iniciando migración para sistema de intercambios...\n\n";
    
    // 1. Crear tabla chats_temporales
    echo "1. Creando tabla chats_temporales...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chats_temporales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario1_id INT NOT NULL,
            usuario2_id INT NOT NULL,
            producto_relacionado_id INT,
            activo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 7 DAY),
            FOREIGN KEY (usuario1_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario2_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (producto_relacionado_id) REFERENCES productos(id) ON DELETE SET NULL,
            INDEX idx_usuarios (usuario1_id, usuario2_id),
            INDEX idx_activo (activo),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla chats_temporales creada\n\n";
    
    // 2. Verificar columnas en mensajes (ya existen según tu BD exportada)
    echo "2. Verificando columnas en tabla mensajes...\n";
    
    // Verificar si tipo_mensaje existe
    $stmt = $pdo->query("SHOW COLUMNS FROM mensajes LIKE 'tipo_mensaje'");
    if ($stmt->rowCount() == 0) {
        echo "⚠ Columna tipo_mensaje NO existe - se espera que ya exista\n";
        $pdo->exec("ALTER TABLE mensajes ADD COLUMN tipo_mensaje VARCHAR(50) DEFAULT 'normal' AFTER message");
        echo "✓ Columna tipo_mensaje añadida\n";
    } else {
        echo "○ Columna tipo_mensaje ya existe (OK)\n";
    }
    
    // Verificar si producto_relacionado_id existe
    $stmt = $pdo->query("SHOW COLUMNS FROM mensajes LIKE 'producto_relacionado_id'");
    if ($stmt->rowCount() == 0) {
        echo "⚠ Columna producto_relacionado_id NO existe - se espera que ya exista\n";
        $pdo->exec("ALTER TABLE mensajes ADD COLUMN producto_relacionado_id INT NULL AFTER tipo_mensaje");
        try {
            $pdo->exec("ALTER TABLE mensajes ADD FOREIGN KEY (producto_relacionado_id) REFERENCES productos(id) ON DELETE SET NULL");
        } catch (PDOException $e) {
            echo "ℹ FK para producto_relacionado_id ya existe o no se pudo crear\n";
        }
        echo "✓ Columna producto_relacionado_id añadida\n";
    } else {
        echo "○ Columna producto_relacionado_id ya existe (OK)\n";
    }
    echo "\n";
    
    // 3. Crear tabla notificaciones
    echo "3. Creando tabla notificaciones...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notificaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            mensaje TEXT,
            enlace VARCHAR(500),
            leida TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_usuario_leida (user_id, leida),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabla notificaciones creada\n\n";
    
    echo "========================================\n";
    echo "✓ Migración completada exitosamente!\n";
    echo "========================================\n";
    echo "\nAhora puedes usar el sistema de intercambios.\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
