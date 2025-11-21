<?php
/**
 * Script para crear usuario Francisco Torrecillas y productos de ejemplo
 */

require_once 'config/database.php';

// Generar hash de la contraseña 'orpheus'
$password_hash = password_hash('orpheus', PASSWORD_DEFAULT);

echo "<h2>🔧 Script de Creación de Usuario y Productos</h2>";

try {
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    // 1. CREAR USUARIO
    echo "<h3>1. Crear Usuario: Francisco Torrecillas</h3>";
    
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (username, fullname, email, password, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        'ftorrecillas',
        'Francisco Torrecillas',
        'francisco.torrecillas@example.com',
        $password_hash
    ]);
    
    $usuario_id = $pdo->lastInsertId();
    echo "<p>✅ Usuario creado con ID: <strong>$usuario_id</strong></p>";
    echo "<p>📧 Email: <strong>francisco.torrecillas@example.com</strong></p>";
    echo "<p>🔑 Contraseña: <strong>orpheus</strong></p>";
    echo "<p>🔐 Hash: <code>$password_hash</code></p>";
    
    // 2. CREAR PRODUCTOS DE EJEMPLO
    echo "<hr>";
    echo "<h3>2. Crear Productos para Francisco Torrecillas</h3>";
    
    $productos = [
        [
            'nombre' => 'Persona 5 Royal - Edición Deluxe',
            'descripcion' => 'Juego completo de Persona 5 Royal con todos los DLCs incluidos. Perfecto estado, casi sin usar. Incluye caja original y manual.',
            'categoria' => 'Videojuegos',
            'imagen' => 'img/productos/default.jpg',
            'estado' => 'disponible'
        ],
        [
            'nombre' => 'Evoker de Persona 3 (Réplica)',
            'descripcion' => 'Réplica oficial del Evoker de Persona 3. Coleccionable de alta calidad, material resistente. Ideal para fans de la saga Shin Megami Tensei.',
            'categoria' => 'Coleccionables',
            'imagen' => 'img/productos/default.jpg',
            'estado' => 'disponible'
        ],
        [
            'nombre' => 'Soundtrack Persona 3 FES - Vinilo',
            'descripcion' => 'Edición limitada en vinilo del soundtrack de Persona 3 FES. Incluye temas icónicos como "Burn My Dread" y "Mass Destruction". Estado impecable.',
            'categoria' => 'Música',
            'imagen' => 'img/productos/default.jpg',
            'estado' => 'disponible'
        ],
        [
            'nombre' => 'Figura Orpheus Telos',
            'descripcion' => 'Figura articulada de Orpheus Telos de 25cm de altura. Pintado a mano, detalles increíbles. Incluye base y accesorios intercambiables.',
            'categoria' => 'Juguetes',
            'imagen' => 'img/productos/default.jpg',
            'estado' => 'disponible'
        ],
        [
            'nombre' => 'Manga Persona 3 - Colección Completa',
            'descripcion' => 'Colección completa del manga de Persona 3 (6 tomos). En español, estado excelente. Incluye páginas a color y portadas alternativas.',
            'categoria' => 'Libros',
            'imagen' => 'img/productos/default.jpg',
            'estado' => 'disponible'
        ]
    ];
    
    foreach ($productos as $index => $producto) {
        $stmt = $pdo->prepare("
            INSERT INTO productos (nombre, descripcion, categoria, imagen, estado, user_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $producto['nombre'],
            $producto['descripcion'],
            $producto['categoria'],
            $producto['imagen'],
            $producto['estado'],
            $usuario_id
        ]);
        
        $producto_id = $pdo->lastInsertId();
        echo "<p>✅ Producto creado: <strong>{$producto['nombre']}</strong> (ID: $producto_id)</p>";
    }
    
    // Commit de la transacción
    $pdo->commit();
    
    echo "<hr>";
    echo "<h3>✅ ¡Proceso Completado!</h3>";
    echo "<p><strong>Resumen:</strong></p>";
    echo "<ul>";
    echo "<li>👤 Usuario creado: Francisco Torrecillas (ID: $usuario_id)</li>";
    echo "<li>📦 Productos creados: " . count($productos) . "</li>";
    echo "<li>🔑 Contraseña: orpheus</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>📝 Cómo usar:</h3>";
    echo "<ol>";
    echo "<li>Ve a <a href='iniciarsesion.php'>Iniciar Sesión</a></li>";
    echo "<li>Email: <code>francisco.torrecillas@example.com</code></li>";
    echo "<li>Contraseña: <code>orpheus</code></li>";
    echo "<li>Verás los 5 productos creados en <a href='mis-productos.php'>Mis Productos</a></li>";
    echo "</ol>";
    
    echo "<hr>";
    echo "<h3>🎮 Referencia a Persona:</h3>";
    echo "<p><em>Orpheus</em> es el Persona inicial del protagonista en Persona 3, ";
    echo "representando al legendario músico de la mitología griega. ";
    echo "Posteriormente evoluciona a <em>Orpheus Telos</em> en The Answer.</p>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
    h2 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
    h3 { color: #34495e; margin-top: 20px; }
    p { line-height: 1.6; }
    code { background: #ecf0f1; padding: 2px 6px; border-radius: 3px; }
    ul, ol { line-height: 1.8; }
    hr { margin: 30px 0; border: none; border-top: 2px solid #ecf0f1; }
    a { color: #3498db; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
