<?php
session_start();

echo "<h2>Test de Valoración</h2>";

echo "<h3>Sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Usuario logueado:</h3>";
echo "ID: " . ($_SESSION['user_id'] ?? 'NO LOGUEADO') . "<br>";
echo "Username: " . ($_SESSION['username'] ?? 'NO LOGUEADO') . "<br>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'><strong>ERROR: Debes iniciar sesión para valorar</strong></p>";
    echo "<a href='iniciarsesion.php'>Ir a iniciar sesión</a>";
} else {
    echo "<p style='color: green;'><strong>✓ Estás logueado correctamente</strong></p>";
    
    echo "<h3>Test de API:</h3>";
    echo "<button onclick='testValoracion()'>Probar enviar valoración</button>";
    echo "<div id='resultado'></div>";
}
?>

<script>
function testValoracion() {
    const data = {
        action: 'crear',
        usuario_id: 2, // ID del vendedor
        puntuacion: 4,
        comentario: 'Test de valoración'
    };
    
    console.log('Enviando:', data);
    
    fetch('api/valoraciones.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response:', text);
        document.getElementById('resultado').innerHTML = '<pre>' + text + '</pre>';
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('resultado').innerHTML = '<p style="color: red;">Error: ' + error + '</p>';
    });
}
</script>
