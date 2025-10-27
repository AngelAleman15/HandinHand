<?php
// publicar-producto.php
session_start();
require_once 'includes/functions.php';
requireLogin();

$page_title = "Publicar Producto - HandinHand";
$body_class = "body-add-product";

require_once 'config/database.php';
$pdo = getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $condicion = trim($_POST['condicion'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $estado = 'disponible';
    $user = getCurrentUser();

    if (empty($nombre) || empty($descripcion) || empty($ubicacion)) {
        $error = 'Nombre, descripción y ubicación son obligatorios.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO productos (user_id, nombre, descripcion, categoria, condicion, ubicacion, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$user['id'], $nombre, $descripcion, $categoria, $condicion, $ubicacion, $estado]);
            $producto_id = $pdo->lastInsertId();
            header('Location: editar-producto.php?id=' . $producto_id);
            exit();
        } catch (Exception $e) {
            $error = 'Error al publicar el producto: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="edit-product-container">
    <div class="edit-product-card">
        <div class="card-header">
            <h1>📦 Publicar Producto</h1>
            <p>Completa la información para publicar tu producto</p>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-tag"></i> Nombre del Producto</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="descripcion"><i class="fas fa-align-left"></i> Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" required maxlength="500" placeholder="Describe tu producto en detalle..."></textarea>
                </div>
                <div class="form-group">
                    <label for="categoria"><i class="fas fa-list"></i> Categoría</label>
                    <select id="categoria" name="categoria" class="form-control form-select">
                        <option value="">Seleccionar categoría</option>
                        <option value="Electrónicos">📱 Electrónicos</option>
                        <option value="Ropa">👕 Ropa</option>
                        <option value="Libros">📚 Libros</option>
                        <option value="Deportes">⚽ Deportes</option>
                        <option value="Hogar">🏠 Hogar</option>
                        <option value="Juguetes">🧸 Juguetes</option>
                        <option value="Música">🎵 Música</option>
                        <option value="Otro">🔧 Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="condicion"><i class="fas fa-star"></i> Condición</label>
                    <select id="condicion" name="condicion" class="form-control form-select">
                        <option value="">Seleccionar condición</option>
                        <option value="nuevo">Nuevo</option>
                        <option value="usado">Usado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ubicacion"><i class="fas fa-map-marker-alt"></i> Ubicación para el intercambio</label>
                    <input type="text" id="ubicacion" name="ubicacion" class="form-control" required maxlength="150" placeholder="Ejemplo: Parque Central, Ciudad, Estado">
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Publicar Producto</button>
                    <a href="mis-productos.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
