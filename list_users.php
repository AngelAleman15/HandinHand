<?php
/**
 * Script para listar todos los usuarios
 * ELIMINAR después de usar por seguridad
 */

require_once 'config/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, username, fullname, email, created_at FROM usuarios ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📋 Lista de Usuarios</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; font-family: monospace;'>";
    echo "<tr style='background: #333; color: white;'>
            <th>ID</th>
            <th>Usuario</th>
            <th>Nombre Completo</th>
            <th>Email</th>
            <th>Fecha Registro</th>
          </tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['username']}</strong></td>";
        echo "<td>{$user['fullname']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p style='color: red; margin-top: 20px;'>⚠️ IMPORTANTE: Elimina este archivo después de usarlo</p>";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
