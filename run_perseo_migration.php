<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración - Sistema de Notificaciones Perseo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .result {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .success {
            border-left: 4px solid #28a745;
            background: #d4edda;
            color: #155724;
        }
        
        .error {
            border-left: 4px solid #dc3545;
            background: #f8d7da;
            color: #721c24;
        }
        
        .info {
            border-left: 4px solid #17a2b8;
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .result-item {
            margin: 10px 0;
            padding: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .icon {
            font-size: 24px;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: transform 0.2s;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
        }
        
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Migración de Base de Datos</h1>
        <p class="subtitle">Sistema de Notificaciones con Perseo</p>
        
        <?php
        require_once 'config/database.php';
        
        $results = [];
        $hasErrors = false;
        
        try {
            $pdo = getConnection();
            
            // Verificar si la columna ya existe
            $checkColumn = $pdo->query("SHOW COLUMNS FROM mensajes LIKE 'is_perseo_auto'");
            $columnExists = $checkColumn->rowCount() > 0;
            
            if ($columnExists) {
                $results[] = [
                    'type' => 'info',
                    'icon' => 'ℹ️',
                    'message' => 'La columna "is_perseo_auto" ya existe en la tabla mensajes'
                ];
            } else {
                // Agregar columna is_perseo_auto
                try {
                    $sql1 = "ALTER TABLE mensajes ADD COLUMN is_perseo_auto TINYINT(1) DEFAULT 0 AFTER message";
                    $pdo->exec($sql1);
                    $results[] = [
                        'type' => 'success',
                        'icon' => '✅',
                        'message' => 'Columna "is_perseo_auto" agregada correctamente'
                    ];
                } catch (Exception $e) {
                    // Si falla, puede ser porque ya existe en MySQL que no soporta IF NOT EXISTS en ALTER
                    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                        $results[] = [
                            'type' => 'info',
                            'icon' => 'ℹ️',
                            'message' => 'La columna "is_perseo_auto" ya existe'
                        ];
                    } else {
                        throw $e;
                    }
                }
            }
            
            // Verificar si el índice ya existe
            $checkIndex = $pdo->query("SHOW INDEX FROM mensajes WHERE Key_name = 'idx_unread_messages'");
            $indexExists = $checkIndex->rowCount() > 0;
            
            if ($indexExists) {
                $results[] = [
                    'type' => 'info',
                    'icon' => 'ℹ️',
                    'message' => 'El índice "idx_unread_messages" ya existe'
                ];
            } else {
                // Crear índice
                try {
                    $sql2 = "CREATE INDEX idx_unread_messages ON mensajes(receiver_id, is_read)";
                    $pdo->exec($sql2);
                    $results[] = [
                        'type' => 'success',
                        'icon' => '✅',
                        'message' => 'Índice "idx_unread_messages" creado correctamente'
                    ];
                } catch (Exception $e) {
                    // Si falla, puede ser porque ya existe
                    if (strpos($e->getMessage(), 'Duplicate key') !== false) {
                        $results[] = [
                            'type' => 'info',
                            'icon' => 'ℹ️',
                            'message' => 'El índice "idx_unread_messages" ya existe'
                        ];
                    } else {
                        throw $e;
                    }
                }
            }
            
            // Verificar que todo esté correcto
            $verifyColumn = $pdo->query("SHOW COLUMNS FROM mensajes LIKE 'is_perseo_auto'");
            $verifyIndex = $pdo->query("SHOW INDEX FROM mensajes WHERE Key_name = 'idx_unread_messages'");
            
            if ($verifyColumn->rowCount() > 0 && $verifyIndex->rowCount() > 0) {
                $results[] = [
                    'type' => 'success',
                    'icon' => '🎉',
                    'message' => '<strong>¡Migración completada exitosamente!</strong>'
                ];
            }
            
        } catch (Exception $e) {
            $hasErrors = true;
            $results[] = [
                'type' => 'error',
                'icon' => '❌',
                'message' => 'Error en la migración: ' . htmlspecialchars($e->getMessage())
            ];
        }
        
        // Mostrar resultados
        foreach ($results as $result) {
            echo '<div class="result ' . $result['type'] . '">';
            echo '<div class="result-item">';
            echo '<span class="icon">' . $result['icon'] . '</span>';
            echo '<span>' . $result['message'] . '</span>';
            echo '</div>';
            echo '</div>';
        }
        
        if (!$hasErrors) {
            echo '<div class="warning">';
            echo '<strong>⚠️ Importante:</strong> Si hiciste cambios en el código PHP o JavaScript, asegúrate de limpiar el caché del navegador (Ctrl + Shift + R) para ver los cambios.';
            echo '</div>';
        }
        ?>
        
        <a href="index.php" class="back-btn">← Volver al Inicio</a>
    </div>
</body>
</html>
