<?php
require_once '../api_base.php';
require_once '../config/database.php';

validateMethod(['POST']);

$data = getJsonInput();
validateRequired($data, ['mensaje']);

$mensaje = sanitizeData($data['mensaje']);
$userId = null;

// Verificar si hay sesión activa (opcional para el chatbot)
session_start();
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
}

try {
    $respuesta = procesarMensajeChatbot($mensaje, $userId);
    sendSuccess(['respuesta' => $respuesta], 'Respuesta del chatbot generada');
    
} catch (Exception $e) {
    sendError('Error en el chatbot: ' . $e->getMessage(), 500);
}

/**
 * Procesar mensaje del chatbot con lógica mejorada
 */
function procesarMensajeChatbot($mensaje, $userId = null) {
    $mensaje = strtolower(trim($mensaje));
    $mensaje = normalizeText($mensaje);
    
    // Respuestas contextuales si el usuario está logueado
    if ($userId) {
        $contextResponse = getContextualResponse($mensaje, $userId);
        if ($contextResponse) {
            return $contextResponse;
        }
    }
    
    // Patrones de respuesta más sofisticados
    $patterns = [
        // Saludos
        'hola|hi|buenas|hey|saludos' => [
            '¡Hola! 👋 Soy Perseo, tu asistente virtual de HandInHand. ¿En qué puedo ayudarte?',
            '¡Bienvenido a HandInHand! 🤝 ¿Cómo puedo asistirte hoy?',
            '¡Hola! Estoy aquí para ayudarte con HandInHand. ¿Qué necesitas?'
        ],
        
        // Productos
        'producto|intercambiar|truequear|publicar|subir' => [
            '� Para publicar un producto para intercambio, ve a tu perfil y selecciona "Publicar Producto". Recuerda incluir buenas fotos y una descripción detallada.',
            '¿Quieres intercambiar algo? Es muy fácil: sube fotos, describe tu producto y especifica qué buscas a cambio. ¡La comunidad te está esperando!',
            'Para hacer trueques en HandInHand: 1) Toma buenas fotos 2) Escribe una descripción clara 3) Especifica qué buscas. ¡Así de simple!'
        ],
        
        // Comprar
        'buscar|encontrar|necesito|quiero' => [
            '🔍 Usa nuestra barra de búsqueda en la página principal para encontrar lo que necesitas para intercambiar. ¡También puedes filtrar por categorías!',
            '¿Buscas algo específico para intercambiar? Utiliza los filtros de búsqueda o navega por categorías. ¡Seguro encontrarás lo que necesitas!',
            'Para encontrar productos disponibles para trueque, usa la búsqueda avanzada. Puedes filtrar por categoría y ubicación.'
        ],
        
        // Seguridad
        'segur|estafa|confianza|seguro' => [
            '🛡️ Tu seguridad es importante. Siempre revisa el perfil del vendedor, sus valoraciones y encuentra en lugares públicos.',
            'Consejos de seguridad: Verifica las valoraciones del vendedor, haz preguntas sobre el producto y reúnete en lugares seguros.',
            '¡La seguridad primero! Lee las valoraciones, comunícate por nuestra plataforma y encuentra en lugares concurridos.'
        ],
        
        // Precios y pagos (ahora sobre trueques)
        'precio|pago|dinero|cuanto|cuesta|trueque|intercambio' => [
            '� En HandInHand no hay precios, ¡es una app de trueques! Intercambia directamente productos con otros usuarios sin dinero.',
            '¡Aquí no necesitas dinero! Solo intercambia productos que ya no uses por cosas que realmente necesites. ¡Trueque justo para todos!',
            'HandInHand es 100% trueque. Contacta al usuario y acuerden qué intercambiar. ¡Sin dinero, solo intercambios justos!'
        ],
        
        // Contacto y soporte
        'contacto|ayuda|soporte|problema' => [
            '📞 ¿Necesitas más ayuda? Contáctanos:\n• WhatsApp: +598 XXX XXX\n• Email: soporte@handinhand.com\n• Horario: Lunes a Viernes 9-18hrs',
            '¿Tienes algún problema? Nuestro equipo está aquí para ayudarte de Lunes a Viernes de 9:00 a 18:00. ¡Escríbenos!',
            'Para soporte personalizado, contáctanos por WhatsApp o email. ¡Respondemos rápido!'
        ],
        
        // Valoraciones
        'valoracion|opinion|comentario|reseña' => [
            '⭐ Las valoraciones ayudan a toda la comunidad. Después de cada intercambio, no olvides valorar tu experiencia.',
            'Tu opinión importa. Comparte tu experiencia con otros usuarios dejando una valoración honesta.',
            'Las valoraciones construyen confianza en nuestra comunidad. ¡Ayuda a otros usuarios compartiendo tu experiencia!'
        ],
        
        // Cuenta y perfil
        'cuenta|perfil|usuario|registro' => [
            '👤 En tu perfil puedes ver tus productos, mensajes y valoraciones. ¡Mantenlo actualizado para generar más confianza!',
            'Tu perfil es tu carta de presentación. Agrega una buena foto y completa tu información para generar confianza.',
            '¿Problemas con tu cuenta? Ve a Configuración en tu perfil o contáctanos si necesitas ayuda específica.'
        ],
        
        // Despedidas
        'adios|chau|bye|hasta luego|gracias' => [
            '¡Hasta luego! 👋 Espero haberte ayudado. ¡Que tengas éxito en HandInHand!',
            '¡Gracias por usar HandInHand! 🤝 ¡Que tengas buenos intercambios!',
            '¡Nos vemos! Si necesitas algo más, aquí estaré. ¡Éxito en tus intercambios! ✨'
        ]
    ];
    
    // Buscar patrón coincidente
    foreach ($patterns as $pattern => $responses) {
        if (preg_match("/($pattern)/i", $mensaje)) {
            return $responses[array_rand($responses)];
        }
    }
    
    // Respuestas inteligentes para consultas específicas
    if (strpos($mensaje, '?') !== false) {
        return '🤔 Esa es una buena pregunta. Para consultas específicas, te recomiendo contactar a nuestro equipo de soporte o revisar nuestra sección de ayuda.';
    }
    
    // Respuesta por defecto más útil
    $defaultResponses = [
        '🤖 No estoy seguro de entender tu consulta. ¿Podrías ser más específico? Puedo ayudarte con: productos, compras, ventas, seguridad o soporte.',
        '💡 No reconozco esa consulta. Intenta preguntarme sobre: cómo vender, cómo comprar, seguridad, precios o contacto.',
        '🔄 Reformula tu pregunta, por favor. Puedo asistirte con temas como: productos, valoraciones, cuenta o soporte.'
    ];
    
    return $defaultResponses[array_rand($defaultResponses)];
}

/**
 * Respuestas contextuales para usuarios logueados
 */
function getContextualResponse($mensaje, $userId) {
    try {
        require_once '../config/database.php';
        $pdo = getConnection();
        
        // Si pregunta por "mis productos"
        if (preg_match('/(mis?\s*productos?|productos?\s*míos?)/i', $mensaje)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM productos WHERE user_id = ?");
            $stmt->execute([$userId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($count == 0) {
                return "📦 Aún no tienes productos publicados. ¡Es momento de subir tu primer producto y comenzar a vender!";
            } else {
                return "📦 Tienes $count producto(s) publicado(s). Puedes verlos y gestionarlos desde tu perfil.";
            }
        }
        
        // Si pregunta por mensajes
        if (preg_match('/(mensajes?|conversaciones?)/i', $mensaje)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM mensajes WHERE destinatario_id = ? AND leido = 0");
            $stmt->execute([$userId]);
            $unread = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($unread == 0) {
                return "📨 No tienes mensajes nuevos. ¡Revisa la sección de mensajes para ver tus conversaciones!";
            } else {
                return "📨 Tienes $unread mensaje(s) sin leer. ¡Ve a la sección de mensajes para revisarlos!";
            }
        }
        
        // Si pregunta por valoraciones
        if (preg_match('/(valoraciones?|reseñas?|rating)/i', $mensaje)) {
            $stmt = $pdo->prepare("SELECT AVG(puntuacion) as promedio, COUNT(*) as total FROM valoraciones WHERE usuario_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] == 0) {
                return "⭐ Aún no tienes valoraciones. ¡Completa algunas transacciones para comenzar a recibir reseñas!";
            } else {
                $promedio = round($result['promedio'], 1);
                $total = $result['total'];
                return "⭐ Tienes una valoración promedio de $promedio estrellas basada en $total reseña(s). ¡Excelente trabajo!";
            }
        }
        
    } catch (Exception $e) {
        // Si hay error en la consulta contextual, continuar con respuestas normales
        return null;
    }
    
    return null;
}

/**
 * Normalizar texto para mejor matching
 */
function normalizeText($text) {
    // Remover acentos
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    // Remover caracteres especiales excepto espacios
    $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
    // Remover espacios múltiples
    $text = preg_replace('/\s+/', ' ', $text);
    
    return trim($text);
}
?>
