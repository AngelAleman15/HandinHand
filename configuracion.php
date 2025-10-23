<?php
session_start();
require_once 'includes/functions.php';

// Verificar que esté logueado
requireLogin();

// Configuración de la página
$page_title = "Configuración - HandinHand";
$body_class = "body-settings";

// Incluir header
include 'includes/header.php';
?>

<div class="container">
    <div class="settings-header">
        <h1>Configuración</h1>
        <p>Personaliza tu experiencia en HandinHand</p>
    </div>
    
    <div class="settings-content">
        <div class="settings-section">
            <h2>Cuenta</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Cambiar contraseña</h3>
                    <p>Actualiza tu contraseña para mantener tu cuenta segura</p>
                </div>
                <button class="btn btn-secondary" onclick="changePassword()">Cambiar</button>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Actualizar información personal</h3>
                    <p>Modifica tu nombre, email y otros datos personales</p>
                </div>
                <button class="btn btn-secondary" onclick="showWipMessage('Actualizar información personal')">Editar <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span></button>
            </div>
        </div>
        
        <div class="settings-section">
            <h2>Notificaciones</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Notificaciones por email <span style="font-size: 0.8em; opacity: 0.7; margin-left: 5px;">(WIP)</span></h3>
                    <p>Recibe emails cuando alguien te contacte por un producto</p>
                </div>
                <label class="switch" onclick="showWipMessage('Notificaciones por email')">
                    <input type="checkbox" checked disabled>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Recordatorios de intercambio <span style="font-size: 0.8em; opacity: 0.7; margin-left: 5px;">(WIP)</span></h3>
                    <p>Te recordaremos cuando tengas intercambios pendientes</p>
                </div>
                <label class="switch" onclick="showWipMessage('Recordatorios de intercambio')">
                    <input type="checkbox" disabled>
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        
        <div class="settings-section">
            <h2>Privacidad</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Perfil público <span style="font-size: 0.8em; opacity: 0.7; margin-left: 5px;">(WIP)</span></h3>
                    <p>Permite que otros usuarios vean tu perfil y valoraciones</p>
                </div>
                <label class="switch" onclick="showWipMessage('Perfil público')">
                    <input type="checkbox" checked disabled>
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        
        <div class="settings-section danger-zone">
            <h2>Zona de peligro</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Eliminar cuenta</h3>
                    <p>Elimina permanentemente tu cuenta y todos tus datos</p>
                </div>
                <button class="btn btn-danger" onclick="showWipMessage('Eliminar Cuenta')">Eliminar cuenta <span style="font-size: 0.8em; opacity: 0.7;">(WIP)</span></button>
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

.settings-header {
    text-align: center;
    margin-bottom: 40px;
}

.settings-header h1 {
    color: #6a994e;
    font-size: 2.5em;
    margin-bottom: 10px;
}

.settings-header p {
    color: #666;
    font-size: 1.1em;
}

.settings-content {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.settings-section {
    padding: 30px;
    border-bottom: 1px solid #eee;
}

.settings-section:last-child {
    border-bottom: none;
}

.settings-section h2 {
    color: #333;
    font-size: 1.5em;
    margin-bottom: 20px;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    border-bottom: 1px solid #f5f5f5;
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-info h3 {
    color: #333;
    margin-bottom: 5px;
    font-size: 1.1em;
}

.setting-info p {
    color: #666;
    font-size: 0.9em;
    line-height: 1.4;
}

.btn {
    padding: 8px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9em;
    text-decoration: none;
    display: inline-block;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

/* Switch Toggle */
.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #6a994e;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.danger-zone {
    background-color: #fff5f5;
}

.danger-zone h2 {
    color: #dc3545;
}

@media (max-width: 600px) {
    .setting-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .setting-item .btn,
    .setting-item .switch {
        align-self: flex-end;
    }
}
</style>

<script>
function changePassword() {
    Swal.fire({
        title: 'Cambiar contraseña',
        text: 'Funcionalidad próximamente',
        icon: 'info',
        confirmButtonColor: '#6a994e'
    });
}

function updateInfo() {
    Swal.fire({
        title: 'Actualizar información',
        text: 'Funcionalidad próximamente',
        icon: 'info',
        confirmButtonColor: '#6a994e'
    });
}

function deleteAccount() {
    Swal.fire({
        title: '¿Estás absolutamente seguro?',
        text: 'Esta acción eliminará permanentemente tu cuenta y todos tus datos. No se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar mi cuenta',
        cancelButtonText: 'Cancelar',
        input: 'text',
        inputPlaceholder: 'Escribe "ELIMINAR" para confirmar',
        inputValidator: (value) => {
            if (value !== 'ELIMINAR') {
                return 'Debes escribir "ELIMINAR" para confirmar';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Cuenta eliminada',
                text: 'Funcionalidad próximamente',
                icon: 'info',
                confirmButtonColor: '#6a994e'
            });
        }
    });
}
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
