<?php
/**
 * Script para actualizar scores del sistema FYP
 * Ejecutar después de importar las tablas o cuando sea necesario recalcular
 */

require_once 'config/database.php';

try {
    $pdo = getConnection();
    
    echo "<h2>🔄 Actualizando Scores del Sistema FYP...</h2>";
    
    // 1. Verificar que las tablas existen
    echo "<h3>1️⃣ Verificando tablas...</h3>";
    $tablas = ['producto_vistas', 'producto_guardados', 'producto_chats', 'producto_scores', 'producto_similitudes'];
    foreach ($tablas as $tabla) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla <strong>$tabla</strong> existe<br>";
        } else {
            echo "❌ Tabla <strong>$tabla</strong> NO existe - debes importar sql/crear_sistema_fyp.sql<br>";
            exit;
        }
    }
    
    // 2. Mostrar estadísticas actuales
    echo "<h3>2️⃣ Estadísticas actuales:</h3>";
    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM producto_vistas) as total_vistas,
            (SELECT COUNT(*) FROM producto_guardados) as total_guardados,
            (SELECT COUNT(*) FROM producto_chats) as total_chats,
            (SELECT COUNT(*) FROM productos WHERE estado = 'disponible') as productos_disponibles
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 Total vistas: <strong>{$stats['total_vistas']}</strong><br>";
    echo "⭐ Total guardados: <strong>{$stats['total_guardados']}</strong><br>";
    echo "💬 Total chats: <strong>{$stats['total_chats']}</strong><br>";
    echo "📦 Productos disponibles: <strong>{$stats['productos_disponibles']}</strong><br>";
    
    if ($stats['total_vistas'] == 0 && $stats['total_guardados'] == 0 && $stats['total_chats'] == 0) {
        echo "<br><strong>⚠️ No hay interacciones registradas todavía.</strong><br>";
        echo "Las vistas se registran automáticamente al ver productos.<br>";
        echo "Visita algunos productos y vuelve a ejecutar este script.<br>";
    }
    
    // 3. Limpiar y recalcular scores
    echo "<h3>3️⃣ Recalculando scores...</h3>";
    $pdo->exec("TRUNCATE TABLE producto_scores");
    echo "🗑️ Tabla producto_scores limpiada<br>";
    
    $stmt = $pdo->exec("
        INSERT INTO producto_scores (producto_id, total_vistas, total_guardados, total_chats, score_total)
        SELECT 
            p.id,
            COALESCE(COUNT(DISTINCT pv.id), 0) as total_vistas,
            COALESCE(COUNT(DISTINCT pg.id), 0) as total_guardados,
            COALESCE(COUNT(DISTINCT pc.id), 0) as total_chats,
            -- Fórmula: vistas×1 + guardados×3 + chats×5 + valoraciones×2
            (COALESCE(COUNT(DISTINCT pv.id), 0) * 1) +
            (COALESCE(COUNT(DISTINCT pg.id), 0) * 3) +
            (COALESCE(COUNT(DISTINCT pc.id), 0) * 5) +
            (COALESCE(p.total_valoraciones, 0) * 2) as score_total
        FROM productos p
        LEFT JOIN producto_vistas pv ON p.id = pv.producto_id
        LEFT JOIN producto_guardados pg ON p.id = pg.producto_id
        LEFT JOIN producto_chats pc ON p.id = pc.producto_id
        WHERE p.estado = 'disponible'
        GROUP BY p.id
    ");
    
    echo "✅ Scores calculados para todos los productos<br>";
    
    // 4. Calcular similitudes
    echo "<h3>4️⃣ Calculando similitudes entre productos...</h3>";
    $pdo->exec("TRUNCATE TABLE producto_similitudes");
    
    $stmt = $pdo->exec("
        INSERT INTO producto_similitudes (producto_a_id, producto_b_id, veces_visto_juntos, similitud_score)
        SELECT 
            pv1.producto_id as producto_a_id,
            pv2.producto_id as producto_b_id,
            COUNT(DISTINCT COALESCE(pv1.usuario_id, pv1.session_id)) as veces_visto_juntos,
            COUNT(DISTINCT COALESCE(pv1.usuario_id, pv1.session_id)) * 10 as similitud_score
        FROM producto_vistas pv1
        INNER JOIN producto_vistas pv2 
            ON COALESCE(pv1.usuario_id, pv1.session_id) = COALESCE(pv2.usuario_id, pv2.session_id)
            AND pv1.producto_id < pv2.producto_id
        GROUP BY pv1.producto_id, pv2.producto_id
        HAVING COUNT(DISTINCT COALESCE(pv1.usuario_id, pv1.session_id)) >= 1
    ");
    
    echo "✅ Similitudes calculadas<br>";
    
    // 5. Mostrar TOP 10 productos recomendados
    echo "<h3>5️⃣ TOP 10 Productos Recomendados:</h3>";
    $top = $pdo->query("
        SELECT 
            p.nombre,
            ps.total_vistas,
            ps.total_guardados,
            ps.total_chats,
            ps.score_total
        FROM producto_scores ps
        JOIN productos p ON ps.producto_id = p.id
        ORDER BY ps.score_total DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($top) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #6a994e; color: white;'>
                <th>Producto</th>
                <th>Vistas</th>
                <th>Guardados</th>
                <th>Chats</th>
                <th>Score Total</th>
              </tr>";
        
        foreach ($top as $producto) {
            echo "<tr>";
            echo "<td>{$producto['nombre']}</td>";
            echo "<td>{$producto['total_vistas']}</td>";
            echo "<td>{$producto['total_guardados']}</td>";
            echo "<td>{$producto['total_chats']}</td>";
            echo "<td><strong>{$producto['score_total']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No hay productos con score calculado todavía.</p>";
    }
    
    echo "<br><h2 style='color: #6a994e;'>✅ Actualización completada exitosamente!</h2>";
    echo "<p>Los productos recomendados ya deberían aparecer en el FYP.</p>";
    echo "<p><a href='index.php' style='background: #6a994e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Volver al inicio</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>";
    echo "<p>{$e->getMessage()}</p>";
    echo "<pre>{$e->getTraceAsString()}</pre>";
}
?>
