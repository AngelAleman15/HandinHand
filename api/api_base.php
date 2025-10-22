<?php

// CONFIGURACIÓN DE HEADERS HTTP PARA APIs
// header(): Función de PHP que manda "carteles" al navegador antes de mostrar nada.
// Los headers HTTP son como los mensajes que se pasan entre el servidor y el cliente (el navegador).
// Acá, los headers le avisan al navegador cosas como: "esto es JSON", "dejá entrar a cualquiera", o "solo acepto GET, POST, ETC".
// Son instrucciones que van antes del contenido, para que todo se entienda bien y no haya lío.
// 'Content-Type: application/json': Le dice al navegador que el contenido será en formato JSON
// JSON: JavaScript Object Notation - formato de intercambio de datos legible y estándar
// charset=utf-8: Especifica que el texto usará codificación UTF-8 (para caracteres especiales como ñ, á, etc.)
header('Content-Type: application/json; charset=utf-8');

// CONFIGURACIÓN CORS (Cross-Origin Resource Sharing)
// CORS: Mecanismo de seguridad que permite o restringe solicitudes entre diferentes dominios
// 'Access-Control-Allow-Origin: *': Permite que cualquier dominio/sitio web haga solicitudes a esta API
// *: Asterisco significa "todos los dominios"
header('Access-Control-Allow-Origin: *');

// 'Access-Control-Allow-Methods': Acá le decimos al navegador qué "palabras" HTTP puede usar.
// Es como decirle: "podés usar GET, POST, PUT, DELETE, OPTIONS".
// GET: Para pedir/leer datos, POST: Para crear/mandar datos, PUT: Para actualizar datos
// DELETE: Para borrar datos, OPTIONS: Para preguntar qué se puede hacer
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// 'Access-Control-Allow-Headers': Le dice al navegador qué "headers" (cabeceras) puede mandar en las solicitudes.
// Es como una lista de "papeles" que el navegador puede incluir cuando hace una petición.
// Content-Type: Para decir qué tipo de contenido está mandando, Authorization: Para mandar tokens de login
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// MANEJO DE PREFLIGHT REQUESTS
// Preflight request: Es como cuando el navegador pregunta "¿puedo hacer esto?" antes de hacerlo de verdad.
// Es una solicitud automática que manda el navegador para verificar si el servidor le va a permitir hacer lo que quiere.
// $_SERVER: Es una variable gigante de PHP que tiene toda la info del servidor y de la solicitud que llegó.
// ['REQUEST_METHOD']: La parte que dice QUÉ quiere hacer el navegador (GET, POST, PUT, etc.)
// === 'OPTIONS': Estamos preguntando si la solicitud es del tipo "OPTIONS" (la pregunta de permiso)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // http_response_code(): Le decimos al navegador qué "número de respuesta" mandamos.
    // 200: Es como decir la solicitud salió bien
    // Otros códigos: 400=está mal tu pedido, 401=no tenés permiso, 404=no lo encontré, 500=se rompió el servidor
    http_response_code(200);
    
    // exit(): Le decimos a PHP "hasta acá llegamos, no hagas nada más".
    // Para las solicitudes OPTIONS solo necesitamos mandar los headers y listo.
    exit();
}

/**
 * FUNCIÓN PARA MANDAR RESPUESTAS DE ÉXITO
 * PHPDoc: Es como la "documentación oficial" que leen las herramientas de programación
 * Es un estándar para escribir comentarios que explican qué hace una función
 * Esta función la usamos cuando todo sale bien en nuestras APIs
 */

// Parámetros con valores "por si acaso":
// $data = []: Si no se le pasan datos, queda como un array vacío []
// $message = 'Operación exitosa': Si no se le pasa mensaje, usa este por defecto
// $code = 200: Si no se le pasa código, usa 200, que significa "todo ok"
function sendSuccess($data = [], $message = 'Operación exitosa', $code = 200) {
    // http_response_code(): Le decimos al navegador qué número de respuesta mandamos
    http_response_code($code);
    
    // echo: Lo que hace es imprimir algo para que lo vea el que pidió la información
    // json_encode(): Convierte nuestras cosas de PHP en formato JSON
    // Array asociativo: Como un recipiente con compartimientos etiquetados
    echo json_encode([
        'success' => true, // true: Le decimos "sí, todo salió bien"
        'message' => $message, // El mensaje que queremos mostrar
        'data' => $data, // La información real que estamos devolviendo
        'timestamp' => date('Y-m-d H:i:s') // La fecha y hora de este momento, formato: año-mes-día hora:minuto:segundo
    ], JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE: Para que respete las ñ, acentos y esas cosas
    
    exit();
}

/**
 * FUNCIÓN PARA MANDAR RESPUESTAS DE ERROR
 * Esta la usamos cuando algo sale mal en nuestras APIs
 * Hace que todos los errores se vean iguales y sean fáciles de entender
 */
// Parámetros:
// $message: El mensaje de error que queremos mostrar
// $code: El número de error HTTP (400 por defecto = "tu pedido está mal")
// $details: Info extra sobre el error
function sendError($message = 'Error en la operación', $code = 400, $details = null) {
    // http_response_code(): Le ponemos una "etiqueta de error" a nuestra respuesta
    // Códigos famosos: 400=está mal tu pedido, 401=no tenés permiso, 404=no lo encontré, 500=se rompió nuestro servidor
    http_response_code($code);
    
    // $response: Variable donde vamos armando la respuesta de error
    // La armamos de a pedacitos para que sea más claro qué estamos haciendo
    $response = [
        'success' => false, // false: Le decimos "no, algo salió mal"
        'message' => $message, // El mensaje de error para que el usuario entienda qué pasó
        'timestamp' => date('Y-m-d H:i:s') // Cuándo pasó este error (fecha y hora actual)
    ];

    if ($details !== null) {
        // Si nos pasaron detalles extra, los agregamos a la respuesta
        // Es como poner una "nota adicional" explicando más sobre el error
        $response['details'] = $details;
    }
    
    // Limpiar cualquier output previo (warnings, etc.)
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // json_encode(): Convertimos nuestro array PHP en JSON para mandarlo
    // echo: Lo imprimimos para que llegue al navegador/aplicación que pidió la info
    // Limpiar cualquier output previo (warnings, etc.)
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
    exit();
}

/**
 * FUNCIÓN PARA CHEQUEAR QUÉ MÉTODOS HTTP ESTÁN PERMITIDOS
 * Verifica que usen nuestra API de la forma correcta
 * Es como un control de entrada, solo deja pasar lo que debe
 */

// $allowedMethods: Lista de métodos que SÍ están permitidos
function validateMethod($allowedMethods) {
    // $_SERVER['REQUEST_METHOD']: Acá PHP nos dice QUÉ método usó el navegador
    // Es como preguntarle ¿viniste a buscar info (GET) o a mandar info (POST)?
    $method = $_SERVER['REQUEST_METHOD'];
    
    // in_array(): Función que busca si algo está dentro de una lista
    // !in_array(): El signo ! significa "NO", así que preguntamos "¿NO está en la lista?"
    // Si el método que usaron NO está en nuestra lista de permitidos, entonces hay problema
    if (!in_array($method, $allowedMethods)) {
        // implode(): Convierte una lista en texto, separando con comas
        // ', ': La coma y espacio que va entre cada elemento
        // $method: El método que usaron y que NO está permitido
        // 405: Código de error que significa "ese método no se puede usar acá"
        sendError("Método $method no permitido. Métodos permitidos: " . implode(', ', $allowedMethods), 405);
    }
}

/**
 * FUNCIÓN PARA AGARRAR DATOS JSON QUE MANDA EL NAVEGADOR
 * Cuando el navegador nos manda info en formato JSON, esta función la agarra y la convierte
 * Es como un "traductor" que convierte el JSON en algo que PHP puede usar
 */
// No necesita parámetros porque lee directamente lo que llegó de la solicitud
function getJsonInput() {
    // file_get_contents(): Función que lee contenido completo de archivos
    // 'php://input': Stream especial de PHP que contiene el cuerpo crudo de la solicitud HTTP
    // Stream: Flujo de datos que se puede leer secuencialmente
    // $input: Variable que almacenará el contenido JSON en formato string
    $input = file_get_contents('php://input');
    
    // json_decode(): Función que convierte string JSON a estructuras de datos PHP
    // $input: String JSON que queremos decodificar
    // true: Segundo parámetro, si es true devuelve arrays asociativos, si es false devuelve objetos
    // $data: Variable que contendrá los datos PHP resultantes de la decodificación
    $data = json_decode($input, true);
    
    // VALIDACIÓN DE ERRORES JSON
    // json_last_error(): Función que retorna el último error que ocurrió en json_decode()
    // JSON_ERROR_NONE: Constante de PHP que indica "sin errores en JSON"
    // !== JSON_ERROR_NONE: Comparación estricta, verifica si SÍ hubo errores
    // !empty($input): Verifica que el input no esté vacío (evita errores en requests sin contenido)
    if (json_last_error() !== JSON_ERROR_NONE && !empty($input)) {
        // Si hay error de JSON Y el input no está vacío, enviar respuesta de error
        // 400: Bad Request, la solicitud está mal formada
        sendError('JSON inválido en el cuerpo de la petición', 400);
    }
    
    // ?: Operador ternario abreviado
    // $data ?: [] significa: si $data es verdadero/válido retorna $data, sino retorna array vacío []
    return $data ?: [];
}

/**
 * FUNCIÓN PARA VALIDAR CAMPOS REQUERIDOS
 * Verifica que todos los campos obligatorios estén presentes y con contenido
 * Proporciona retroalimentación específica sobre qué información falta
 */

// $data: Array que contiene los datos a validar (por ejemplo, de $_POST o JSON)
// $requiredFields: Array que lista los nombres de campos que son obligatorios
function validateRequired($data, $requiredFields) {
    // $missing: Array que acumulará los nombres de campos que faltan
    // Inicializar como array vacío para ir añadiendo elementos
    $missing = [];
    
    // ITERACIÓN SOBRE CAMPOS REQUERIDOS
    // foreach: Estructura de control que recorre cada elemento de un array
    // $requiredFields as $field: Sintaxis que asigna cada elemento del array a la variable $field
    // En cada vuelta del bucle, $field contendrá el nombre de un campo requerido
    foreach ($requiredFields as $field) {
        // VALIDACIÓN MÚLTIPLE POR CAMPO
        // isset(): Función que verifica si una variable/clave existe y no es null
        // !isset($data[$field]): Verifica si el campo NO existe en los datos
        // empty(): Función que verifica si una variable está vacía (null, "", 0, false, array vacío)
        // trim(): Función que elimina espacios en blanco del principio y final de un string
        // ||: Operador lógico OR - la condición es verdadera si cualquier parte es verdadera
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            // Si el campo no existe O está vacío (sin contar espacios), añadirlo a la lista de faltantes
            // []: Sintaxis para añadir un elemento al final de un array
            $missing[] = $field;
        }
    }
    
    // VERIFICACIÓN FINAL Y RESPUESTA DE ERROR
    // !empty($missing): Verifica si el array de campos faltantes NO está vacío
    if (!empty($missing)) {
        // implode(): Función que convierte array a string usando un separador
        // ', ': Coma y espacio como separador para crear lista legible
        // Crear mensaje de error descriptivo con la lista de campos faltantes
        sendError('Campos requeridos faltantes: ' . implode(', ', $missing), 400);
    }
}

/**
 * FUNCIÓN PARA VERIFICAR AUTENTICACIÓN DE USUARIO
 * Valida que el usuario esté logueado antes de permitir acceso a recursos protegidos
 * Utiliza el sistema de sesiones de PHP para mantener estado entre solicitudes
 */
// requireAuth: Nombre que describe la función (requiere autenticación)
// Sin parámetros porque verifica el estado global de la sesión actual
function requireAuth() {
    // Iniciar sesión solo si no hay una activa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // VERIFICACIÓN DE ESTADO DE AUTENTICACIÓN
    // isset(): Verifica si la variable de sesión existe y no es null
    // !isset(): Negación - verifica si NO existe la variable
    // $_SESSION: Variable superglobal que almacena datos de sesión en el servidor
    // ['user_id']: Clave específica que indica si hay un usuario autenticado
    if (!isset($_SESSION['user_id'])) {
        // Si no hay usuario autenticado, enviar error de autorización
        // 401: Código HTTP "Unauthorized" - se requiere autenticación
        sendError('Se requiere autenticación', 401);
    }
    
    // return: Devuelve el ID del usuario autenticado
    // Esto permite que las APIs usen el ID sin verificar la sesión nuevamente
    // El ID se puede usar para consultas de base de datos específicas del usuario
    return $_SESSION['user_id'];
}

/**
 * FUNCIÓN REQUIRELOGIN() - ALIAS DE REQUIREAUTH()
 * Verifica que el usuario esté autenticado
 * Es un alias para mantener compatibilidad con código existente
 */
function requireLogin() {
    return requireAuth();
}

/**
 * FUNCIÓN PARA OBTENER LOS DATOS DEL USUARIO ACTUAL
 * Retorna todos los datos del usuario autenticado desde la base de datos
 * Incluye información completa del perfil del usuario
 */
function getCurrentUser() {
    // Iniciar sesión solo si no hay una activa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar que haya sesión activa
    if (!isset($_SESSION['user_id'])) {
        sendError('Se requiere autenticación', 401);
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Conectar a la base de datos
    require_once __DIR__ . '/../config/database.php';
    
    try {
        // Obtener conexión PDO
        $pdo = getConnection();
        
        // Consultar datos del usuario
        $stmt = $pdo->prepare("
            SELECT 
                id,
                fullname,
                username,
                email,
                phone,
                password,
                birthdate,
                created_at,
                updated_at,
                avatar_path
            FROM usuarios 
            WHERE id = ?
        ");
        
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar que el usuario exista
        if (!$user) {
            sendError('Usuario no encontrado', 404);
        }
        
        return $user;
        
    } catch (PDOException $e) {
        error_log("Error al obtener usuario actual: " . $e->getMessage());
        sendError('Error al obtener datos del usuario', 500);
    }
}

/**
 * FUNCIÓN PARA SANITIZAR/LIMPIAR DATOS DE ENTRADA
 * Procesa datos para eliminar contenido peligroso y prevenir ataques de seguridad
 * Funciona tanto con strings individuales como con arrays de datos (recursivamente)
 */
// sanitizeData: Nombre que describe la función (sanitizar datos)
// $data: Parámetro que puede ser string, array, o cualquier tipo de dato
function sanitizeData($data) {
    // VERIFICACIÓN DE TIPO DE DATO
    // is_array(): Función que verifica si la variable es un array
    // Array: Estructura de datos que puede contener múltiples valores
    if (is_array($data)) {
        // PROCESAMIENTO RECURSIVO DE ARRAYS
        // array_map(): Función que aplica una función a cada elemento de un array
        // 'sanitizeData': Nombre de esta misma función (llamada recursiva)
        // $data: Array que se va a procesar
        // Recursivo: La función se llama a sí misma para procesar arrays anidados
        return array_map('sanitizeData', $data);
    }
    
    // PROCESAMIENTO DE STRINGS INDIVIDUALES
    // Secuencia de funciones de limpieza aplicadas de adentro hacia afuera:
    
    // 1. trim($data): Elimina espacios en blanco del inicio y final del string
    //    Espacios en blanco: espacios, tabs, saltos de línea, retornos de carro
    
    // 2. strip_tags(): Elimina todas las etiquetas HTML y PHP del string
    //    Previene inyección de código HTML/JavaScript malicioso
    //    Ejemplo: "<script>alert('hack')</script>" se convierte en "alert('hack')"
    
    // 3. htmlspecialchars(): Convierte caracteres especiales a entidades HTML
    //    Previene ataques XSS (Cross-Site Scripting)
    //    Ejemplo: "<" se convierte en "&lt;", ">" se convierte en "&gt;"
    //    XSS: Ataque donde se inyecta código JavaScript en páginas web
    
    return htmlspecialchars(strip_tags(trim($data)));
}

// Final del archivo - sin etiqueta de cierre para evitar problemas de output
