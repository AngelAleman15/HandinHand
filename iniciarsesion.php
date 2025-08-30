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

    <div class="main-content">
        <div class="cardquote">
            <img src="img/Hand(sinfondo).png" alt="H&H">
            <p>"Unite, Creá, Transformá"</p>
        </div>
        <div class="login">
            <div class="login-title">Iniciar Sesión</div>
            <!-- Formulario de login que se envía a iniciarsesion.php mediante POST -->
            <form class="login-form" id="login-form" action="iniciarsesion.php" method="post" novalidate>
                <div class="fieldscontainer">
                    <!-- Campo de nombre de usuario con validación visual de errores -->
                    <!-- Sintaxis de PHP para ejecutar código dentro de HTML -->
                    <!-- !empty($error_message): Verifica si hay un mensaje de error -->
                    <!-- ? ' error' : '': Operador ternario - si hay error añade clase 'error', si no añade cadena vacía -->
                    <div><input class="field namefield<?php echo !empty($error_message) ? ' error' : ''; ?>" type="text" name="username" id="name" placeholder="Nombre de usuario" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"></div>
                    <!-- isset($_POST['username']): Verifica si la variable existe y no es null -->
                    <!-- htmlspecialchars(): Función que convierte caracteres especiales a entidades HTML para prevenir XSS -->
                    <!-- value="...": Preserva el valor ingresado si hubo un error para que el usuario no tenga que reescribirlo -->
                    
                    <!-- Campo de contraseña con la misma validación visual de errores -->
                    <div><input class="field passwordfield<?php echo !empty($error_message) ? ' error' : ''; ?>" type="password" name="password" id="password" placeholder="Contraseña" required></div>
                    
                    <!-- Área de mensajes de error que se muestra condicionalmente -->
                    <!-- style="...": CSS inline que cambia según si hay error o no -->
                    <!-- 'color: red;': Muestra el texto en rojo si hay error -->
                    <!-- 'color: transparent;': Oculta el texto si no hay error (pero mantiene el espacio) -->
                    <p class="error-message" id="error" style="<?php echo !empty($error_message) ? 'color: red;' : 'color: transparent;'; ?>"><?php echo htmlspecialchars($error_message); ?><?php echo empty($error_message) ? 'p' : ''; ?></p>
                    <!-- htmlspecialchars($error_message): Sanitiza el mensaje de error para prevenir inyección de código -->
                    <!-- empty($error_message) ? 'p' : '': Si no hay error, muestra una 'p' invisible para mantener la altura del elemento -->
                     
                </div>
                <!-- Botón de envío del formulario -->
                <button class="btnlogin" type="submit" id="login-button">Iniciar Sesión</button>
            </form>
            <div class="login-footer">
                <!-- Enlace para usuarios que no tienen cuenta -->
                <a href="registrar.php" class="text">¿No tienes una cuenta? Regístrate.</a>
            </div>
        </div>
    </div>

<?php
// Incluye el archivo footer.php que contiene el cierre de la estructura HTML y scripts finales
include 'includes/footer.php';
?>
