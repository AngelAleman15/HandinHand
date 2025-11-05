# 🔄 MIGRACIÓN: Sistema de Valoraciones de Productos

## 📋 Resumen de Cambios

El sistema de valoraciones ha sido **rediseñado completamente** para valorar **productos** en lugar de usuarios.

### ✅ Lo que se ha hecho:

1. **Nueva tabla**: `valoraciones_productos`
   - Almacena valoraciones de 1 a 5 estrellas para cada producto
   - Permite comentarios opcionales
   - Un usuario puede valorar cada producto solo una vez (o actualizar su valoración)

2. **Nuevo API**: `api/valoraciones-productos.php`
   - GET: Obtener todas las valoraciones de un producto
   - POST: Crear o actualizar valoración de un producto
   - DELETE: Eliminar una valoración propia

3. **Actualizado**: `producto.php`
   - Ahora valora el producto (no al vendedor)
   - Muestra las valoraciones del producto
   - Actualiza el promedio y total de estrellas

4. **Nuevas columnas** en tabla `productos`:
   - `promedio_estrellas`: DECIMAL(2,1) - Promedio de valoraciones
   - `total_valoraciones`: INT - Cantidad de valoraciones

---

## 🚀 INSTRUCCIONES DE INSTALACIÓN

### Paso 1: Ejecutar la migración SQL

1. Abre **phpMyAdmin** en tu navegador:
   ```
   http://localhost/phpmyadmin
   ```

2. Selecciona la base de datos **handinhand**

3. Ve a la pestaña **SQL**

4. Copia y pega TODO el contenido del archivo:
   ```
   sql/migrar_valoraciones_productos.sql
   ```

5. Haz clic en **Continuar** para ejecutar

6. Verifica que veas el mensaje:
   ```
   Migración completada exitosamente!
   ```

### Paso 2: Verificar la instalación

1. En phpMyAdmin, ve a la pestaña **Estructura**

2. Verifica que existe la tabla **valoraciones_productos**

3. Haz clic en la tabla `valoraciones_productos` y ve a **Examinar**
   - Deberías ver 7 valoraciones de ejemplo

4. Haz clic en la tabla `productos` y ve a **Estructura**
   - Verifica que existen las columnas:
     - `promedio_estrellas`
     - `total_valoraciones`

### Paso 3: Probar el sistema

1. Abre un producto en tu navegador:
   ```
   http://handinhand.sytes.net/producto.php?id=1
   ```

2. Inicia sesión si no lo has hecho

3. Desplázate a la sección **"Valorar este producto"**

4. Selecciona estrellas (1-5) y escribe un comentario opcional

5. Haz clic en **"Enviar valoración"**

6. Deberías ver:
   - ✓ Mensaje de éxito
   - La página se recarga
   - El promedio de estrellas se actualiza

7. Haz clic en **"Ver todas las valoraciones"** para ver el modal

---

## 🔍 Diferencias Clave

### ANTES (Sistema antiguo - Usuarios):
```javascript
// Valoraba al VENDEDOR del producto
enviarValoracion(<?php echo $producto['user_id']; ?>)

// Mostraba valoraciones DEL VENDEDOR
api/valoraciones.php?usuario_id=X
```

### AHORA (Sistema nuevo - Productos):
```javascript
// Valora el PRODUCTO
enviarValoracion(<?php echo $producto['id']; ?>)

// Muestra valoraciones DEL PRODUCTO
api/valoraciones-productos.php?producto_id=X
```

---

## 📊 Estructura de Datos

### Tabla: valoraciones_productos
```sql
id                INT (PK, AUTO_INCREMENT)
producto_id       INT (FK → productos.id)
usuario_id        INT (FK → usuarios.id)
puntuacion        INT (1-5)
comentario        TEXT (opcional)
created_at        TIMESTAMP
updated_at        TIMESTAMP

UNIQUE KEY: (producto_id, usuario_id)
```

### Tabla: productos (columnas nuevas)
```sql
promedio_estrellas   DECIMAL(2,1) DEFAULT 0.0
total_valoraciones   INT DEFAULT 0
```

---

## 🧪 Datos de Prueba

Se incluyen 7 valoraciones de ejemplo:

| Producto | Usuario | Estrellas | Comentario |
|----------|---------|-----------|------------|
| Smartphone Samsung | Usuario 2 | ⭐⭐⭐⭐⭐ | Excelente smartphone... |
| Smartphone Samsung | Usuario 3 | ⭐⭐⭐⭐ | Buen estado general... |
| Smartphone Samsung | Usuario 4 | ⭐⭐⭐⭐⭐ | Tal como se describe... |
| Zapatillas Nike | Usuario 1 | ⭐⭐⭐⭐ | Zapatillas en buen estado... |
| Zapatillas Nike | Usuario 3 | ⭐⭐⭐⭐⭐ | Perfectas para deportes... |
| Guitarra | Usuario 2 | ⭐⭐⭐⭐⭐ | Guitarra con excelente sonido... |
| Guitarra | Usuario 4 | ⭐⭐⭐⭐ | Buen instrumento... |

---

## ⚠️ IMPORTANTE

### La tabla antigua `valoraciones` sigue existiendo
- Contiene valoraciones de **usuarios** (no productos)
- **NO se elimina** en esta migración por seguridad
- Si ya no la necesitas, puedes eliminarla manualmente

### Si quieres eliminar la tabla antigua:
```sql
DROP TABLE IF EXISTS `valoraciones`;
```

### API antigua sigue disponible
- `api/valoraciones.php` - Para valoraciones de usuarios
- `api/valoraciones-productos.php` - Para valoraciones de productos (NUEVO)

---

## 🐛 Solución de Problemas

### Error: "Tabla ya existe"
- Es normal, la migración usa `CREATE TABLE IF NOT EXISTS`
- Puedes ejecutar el script múltiples veces sin problemas

### Error: "Columna duplicada"
- Es normal, el script verifica antes de agregar columnas
- Puedes ejecutar el script múltiples veces sin problemas

### Error: "Foreign key constraint fails"
- Verifica que existan los productos con id 1, 2, 3
- Verifica que existan los usuarios con id 1, 2, 3, 4

### Las estrellas no se actualizan
- Verifica que ejecutaste la última parte del script SQL
- Ejecuta manualmente:
  ```sql
  UPDATE productos p
  SET promedio_estrellas = (
      SELECT ROUND(AVG(puntuacion), 1) 
      FROM valoraciones_productos 
      WHERE producto_id = p.id
  );
  ```

---

## ✅ Checklist de Verificación

- [ ] Tabla `valoraciones_productos` creada
- [ ] Columnas `promedio_estrellas` y `total_valoraciones` agregadas a `productos`
- [ ] 7 valoraciones de ejemplo insertadas
- [ ] API `api/valoraciones-productos.php` funcionando
- [ ] `producto.php` actualizado correctamente
- [ ] Puedes enviar una valoración sin errores
- [ ] El modal muestra las valoraciones correctamente
- [ ] El promedio se actualiza después de valorar

---

## 📞 Soporte

Si encuentras algún error:
1. Revisa la consola del navegador (F12)
2. Revisa los logs de PHP (error.log)
3. Verifica que ejecutaste el script SQL completo
4. Asegúrate de estar logueado para poder valorar

---

**Fecha de creación**: 5 de noviembre de 2025  
**Versión**: 1.0  
**Sistema**: HandinHand - Plataforma de Intercambio
