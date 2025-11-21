# 🔧 Solución de Errores - HandinHand

## ❌ Problemas detectados:

### 1. Error en perfil.php
**Error:** `Column not found: 1054 Unknown column 'destinatario_id' in 'where clause'`

**Causa:** El código estaba usando la columna antigua `destinatario_id` en lugar de la nueva `receiver_id`.

**✅ Solución aplicada:** 
- Archivo `perfil.php` línea 42 corregida
- Cambio: `destinatario_id` → `receiver_id`

---

### 2. Error en propuesta de intercambio
**Error:** `Column not found: 1054 Unknown column 'mensaje' in 'field list'`

**Causa:** La tabla `mensajes` no tiene las columnas nuevas (`sender_id`, `receiver_id`, `message`, `is_read`).

**✅ Soluciones disponibles:**

#### **Opción A: Ejecutar script SQL automático (RECOMENDADO)**
1. Abre phpMyAdmin o tu cliente MySQL
2. Selecciona la base de datos `handinhand`
3. Ve a la pestaña **SQL**
4. Ejecuta el archivo: `sql/agregar_columnas_nuevas_mensajes.sql`

Este script:
- ✅ Verifica si las columnas nuevas existen
- ✅ Las crea si no existen
- ✅ Copia los datos de las columnas antiguas a las nuevas
- ✅ Es seguro ejecutarlo múltiples veces (idempotente)

#### **Opción B: Verificar estado de la tabla primero**
1. Abre en el navegador: `http://localhost/handinhand/verificar-columnas-mensajes.php`
2. Verás un reporte detallado del estado de la tabla `mensajes`
3. Según el resultado:
   - Si dice "NO MIGRADO" → Ejecuta Opción A
   - Si dice "ESTADO DE TRANSICIÓN" → Las columnas están duplicadas, todo OK
   - Si dice "MIGRACIÓN COMPLETA" → El problema está en otro lado

---

## 📋 Checklist de verificación:

Después de ejecutar las soluciones, verifica:

- [ ] ✅ `perfil.php` carga sin errores
- [ ] ✅ Puedes ver tu perfil completo
- [ ] ✅ Las estadísticas de mensajes se muestran correctamente
- [ ] ✅ Puedes enviar propuestas de intercambio desde `producto.php`
- [ ] ✅ El sistema de mensajería funciona correctamente

---

## 🔍 Si los problemas persisten:

### Verificación manual de la base de datos:
```sql
-- Ejecuta esto en phpMyAdmin para ver las columnas:
DESCRIBE mensajes;

-- Deberías ver estas columnas NUEVAS:
-- sender_id
-- receiver_id
-- message
-- is_read

-- Y opcionalmente estas ANTIGUAS (si aún no fueron eliminadas):
-- remitente_id
-- destinatario_id
-- mensaje
-- leido
```

### Verificar logs de errores:
1. Abre la consola del navegador (F12)
2. Ve a la pestaña "Console"
3. Reproduce el error
4. Copia el error completo y compártelo

---

## 📝 Notas técnicas:

### Archivos modificados en esta corrección:
1. `perfil.php` - Línea 42: `destinatario_id` → `receiver_id`
2. `sql/agregar_columnas_nuevas_mensajes.sql` - Script de migración
3. `verificar-columnas-mensajes.php` - Herramienta de diagnóstico

### Archivos que YA usan las columnas nuevas (no requieren cambios):
- ✅ `api/mensajes.php`
- ✅ `api/save-message.php`
- ✅ `api/perseo-auto-reply.php`
- ✅ `api/users.php`
- ✅ `api/proponer-intercambio.php`

---

## 🚀 Próximos pasos (después de que funcione todo):

Una vez que verifiques que todo funciona correctamente con las columnas duplicadas, puedes ejecutar el **Fase 2** del script de limpieza para eliminar las columnas antiguas:

```sql
-- ⚠️ SOLO EJECUTAR DESPUÉS DE VERIFICAR QUE TODO FUNCIONA
-- Eliminar columnas antiguas
ALTER TABLE mensajes DROP COLUMN remitente_id;
ALTER TABLE mensajes DROP COLUMN destinatario_id;
ALTER TABLE mensajes DROP COLUMN mensaje;
ALTER TABLE mensajes DROP COLUMN leido;
```

Esto liberará espacio y mejorará el rendimiento de la base de datos.

---

## ❓ Dudas frecuentes:

**P: ¿Puedo ejecutar el script SQL varias veces?**
R: ✅ Sí, el script verifica si las columnas existen antes de crearlas.

**P: ¿Perderé datos al ejecutar el script?**
R: ❌ No, el script copia los datos de las columnas antiguas a las nuevas.

**P: ¿Cuánto tiempo tarda?**
R: Depende del número de mensajes, pero usualmente menos de 1 segundo para miles de registros.

**P: ¿Qué pasa si ya ejecuté el script antes?**
R: No pasa nada, el script detecta que las columnas ya existen y no hace cambios.

---

**Última actualización:** 6 de noviembre de 2025
