<?php
/**
 * Script temporal para resetear contraseña de un usuario
 * ELIMINAR después de usar por seguridad
 */

require_once 'config/database.php';

// ========== CONFIGURA AQUÍ ==========
$username = 'Zami';  // Nombre de usuario
$newPassword = 'nueva123';  // Nueva contraseña que quieres asignar
// ====================================

try {
    $pdo = getConnection();
    
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("❌ Error: Usuario '$username' no encontrado\n");
    }
    
    // Generar hash de la nueva contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Actualizar en base de datos
    $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE username = ?");
    $success = $stmt->execute([$hashedPassword, $username]);
    
    if ($success) {
        echo "✅ Contraseña actualizada exitosamente!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "👤 Usuario: $username\n";
        echo "🔑 Nueva contraseña: $newPassword\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "⚠️  IMPORTANTE: Elimina este archivo (reset_password.php) después de usarlo\n";
    } else {
        echo "❌ Error al actualizar la contraseña\n";
    }
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
?>
