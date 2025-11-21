<?php
require_once 'config/database.php';

echo "<h2>🔍 Verificar Usuario: Francisco Torrecillas</h2>";

try {
    $pdo = getConnection();
    
    // Buscar el usuario
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? OR username = ?");
    $stmt->execute(['francisco.torrecillas@example.com', 'ftorrecillas']);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        echo "<p style='color: green;'>✅ Usuario encontrado en la base de datos</p>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>{$usuario['id']}</td></tr>";
        echo "<tr><td>Username</td><td>{$usuario['username']}</td></tr>";
        echo "<tr><td>Nombre completo</td><td>{$usuario['fullname']}</td></tr>";
        echo "<tr><td>Email</td><td>{$usuario['email']}</td></tr>";
        echo "<tr><td>Password Hash</td><td><code>" . substr($usuario['password'], 0, 50) . "...</code></td></tr>";
        echo "<tr><td>Creado</td><td>{$usuario['created_at']}</td></tr>";
        echo "</table>";
        
        // Verificar contraseña
        echo "<hr>";
        echo "<h3>🔐 Verificación de Contraseña</h3>";
        
        $password_correcta = 'orpheus';
        $hash_actual = $usuario['password'];
        
        if (password_verify($password_correcta, $hash_actual)) {
            echo "<p style='color: green;'>✅ La contraseña 'orpheus' es correcta para este usuario</p>";
        } else {
            echo "<p style='color: red;'>❌ La contraseña 'orpheus' NO coincide con el hash almacenado</p>";
            echo "<p>Esto puede significar que el hash no se generó correctamente.</p>";
        }
        
        // Generar nuevo hash
        echo "<hr>";
        echo "<h3>🔧 Nuevo Hash de 'orpheus'</h3>";
        $nuevo_hash = password_hash('orpheus', PASSWORD_DEFAULT);
        echo "<p>Hash generado ahora: <code>$nuevo_hash</code></p>";
        
        echo "<hr>";
        echo "<h3>📝 Para actualizar el password:</h3>";
        echo "<pre>";
        echo "UPDATE usuarios SET password = '$nuevo_hash' WHERE id = {$usuario['id']};";
        echo "</pre>";
        
    } else {
        echo "<p style='color: red;'>❌ Usuario NO encontrado en la base de datos</p>";
        echo "<p><strong>El usuario aún no ha sido creado.</strong></p>";
        echo "<p>Para crearlo, ejecuta: <a href='crear_francisco_torrecillas.php'>crear_francisco_torrecillas.php</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
    h2 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
    h3 { color: #34495e; margin-top: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th { background: #3498db; color: white; text-align: left; }
    td { background: #ecf0f1; }
    code { background: #ecf0f1; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
    hr { margin: 30px 0; border: none; border-top: 2px solid #ecf0f1; }
</style>
