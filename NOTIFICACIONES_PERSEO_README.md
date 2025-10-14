# Sistema de Notificaciones de Mensajes con Perseo

Este sistema permite que Perseo (la IA) notifique al usuario sobre mensajes sin leer y ofrezca enviar respuestas automáticas.

## Instalación

### 1. Ejecutar script SQL

Ejecuta el siguiente script SQL en tu base de datos para agregar la columna necesaria:

```sql
-- Agregar columna para identificar mensajes automáticos de Perseo
ALTER TABLE mensajes ADD COLUMN IF NOT EXISTS is_perseo_auto TINYINT(1) DEFAULT 0 AFTER message;

-- Crear índice para optimizar consultas de mensajes no leídos
CREATE INDEX IF NOT EXISTS idx_unread_messages ON mensajes(receiver_id, is_read);
```

**Opciones para ejecutar:**

#### Opción 1: phpMyAdmin
1. Abre phpMyAdmin en tu navegador (http://localhost/phpmyadmin)
2. Selecciona tu base de datos `handinhand`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido del archivo `sql/add_perseo_auto_column.sql`
5. Haz clic en "Continuar"

#### Opción 2: MySQL Command Line
```bash
cd c:\wamp64\www\MisTrabajos\HandinHand
mysql -u root -p handinhand < sql/add_perseo_auto_column.sql
```

#### Opción 3: Script PHP
Puedes ejecutar este archivo PHP directamente en el navegador:

```php
<?php
// Archivo: run_perseo_migration.php
require_once 'config/database.php';

try {
    $pdo = getConnection();
    
    // Agregar columna is_perseo_auto
    $sql1 = "ALTER TABLE mensajes ADD COLUMN IF NOT EXISTS is_perseo_auto TINYINT(1) DEFAULT 0 AFTER message";
    $pdo->exec($sql1);
    echo "✅ Columna is_perseo_auto agregada correctamente<br>";
    
    // Crear índice
    $sql2 = "CREATE INDEX IF NOT EXISTS idx_unread_messages ON mensajes(receiver_id, is_read)";
    $pdo->exec($sql2);
    echo "✅ Índice idx_unread_messages creado correctamente<br>";
    
    echo "<br><strong>✅ Migración completada exitosamente</strong>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
```

### 2. Verificar instalación

Los archivos ya están creados y listos:
- ✅ `api/get-total-unread.php` - Obtiene el total de mensajes sin leer
- ✅ `api/perseo-auto-reply.php` - Envía respuestas automáticas
- ✅ `js/notifications.js` - Sistema de notificaciones del frontend
- ✅ `sql/add_perseo_auto_column.sql` - Script SQL para migración
- ✅ Estilos CSS agregados a `css/style.css`
- ✅ `includes/header.php` actualizado con el script de notificaciones

## Funcionalidades

### 1. Badge de Notificación
- Se muestra un badge rojo en el menú "💬 Mensajes" cuando hay mensajes sin leer
- El badge muestra el número total de mensajes no leídos
- Tiene una animación de pulso para llamar la atención

### 2. Notificación de Perseo
Cuando el usuario tiene mensajes sin leer:
1. **Perseo abre automáticamente** el chatbot (después de 2 segundos)
2. **Notifica al usuario** sobre los mensajes pendientes
3. **Pregunta si desea** que responda automáticamente
4. **Muestra botones** para aceptar o rechazar

### 3. Respuestas Automáticas
Si el usuario acepta:
- Perseo envía mensajes automáticos a todos los usuarios que enviaron mensajes
- Los mensajes indican que el usuario no está disponible
- Se varía el mensaje para que no sea repetitivo
- Los mensajes están marcados con 🤖 para diferenciarlos

### 4. Identificación Visual
Los mensajes automáticos de Perseo se distinguen por:
- **Fondo especial** con gradiente verde claro
- **Borde izquierdo verde** para destacarlos
- **Icono 🤖** al lado del mensaje
- **Etiqueta "Respuesta Automática"** en el timestamp
- **Color especial** en el indicador de hora

## Mensajes Automáticos

Perseo utiliza mensajes variados como:

1. "🤖 [Respuesta Automática de Perseo] Hola, {username} no está disponible en este momento. Tu mensaje ha sido recibido y será respondido en breve. ¡Gracias por tu paciencia!"

2. "🤖 [Respuesta Automática de Perseo] {username} está ocupado/a ahora mismo. He guardado tu mensaje y te responderá lo antes posible. ¡Gracias!"

3. "🤖 [Respuesta Automática de Perseo] Actualmente {username} no puede responder. Tu mensaje es importante y será atendido en cuanto sea posible."

## Configuración

### Intervalo de Verificación
Por defecto, el sistema verifica mensajes no leídos cada 15 segundos. Puedes cambiar esto en `js/notifications.js`:

```javascript
// Línea 15
notificationCheckInterval = setInterval(checkUnreadMessages, 15000); // 15000 = 15 segundos
```

### Tiempo de Notificación de Perseo
Perseo abre el chat 2 segundos después de detectar mensajes. Puedes ajustar esto en `js/notifications.js`:

```javascript
// Línea 109
setTimeout(() => {
    // ...
}, 2000); // 2000 = 2 segundos
```

## Solución de Problemas

### El badge no aparece
- Verifica que estés logueado
- Abre la consola del navegador (F12) y busca errores
- Verifica que `api/get-total-unread.php` responda correctamente

### Perseo no notifica
- Verifica que el chatbot esté cargado (debe aparecer el ícono de Perseo)
- Revisa la consola del navegador para errores
- Asegúrate de que `js/notifications.js` esté cargándose

### Las respuestas automáticas no se envían
- Verifica que la columna `is_perseo_auto` exista en la tabla `mensajes`
- Revisa los logs de PHP para errores
- Confirma que `api/perseo-auto-reply.php` sea accesible

### Los mensajes automáticos no se ven diferentes
- Limpia el caché del navegador (Ctrl + Shift + R)
- Verifica que los estilos CSS se hayan guardado correctamente
- Asegúrate de que `css/style.css` tenga los estilos de `.perseo-auto-message`

## Pruebas

Para probar el sistema:

1. **Crear mensajes de prueba:**
   - Usa otra cuenta de usuario
   - Envía mensajes a tu cuenta principal
   - No los leas aún

2. **Verificar notificación:**
   - Inicia sesión con tu cuenta principal
   - Espera a que aparezca el badge en el menú
   - Perseo debería abrir el chat y notificarte

3. **Probar respuesta automática:**
   - Haz clic en "✅ Sí, responde por mí"
   - Verifica que se envíen las respuestas
   - Revisa en la otra cuenta que los mensajes tengan el formato correcto con 🤖

## Tecnologías Utilizadas

- **Frontend:** JavaScript vanilla, CSS3
- **Backend:** PHP 8.2, MySQL
- **APIs:** RESTful endpoints
- **Tiempo Real:** Sistema de polling cada 15 segundos

## Soporte

Si encuentras algún problema o tienes sugerencias, por favor:
1. Revisa los logs de errores en el navegador (F12 > Console)
2. Revisa los logs de PHP en WAMP
3. Verifica que todos los archivos estén en su lugar
