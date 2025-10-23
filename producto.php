<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$producto = null;
if ($id) {
    // Obtener datos reales desde la API
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "api/productos.php?id=" . $id);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    $data = json_decode($response, true);
    if ($data && isset($data['data']) && is_array($data['data']) && !empty($data['data']['id'])) {
        $producto = $data['data'];
    }
}
if (!$producto) {
    echo '<div style="padding:2em;text-align:center;color:#A2CB8D;font-size:1.3em">Producto no encontrado o no disponible</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($producto['nombre']); ?> - HandinHand</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/producto.css">
</head>
<body>
<div class="producto-container">
    <div class="producto-imagenes">
        <div class="carrusel">
            <?php if (!empty($producto['imagenes'])): ?>
                <?php foreach ($producto['imagenes'] as $i => $img): ?>
                    <div class="carrusel-item<?php echo $i === 0 ? ' activo' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="Foto del producto">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="carrusel-item activo"><img src="img/productos/default.jpg" alt="Foto del producto"></div>
            <?php endif; ?>
            <button class="carrusel-btn prev">&#10094;</button>
            <button class="carrusel-btn next">&#10095;</button>
        </div>
    </div>
    <div class="producto-info">
        <h1 class="producto-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
        <div class="producto-estado">Estado: <?php echo htmlspecialchars($producto['estado']); ?></div>
        <div class="producto-categorias">
            Categorías:
            <?php if (!empty($producto['categorias'])): ?>
                <?php foreach ($producto['categorias'] as $cat): ?>
                    <span class="categoria-badge"><?php echo htmlspecialchars(is_array($cat) ? $cat['nombre'] : $cat); ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="producto-valoracion">
            Valoración del vendedor: <span class="stars">&#9733; <?php echo number_format($producto['promedio_estrellas'], 1); ?></span>
            (<?php echo (int)$producto['total_valoraciones']; ?> valoraciones)
        </div>
        <div class="producto-vendedor">
            <img src="<?php echo !empty($producto['avatar_path']) ? htmlspecialchars($producto['avatar_path']) : 'img/default-avatar.png'; ?>" class="vendedor-avatar" alt="Avatar vendedor">
            <span class="vendedor-nombre"><?php echo htmlspecialchars($producto['vendedor_name']); ?></span>
        </div>
        <div class="producto-punto">
            <strong>Punto de encuentro:</strong>
            <?php if (!empty($producto['latitud']) && !empty($producto['longitud'])): ?>
                <span class="punto-badge">GPS: <?php echo $producto['latitud'] . ', ' . $producto['longitud']; ?></span>
                <button class="btn-mapa" onclick="window.open('https://www.google.com/maps?q=<?php echo $producto['latitud'] . ',' . $producto['longitud']; ?>','_blank')">Ver en mapa</button>
                <div style="margin-top:10px">
                    <iframe width="320" height="180" style="border-radius:8px;border:1px solid #A2CB8D" frameborder="0" src="https://maps.google.com/maps?q=<?php echo $producto['latitud'] . ',' . $producto['longitud']; ?>&z=15&output=embed"></iframe>
                </div>
            <?php else: ?>
                <span class="punto-badge">No definido</span>
            <?php endif; ?>
        </div>
        <div class="producto-acciones">
            <button class="btn-contactar">Contactar vendedor</button>
            <button class="btn-denunciar">Denunciar</button>
            <button class="btn-compartir">Compartir</button>
            <button class="btn-favorito">&#9734; Favorito</button>
        </div>
    </div>
</div>
<script src="js/producto.js"></script>
</body>
</html>
