<?php
session_start();

// Configuración de la página
$page_title = "HandinHand - Iniciar Sesión";
$body_class = "body-lr";
$footer_style = "background-color: rgba(255, 255, 255); border: none;";
$additional_scripts = [];

// Procesar formulario de login
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'config/database.php';
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error_message = 'Por favor, complete todos los campos.';
    } else {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT id, username, password FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: index.php');
                exit();
            } else {
                $error_message = 'Verifica tu usuario y contraseña, alguno de estos es incorrecto.';
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
            <div class="login-title">Iniciar Sesión</div>
            <form class="login-form" id="login-form" action="iniciarsesion.php" method="post" novalidate>
                <div class="fieldscontainer">
                    <div><input class="field namefield<?php echo !empty($error_message) ? ' error' : ''; ?>" type="text" name="username" id="name" placeholder="Nombre de usuario" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"></div>
                    <div><input class="field passwordfield<?php echo !empty($error_message) ? ' error' : ''; ?>" type="password" name="password" id="password" placeholder="Contraseña" required></div>
                    <p class="error-message" id="error" style="<?php echo !empty($error_message) ? 'color: red;' : 'color: transparent;'; ?>"><?php echo htmlspecialchars($error_message); ?><?php echo empty($error_message) ? 'p' : ''; ?></p>
                </div>
                <button class="btnlogin" type="submit" id="login-button">Iniciar Sesión</button>
            </form>
            <div class="login-footer">
                <a href="registrar.php" class="text">¿No tienes una cuenta? Regístrate.</a>
            </div>
        </div>
    </div>

<?php
// Incluir footer
include 'includes/footer.php';
?>
