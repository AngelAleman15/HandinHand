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

    <div class="main-content">
        <div class="cardquote">
            <img src="img/Hand(sinfondo).png" alt="H&H">
            <p>"Unite, Creá, Transformá"</p>
        </div>
        <div class="login">
            <div class="login-title">Registrarse</div>
            <!-- Formulario de registro que se envía a sí mismo (registrar.php) mediante POST -->
            <!-- novalidate: Atributo que desactiva la validación HTML5 del navegador para usar validación PHP personalizada -->
            <form class="registration-form" id="registration-form" action="registrar.php" method="post" novalidate>
                <div class="fieldscontainer">
                    <!-- CAMPO DE NOMBRE COMPLETO -->
                    <!-- Sintaxis de PHP para ejecutar código dentro de HTML -->
                    <!-- isset($field_errors['fullname']): Verifica si existe error específico para este campo -->
                    <!-- ? ' error' : '': Operador ternario - si hay error añade clase 'error', si no añade cadena vacía -->
                    <div><input class="field namefield<?php echo isset($field_errors['fullname']) ? ' error' : ''; ?>" type="text" name="firstname" placeholder="Nombre completo" id="fullname" required value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>"></div>
                    
                    <!-- CAMPO DE NOMBRE DE USUARIO -->
                    <!-- value="...": Preserva el valor ingresado si hubo un error para que el usuario no tenga que reescribirlo -->
                    <!-- htmlspecialchars(): Función que convierte caracteres especiales a entidades HTML para prevenir XSS -->
                    <div><input class="field namefield<?php echo isset($field_errors['username']) ? ' error' : ''; ?>" type="text" name="lastname" placeholder="Nombre de usuario" id="username" required value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>"></div>
                    
                    <!-- CAMPO DE EMAIL -->
                    <!-- type="email": Tipo de input HTML5 que proporciona validación básica de formato de email en el navegador -->
                    <div><input class="field namefield<?php echo isset($field_errors['email']) ? ' error' : ''; ?>" type="email" name="email" placeholder="Correo electrónico" id="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"></div>
                    
                    <!-- CAMPO DE TELÉFONO -->
                    <!-- type="tel": Tipo de input HTML5 optimizado para números de teléfono -->
                    <div><input class="field namefield<?php echo isset($field_errors['phone']) ? ' error' : ''; ?>" type="tel" name="phone" placeholder="Número de teléfono" id="phone" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"></div>
                    
                    <!-- CAMPO DE CONTRASEÑA -->
                    <!-- type="password": Oculta los caracteres ingresados por seguridad -->
                    <!-- value preservado para debugging (en producción se podría omitir por seguridad) -->
                    <div><input class="field passwordfield<?php echo isset($field_errors['password']) ? ' error' : ''; ?>" type="password" name="password" placeholder="Contraseña" id="password" required value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>"></div>
                    
                    <!-- CAMPO DE CONFIRMACIÓN DE CONTRASEÑA -->
                    <!-- Validación adicional para asegurar que el usuario escribió la contraseña correctamente -->
                    <div><input class="field passwordfield<?php echo isset($field_errors['confirm_password']) ? ' error' : ''; ?>" type="password" name="confirm_password" placeholder="Confirmar contraseña" id="confirm_password" required value="<?php echo isset($_POST['confirm_password']) ? htmlspecialchars($_POST['confirm_password']) : ''; ?>"></div>
                    
                    <!-- CAMPO DE FECHA DE NACIMIENTO -->
                    <!-- type="date": Tipo de input HTML5 que proporciona un selector de fecha -->
                    <div><input class="field namefield<?php echo isset($field_errors['birthdate']) ? ' error' : ''; ?>" type="date" name="birthdate" placeholder="Fecha de nacimiento" id="birthdate" required value="<?php echo isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : ''; ?>"></div>
                    
                    <!-- ÁREA DE MENSAJES DE ERROR -->
                    <!-- style="...": CSS inline que cambia según si hay error o no -->
                    <!-- 'color: red;': Muestra el texto en rojo si hay error -->
                    <!-- 'color: transparent;': Oculta el texto si no hay error (pero mantiene el espacio) -->
                    <p class="error-message" id="error" style="<?php echo !empty($error_message) ? 'color: red;' : 'color: transparent;'; ?>">
                        <!-- htmlspecialchars($error_message): Sanitiza el mensaje de error para prevenir inyección de código -->
                        <?php echo htmlspecialchars($error_message); ?><?php echo empty($error_message) ? 'p' : ''; ?>
                    </p>
                    <!-- empty($error_message) ? 'p' : '': Si no hay error, muestra una 'p' invisible para mantener la altura del elemento -->
                </div>
                <!-- Botón de envío del formulario -->
                <!-- type="submit": Especifica que este botón enviará el formulario -->
                <button class="btnlogin" type="submit" id="btn-register">Registrarse</button>
            </form>
            <div class="login-footer">
                <!-- Enlace para usuarios que ya tienen cuenta -->
                <a href="iniciarsesion.php" class="text">¿Ya tienes cuenta? Inicia Sesión.</a>
            </div>
        </div>
    </div>

<?php
// Incluye el archivo footer.php que contiene el cierre de la estructura HTML y scripts finales
include 'includes/footer.php';
?>
