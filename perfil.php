<?php
session_start();
require_once 'includes/functions.php';

// Verificar que esté logueado
requireLogin();

// Configuración de la página
$page_title = "Mi Perfil - HandinHand";
$body_class = "body-profile";

// Obtener datos del usuario
$user = getCurrentUser();

// Incluir header
include 'includes/header.php';
?>

<div class="container">
    <div class="profile-header">
        <h1>Mi Perfil</h1>
    </div>
    
    <div class="profile-content">
        <div class="profile-info">
            <div class="profile-avatar">
                <img src="img/usuario.png" alt="Avatar">
            </div>
            
            <div class="profile-details">
                <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>
                <p><strong>Usuario:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                
                <div class="profile-actions">
                    <button class="btn btn-primary" onclick="editProfile()">Editar Perfil</button>
                    <button class="btn btn-secondary" onclick="window.location.href='mis-productos.php'">Ver mis productos</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
}

.profile-header {
    text-align: center;
    margin-bottom: 30px;
}

.profile-header h1 {
    color: #6a994e;
    font-size: 2.5em;
    margin-bottom: 10px;
}

.profile-content {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.profile-info {
    display: flex;
    align-items: center;
    gap: 30px;
}

.profile-avatar img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid #6a994e;
}

.profile-details h2 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.8em;
}

.profile-details p {
    margin-bottom: 10px;
    color: #666;
    font-size: 1.1em;
}

.profile-actions {
    margin-top: 20px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1em;
    margin-right: 10px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background-color: #6a994e;
    color: white;
}

.btn-primary:hover {
    background-color: #5a8142;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}

@media (max-width: 600px) {
    .profile-info {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
function editProfile() {
    Swal.fire({
        title: 'Editar Perfil',
        text: 'Funcionalidad de edición de perfil próximamente',
        icon: 'info',
        confirmButtonColor: '#6a994e'
    });
}
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
