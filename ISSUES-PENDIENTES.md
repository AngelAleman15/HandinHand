# Issues Pendientes - Sistema de Intercambios

## 🔴 CRÍTICOS - Resolver Ahora

### 1. Página "Mis Intercambios" no visible
**Problema:** No hay enlace visible para acceder a `mis-intercambios.php`
**Solución:** Agregar enlace en el header/menú principal
**Ubicación:** `includes/header.php`

### 2. Notificaciones de intercambio no aparecen
**Problema:** Cuando se acepta un intercambio, la notificación no se muestra en el panel de campana
**Causa posible:** 
- La notificación se crea correctamente en BD
- Pero el sistema de notificaciones no la está cargando/mostrando
**Verificar:** 
- `api/notificaciones.php` - query y tipos
- `js/header-notifications.js` - renderizado
**Archivos:** `crear-seguimiento.php` línea 102-111

### 3. Perseo pregunta repetidamente sobre auto-respuesta
**Problema:** Al recargar/cambiar de ventana, Perseo vuelve a preguntar si activar auto-respuesta
**Comportamiento esperado:** Solo preguntar cuando llega un NUEVO mensaje
**Causa:** No se está guardando el estado de "ya preguntó" en BD o localStorage
**Solución:** Guardar flag en tabla `usuarios` o localStorage con timestamp del último mensaje
**Archivos:** `js/chatbot.js`, `api/perseo-auto-reply.php`

---

## 🟡 MEJORAS - Perseo Dialogos

### Diálogos actuales de Perseo:
- Búsqueda de productos
- Estadísticas del usuario
- Recomendaciones
- Info de categorías

### Sugerencias de nuevos diálogos:
1. **"¿Cómo hacer un intercambio?"** - Tutorial paso a paso
2. **"¿Qué productos puedo intercambiar?"** - Listar productos disponibles del usuario
3. **"¿Tengo intercambios pendientes?"** - Mostrar propuestas y seguimientos activos
4. **"Recordatorios de intercambio"** - Avisar de encuentros próximos (menos de 24h)
5. **"Consejos de seguridad"** - Tips para intercambios seguros
6. **"¿Cómo valorar a un usuario?"** - Explicar sistema de valoraciones
7. **"Ver mi reputación"** - Mostrar promedio de estrellas y valoraciones recibidas
8. **"Problemas con un intercambio"** - Guía para denunciar

---

## 📋 ESTADO ACTUAL DEL SISTEMA

### ✅ Funcionando:
- Base de datos completa (8 tablas)
- API de aceptación de propuestas
- Sistema de notificaciones (BD)
- Página mis-intercambios.php (creada)
- JavaScript intercambios-activos.js
- CSS unificado con diseño HandinHand

### ⚠️ Necesita Testing:
- Flujo completo de aceptación
- Acciones de seguimiento (En camino, Entregado, etc.)
- Cierre automático de intercambios
- Eliminación de productos al completar
- Notificaciones en tiempo real (Socket.IO)

### ❌ No Funciona:
- Acceso a "Mis Intercambios" (falta enlace)
- Notificaciones en campana (no se muestran)
- Persistencia de estado Perseo auto-reply

---

## 🔧 CORRECCIONES APLICADAS HOY

1. ✅ Migración tabla `notificaciones` (estructura nueva)
2. ✅ Corrección columnas `usuarios` (fullname, avatar_path)
3. ✅ Corrección columnas `propuestas_intercambio` (solicitante_id, receptor_id)
4. ✅ Corrección columnas `mensajes` (sender_id, receiver_id)
5. ✅ Unificación estética CSS (verde #6a994e, border-radius 8px)
6. ✅ Deshabilitada notificación antigua en proponer-intercambio.php

---

## 📝 PRÓXIMOS PASOS

### Paso 1: Agregar enlace "Mis Intercambios" en header
### Paso 2: Verificar por qué notificaciones no aparecen en campana
### Paso 3: Arreglar persistencia de estado Perseo
### Paso 4: Agregar nuevos diálogos a Perseo
### Paso 5: Testing completo del flujo de intercambios
