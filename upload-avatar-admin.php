<?php
/**
 * Script temporal para subir avatar de cualquier usuario
 * ELIMINAR después de usar por seguridad
 */

require_once 'config/database.php';

$message = '';
$error = '';

// Procesar subida de imagen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    $file = $_FILES['avatar'];
    
    // Validar archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileInfo = getimagesize($file['tmp_name']);
    
    if ($fileInfo && in_array($fileInfo['mime'], $allowedTypes)) {
        $uploadDir = 'uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Actualizar base de datos
            try {
                $pdo = getConnection();
                
                // Eliminar avatar anterior si existe
                $stmt = $pdo->prepare("SELECT avatar_path FROM usuarios WHERE id = ?");
                $stmt->execute([$userId]);
                $oldAvatar = $stmt->fetchColumn();
                
                if ($oldAvatar && $oldAvatar !== 'img/usuario.svg' && file_exists($oldAvatar)) {
                    unlink($oldAvatar);
                }
                
                // Actualizar nuevo avatar
                $stmt = $pdo->prepare("UPDATE usuarios SET avatar_path = ? WHERE id = ?");
                $stmt->execute([$filePath, $userId]);
                
                $message = "✅ Avatar actualizado correctamente para usuario ID: $userId";
            } catch (Exception $e) {
                $error = "❌ Error al actualizar la base de datos: " . $e->getMessage();
            }
        } else {
            $error = "❌ Error al mover el archivo";
        }
    } else {
        $error = "❌ Tipo de archivo no válido. Usa JPG, PNG, GIF o WebP";
    }
}

// Obtener lista de usuarios
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, username, fullname, avatar_path FROM usuarios ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Avatar - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 8px;
            color: #856404;
            font-weight: 600;
        }
        .message {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            color: #155724;
            font-weight: 600;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            color: #721c24;
            font-weight: 600;
        }
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .user-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 3px solid #667eea;
        }
        .user-info {
            margin-bottom: 15px;
        }
        .user-name {
            font-weight: 700;
            color: #333;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .user-username {
            color: #667eea;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .user-id {
            color: #999;
            font-size: 12px;
        }
        .upload-form {
            margin-top: 15px;
        }
        .file-input {
            display: none;
        }
        .upload-label {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .upload-label:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .file-name {
            margin-top: 8px;
            font-size: 12px;
            color: #666;
            min-height: 18px;
        }
        .upload-btn {
            display: none;
            margin-top: 10px;
            padding: 8px 16px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        .upload-btn:hover {
            background: #218838;
            transform: scale(1.05);
        }
        .upload-btn.show {
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📸 Subir Avatar - Admin</h1>
        <div class="warning">
            ⚠️ Este es un script temporal de administración. ELIMÍNALO después de usarlo por seguridad.
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="users-grid">
            <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <img 
                        src="<?= $user['avatar_path'] ?: 'img/usuario.svg' ?>" 
                        alt="Avatar" 
                        class="avatar-preview"
                    >
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($user['fullname']) ?></div>
                        <div class="user-username">@<?= htmlspecialchars($user['username']) ?></div>
                        <div class="user-id">ID: <?= $user['id'] ?></div>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <input 
                            type="file" 
                            name="avatar" 
                            accept="image/*" 
                            class="file-input" 
                            id="file-<?= $user['id'] ?>"
                            onchange="showFileName(this, <?= $user['id'] ?>)"
                        >
                        <label for="file-<?= $user['id'] ?>" class="upload-label">
                            📁 Elegir imagen
                        </label>
                        <div class="file-name" id="filename-<?= $user['id'] ?>"></div>
                        <button type="submit" class="upload-btn" id="btn-<?= $user['id'] ?>">
                            ✅ Subir Avatar
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        function showFileName(input, userId) {
            const filenameDiv = document.getElementById('filename-' + userId);
            const uploadBtn = document.getElementById('btn-' + userId);
            
            if (input.files && input.files[0]) {
                filenameDiv.textContent = '📎 ' + input.files[0].name;
                uploadBtn.classList.add('show');
            } else {
                filenameDiv.textContent = '';
                uploadBtn.classList.remove('show');
            }
        }
    </script>
</body>
</html>
