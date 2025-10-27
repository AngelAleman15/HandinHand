<?php
/**
 * Script para probar contraseñas comunes
 * ELIMINAR después de usar por seguridad
 */

require_once 'config/database.php';

// ========== CONFIGURA AQUÍ ==========
$username = 'Zami';  // Usuario a verificar

// Lista de contraseñas comunes a probar
$commonPasswords = [
    'zami',
    'Zami',
    'zami123',
    'Zami123',
    '123456',
    'password',
    'admin',
    '12345678',
    'qwerty',
    'abc123',
    'password123',
    '111111',
    'welcome',
    'monkey',
    'dragon',
    '1234',
    '1234567890',
];
// ====================================

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, username, password FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("❌ Error: Usuario '$username' no encontrado\n");
    }
    
    echo "🔍 Probando contraseñas comunes para el usuario: <strong>$username</strong><br><br>";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<br>";
    
    $found = false;
    foreach ($commonPasswords as $testPassword) {
        if (password_verify($testPassword, $user['password'])) {
            echo "✅ <strong style='color: green;'>¡ENCONTRADA!</strong><br>";
            echo "🔑 La contraseña es: <strong style='font-size: 18px; color: blue;'>$testPassword</strong><br>";
            $found = true;
            break;
        } else {
            echo "❌ No es: $testPassword<br>";
        }
    }
    
    if (!$found) {
        echo "<br>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<br>";
        echo "⚠️ No se encontró la contraseña entre las comunes probadas<br>";
        echo "💡 Usa el script <strong>reset_password.php</strong> para cambiarla<br>";
    }
    
    echo "<br><p style='color: red;'>⚠️ IMPORTANTE: Elimina este archivo después de usarlo</p>";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
