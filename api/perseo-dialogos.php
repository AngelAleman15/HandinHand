<?php
/**
 * Diálogos adicionales para Perseo
 * Incluye: Tutoriales, estadísticas, consejos, ayuda
 */

require_once '../config/database.php';

/**
 * Obtener diálogo de tutorial de intercambios
 */
function getTutorialIntercambio() {
    return "📚 **Tutorial: Cómo hacer un intercambio**\n\n" .
           "1️⃣ Encuentra un producto que te guste\n" .
           "2️⃣ Haz clic en 'Proponer Intercambio'\n" .
           "3️⃣ Selecciona qué producto tuyo quieres ofrecer\n" .
           "4️⃣ Escribe un mensaje al dueño\n" .
           "5️⃣ Espera su respuesta\n" .
           "6️⃣ Si acepta, coordinen lugar y fecha de encuentro\n" .
           "7️⃣ Realicen el intercambio y marquen como 'Entregado'\n" .
           "8️⃣ ¡Valoren su experiencia mutua!\n\n" .
           "💡 **Consejo:** Revisa el perfil del usuario y sus valoraciones antes de proponer.";
}

/**
 * Listar productos disponibles del usuario
 */
function listarMisProductos($user_id) {
    try {
        $db = getConnection();
        $stmt = $db->prepare("
            SELECT id, nombre, condicion, estado
            FROM productos
            WHERE user_id = ? AND estado = 'disponible'
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($productos)) {
            return "😕 No tienes productos disponibles para intercambiar.\n\n" .
                   "¿Quieres agregar uno ahora? Solo dime 'agregar producto' y te ayudo.";
        }
        
        $mensaje = "📦 **Tus productos disponibles:**\n\n";
        foreach ($productos as $i => $prod) {
            $emoji_condicion = match($prod['condicion']) {
                'nuevo' => '✨',
                'como nuevo' => '⭐',
                'poco uso' => '👍',
                'usado' => '👌',
                'muy desgastado' => '🔧',
                default => '📦'
            };
            $mensaje .= ($i + 1) . ". {$emoji_condicion} {$prod['nombre']} ({$prod['condicion']})\n";
        }
        
        $mensaje .= "\n💡 Puedes proponer intercambios desde la página de cualquier producto.";
        return $mensaje;
        
    } catch (Exception $e) {
        return "❌ Error al obtener tus productos: " . $e->getMessage();
    }
}

/**
 * Verificar intercambios pendientes
 */
function verificarIntercambiosPendientes($user_id) {
    try {
        $db = getConnection();
        
        // Propuestas pendientes (que te hicieron)
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM propuestas_intercambio
            WHERE receptor_id = ? AND estado = 'pendiente'
        ");
        $stmt->execute([$user_id]);
        $pendientes_recibidas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Propuestas enviadas esperando respuesta
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM propuestas_intercambio
            WHERE solicitante_id = ? AND estado = 'pendiente'
        ");
        $stmt->execute([$user_id]);
        $pendientes_enviadas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Intercambios activos
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM seguimiento_intercambios
            WHERE (usuario1_id = ? OR usuario2_id = ?)
            AND estado NOT IN ('completado', 'cancelado', 'denunciado')
        ");
        $stmt->execute([$user_id, $user_id]);
        $activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $mensaje = "📊 **Estado de tus intercambios:**\n\n";
        
        if ($pendientes_recibidas > 0) {
            $mensaje .= "📥 **{$pendientes_recibidas}** propuesta(s) esperando tu respuesta\n";
        }
        
        if ($pendientes_enviadas > 0) {
            $mensaje .= "📤 **{$pendientes_enviadas}** propuesta(s) enviadas esperando respuesta\n";
        }
        
        if ($activos > 0) {
            $mensaje .= "🔄 **{$activos}** intercambio(s) activo(s) en curso\n";
        }
        
        if ($pendientes_recibidas == 0 && $pendientes_enviadas == 0 && $activos == 0) {
            $mensaje .= "✅ No tienes intercambios pendientes.\n\n";
            $mensaje .= "💡 ¿Quieres buscar productos para intercambiar?";
        } else {
            $mensaje .= "\n📱 Ve a 'Mis Intercambios' en el menú para gestionar.";
        }
        
        return $mensaje;
        
    } catch (Exception $e) {
        return "❌ Error al verificar intercambios: " . $e->getMessage();
    }
}

/**
 * Recordatorios de intercambios próximos
 */
function recordatoriosIntercambios($user_id) {
    try {
        $db = getConnection();
        $stmt = $db->prepare("
            SELECT 
                s.id,
                s.fecha_encuentro,
                s.lugar_encuentro,
                p1.nombre as producto_ofrecido,
                p2.nombre as producto_recibido,
                CASE 
                    WHEN s.usuario1_id = ? THEN u2.fullname
                    ELSE u1.fullname
                END as otro_usuario
            FROM seguimiento_intercambios s
            INNER JOIN productos p1 ON s.producto_ofrecido_id = p1.id
            INNER JOIN productos p2 ON s.producto_solicitado_id = p2.id
            INNER JOIN usuarios u1 ON s.usuario1_id = u1.id
            INNER JOIN usuarios u2 ON s.usuario2_id = u2.id
            WHERE (s.usuario1_id = ? OR s.usuario2_id = ?)
            AND s.estado IN ('confirmado', 'en_camino_usuario1', 'en_camino_usuario2', 'en_camino_ambos')
            AND s.fecha_encuentro IS NOT NULL
            AND s.fecha_encuentro > NOW()
            AND s.fecha_encuentro < DATE_ADD(NOW(), INTERVAL 72 HOUR)
            ORDER BY s.fecha_encuentro ASC
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $proximos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($proximos)) {
            return "📅 No tienes intercambios programados en las próximas 72 horas.\n\n" .
                   "✅ Todo tranquilo por ahora.";
        }
        
        $mensaje = "⏰ **Recordatorios de intercambios:**\n\n";
        foreach ($proximos as $i => $int) {
            $fecha = new DateTime($int['fecha_encuentro']);
            $ahora = new DateTime();
            $diff = $ahora->diff($fecha);
            
            $tiempo_restante = "";
            if ($diff->days == 0) {
                if ($diff->h > 0) {
                    $tiempo_restante = "🔴 En {$diff->h} hora(s)";
                } else {
                    $tiempo_restante = "🔴 En {$diff->i} minuto(s)";
                }
            } elseif ($diff->days == 1) {
                $tiempo_restante = "🟡 Mañana";
            } else {
                $tiempo_restante = "🟢 En {$diff->days} días";
            }
            
            $mensaje .= ($i + 1) . ". {$tiempo_restante}\n";
            $mensaje .= "   Con: {$int['otro_usuario']}\n";
            $mensaje .= "   Lugar: {$int['lugar_encuentro']}\n";
            $mensaje .= "   Fecha: " . $fecha->format('d/m/Y H:i') . "\n\n";
        }
        
        $mensaje .= "💡 **Consejo:** Confirma con la otra persona un día antes.";
        return $mensaje;
        
    } catch (Exception $e) {
        return "❌ Error al obtener recordatorios: " . $e->getMessage();
    }
}

/**
 * Consejos de seguridad
 */
function consejosSeguridad() {
    return "🛡️ **Consejos de seguridad para intercambios:**\n\n" .
           "✅ **Antes del encuentro:**\n" .
           "• Revisa el perfil y valoraciones del otro usuario\n" .
           "• Comunica claramente qué vas a intercambiar\n" .
           "• Acuerden un lugar público y seguro\n" .
           "• Prefiere horarios diurnos\n\n" .
           "✅ **Durante el encuentro:**\n" .
           "• Lleva a un amigo/familiar si es posible\n" .
           "• Verifica el producto antes de intercambiar\n" .
           "• No compartas información personal innecesaria\n" .
           "• Confía en tu instinto\n\n" .
           "✅ **Después del encuentro:**\n" .
           "• Marca el intercambio como 'Entregado'\n" .
           "• Valora tu experiencia honestamente\n" .
           "• Reporta cualquier problema\n\n" .
           "⚠️ **Si algo no se siente bien, cancela el encuentro.**";
}

/**
 * Cómo valorar a un usuario
 */
function comoValorar() {
    return "⭐ **Cómo valorar a un usuario:**\n\n" .
           "Después de completar un intercambio, podrás valorar tu experiencia:\n\n" .
           "1️⃣ Ve a 'Mis Intercambios' → Intercambios completados\n" .
           "2️⃣ Haz clic en 'Valorar usuario'\n" .
           "3️⃣ Selecciona de 1 a 5 estrellas\n" .
           "4️⃣ Escribe un comentario (opcional pero recomendado)\n" .
           "5️⃣ Sé honesto pero respetuoso\n\n" .
           "💡 **Criterios sugeridos:**\n" .
           "• Puntualidad\n" .
           "• Estado del producto según descripción\n" .
           "• Amabilidad y comunicación\n" .
           "• Experiencia general\n\n" .
           "Las valoraciones ayudan a construir confianza en la comunidad.";
}

/**
 * Ver reputación del usuario
 */
function verReputacion($user_id) {
    try {
        $db = getConnection();
        
        // Obtener promedio de valoraciones
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_valoraciones,
                AVG(estrellas) as promedio
            FROM valoraciones
            WHERE usuario_valorado_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener intercambios completados
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM seguimiento_intercambios
            WHERE (usuario1_id = ? OR usuario2_id = ?)
            AND estado = 'completado'
        ");
        $stmt->execute([$user_id, $user_id]);
        $completados = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $mensaje = "📈 **Tu reputación en HandinHand:**\n\n";
        
        if ($stats['total_valoraciones'] > 0) {
            $promedio = round($stats['promedio'], 1);
            $estrellas = str_repeat('⭐', floor($promedio));
            if ($promedio - floor($promedio) >= 0.5) $estrellas .= '✨';
            
            $mensaje .= "**Calificación:** {$estrellas} {$promedio}/5.0\n";
            $mensaje .= "**Valoraciones recibidas:** {$stats['total_valoraciones']}\n";
        } else {
            $mensaje .= "**Calificación:** Sin valoraciones aún\n";
        }
        
        $mensaje .= "**Intercambios completados:** {$completados}\n\n";
        
        if ($stats['total_valoraciones'] == 0) {
            $mensaje .= "💡 Completa más intercambios para recibir valoraciones.";
        } elseif ($stats['promedio'] >= 4.5) {
            $mensaje .= "🏆 ¡Excelente! Eres un usuario destacado.";
        } elseif ($stats['promedio'] >= 3.5) {
            $mensaje .= "👍 Buen trabajo. Sigue mejorando.";
        } else {
            $mensaje .= "💪 Trabaja en mejorar la experiencia de otros usuarios.";
        }
        
        return $mensaje;
        
    } catch (Exception $e) {
        return "❌ Error al obtener reputación: " . $e->getMessage();
    }
}

/**
 * Guía para denunciar
 */
function guiaDenuncia() {
    return "🚨 **Cómo denunciar un problema:**\n\n" .
           "Si tuviste una mala experiencia en un intercambio:\n\n" .
           "1️⃣ Ve a 'Mis Intercambios'\n" .
           "2️⃣ Busca el intercambio problemático\n" .
           "3️⃣ Haz clic en 'Denunciar'\n" .
           "4️⃣ Selecciona el motivo:\n" .
           "   • No apareció al encuentro\n" .
           "   • Producto distinto al descrito\n" .
           "   • Producto dañado\n" .
           "   • Actitud inapropiada\n" .
           "   • Estafa\n" .
           "   • Otro\n" .
           "5️⃣ Describe detalladamente lo ocurrido\n" .
           "6️⃣ Adjunta evidencias si es posible (fotos, capturas)\n\n" .
           "⚖️ Nuestro equipo revisará tu denuncia en 24-48 horas.\n\n" .
           "⚠️ **Importante:** Las denuncias falsas pueden resultar en sanciones.";
}

/**
 * Detectar intención del mensaje y devolver diálogo apropiado
 */
function detectarIntencionDialogo($mensaje, $user_id) {
    $mensaje_lower = strtolower($mensaje);
    
    // Tutorial de intercambios
    if (preg_match('/(como|cómo).*(hacer|funciona|realiz).*(intercambio|trueque)/i', $mensaje) ||
        preg_match('/(tutorial|ayuda|guia|guía).*(intercambio)/i', $mensaje)) {
        return getTutorialIntercambio();
    }
    
    // Listar productos
    if (preg_match('/(mis|muestrame|muéstrame|lista|ver).*(producto|articulo|artículo)/i', $mensaje) ||
        preg_match('/(que|qué).*(producto|cosa).*(tengo|puedo|tiene)/i', $mensaje)) {
        return listarMisProductos($user_id);
    }
    
    // Intercambios pendientes
    if (preg_match('/(tengo|hay).*(intercambio|propuesta|trueque).*(pendiente|activo)/i', $mensaje) ||
        preg_match('/(intercambio|propuesta).*(esperando|pendiente)/i', $mensaje)) {
        return verificarIntercambiosPendientes($user_id);
    }
    
    // Recordatorios
    if (preg_match('/(recordatorio|cuando|cuándo|próximo|proximo).*(intercambio|encuentro)/i', $mensaje) ||
        preg_match('/(tengo|hay).*(encuentro|reunion|reunión)/i', $mensaje)) {
        return recordatoriosIntercambios($user_id);
    }
    
    // Consejos de seguridad
    if (preg_match('/(consejo|tip|recomendacion|recomendación).*(seguridad|seguro)/i', $mensaje) ||
        preg_match('/(como|cómo).*(seguro|cuidarme|proteger)/i', $mensaje)) {
        return consejosSeguridad();
    }
    
    // Cómo valorar
    if (preg_match('/(como|cómo).*(valorar|calificar|puntuar)/i', $mensaje) ||
        preg_match('/(valoracion|valoración|calificacion|calificación)/i', $mensaje)) {
        return comoValorar();
    }
    
    // Reputación
    if (preg_match('/(mi|ver|mostrar).*(reputacion|reputación|calificacion|calificación|valoracion|valoración)/i', $mensaje) ||
        preg_match('/(como|cómo).*(me|estoy).*(visto|valorado)/i', $mensaje)) {
        return verReputacion($user_id);
    }
    
    // Denunciar
    if (preg_match('/(como|cómo).*(denunciar|reportar|quejar)/i', $mensaje) ||
        preg_match('/(problema|malo|estafa).*(intercambio)/i', $mensaje)) {
        return guiaDenuncia();
    }
    
    return null; // No se detectó ningún diálogo específico
}
