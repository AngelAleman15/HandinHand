<?php
session_start();

// Configuración de la página
$page_title = "HandinHand - Registrarse";
$body_class = "body-lr";
$footer_style = "background-color: rgba(255, 255, 255); border: none;";
$additional_scripts = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11'
];

// Procesar formulario de registro
$error_message = '';
$success_message = '';
$field_errors = []; // Array para rastrear errores específicos de campos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'config/database.php';
    
    $fullname = trim($_POST['firstname']);
    $username = trim($_POST['lastname']); // En tu HTML original, lastname era el username
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $birthdate = trim($_POST['birthdate']);
    
    // Validaciones específicas por campo
    if (empty($fullname)) {
        $field_errors['fullname'] = true;
    }
    if (empty($username)) {
        $field_errors['username'] = true;
    }
    if (empty($email)) {
        $field_errors['email'] = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $field_errors['email'] = true;
        $error_message = 'El formato del email es incorrecto. Debe ser: ejemplo@correo.com';
    }
    if (empty($phone)) {
        $field_errors['phone'] = true;
    }
    if (empty($password)) {
        $field_errors['password'] = true;
    } elseif (strlen($password) < 6) {
        $field_errors['password'] = true;
        $error_message = 'La contraseña es muy corta. Debe tener al menos 6 caracteres';
    }
    if (empty($confirm_password)) {
        $field_errors['confirm_password'] = true;
    } elseif ($password !== $confirm_password) {
        $field_errors['password'] = true;
        $field_errors['confirm_password'] = true;
        $error_message = 'Las contraseñas no coinciden';
    }
    if (empty($birthdate)) {
        $field_errors['birthdate'] = true;
    } else {
        // Validar fecha de nacimiento y edad
        $birthDate = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$birthDate) {
            $field_errors['birthdate'] = true;
            $error_message = 'Formato de fecha de nacimiento inválido.';
        } else {
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            if ($age < 18) {
                $field_errors['birthdate'] = true;
                $error_message = 'Debes ser mayor de 18 años para poder registrarte en HandInHand';
            }
        }
    }
    
    // Si hay campos vacíos, mostrar mensaje general
    if (!empty($field_errors) && empty($error_message)) {
        $error_message = 'Por favor, complete todos los campos.';
    }
    
    // Si no hay errores, proceder con el registro
    if (empty($field_errors)) {
        try {
            $pdo = getConnection();
            
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $field_errors['username'] = true;
                $field_errors['email'] = true;
                $error_message = 'El nombre de usuario o email ya está en uso.';
            } else {
                // Insertar nuevo usuario
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (fullname, username, email, phone, password, birthdate, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                
                if ($stmt->execute([$fullname, $username, $email, $phone, $hashed_password, $birthdate])) {
                    // No mostrar mensaje de éxito, redirigir directamente
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            window.location.href = 'iniciarsesion.php';
                        });
                    </script>";
                    exit();
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
                    <div><input class="field namefield<?php echo isset($field_errors['fullname']) ? ' error' : ''; ?>" type="text" name="firstname" placeholder="Nombre completo" id="fullname" required value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>"></div>
                    <div><input class="field namefield<?php echo isset($field_errors['username']) ? ' error' : ''; ?>" type="text" name="lastname" placeholder="Nombre de usuario" id="username" required value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>"></div>
                    <div><input class="field namefield<?php echo isset($field_errors['email']) ? ' error' : ''; ?>" type="email" name="email" placeholder="Correo electrónico" id="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"></div>
                    <div><input class="field namefield<?php echo isset($field_errors['phone']) ? ' error' : ''; ?>" type="tel" name="phone" placeholder="Número de teléfono" id="phone" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"></div>
                    <div><input class="field passwordfield<?php echo isset($field_errors['password']) ? ' error' : ''; ?>" type="password" name="password" placeholder="Contraseña" id="password" required value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>"></div>
                    <div><input class="field passwordfield<?php echo isset($field_errors['confirm_password']) ? ' error' : ''; ?>" type="password" name="confirm_password" placeholder="Confirmar contraseña" id="confirm_password" required value="<?php echo isset($_POST['confirm_password']) ? htmlspecialchars($_POST['confirm_password']) : ''; ?>"></div>
                    <div><input class="field namefield<?php echo isset($field_errors['birthdate']) ? ' error' : ''; ?>" type="date" name="birthdate" placeholder="Fecha de nacimiento" id="birthdate" required value="<?php echo isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : ''; ?>"></div>
                    <p class="error-message" id="error" style="<?php echo !empty($error_message) ? 'color: red;' : 'color: transparent;'; ?>">
                        <?php echo htmlspecialchars($error_message); ?><?php echo empty($error_message) ? 'p' : ''; ?>
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
// Incluir footer
include 'includes/footer.php';
?>
