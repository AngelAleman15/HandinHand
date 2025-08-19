<?php
// Chatbot Perseo con Motor de Intenciones PLN y Contexto de Usuario
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Buffer de salida
ob_start();

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['mensaje'])) {
        throw new Exception('Mensaje requerido');
    }

    $mensaje = trim($data['mensaje']);
    
    // Iniciar sesión para obtener contexto del usuario y memoria
    session_start();
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Incluir conexión a BD
    $configPath = '../config/database.php';
    if (!file_exists($configPath)) {
        $configPath = dirname(__DIR__) . '/config/database.php';
    }
    include_once $configPath;
    $pdo = getConnection();
    
    // Obtener o inicializar memoria conversacional
    $memoriaConversacion = obtenerMemoriaConversacion();
    
    // Procesar mensaje con motor PLN y memoria
    $respuesta = procesarMensajeConPLN($mensaje, $userId, $pdo, $memoriaConversacion);
    
    // Actualizar memoria conversacional
    actualizarMemoriaConversacion($mensaje, $respuesta['texto'], $respuesta['contexto']);
    
    // Limpiar buffer y enviar respuesta
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Respuesta de Perseo generada',
        'data' => ['respuesta' => $respuesta['texto']]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    ob_clean();
    
    // Log del error para debugging
    error_log("Error en Perseo: " . $e->getMessage() . " - Línea: " . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'message' => 'Oops, tuve un pequeño problema técnico. ¿Podrías reformular tu pregunta?',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    ob_clean();
    
    // Log del error fatal
    error_log("Error fatal en Perseo: " . $e->getMessage() . " - Línea: " . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'message' => 'Tuve un problema interno. Por favor, intenta de nuevo en un momento.',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * ===== MOTOR DE INTENCIONES PLN CON MEMORIA =====
 */
function procesarMensajeConPLN($mensaje, $userId, $pdo, $memoria) {
    // Normalizar texto
    $mensajeNormalizado = normalizarTexto($mensaje);
    
    // Obtener contexto del usuario
    $contextoUsuario = obtenerContextoUsuario($userId, $pdo);
    
    // Detectar intención considerando la memoria
    $intencion = detectarIntencionConMemoria($mensajeNormalizado, $memoria);
    
    // Variable para almacenar contexto de respuesta
    $contextoRespuesta = $intencion['tipo'];
    
    // Procesar según la intención detectada
    switch ($intencion['tipo']) {
        case 'saludo':
            $respuesta = generarSaludo($contextoUsuario);
            break;
            
        case 'mis_productos':
            $respuesta = consultarMisProductos($userId, $pdo, $contextoUsuario);
            break;
            
        case 'mis_intercambios':
            $respuesta = consultarMisIntercambios($userId, $pdo, $contextoUsuario);
            break;
            
        case 'buscar_producto':
            $respuesta = ayudarBusquedaProducto($intencion['entidades'], $pdo);
            break;
            
        case 'publicar_producto':
            $respuesta = guiarPublicacion($contextoUsuario);
            $contextoRespuesta = 'guia_publicacion';
            break;
            
        case 'perfil_usuario':
            $respuesta = mostrarInformacionPerfil($userId, $pdo, $contextoUsuario);
            break;
            
        case 'estadisticas':
            $respuesta = generarEstadisticasUsuario($userId, $pdo, $contextoUsuario);
            break;
            
        case 'ayuda_intercambio':
            $respuesta = explicarProcesoIntercambio($contextoUsuario);
            $contextoRespuesta = 'proceso_intercambio';
            break;
            
        case 'valoraciones':
            $respuesta = consultarVloraciones($userId, $pdo, $contextoUsuario);
            break;
            
        case 'seguridad':
            $respuesta = darConsejosSeguridad();
            $contextoRespuesta = 'consejos_seguridad';
            break;
            
        case 'despedida':
            $respuesta = generarDespedida($contextoUsuario);
            break;
            
        case 'como_hacer_seguimiento':
            $respuesta = responderSeguimiento($intencion['contexto_previo'], $contextoUsuario);
            break;
            
        case 'login_necesario':
            $respuesta = explicarComoIniciarSesion();
            $contextoRespuesta = 'explicacion_login';
            break;
            
        case 'ya_logueado':
            $respuesta = responderYaLogueado($contextoUsuario);
            break;
            
        default:
            $respuesta = respuestaInteligentePorDefecto($mensajeNormalizado, $contextoUsuario);
    }
    
    return [
        'texto' => $respuesta,
        'contexto' => $contextoRespuesta
    ];
}

/**
 * ===== SISTEMA DE MEMORIA CONVERSACIONAL =====
 */
function obtenerMemoriaConversacion() {
    if (!isset($_SESSION['perseo_memoria'])) {
        $_SESSION['perseo_memoria'] = [
            'historial' => [],
            'ultimo_contexto' => null,
            'ultimo_mensaje_bot' => null,
            'temas_mencionados' => [],
            'usuario_necesita_login' => false
        ];
    }
    
    // Limpiar memoria si es muy antigua (más de 30 minutos)
    $tiempoLimite = time() - (30 * 60);
    $_SESSION['perseo_memoria']['historial'] = array_filter(
        $_SESSION['perseo_memoria']['historial'],
        function($entrada) use ($tiempoLimite) {
            return $entrada['timestamp'] > $tiempoLimite;
        }
    );
    
    return $_SESSION['perseo_memoria'];
}

function actualizarMemoriaConversacion($mensajeUsuario, $respuestaBot, $contexto) {
    if (!isset($_SESSION['perseo_memoria'])) {
        $_SESSION['perseo_memoria'] = [
            'historial' => [],
            'ultimo_contexto' => null,
            'ultimo_mensaje_bot' => null,
            'temas_mencionados' => [],
            'usuario_necesita_login' => false
        ];
    }
    
    // Agregar al historial
    $_SESSION['perseo_memoria']['historial'][] = [
        'usuario' => $mensajeUsuario,
        'bot' => $respuestaBot,
        'contexto' => $contexto,
        'timestamp' => time()
    ];
    
    // Mantener solo los últimos 10 intercambios
    if (count($_SESSION['perseo_memoria']['historial']) > 10) {
        array_shift($_SESSION['perseo_memoria']['historial']);
    }
    
    // Actualizar contexto actual
    $_SESSION['perseo_memoria']['ultimo_contexto'] = $contexto;
    $_SESSION['perseo_memoria']['ultimo_mensaje_bot'] = $respuestaBot;
    
    // Detectar si mencionamos que necesita login
    if (strpos($respuestaBot, 'Inicia sesión') !== false || 
        strpos($respuestaBot, 'iniciar sesión') !== false ||
        strpos($respuestaBot, 'Necesitas iniciar sesión') !== false) {
        $_SESSION['perseo_memoria']['usuario_necesita_login'] = true;
    }
    
    // Agregar temas mencionados
    $temas = extraerTemas($mensajeUsuario . ' ' . $respuestaBot);
    $_SESSION['perseo_memoria']['temas_mencionados'] = array_unique(
        array_merge($_SESSION['perseo_memoria']['temas_mencionados'], $temas)
    );
}

function extraerTemas($texto) {
    $temas = [];
    $palabrasClave = [
        'productos', 'intercambios', 'trueques', 'valoraciones', 
        'perfil', 'seguridad', 'login', 'registro', 'publicar'
    ];
    
    foreach ($palabrasClave as $tema) {
        if (strpos(strtolower($texto), $tema) !== false) {
            $temas[] = $tema;
        }
    }
    
    return $temas;
}

/**
 * ===== DETECTOR DE INTENCIONES CON MEMORIA =====
 */
function detectarIntencionConMemoria($mensaje, $memoria) {
    // Verificar si dice que ya inició sesión
    $yaLogueado = [
        'ya me inicie', 'ya estoy logueado', 'ya entre', 'ya me loguee',
        'ya tengo sesion', 'ya estoy adentro', 'ya inicié', 'ya me registré'
    ];
    
    foreach ($yaLogueado as $patron) {
        if (strpos($mensaje, $patron) !== false) {
            return [
                'tipo' => 'ya_logueado',
                'confianza' => 1.0,
                'entidades' => []
            ];
        }
    }
    
    // Verificar si es una pregunta específica sobre login
    $preguntasLogin = [
        'como inicio sesion', 'como iniciar sesion', 'como me logueo', 
        'como entrar', 'como acceder', 'como hago login', 'donde me logueo',
        'donde inicio sesion', 'donde entrar', 'como entro', 'inicio sesion',
        'iniciar sesion', 'entrar cuenta', 'acceder cuenta', 'login',
        'como me registro', 'como registrarme', 'registrarse'
    ];
    
    foreach ($preguntasLogin as $patron) {
        if (strpos($mensaje, $patron) !== false) {
            return [
                'tipo' => 'login_necesario',
                'confianza' => 1.0,
                'entidades' => []
            ];
        }
    }
    
    // Verificar si es una pregunta de seguimiento
    $preguntasSeguimiento = [
        'como lo hago', 'como hago eso', 'como', 'donde', 'cuando',
        'que pasos', 'ayudame', 'explicame', 'no entiendo',
        'mas detalles', 'puedes explicar', 'como funciona eso'
    ];
    
    $esPreguntaSeguimiento = false;
    foreach ($preguntasSeguimiento as $patron) {
        if (strpos($mensaje, $patron) !== false) {
            $esPreguntaSeguimiento = true;
            break;
        }
    }
    
    // Si es pregunta de seguimiento y hay contexto previo
    if ($esPreguntaSeguimiento && $memoria['ultimo_contexto']) {
        return [
            'tipo' => 'como_hacer_seguimiento',
            'contexto_previo' => $memoria['ultimo_contexto'],
            'confianza' => 1.0,
            'entidades' => []
        ];
    }
    
    // Si usuario necesita login y pregunta cómo hacerlo
    if ($memoria['usuario_necesita_login'] && 
        (strpos($mensaje, 'como') !== false || strpos($mensaje, 'donde') !== false)) {
        $_SESSION['perseo_memoria']['usuario_necesita_login'] = false; // Reset
        return [
            'tipo' => 'login_necesario',
            'confianza' => 1.0,
            'entidades' => []
        ];
    }
    
    // Si no es seguimiento, usar detección normal
    return detectarIntencion($mensaje);
}

/**
 * ===== RESPUESTAS DE SEGUIMIENTO CONTEXTUALES =====
 */
function responderSeguimiento($contextoPrevio, $contextoUsuario) {
    switch ($contextoPrevio) {
        case 'guia_publicacion':
            return "📸 **Pasos detallados para publicar:**\n\n1️⃣ **Accede a tu perfil**: Haz clic en tu nombre (esquina superior derecha)\n\n2️⃣ **Ve a 'Mis Productos'**: Encontrarás esta opción en tu perfil\n\n3️⃣ **Clic en 'Agregar Producto'**: Botón verde en la página\n\n4️⃣ **Completa el formulario**:\n   • Título descriptivo\n   • Categoría apropiada\n   • Fotos claras (mínimo 2)\n   • Descripción detallada\n   • Qué buscas a cambio\n\n5️⃣ **Publica**: ¡Y listo para intercambiar!\n\n💡 **Tip**: Productos con buenas fotos reciben 3x más mensajes.";
            
        case 'proceso_intercambio':
            return "🔄 **Guía paso a paso para intercambiar:**\n\n**Paso 1: Encuentra un producto**\n• Usa el buscador o navega por categorías\n• Revisa fotos y descripción\n\n**Paso 2: Contacta al dueño**\n• Clic en 'Contactar' del producto\n• Presenta tu oferta claramente\n• Menciona qué ofreces a cambio\n\n**Paso 3: Negocia**\n• Ambos deben estar conformes\n• Acuerden detalles del intercambio\n\n**Paso 4: Planifica el encuentro**\n• Lugar público y seguro\n• Horario conveniente para ambos\n\n**Paso 5: Realiza el trueque**\n• Inspecciona los productos\n• Completa el intercambio\n\n**Paso 6: Califícanse**\n• Deja tu valoración honest\n• Ayuda a la comunidad";
            
        case 'consejos_seguridad':
            return "🛡️ **Medidas de seguridad detalladas:**\n\n**Antes del encuentro:**\n✅ Revisa el perfil y valoraciones del usuario\n✅ Comunícate solo por HandinHand\n✅ Haz preguntas sobre el producto\n✅ Pide fotos adicionales si es necesario\n\n**Durante el encuentro:**\n✅ Reúnete en lugares públicos (centros comerciales, parques concurridos)\n✅ Ve acompañado/a si es posible\n✅ Inspecciona bien el producto\n✅ Verifica que funcione correctamente\n\n**Señales de alerta:**\n🚨 Presión para encontrarse rápido\n🚨 Lugares remotos o privados\n🚨 Precios demasiado buenos\n🚨 Comunicación fuera de la app\n\n**En caso de problemas:**\n📞 Reporta usuarios sospechosos\n📞 Confía en tu instinto";
            
        case 'explicacion_login':
            return explicarComoIniciarSesion();
            
        default:
            return "🤔 Puedo darte más detalles sobre el tema que estábamos hablando. ¿Qué específicamente te gustaría saber?";
    }
}

function explicarComoIniciarSesion() {
    return "🔐 **Cómo iniciar sesión en HandinHand:**\n\n**Si ya tienes cuenta:**\n1️⃣ Ve a la esquina superior derecha\n2️⃣ Haz clic en 'Iniciar Sesión'\n3️⃣ Ingresa tu email y contraseña\n4️⃣ ¡Listo!\n\n**Si no tienes cuenta:**\n1️⃣ Haz clic en 'Registrarse'\n2️⃣ Completa el formulario:\n   • Nombre completo\n   • Email válido\n   • Contraseña segura\n   • Confirmación de contraseña\n3️⃣ Acepta términos y condiciones\n4️⃣ ¡Bienvenido a HandinHand!\n\n**¿Olvidaste tu contraseña?**\n🔄 Usa 'Recuperar contraseña' en la página de login\n\n💡 Una vez logueado, podré mostrarte tus productos, intercambios y estadísticas personales.";
}

function responderYaLogueado($contextoUsuario) {
    if ($contextoUsuario['logueado']) {
        return "¡Perfecto, " . $contextoUsuario['nombre'] . "! 🎉 Veo que ya tienes sesión iniciada.\n\n📊 **Tu estado actual:**\n📦 " . $contextoUsuario['total_productos'] . " productos publicados\n💬 " . $contextoUsuario['total_intercambios'] . " conversaciones\n⭐ " . $contextoUsuario['valoracion_promedio'] . "/5 de reputación\n\n¿En qué puedo ayudarte ahora? Puedo mostrarte:\n• Tus productos y mensajes\n• Estadísticas detalladas\n• Guías para intercambios\n• Consejos personalizados";
    } else {
        return "🤔 Hmm, parece que aún no detecto tu sesión activa. Esto puede pasar por:\n\n**Posibles causas:**\n• La página no se refrescó después del login\n• Cookies bloqueadas\n• Sesión expirada\n\n**Soluciones:**\n1️⃣ Recarga la página (F5)\n2️⃣ Cierra y abre el chatbot\n3️⃣ Si persiste, cierra sesión y vuelve a entrar\n\n💡 Una vez que detecte tu sesión, podré darte información personalizada de tu cuenta.";
    }
}
function detectarIntencion($mensaje) {
    $intenciones = [
        'saludo' => [
            'patrones' => ['hola', 'buenas', 'hey', 'saludos', 'buenos dias', 'buenas tardes', 'buenas noches'],
            'peso' => 1.0
        ],
        'login_necesario' => [
            'patrones' => ['como inicio sesion', 'como iniciar sesion', 'como me logueo', 'como entrar', 'como acceder', 'login', 'iniciar sesion', 'entrar cuenta', 'acceder cuenta'],
            'peso' => 1.0
        ],
        'mis_productos' => [
            'patrones' => ['mis productos', 'que publique', 'que tengo publicado', 'mis publicaciones', 'productos mios', 'mis articulos'],
            'peso' => 1.0
        ],
        'mis_intercambios' => [
            'patrones' => ['mis intercambios', 'trueques activos', 'mis trueques', 'intercambios pendientes', 'que intercambie'],
            'peso' => 1.0
        ],
        'buscar_producto' => [
            'patrones' => ['busco', 'necesito', 'quiero', 'buscar', 'donde encuentro', 'hay algun'],
            'peso' => 0.8
        ],
        'publicar_producto' => [
            'patrones' => ['como publico', 'como subo', 'publicar', 'subir producto', 'agregar producto'],
            'peso' => 1.0
        ],
        'perfil_usuario' => [
            'patrones' => ['mi perfil', 'mis datos', 'mi informacion', 'mi cuenta'],
            'peso' => 1.0
        ],
        'estadisticas' => [
            'patrones' => ['estadisticas', 'mi historial', 'resumen', 'actividad'],
            'peso' => 0.9
        ],
        'ayuda_intercambio' => [
            'patrones' => ['como intercambiar', 'como hacer trueque', 'proceso intercambio', 'como funciona'],
            'peso' => 1.0
        ],
        'valoraciones' => [
            'patrones' => ['mis valoraciones', 'calificaciones', 'opiniones', 'reputacion'],
            'peso' => 1.0
        ],
        'seguridad' => [
            'patrones' => ['es seguro', 'seguridad', 'confianza', 'riesgos'],
            'peso' => 1.0
        ],
        'despedida' => [
            'patrones' => ['adios', 'chau', 'hasta luego', 'nos vemos', 'gracias'],
            'peso' => 1.0
        ]
    ];
    
    $mejorIntencion = ['tipo' => 'desconocida', 'confianza' => 0, 'entidades' => []];
    
    foreach ($intenciones as $tipo => $definicion) {
        $confianza = 0;
        $entidades = [];
        
        foreach ($definicion['patrones'] as $patron) {
            if (strpos($mensaje, $patron) !== false) {
                $confianza += $definicion['peso'];
                
                // Extraer entidades si es búsqueda de producto
                if ($tipo === 'buscar_producto') {
                    $entidades = extraerEntidadesProducto($mensaje);
                }
            }
        }
        
        if ($confianza > $mejorIntencion['confianza']) {
            $mejorIntencion = [
                'tipo' => $tipo,
                'confianza' => $confianza,
                'entidades' => $entidades
            ];
        }
    }
    
    return $mejorIntencion;
}

/**
 * ===== CONTEXTO DEL USUARIO =====
 */
function obtenerContextoUsuario($userId, $pdo) {
    if (!$userId) {
        return [
            'logueado' => false,
            'nombre' => 'Usuario',
            'total_productos' => 0,
            'total_intercambios' => 0,
            'valoracion_promedio' => 0,
            'debug_info' => 'Sin user_id en sesión'
        ];
    }
    
    try {
        // Verificar conexión a BD
        if (!$pdo) {
            return [
                'logueado' => false, 
                'nombre' => 'Usuario',
                'debug_info' => 'Error: No hay conexión a BD'
            ];
        }
        
        // Datos básicos del usuario
        $stmt = $pdo->prepare("SELECT fullname, email, created_at FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            return [
                'logueado' => false, 
                'nombre' => 'Usuario',
                'debug_info' => "Usuario con ID $userId no encontrado en BD"
            ];
        }
        
        // Contar productos
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM productos WHERE user_id = ?");
        $stmt->execute([$userId]);
        $totalProductos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Contar intercambios (usando mensajes como proxy)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT m.id) as total 
            FROM mensajes m 
            JOIN productos p ON (m.producto_id = p.id) 
            WHERE p.user_id = ? OR m.remitente_id = ? OR m.destinatario_id = ?
        ");
        $stmt->execute([$userId, $userId, $userId]);
        $totalIntercambios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Valoración promedio (si existe la tabla)
        $valoracionPromedio = 0;
        try {
            $stmt = $pdo->prepare("SELECT AVG(puntuacion) as promedio FROM valoraciones WHERE usuario_id = ?");
            $stmt->execute([$userId]);
            $valoracionPromedio = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'] ?: 0;
        } catch (Exception $e) {
            // Tabla valoraciones no existe o error, usar 0
            $valoracionPromedio = 0;
        }
        
        return [
            'logueado' => true,
            'nombre' => $usuario['fullname'],
            'email' => $usuario['email'],
            'fecha_registro' => $usuario['created_at'],
            'total_productos' => $totalProductos,
            'total_intercambios' => $totalIntercambios,
            'valoracion_promedio' => round($valoracionPromedio, 1),
            'debug_info' => "Usuario encontrado correctamente"
        ];
        
    } catch (Exception $e) {
        return [
            'logueado' => false, 
            'nombre' => 'Usuario',
            'debug_info' => 'Error en BD: ' . $e->getMessage()
        ];
    }
}

/**
 * ===== FUNCIONES DE RESPUESTA DINÁMICAS =====
 */
function generarSaludo($contexto) {
    // Verificar si ya se saludó en esta sesión
    $memoria = obtenerMemoriaConversacion();
    $yaSaludo = false;
    
    foreach ($memoria['historial'] as $entrada) {
        if ($entrada['contexto'] === 'saludo') {
            $yaSaludo = true;
            break;
        }
    }
    
    if (!$contexto['logueado']) {
        if ($yaSaludo) {
            return "👋 ¡Hola de nuevo! Sigo aquí para ayudarte.\n\n🔐 Recuerda que si inicias sesión podrás acceder a funciones personalizadas.\n\n¿En qué más puedo asistirte?";
        }
        return "¡Hola! 👋 Soy Perseo, tu asistente inteligente de HandinHand.\n\n🔐 **Inicia sesión** para que pueda ayudarte con tus productos e intercambios específicos.\n\n¿En qué puedo ayudarte?";
    }
    
    if ($yaSaludo) {
        return "¡Hola otra vez, " . $contexto['nombre'] . "! 😊\n\n¿Hay algo más en lo que pueda ayudarte?";
    }
    
    $saludo = "¡Hola " . $contexto['nombre'] . "! 👋 Me alegra verte de nuevo.\n\n";
    
    if ($contexto['total_productos'] > 0) {
        $saludo .= "📦 Tienes " . $contexto['total_productos'] . " producto(s) publicado(s).\n";
    } else {
        $saludo .= "💡 ¿Listo para publicar tu primer producto?\n";
    }
    
    if ($contexto['valoracion_promedio'] > 0) {
        $estrellas = str_repeat('⭐', floor($contexto['valoracion_promedio']));
        $saludo .= "🏆 Tu reputación: " . $contexto['valoracion_promedio'] . "/5 " . $estrellas . "\n";
    }
    
    $saludo .= "\n¿En qué puedo ayudarte hoy?";
    
    return $saludo;
}

function consultarMisProductos($userId, $pdo, $contexto) {
    if (!$contexto['logueado']) {
        return "🔐 **Necesitas iniciar sesión para ver tus productos.**\n\n¡Regístrate o inicia sesión para comenzar a publicar!\n\n💡 Una vez logueado podrás:\n• Ver todos tus productos\n• Gestionar publicaciones\n• Revisar mensajes recibidos\n• Estadísticas detalladas";
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.nombre as titulo, p.descripcion, p.estado, p.categoria, 
                   COUNT(m.id) as mensajes_recibidos
            FROM productos p 
            LEFT JOIN mensajes m ON p.id = m.producto_id 
            WHERE p.user_id = ? 
            GROUP BY p.id 
            ORDER BY p.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($productos)) {
            return "📭 No tienes productos publicados aún, " . $contexto['nombre'] . ".\n\n💡 ¿Te ayudo a publicar tu primer producto? Es muy fácil:\n1️⃣ Ve a tu perfil\n2️⃣ Clic en 'Mis Productos'\n3️⃣ 'Agregar Producto'\n4️⃣ Sube fotos y describe tu artículo\n\n¡En pocos minutos tendrás tu primer intercambio!";
        }
        
        $respuesta = "📦 Tus productos publicados:\n\n";
        foreach ($productos as $producto) {
            $estado_emoji = $producto['estado'] === 'disponible' ? '✅' : ($producto['estado'] === 'intercambiado' ? '🔄' : '⏸️');
            $respuesta .= $estado_emoji . " **" . $producto['titulo'] . "**\n";
            $respuesta .= "📂 " . ($producto['categoria'] ?: 'Sin categoría') . "\n";
            $respuesta .= "💬 " . $producto['mensajes_recibidos'] . " mensaje(s) recibido(s)\n\n";
        }
        
        if (count($productos) === 5) {
            $respuesta .= "📋 Mostrando los últimos 5 productos. Ve a tu perfil para ver todos.";
        }
        
        return $respuesta;
        
    } catch (Exception $e) {
        return "❌ Error al consultar tus productos. Intenta nuevamente.";
    }
}

function consultarMisIntercambios($userId, $pdo, $contexto) {
    if (!$contexto['logueado']) {
        return "🔐 **Inicia sesión para ver tus intercambios activos.**\n\n💡 Con una cuenta podrás:\n• Ver conversaciones en tiempo real\n• Hacer seguimiento de trueques\n• Recibir notificaciones de mensajes";
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.nombre as titulo, u.fullname as interesado, m.mensaje, m.created_at as fecha
            FROM mensajes m
            JOIN productos p ON m.producto_id = p.id
            JOIN usuarios u ON m.remitente_id = u.id
            WHERE p.user_id = ?
            ORDER BY m.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $intercambios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($intercambios)) {
            return "📬 No tienes intercambios activos, " . $contexto['nombre'] . ".\n\n💡 Para generar más intercambios:\n• Publica productos atractivos\n• Responde rápido a los mensajes\n• Mantén precios justos de intercambio";
        }
        
        $respuesta = "🔄 Tus intercambios recientes:\n\n";
        foreach ($intercambios as $intercambio) {
            $tiempo = time() - strtotime($intercambio['fecha']);
            $hace = $tiempo < 3600 ? floor($tiempo/60) . ' min' : floor($tiempo/3600) . ' h';
            
            $respuesta .= "📦 **" . $intercambio['titulo'] . "**\n";
            $respuesta .= "👤 " . $intercambio['interesado'] . " (hace " . $hace . ")\n";
            $respuesta .= "💬 \"" . substr($intercambio['mensaje'], 0, 50) . "...\"\n\n";
        }
        
        $respuesta .= "📱 Ve a 'Mensajes' para responder y coordinar encuentros.";
        
        return $respuesta;
        
    } catch (Exception $e) {
        return "❌ Error al consultar intercambios. Intenta nuevamente.";
    }
}

function generarEstadisticasUsuario($userId, $pdo, $contexto) {
    if (!$contexto['logueado']) {
        return "🔐 Inicia sesión para ver tus estadísticas.";
    }
    
    try {
        // Días desde registro
        $diasRegistro = floor((time() - strtotime($contexto['fecha_registro'])) / 86400);
        
        // Categoría más popular
        $stmt = $pdo->prepare("
            SELECT p.categoria, COUNT(*) as total 
            FROM productos p 
            WHERE p.user_id = ? AND p.categoria IS NOT NULL
            GROUP BY p.categoria 
            ORDER BY total DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $categoriaPopular = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $respuesta = "📊 **Estadísticas de " . $contexto['nombre'] . "**\n\n";
        $respuesta .= "📅 Miembro desde hace " . $diasRegistro . " días\n";
        $respuesta .= "📦 Total productos: " . $contexto['total_productos'] . "\n";
        $respuesta .= "💬 Conversaciones: " . $contexto['total_intercambios'] . "\n";
        
        if ($contexto['valoracion_promedio'] > 0) {
            $estrellas = str_repeat('⭐', floor($contexto['valoracion_promedio']));
            $respuesta .= "🏆 Reputación: " . $contexto['valoracion_promedio'] . "/5 " . $estrellas . "\n";
        }
        
        if ($categoriaPopular) {
            $respuesta .= "🔥 Categoría favorita: " . $categoriaPopular['categoria'] . "\n";
        }
        
        // Consejos personalizados
        $respuesta .= "\n💡 **Consejos personalizados:**\n";
        if ($contexto['total_productos'] < 3) {
            $respuesta .= "• Publica más productos para aumentar intercambios\n";
        }
        if ($contexto['valoracion_promedio'] < 4 && $contexto['valoracion_promedio'] > 0) {
            $respuesta .= "• Mejora tu reputación respondiendo rápido\n";
        }
        if ($contexto['total_intercambios'] < 5) {
            $respuesta .= "• Inicia más conversaciones con otros usuarios\n";
        }
        
        return $respuesta;
        
    } catch (Exception $e) {
        return "❌ Error al generar estadísticas.";
    }
}

/**
 * ===== FUNCIONES AUXILIARES =====
 */
function normalizarTexto($texto) {
    // Convertir a minúsculas
    $texto = mb_strtolower($texto, 'UTF-8');
    
    // Remover menciones del bot ANTES de procesar acentos
    $texto = preg_replace('/\b(perseo|perseon|bot|chatbot)\b[,:]?\s*/iu', '', $texto);
    
    // Remover acentos de manera más completa
    $acentos = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c'
    ];
    $texto = str_replace(array_keys($acentos), array_values($acentos), $texto);
    
    // Remover signos de puntuación pero mantener espacios y letras
    $texto = preg_replace('/[^\w\s]/u', ' ', $texto);
    
    // Limpiar espacios múltiples
    $texto = preg_replace('/\s+/', ' ', $texto);
    
    // Correcciones ortográficas y contracciones
    $correcciones = [
        'q' => 'que', 'k' => 'que', 'xq' => 'porque', 'pq' => 'porque',
        'tmb' => 'tambien', 'tb' => 'tambien', 'dnd' => 'donde',
        'inicie' => 'iniciar', 'ya me inicie' => 'ya estoy logueado'
    ];
    
    foreach ($correcciones as $error => $correccion) {
        $texto = preg_replace('/\b' . $error . '\b/u', $correccion, $texto);
    }
    
    return trim($texto);
}

function extraerEntidadesProducto($mensaje) {
    $entidades = [];
    
    // Categorías comunes
    $categorias = ['ropa', 'zapatos', 'electronico', 'libro', 'juguete', 'deporte', 'cocina'];
    foreach ($categorias as $categoria) {
        if (strpos($mensaje, $categoria) !== false) {
            $entidades['categoria'] = $categoria;
            break;
        }
    }
    
    return $entidades;
}

function respuestaInteligentePorDefecto($mensaje, $contexto) {
    $respuestas = [
        "🤔 Interesante pregunta, " . $contexto['nombre'] . ". Puedo ayudarte con:\n• Tus productos e intercambios\n• Buscar artículos específicos\n• Estadísticas de tu cuenta\n• Consejos de seguridad\n\n¿Sobre qué te gustaría saber más?",
        
        "💡 No estoy seguro de entender exactamente, pero puedo asistirte con muchas cosas:\n• Ver tus publicaciones\n• Revisar intercambios pendientes\n• Buscar productos específicos\n• Información de tu perfil\n\n¿Qué necesitas?",
        
        "🤖 Reformula tu pregunta y te ayudo mejor. Soy especialista en:\n• Gestión de productos\n• Intercambios activos\n• Búsquedas en la plataforma\n• Tu actividad y estadísticas"
    ];
    
    return $respuestas[array_rand($respuestas)];
}

// Funciones adicionales para completar todas las intenciones...
function ayudarBusquedaProducto($entidades, $pdo) {
    // Implementar búsqueda inteligente
    return "🔍 Te ayudo a buscar productos. ¿Qué específicamente necesitas?";
}

function guiarPublicacion($contexto) {
    return "📸 Para publicar un producto:\n1️⃣ Ve a tu perfil\n2️⃣ 'Mis Productos' → 'Agregar'\n3️⃣ Sube fotos claras\n4️⃣ Describe detalladamente\n5️⃣ Especifica qué buscas a cambio";
}

function mostrarInformacionPerfil($userId, $pdo, $contexto) {
    if (!$contexto['logueado']) return "🔐 Inicia sesión para ver tu perfil.";
    return "👤 **Tu perfil:**\n📧 " . $contexto['email'] . "\n📦 " . $contexto['total_productos'] . " productos\n⭐ " . $contexto['valoracion_promedio'] . "/5 reputación";
}

function explicarProcesoIntercambio($contexto) {
    return "🔄 **Proceso de intercambio:**\n1️⃣ Encuentra un producto que te guste\n2️⃣ Contacta al dueño\n3️⃣ Negocien el intercambio\n4️⃣ Acuerden lugar seguro\n5️⃣ Realicen el trueque\n6️⃣ ¡Califíquense mutuamente!";
}

function consultarVloraciones($userId, $pdo, $contexto) {
    if (!$contexto['logueado']) return "🔐 Inicia sesión para ver valoraciones.";
    return "⭐ Tu reputación actual: " . $contexto['valoracion_promedio'] . "/5\n\n💡 Mejora tu reputación siendo puntual, honesto y comunicativo.";
}

function darConsejosSeguridad() {
    return "🛡️ **Consejos de seguridad:**\n✅ Revisa valoraciones del usuario\n✅ Reúnete en lugares públicos\n✅ Inspecciona antes de intercambiar\n✅ Confía en tu instinto\n✅ Usa el chat de la app";
}

function generarDespedida($contexto) {
    return "¡Hasta luego, " . $contexto['nombre'] . "! 👋\n\n¡Que tengas excelentes intercambios en HandinHand! ✨";
}
?>
