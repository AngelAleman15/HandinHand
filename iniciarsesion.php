<?php

// session_start(): Función de PHP que inicia una nueva sesión o reanuda una existente
// Las sesiones permiten mantener información del usuario entre diferentes páginas web
// Por ejemplo, después del login, la sesión recordará que el usuario está autenticado
session_start();

// Variables de configuración de la página que serán utilizadas por el header para personalizar la apariencia
$page_title = "HandinHand - Iniciar Sesión"; // Título que aparecerá en la pestaña del navegador
$body_class = "body-lr"; // Clase CSS que se aplicará al body para estilos específicos de login/registro
$footer_style = "background-color: rgba(255, 255, 255); border: none;"; // Estilos CSS inline para el footer
$additional_scripts = []; // Array vacío para scripts adicionales que podría necesitar la página

// Inicializa la variable que contendrá mensajes de error para mostrar al usuario
$error_message = '';

// $_SERVER: Variable superglobal de PHP que contiene información sobre headers, paths y script locations
// ['REQUEST_METHOD']: Contiene el método HTTP usado para acceder a la página (GET, POST, PUT, DELETE, etc.)
// == 'POST': Verifica si el formulario fue enviado mediante método POST (cuando el usuario hace clic en "Iniciar Sesión")
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // require_once: Incluye un archivo PHP una sola vez. Si ya fue incluido, no lo vuelve a incluir
    // Incluye el archivo de configuración de base de datos que contiene la función getConnection()
    require_once 'config/database.php';
    
    // $_POST: Variable superglobal que contiene todos los datos enviados por formularios con method="post"
    // trim(): Función que elimina espacios en blanco (espacios, tabs, saltos de línea) del inicio y final de una cadena
    // Obtiene y limpia los datos enviados por el formulario
    $username = trim($_POST['username']); // Nombre de usuario ingresado, sin espacios extra
    $password = trim($_POST['password']); // Contraseña ingresada (sin hashear aún), sin espacios extra
    
    // empty(): Función que verifica si una variable está vacía (null, "", 0, false, etc.)
    // Validación básica: verifica que ambos campos no estén vacíos
    if (empty($username) || empty($password)) {
        $error_message = 'Por favor, complete todos los campos.'; // Mensaje de error si falta información
    } else {
        // try-catch: Estructura para manejar excepciones (errores) que puedan ocurrir
        // Permite capturar errores de base de datos sin que la aplicación se rompa
        try {
            // getConnection(): Función personalizada por nosotros definida en config/database.php
            // Devuelve un objeto PDO configurado para conectarse a la base de datos MySQL
            // $pdo: Variable que contiene el objeto PDO (PHP Data Objects) para interactuar con la base de datos
            $pdo = getConnection();
            
            // prepare(): Método de PDO que prepara una consulta SQL para ejecutar de forma segura
            // Los "?" son placeholders que serán reemplazados por valores reales de forma segura
            // $stmt: Variable que contiene el statement (consulta preparada) listo para ejecutar
            $stmt = $pdo->prepare("SELECT id, username, password FROM usuarios WHERE username = ?");
            
            // execute(): Método que ejecuta la consulta preparada pasando los parámetros en un array
            // [$username]: Array con los valores que reemplazarán los "?" en orden
            // Esto previene inyección SQL porque PDO escapa automáticamente los caracteres especiales
            $stmt->execute([$username]);
            
            // fetch(): Método que obtiene UNA fila del resultado de la consulta
            // PDO::FETCH_ASSOC: Constante que indica que queremos un array asociativo (clave => valor)
            // $user: Contendrá los datos del usuario encontrado o null si no existe
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // password_verify(): Función de PHP que compara una contraseña en texto plano con su hash
            // $password: Contraseña ingresada por el usuario
            // $user['password']: Hash de la contraseña almacenado en la base de datos
            // Devuelve true si coinciden, false si no
            if ($user && password_verify($password, $user['password'])) {
                // $_SESSION: Variable superglobal que almacena datos que persisten durante toda la sesión del usuario
                // Guarda la información del usuario en la sesión para futuras páginas
                $_SESSION['user_id'] = $user['id']; // ID único del usuario para futuras consultas
                $_SESSION['username'] = $user['username']; // Nombre de usuario para mostrar en la interfaz
                
                // header(): Función que envía un header HTTP al navegador
                // 'Location: index.php': Redirige al navegador a la página principal
                header('Location: index.php');
                // exit(): Termina la ejecución del script inmediatamente para asegurar la redirección
                exit();
            } else {
                // Si las credenciales son incorrectas, establece mensaje de error genérico por seguridad
                $error_message = 'Verifica tu usuario y contraseña, alguno de estos es incorrecto.';
            }
        // catch: Captura cualquier excepción de tipo PDOException (errores de base de datos)
        } catch (PDOException $e) {
            // $e: Variable que contiene información del error ocurrido
            // Si ocurre un error de base de datos, muestra mensaje genérico sin exponer detalles técnicos
            $error_message = 'Error en el sistema. Intente más tarde.';
        }
    }
}

// include: Función de PHP que incluye y ejecuta el contenido de otro archivo PHP
// Incluye el archivo header.php que contiene la estructura HTML inicial, CSS y navegación
include 'includes/header.php';
?>

<style>
/* === ESTILOS MODERNOS PARA LOGIN === */
body.body-lr {
    background-color: #f8f9fa;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding: 0;
    margin: 0;
}

.page-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px; /* Reducido de 20px */
    padding-top: 70px; /* Reducido de 80px */
    width: 100%;
}

.login-container {
    max-width: 900px; /* Reducido de 1000px */
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.6s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Columna izquierda - Quote */
.quote-section {
    background: transparent;
    padding: 30px 25px; /* Reducido de 40px 30px */
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #2d3748;
    position: relative;
    overflow: hidden;
}

.quote-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(106, 153, 78, 0.05) 0%, transparent 70%);
    animation: pulse 15s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.quote-logo {
    width: 120px; /* Reducido de 140px */
    height: auto;
    margin-bottom: 15px; /* Reducido de 20px */
    filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));
    position: relative;
    z-index: 1;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.quote-text {
    font-size: 19px; /* Reducido de 22px */
    font-weight: 600;
    text-align: center;
    font-style: italic;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    position: relative;
    z-index: 1;
    line-height: 1.4;
}

.quote-text::before {
    content: '"';
    font-size: 60px;
    position: absolute;
    top: -20px;
    left: -10px;
    opacity: 0.3;
}

/* Columna derecha - Formulario */
.form-section {
    padding: 35px 35px; /* Reducido de 40px 40px */
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.form-header {
    margin-bottom: 25px; /* Reducido de 30px */
}

.form-title {
    font-size: 24px; /* Reducido de 26px */
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 6px; /* Reducido de 8px */
}

.form-subtitle {
    color: #718096;
    font-size: 13px; /* Reducido de 14px */
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 14px; /* Reducido de 16px */
}

.form-group {
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 6px; /* Reducido de 8px */
    color: #4a5568;
    font-weight: 500;
    font-size: 13px; /* Reducido de 14px */
}

.form-group label i {
    margin-right: 6px;
    color: #6a994e;
}

.form-input {
    width: 100%;
    padding: 9px 11px; /* Reducido de 10px 12px */
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px; /* Reducido de 14px */
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-input:focus {
    outline: none;
    border-color: #6a994e;
    background: white;
    box-shadow: 0 0 0 3px rgba(106, 153, 78, 0.1);
}

.form-input.error {
    border-color: #e53e3e;
    background: #fff5f5;
}

.error-message {
    color: #e53e3e;
    font-size: 13px;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
    min-height: 20px;
}

.error-message i {
    font-size: 14px;
}

.btn-login {
    background: linear-gradient(135deg, #6a994e 0%, #5a8840 100%);
    color: white;
    border: none;
    padding: 16px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
    box-shadow: 0 4px 12px rgba(106, 153, 78, 0.3);
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(106, 153, 78, 0.4);
}

.btn-login:active {
    transform: translateY(0);
}

.form-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #e2e8f0;
}

.form-footer a {
    color: #6a994e;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.form-footer a:hover {
    color: #5a8840;
    text-decoration: underline;
}

/* Estilos del footer para login/registro */
body.body-lr .footer {
    background-color: #ffffff;
    position: relative;
    margin: 0;
    padding: 30px 0;
    width: 100%;
    border-top: 1px solid #e0e0e0;
}

body.body-lr .footer .socialcontainer,
body.body-lr .footer .footerinfo {
    position: relative;
    z-index: 1;
    color: #333;
}

body.body-lr .footer .socialinfo {
    color: #666;
}

/* Responsive */
@media (max-width: 968px) {
    body.body-lr {
        padding: 0;
    }
    
    .page-wrapper {
        padding: 20px;
    }
    
    .login-container {
        grid-template-columns: 1fr;
        max-width: 500px;
    }
    
    .quote-section {
        padding: 40px 30px;
    }
    
    .quote-logo {
        width: 120px;
        margin-bottom: 20px;
    }
    
    .quote-text {
        font-size: 20px;
    }
    
    .form-section {
        padding: 40px 30px;
    }
    
    .form-title {
        font-size: 26px;
    }
}

@media (max-width: 576px) {
    .page-wrapper {
        padding: 10px;
    }
    
    .login-container {
        border-radius: 16px;
    }
    
    .form-section {
        padding: 30px 20px;
    }
    
    .quote-section {
        padding: 30px 20px;
    }
}
</style>

<div class="page-wrapper">
<div class="login-container">
    <!-- Sección de Quote -->
    <div class="quote-section">
        <img src="img/Hand(sinfondo).png" alt="HandinHand Logo" class="quote-logo">
        <p class="quote-text">Unite, Creá, Transformá</p>
    </div>
    
    <!-- Sección de Formulario -->
    <div class="form-section">
        <div class="form-header">
            <h1 class="form-title">¡Bienvenido!</h1>
            <p class="form-subtitle">Inicia sesión para continuar</p>
        </div>
        
        <form class="login-form" method="POST" action="iniciarsesion.php">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i>
                    Nombre de usuario
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input<?php echo !empty($error_message) ? ' error' : ''; ?>" 
                    placeholder="Ingresa tu usuario"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i>
                    Contraseña
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input<?php echo !empty($error_message) ? ' error' : ''; ?>" 
                    placeholder="Ingresa tu contraseña"
                    required>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar Sesión
            </button>
        </form>
        
        <div class="form-footer">
            ¿No tienes una cuenta? 
            <a href="registrar.php">Regístrate aquí</a>
        </div>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>
