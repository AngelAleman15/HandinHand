<?php
session_start();

// Configuración de la página
$page_title = "HandinHand - Registrarse";
$body_class = "body-lr";
$additional_scripts = [
    'js/validacion.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11'
];

// Procesar formulario de registro
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'config/database.php';
    
    $fullname = trim($_POST['firstname']);
    $username = trim($_POST['lastname']); // En tu HTML original, lastname era el username
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $birthdate = trim($_POST['birthdate']);
    
    // Validaciones básicas
    if (empty($fullname) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($birthdate)) {
        $error_message = 'Por favor, complete todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Por favor, utilice el formato email@ejemplo.com';
    } elseif (strlen($password) < 6) {
        $error_message = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        try {
            $pdo = getConnection();
            
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error_message = 'El nombre de usuario o email ya está en uso.';
            } else {
                // Insertar nuevo usuario
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (fullname, username, email, phone, password, birthdate, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                
                if ($stmt->execute([$fullname, $username, $email, $phone, $hashed_password, $birthdate])) {
                    $success_message = 'Registro exitoso. Ahora puedes iniciar sesión.';
                    // Limpiar formulario
                    $_POST = [];
                } else {
                    $error_message = 'Error al registrar usuario. Intente más tarde.';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Error en el sistema. Intente más tarde.';
        }
    }
}

// Incluir header
include 'includes/header.php';
?>

    <div class="main-content">
        <div class="cardquote">
            <img src="img/Hand(sinfondo).png" alt="H&H">
            <p>"Unite, Creá, Transformá"</p>
        </div>
        <div class="login">
            <div class="login-title">Registrarse</div>
            <form class="registration-form" id="registration-form" action="registrar.php" method="post" novalidate>
                <div class="fieldscontainer">
                    <div><input class="field namefield" type="text" name="firstname" placeholder="Nombre completo" id="fullname" required value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>"></div>
                    <div><input class="field namefield" type="text" name="lastname" placeholder="Nombre de usuario" id="username" required value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>"></div>
                    <div><input class="field namefield" type="email" name="email" placeholder="Correo electrónico" id="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"></div>
                    <div><input class="field namefield" type="tel" name="phone" placeholder="Número de teléfono" id="phone" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"></div>
                    <div><input class="field passwordfield" type="password" name="password" placeholder="Contraseña" id="password" required></div>
                    <div><input class="field namefield" type="date" name="birthdate" placeholder="Fecha de nacimiento" id="birthdate" required value="<?php echo isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : ''; ?>"></div>
                    <p class="error-message" id="error" style="<?php echo !empty($error_message) ? 'color: red;' : (!empty($success_message) ? 'color: green;' : ''); ?>">
                        <?php echo htmlspecialchars($error_message ?: $success_message); ?>p
                    </p>
                </div>
                <button class="btnlogin" type="submit" id="btn-register">Registrarse</button>
            </form>
            <div class="login-footer">
                <a href="iniciarsesion.php" class="text">¿Ya tienes cuenta? Inicia Sesión.</a>
            </div>
        </div>
    </div>

<?php
// Script para mostrar SweetAlert si hay éxito
if (!empty($success_message)) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Registro exitoso',
                text: 'Te has registrado correctamente.',
                icon: 'success',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                window.location.href = 'iniciarsesion.php';
            });
        });
    </script>";
}

// Incluir footer
include 'includes/footer.php';
?>
