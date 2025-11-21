# 🔧 Resumen de Correcciones - Sistema de Intercambio

## 📋 Problema Identificado

Después de analizar tu base de datos exportada (`sql/handinhandnew.sql`), descubrí que el esquema real tiene **AMBOS conjuntos de columnas** en la tabla `mensajes` debido a la migración `sql/unificar_mensajes.sql` que agregó columnas nuevas pero NO eliminó las viejas.

## 🗃️ Esquema Real de la Tabla `mensajes`

### Columnas NUEVAS (agregadas por unificar_mensajes.sql):
- `sender_id` INT
- `receiver_id` INT  
- `message` TEXT

### Columnas VIEJAS (originales):
- `remitente_id` INT
- `destinatario_id` INT
- `mensaje` TEXT

### Columnas Comunes:
- `producto_id` INT
- `tipo_mensaje` VARCHAR(50)
- `producto_relacionado_id` INT
- `is_read`, `read_at`, `reply_to_message_id`, etc.

---

## ✅ Cambios Realizados

### 1. **api/proponer-intercambio.php** ✅

**CAMBIO:** Modificado el INSERT de mensajes para llenar **AMBOS** conjuntos de columnas (nuevas y viejas).

```php
// ANTES (solo columnas nuevas - causaba error):
INSERT INTO mensajes (sender_id, receiver_id, message, producto_id, tipo_mensaje, ...)

// DESPUÉS (ambas columnas para compatibilidad total):
INSERT INTO mensajes 
(sender_id, receiver_id, message, remitente_id, destinatario_id, mensaje, 
 producto_id, tipo_mensaje, producto_relacionado_id, ...)
VALUES 
(:sender_id, :receiver_id, :message, :remitente_id, :destinatario_id, :mensaje,
 :producto_id, :tipo_mensaje, :producto_relacionado_id, ...)
```

**MOTIVO:** Asegura compatibilidad con código existente que pueda usar cualquier conjunto de columnas.

---

**CAMBIO:** Agregado manejo de errores para notificaciones.

```php
try {
    $stmt->execute([$vendedor_id, $notif_mensaje, $enlace]);
} catch (Exception $e) {
    // Si falla la notificación, continuar
    error_log("No se pudo crear notificación: " . $e->getMessage());
}
```

**MOTIVO:** Si la tabla `notificaciones` no existe (antes de ejecutar migración), no rompe el flujo.

---

### 2. **migrar_sistema_intercambio.php** ✅

**CAMBIO:** Mejorada la verificación de columnas existentes.

```php
// Verificar si tipo_mensaje existe
$stmt = $pdo->query("SHOW COLUMNS FROM mensajes LIKE 'tipo_mensaje'");
if ($stmt->rowCount() == 0) {
    // Solo agregar si NO existe
    $pdo->exec("ALTER TABLE mensajes ADD COLUMN tipo_mensaje ...");
}
```

**MOTIVO:** 
- Según tu BD exportada, `tipo_mensaje` y `producto_relacionado_id` **ya existen**
- La migración solo debe crearlas si faltan
- Evita errores de "columna ya existe"

---

**CAMBIO:** Tabla `chats_temporales` ya existe en tu BD.

**Estado:** La migración solo la creará si NO existe (usando `CREATE TABLE IF NOT EXISTS`).

---

**CAMBIO:** Tabla `notificaciones` NO existe en tu BD actual.

**Estado:** La migración la creará cuando ejecutes el script.

---

## 📊 Estado de las Tablas en tu BD

| Tabla | Estado | Acción de Migración |
|-------|--------|---------------------|
| `mensajes` | ✅ Existe con columnas duales | Verificar y agregar columnas faltantes |
| `productos` | ✅ Existe (`user_id`, `nombre`, etc.) | Ninguna |
| `usuarios` | ✅ Existe (`id`, `username`, etc.) | Ninguna |
| `amistades` | ✅ Existe (`usuario1_id`, `usuario2_id`) | Ninguna |
| `chats_temporales` | ✅ Existe | Verificar y crear si falta |
| `notificaciones` | ❌ NO EXISTE | **Crear al ejecutar migración** |

---

## 🚀 Pasos para Probar

### 1. Ejecutar Migración
```
http://localhost/MisTrabajos/HandinHand/migrar_sistema_intercambio.php
```

**Resultado esperado:**
```
1. Creando tabla chats_temporales...
○ Columna tipo_mensaje ya existe (OK)
○ Columna producto_relacionado_id ya existe (OK)

2. Verificando columnas en tabla mensajes...
○ Columna tipo_mensaje ya existe (OK)
○ Columna producto_relacionado_id ya existe (OK)

3. Creando tabla notificaciones...
✓ Tabla notificaciones creada

========================================
✓ Migración completada exitosamente!
========================================
```

---

### 2. Probar Sistema de Intercambio

**Pasos:**
1. Login con usuario que tenga productos
2. Ir a la página de un producto de OTRO usuario
3. Hacer clic en "Intercambiar"
4. Seleccionar tu producto para ofrecer
5. Escribir mensaje (opcional)
6. Enviar propuesta

**Resultado esperado:**
```json
{
  "success": true,
  "message": "Propuesta de intercambio enviada exitosamente",
  "data": {
    "producto_ofrecido": {...},
    "producto_solicitado": {...},
    "chat_creado": true
  }
}
```

---

### 3. Verificar en Base de Datos

**Consultas para verificar:**

```sql
-- Ver mensajes de intercambio
SELECT id, sender_id, receiver_id, message, mensaje, tipo_mensaje, producto_relacionado_id
FROM mensajes 
WHERE tipo_mensaje = 'propuesta_intercambio'
ORDER BY created_at DESC;

-- Ver productos reservados
SELECT id, nombre, estado, user_id
FROM productos 
WHERE estado = 'reservado';

-- Ver chats temporales
SELECT * FROM chats_temporales 
WHERE activo = 1
ORDER BY created_at DESC;

-- Ver notificaciones
SELECT * FROM notificaciones 
ORDER BY created_at DESC;
```

---

## ⚠️ Notas Importantes

### Sobre Columnas Duplicadas en `mensajes`

Tu tabla `mensajes` tiene columnas duplicadas porque:
1. **Migración original:** `remitente_id`, `destinatario_id`, `mensaje`
2. **Migración unificar_mensajes.sql (líneas 93-97):** Agregó `sender_id`, `receiver_id`, `message`
3. **Líneas 113-117 comentadas:** Deberían haber eliminado columnas viejas pero están comentadas

**Solución implementada:**
- Llenar AMBOS conjuntos de columnas al insertar
- Garantiza compatibilidad con todo el código existente
- No requiere cambiar código de mensajería existente

---

### Recomendación Futura

Si quieres limpiar el esquema (opcional):

```sql
-- SOLO ejecutar si ESTÁS SEGURO que todo tu código usa las columnas NUEVAS
ALTER TABLE mensajes 
DROP COLUMN remitente_id,
DROP COLUMN destinatario_id,  
DROP COLUMN mensaje;
```

**ADVERTENCIA:** NO ejecutar hasta verificar que TODO el código usa `sender_id`/`receiver_id`/`message`.

---

## 📝 Archivos Modificados

1. ✅ `api/proponer-intercambio.php` - INSERT con columnas duales + manejo de errores
2. ✅ `migrar_sistema_intercambio.php` - Verificaciones mejoradas
3. ✅ `RESUMEN-CORRECCION-SCHEMA.md` - Este archivo (documentación)

---

## 🎯 Conclusión

Todos los errores SQL se debían a **asumir un esquema que no coincidía con la realidad**. 

Ahora el código:
- ✅ Usa las columnas que **realmente existen** en tu BD
- ✅ Llena ambos conjuntos de columnas para **compatibilidad total**  
- ✅ Maneja errores de notificaciones **antes de que exista la tabla**
- ✅ Verifica columnas **antes de intentar crearlas**

**El sistema de intercambio debería funcionar correctamente ahora.** 🎉
