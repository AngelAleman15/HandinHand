# 🔧 Pasos para activar Editar y Eliminar Mensajes

## 1️⃣ Ejecutar la migración SQL

Abre **phpMyAdmin** y ejecuta el siguiente SQL en la base de datos `handinhand`:

```sql
-- Agregar columnas para eliminar y editar mensajes
ALTER TABLE mensajes 
ADD COLUMN edited_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN deleted_for TEXT NULL DEFAULT NULL COMMENT 'JSON array de user IDs que eliminaron el mensaje para ellos',
ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE COMMENT 'True si el remitente eliminó el mensaje completamente';

-- Índice para búsquedas rápidas
ALTER TABLE mensajes ADD INDEX idx_is_deleted (is_deleted);
```

O simplemente ejecuta el archivo: `sql/add_message_actions.sql`

---

## 2️⃣ Reiniciar el servidor Socket.IO

En la terminal donde está corriendo el servidor Node.js:

1. **Detén el servidor**: `Ctrl+C`
2. **Reinicia el servidor**: 
   ```bash
   cd c:\wamp64\www\MisTrabajos\HandinHand
   node server.js
   ```

---

## 3️⃣ Recargar la página de mensajería

1. Abre la página de mensajería
2. Haz **Ctrl+F5** (recarga sin caché)
3. ¡Listo!

---

## ✨ Funcionalidades implementadas

### 📝 **Editar mensaje propio**
- Haz hover sobre tu mensaje
- Clic en los 3 puntos (⋮)
- Selecciona "Editar"
- Modifica el texto
- Se actualiza en tiempo real para ambos usuarios
- Muestra indicador "(editado)"

### 🗑️ **Eliminar mensaje propio**
- Haz hover sobre tu mensaje
- Clic en los 3 puntos (⋮)
- Selecciona "Eliminar para todos"
- Confirma la eliminación
- **Se elimina completamente** de ambos chats

### 🚫 **Eliminar mensaje recibido**
- Haz hover sobre mensaje recibido
- Clic en los 3 puntos (⋮)
- Selecciona "Eliminar para mí"
- Confirma la eliminación
- **Solo se oculta para ti**, el otro usuario sigue viéndolo

### 💬 **Responder mensaje**
- Funcionalidad existente mejorada
- Ahora con mejor menú de opciones

---

## 🔧 Archivos modificados/creados

### Nuevos archivos:
- ✅ `api/edit-message.php` - API para editar mensajes
- ✅ `api/delete-message.php` - API para eliminar mensajes
- ✅ `sql/add_message_actions.sql` - Migración de base de datos

### Archivos modificados:
- ✅ `api/get-messages.php` - Filtra mensajes eliminados
- ✅ `js/chat.js` - Lógica de edición/eliminación
- ✅ `mensajeria.php` - Estilos CSS
- ✅ `server.js` - Eventos Socket.IO

---

## 🎨 Estilos CSS

Se agregaron estilos para:
- Menú de opciones mejorado (editar/eliminar)
- Indicador de mensaje editado
- Opción "danger" en rojo para eliminar
- Animaciones y transiciones suaves

---

## 🚀 Verificación

Para verificar que todo funciona:

1. **Abre 2 navegadores diferentes** (o modo incógnito)
2. **Inicia sesión con 2 usuarios** diferentes
3. **Envía mensajes** entre ellos
4. **Prueba editar** un mensaje propio → debe actualizarse en tiempo real
5. **Prueba eliminar** mensaje propio → debe desaparecer de ambos chats
6. **Prueba eliminar** mensaje recibido → solo desaparece para ti

---

## ⚠️ Notas importantes

- Los mensajes editados muestran "(editado)" al final
- Los mensajes eliminados completamente se eliminan de la base de datos (is_deleted = TRUE)
- Los mensajes eliminados "solo para mí" se guardan en un JSON array (deleted_for)
- Todo funciona en tiempo real con Socket.IO
- Compatible con la funcionalidad de responder existente

---

## 🐛 Troubleshooting

### No funciona la edición/eliminación:
1. Verifica que ejecutaste la migración SQL
2. Verifica que reiniciaste el servidor Node.js
3. Recarga la página con Ctrl+F5
4. Abre la consola del navegador (F12) para ver errores

### No se actualiza en tiempo real:
1. Verifica que el servidor Socket.IO esté corriendo
2. Verifica la conexión en la consola del navegador
3. Revisa los logs del servidor Node.js

---

¡Todo listo! 🎉
