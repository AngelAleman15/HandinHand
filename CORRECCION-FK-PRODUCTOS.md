# Error FK Constraint - Productos al Completar Intercambio

## 🔴 Error Detectado

```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: 
a foreign key constraint fails (`handinhand`.`acciones_seguimiento_ibfk_1` 
FOREIGN KEY (`seguimiento_id`) REFERENCES `seguimiento_intercambios` (`id`) 
ON DELETE CASCADE)
```

**Cuando:** Los dos usuarios marcan el intercambio como "Entregado"  
**Archivo:** `api/accion-seguimiento.php` línea 131  

## 🔧 Problema

El código intentaba **ELIMINAR** los productos cuando se completaba el intercambio:

```php
DELETE FROM productos WHERE id IN (?, ?)
```

Pero hay **FOREIGN KEYS** que apuntan a esos productos:
- `seguimiento_intercambios.producto_ofrecido_id` → `productos.id`
- `seguimiento_intercambios.producto_solicitado_id` → `productos.id`

No se pueden eliminar productos que están referenciados por otras tablas.

## ✅ Solución Aplicada

En lugar de **ELIMINAR**, ahora **MARCA COMO INTERCAMBIADO**:

### Antes (❌):
```php
// ELIMINAR PRODUCTOS DEL INVENTARIO
$stmt = $db->prepare("DELETE FROM productos WHERE id IN (?, ?)");
$stmt->execute([$seguimiento['producto_ofrecido_id'], $seguimiento['producto_solicitado_id']]);
```

### Después (✅):
```php
// MARCAR PRODUCTOS COMO INTERCAMBIADOS (no eliminar por las FK)
$stmt = $db->prepare("UPDATE productos SET estado = 'intercambiado' WHERE id IN (?, ?)");
$stmt->execute([$seguimiento['producto_ofrecido_id'], $seguimiento['producto_solicitado_id']]);
```

## 📊 Estados de Producto

El sistema ahora maneja correctamente los estados:

| Estado | Cuándo | Visible en Búsqueda |
|--------|--------|---------------------|
| `disponible` | Producto publicado | ✅ Sí |
| `reservado` | Propuesta aceptada | ❌ No |
| `intercambiado` | Intercambio completado | ❌ No |

## 💡 Beneficios

1. **Mantiene historial** - Los productos no se eliminan, se marcan como intercambiados
2. **Respeta FK** - No rompe las foreign keys de seguimiento_intercambios
3. **Auditoría** - Se puede consultar qué productos fueron intercambiados
4. **Estadísticas** - Se pueden generar reportes de intercambios completados

## 🧪 Testing

Ahora deberías poder:
1. Usuario A marca "Entregado" → ✅ OK
2. Usuario B marca "Entregado" → ✅ OK
3. Estado cambia a "completado" → ✅ OK
4. Productos marcan como "intercambiado" → ✅ OK (NO se eliminan)
5. Notificaciones enviadas → ✅ OK

**Recarga y prueba el flujo completo de nuevo.**
