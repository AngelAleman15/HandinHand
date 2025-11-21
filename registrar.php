<?php
// session_start(): Función de PHP que inicia una nueva sesión o reanuda una existente
// Las sesiones permiten mantener información del usuario entre diferentes páginas web
// En este caso, aunque es registro, podemos necesitar datos de sesión para redirecciones
session_start();

// Variables de configuración de la página que serán utilizadas por el header para personalizar la apariencia
$page_title = "HandinHand - Registrarse"; // Título que aparecerá en la pestaña del navegador
$body_class = "body-lr"; // Clase CSS que se aplicará al body para estilos específicos de login/registro
$footer_style = "background-color: rgba(255, 255, 255); border: none;"; // Estilos CSS inline para el footer
// $additional_scripts: Array que contiene URLs de scripts externos necesarios para esta página
$additional_scripts = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11' // SweetAlert2 para alertas elegantes (aunque no se usa actualmente), en iniciarsesion.php también se va a usar próximamente
];

// Variables para almacenar mensajes y control de errores
$error_message = ''; // Mensaje de error general que se mostrará al usuario
$success_message = ''; // Mensaje de éxito (actualmente no usado, se redirige directamente)
// $field_errors: Array asociativo para rastrear qué campos específicos tienen errores
// Permite mostrar errores visuales en campos individuales
$field_errors = [];

// $_SERVER: Variable superglobal de PHP que contiene información sobre headers, paths y script locations
// ['REQUEST_METHOD']: Contiene el método HTTP usado para acceder a la página (GET, POST, PUT, DELETE, etc.)
// == 'POST': Verifica si el formulario fue enviado mediante método POST (cuando el usuario hace clic en "Registrarse")
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // require_once: Incluye un archivo PHP una sola vez. Si ya fue incluido, no lo vuelve a incluir
    // Incluye el archivo de configuración de base de datos que contiene la función getConnection()
    require_once 'config/database.php';
    
    // $_POST: Variable superglobal que contiene todos los datos enviados por formularios con method="post"
    // trim(): Función que elimina espacios en blanco (espacios, tabs, saltos de línea) del inicio y final de una cadena
    // Obtención y limpieza de todos los datos enviados por el formulario de registro
    $fullname = trim($_POST['firstname']); // Nombre completo del usuario
    $username = trim($_POST['lastname']); // Nombre de usuario único (nota: en HTML lastname es username)
    $email = trim($_POST['email']); // Dirección de correo electrónico
    $phone = trim($_POST['phone']); // Número de teléfono
    $password = trim($_POST['password']); // Contraseña ingresada (sin hashear aún)
    $confirm_password = trim($_POST['confirm_password']); // Confirmación de contraseña
    $birthdate = trim($_POST['birthdate']); // Fecha de nacimiento en formato Y-m-d
    
    // SECCIÓN DE VALIDACIONES ESPECÍFICAS POR CAMPO
    // Cada validación agrega el nombre del campo al array $field_errors si hay un problema
    // Esto permite mostrar errores visuales específicos en cada campo del formulario
    
    // empty(): Función que verifica si una variable está vacía (null, "", 0, false, etc.)
    // Validación del nombre completo
    if (empty($fullname)) {
        $field_errors['fullname'] = true; // Marca el campo fullname como con error
    }
    
    // Validación del nombre de usuario
    if (empty($username)) {
        $field_errors['username'] = true; // Marca el campo username como con error
    }
    
    // Validación del email con verificación de formato
    if (empty($email)) {
        $field_errors['email'] = true; // Marca el campo email como con error
    // filter_var(): Función que filtra una variable con un filtro específico
    // FILTER_VALIDATE_EMAIL: Constante que valida si el formato de email es correcto
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $field_errors['email'] = true; // Marca el campo email como con error
        $error_message = 'El formato del email es incorrecto. Debe ser: ejemplo@correo.com';
    }
    
    // Validación del número de teléfono
    if (empty($phone)) {
        $field_errors['phone'] = true; // Marca el campo phone como con error
    }
    
    // Validación de contraseña con verificación de longitud mínima
    if (empty($password)) {
        $field_errors['password'] = true; // Marca el campo password como con error
    // strlen(): Función que devuelve la longitud de una cadena de texto
    } elseif (strlen($password) < 6) {
        $field_errors['password'] = true; // Marca el campo password como con error
        $error_message = 'La contraseña es muy corta. Debe tener al menos 6 caracteres';
    }
    
    // Validación de confirmación de contraseña
    if (empty($confirm_password)) {
        $field_errors['confirm_password'] = true; // Marca el campo confirm_password como con error
    // !== : Operador de comparación estricta (diferente en valor Y tipo)
    } elseif ($password !== $confirm_password) {
        $field_errors['password'] = true; // Marca ambos campos de contraseña como con error
        $field_errors['confirm_password'] = true;
        $error_message = 'Las contraseñas no coinciden';
    }
    
    // Validación de fecha de nacimiento con verificación de edad
    if (empty($birthdate)) {
        $field_errors['birthdate'] = true; // Marca el campo birthdate como con error
    } else {
        // DateTime::createFromFormat(): Método estático que crea un objeto DateTime desde un formato específico
        // 'Y-m-d': Formato de fecha año-mes-día (ejemplo: 2000-01-15)
        $birthDate = DateTime::createFromFormat('Y-m-d', $birthdate);
        // Verifica si la fecha es válida (createFromFormat devuelve false si es inválida)
        if (!$birthDate) {
            $field_errors['birthdate'] = true;
            $error_message = 'Formato de fecha de nacimiento inválido.';
        } else {
            // new DateTime(): Crea un objeto DateTime con la fecha y hora actual
            $today = new DateTime();
            // diff(): Método que calcula la diferencia entre dos fechas
            // ->y: Propiedad que devuelve la diferencia en años
            $age = $today->diff($birthDate)->y;
            // Verificación de edad mínima (18 años)
            if ($age < 18) {
                $field_errors['birthdate'] = true;
                $error_message = 'Debes ser mayor de 18 años para poder registrarte en HandInHand';
            }
        }
    }
    
    // VERIFICACIÓN FINAL DE ERRORES Y MENSAJE GENERAL
    // !empty($field_errors): Verifica si el array $field_errors contiene algún elemento
    // empty($error_message): Verifica si no hay un mensaje de error específico ya establecido
    // Si hay campos con errores pero no hay un mensaje específico, muestra mensaje genérico
    if (!empty($field_errors) && empty($error_message)) {
        $error_message = 'Por favor, complete todos los campos.';
    }
    
    // PROCESAMIENTO DEL REGISTRO SI NO HAY ERRORES
    // empty($field_errors): Verifica que no haya errores en ningún campo
    // Solo procede con el registro si todos los campos son válidos
    if (empty($field_errors)) {
        // try-catch: Estructura para manejar excepciones (errores) que puedan ocurrir
        // Permite capturar errores de base de datos sin que la aplicación se rompa
        try {
            // getConnection(): Función personalizada definida en config/database.php
            // Devuelve un objeto PDO configurado para conectarse a la base de datos MySQL
            // $pdo: Variable que contiene el objeto PDO (PHP Data Objects) para interactuar con la base de datos
            $pdo = getConnection();
            
            // VERIFICACIÓN DE DUPLICADOS EN LA BASE DE DATOS
            // prepare(): Método de PDO que prepara una consulta SQL para ejecutar de forma segura
            // Los "?" son placeholders que serán reemplazados por valores reales de forma segura
            // Busca si ya existe un usuario con el mismo username o email
            $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE username = ? OR email = ?");
            
            // execute(): Método que ejecuta la consulta preparada pasando los parámetros en un array
            // [$username, $email]: Array con los valores que reemplazarán los "?" en orden
            $stmt->execute([$username, $email]);
            
            // fetch(): Método que obtiene UNA fila del resultado de la consulta
            // Devuelve los datos del usuario si existe, o false si no encuentra nada
            if ($stmt->fetch()) {
                // Si encuentra un usuario existente, marca los campos como con error
                $field_errors['username'] = true; // Marca username como con error
                $field_errors['email'] = true; // Marca email como con error
                $error_message = 'El nombre de usuario o email ya está en uso.';
            } else {
                // INSERCIÓN DEL NUEVO USUARIO EN LA BASE DE DATOS
                // password_hash(): Función de PHP que convierte una contraseña en un hash seguro
                // PASSWORD_DEFAULT: Constante que usa el algoritmo de hashing más seguro disponible (bcrypt)
                // El hash incluye automáticamente un "salt" único para máxima seguridad
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Preparar consulta para insertar el nuevo usuario
                // NOW(): Función de MySQL que devuelve la fecha y hora actual
                $stmt = $pdo->prepare("INSERT INTO usuarios (fullname, username, email, phone, password, birthdate, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                
                // execute(): Ejecuta la inserción con todos los datos del usuario
                // [$fullname, $username, $email, $phone, $hashed_password, $birthdate]: Array con todos los valores
                if ($stmt->execute([$fullname, $username, $email, $phone, $hashed_password, $birthdate])) {
                    // Si el registro es exitoso, redirige al usuario a la página de login
                    // echo: Imprime HTML/JavaScript que será ejecutado por el navegador
                    // document.addEventListener: Espera a que el DOM esté cargado antes de ejecutar
                    // window.location.href: Redirige el navegador a otra página
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            window.location.href = 'iniciarsesion.php';
                        });
                    </script>";
                    // exit(): Termina la ejecución del script inmediatamente para asegurar la redirección
                    exit();
                } else {
                    // Si la inserción falla, muestra mensaje de error genérico
                    $error_message = 'Error al registrar usuario. Intente más tarde.';
                }
            }
        // catch: Captura cualquier excepción de tipo PDOException (errores de base de datos)
        } catch (PDOException $e) {
            // $e: Variable que contiene información del error ocurrido
            // Si ocurre un error de base de datos, muestra mensaje genérico sin exponer detalles técnicos
            $error_message = 'Error en el sistema. Intente más tarde.';
            // En producción, aquí se podría agregar logging del error real: error_log($e->getMessage());
        }
    }
}

// include: Función de PHP que incluye y ejecuta el contenido de otro archivo PHP
// Incluye el archivo header.php que contiene la estructura HTML inicial, meta tags, CSS y navegación
include 'includes/header.php';
?>

<style>
/* === ESTILOS MODERNOS PARA REGISTRO === */
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

.register-container {
    max-width: 900px; /* Reducido de 1000px */
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1.2fr;
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
    padding: 30px 35px; /* Reducido de 35px 40px */
    display: flex;
    flex-direction: column;
    justify-content: center;
    max-height: 90vh;
    overflow-y: auto;
}

.form-section::-webkit-scrollbar {
    width: 6px;
}

.form-section::-webkit-scrollbar-thumb {
    background: #6a994e;
    border-radius: 10px;
}

.form-header {
    margin-bottom: 16px; /* Reducido de 20px */
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

.registration-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px; /* Reducido de 12px */
}

.form-group {
    position: relative;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    margin-bottom: 5px; /* Reducido de 6px */
    color: #4a5568;
    font-weight: 500;
    font-size: 12px; /* Reducido de 13px */
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

.error-message-global {
    grid-column: 1 / -1;
    color: #e53e3e;
    font-size: 13px;
    padding: 12px;
    background: #fff5f5;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-left: 3px solid #e53e3e;
}

.error-message-global i {
    font-size: 16px;
}

.btn-register {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #6a994e 0%, #5a8840 100%);
    color: white;
    border: none;
    padding: 14px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
    box-shadow: 0 4px 12px rgba(106, 153, 78, 0.3);
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(106, 153, 78, 0.4);
}

.btn-register:active {
    transform: translateY(0);
}

.form-footer {
    text-align: center;
    margin-top: 25px;
    padding-top: 25px;
    border-top: 1px solid #e2e8f0;
    grid-column: 1 / -1;
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
@media (max-width: 1100px) {
    body.body-lr {
        padding: 0;
    }
    
    .page-wrapper {
        padding: 20px;
    }
    
    .register-container {
        grid-template-columns: 1fr;
        max-width: 600px;
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
        padding: 40px 35px;
        max-height: none;
    }
}

@media (max-width: 576px) {
    .page-wrapper {
        padding: 10px;
    }
    
    .register-container {
        border-radius: 16px;
    }
    
    .registration-form {
        grid-template-columns: 1fr;
    }
    
    .form-section {
        padding: 30px 20px;
    }
    
    .quote-section {
        padding: 30px 20px;
    }
    
    .form-title {
        font-size: 26px;
    }
}
</style>

<div class="page-wrapper">
<div class="register-container">
    <!-- Sección de Quote -->
    <div class="quote-section">
        <img src="img/Hand(sinfondo).png" alt="HandinHand Logo" class="quote-logo">
        <p class="quote-text">Unite, Creá, Transformá</p>
    </div>
    
    <!-- Sección de Formulario -->
    <div class="form-section">
        <div class="form-header">
            <h1 class="form-title">Crear Cuenta</h1>
            <p class="form-subtitle">Únete a HandinHand hoy</p>
        </div>
        
        <form class="registration-form" method="POST" action="registrar.php">
            <div class="form-group">
                <label for="fullname">
                    <i class="fas fa-user"></i>
                    Nombre completo
                </label>
                <input 
                    type="text" 
                    id="fullname" 
                    name="firstname" 
                    class="form-input<?php echo isset($field_errors['fullname']) ? ' error' : ''; ?>" 
                    placeholder="Tu nombre completo"
                    value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>"
                    required>
            </div>
            
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-at"></i>
                    Nombre de usuario
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="lastname" 
                    class="form-input<?php echo isset($field_errors['username']) ? ' error' : ''; ?>" 
                    placeholder="Tu usuario único"
                    value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>"
                    required>
            </div>
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i>
                    Correo electrónico
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input<?php echo isset($field_errors['email']) ? ' error' : ''; ?>" 
                    placeholder="correo@ejemplo.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required>
            </div>
            
            <div class="form-group">
                <label for="phone">
                    <i class="fas fa-phone"></i>
                    Teléfono
                </label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    class="form-input<?php echo isset($field_errors['phone']) ? ' error' : ''; ?>" 
                    placeholder="Tu número de teléfono"
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
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
                    class="form-input<?php echo isset($field_errors['password']) ? ' error' : ''; ?>" 
                    placeholder="Mínimo 6 caracteres"
                    required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-lock"></i>
                    Confirmar contraseña
                </label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="form-input<?php echo isset($field_errors['confirm_password']) ? ' error' : ''; ?>" 
                    placeholder="Repite tu contraseña"
                    required>
            </div>
            
            <div class="form-group full-width">
                <label for="birthdate">
                    <i class="fas fa-calendar"></i>
                    Fecha de nacimiento (debes ser mayor de 18 años)
                </label>
                <input 
                    type="date" 
                    id="birthdate" 
                    name="birthdate" 
                    class="form-input<?php echo isset($field_errors['birthdate']) ? ' error' : ''; ?>" 
                    value="<?php echo isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : ''; ?>"
                    required>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message-global">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i>
                Crear Cuenta
            </button>
            
            <div class="form-footer">
                ¿Ya tienes cuenta? 
                <a href="iniciarsesion.php">Inicia sesión aquí</a>
            </div>
        </form>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>
