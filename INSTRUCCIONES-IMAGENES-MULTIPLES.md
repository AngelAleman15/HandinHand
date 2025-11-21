# 📸 Instrucciones para Sistema de Múltiples Imágenes

## ✅ Estado del Sistema
- ✅ Base de datos configurada
- ✅ API de upload actualizada
- ✅ API de eliminación creada
- ✅ Galería dinámica funcionando
- ✅ Selector de imagen principal operativo

---

## 🔧 Archivos del Sistema

### 1. Base de Datos
**Archivo:** `sql/add_es_principal_to_imagenes.sql`
- Tabla: `producto_imagenes`
- Campos: id, producto_id, imagen, es_principal, created_at
- Relación: FOREIGN KEY con ON DELETE CASCADE

### 2. API Upload
**Archivo:** `api/upload-producto-imagen.php`
- Acepta: imagen, producto_id, es_principal (1/0)
- Transacciones para integridad
- Solo marca principal si no hay imágenes existentes
- Nombres únicos: `prod_{id}_{timestamp}_{uniqid}.{ext}`

### 3. API Delete
**Archivo:** `api/delete-producto-imagen.php`
- Verifica permisos del usuario
- Elimina BD + archivo físico
- Auto-asigna nueva principal si se elimina la actual
- Rollback automático en errores

### 4. Frontend
**Archivo:** `editar-producto.php`
- Galería dinámica desde BD (líneas 741-763)
- Upload inteligente (no sobrescribe principal)
- Eliminación con confirmación SweetAlert
- Límite de 6 imágenes máximo

### 5. API - Visualización
**Archivo:** `api/productos.php`
- **GET /api/productos.php?id=X**: Carga todas las imágenes del producto desde `producto_imagenes`
- **GET /api/productos.php**: Carga imagen principal (es_principal=1) para cada producto del listado
- Fallback a `productos.imagen` si no hay imágenes en `producto_imagenes`
- Orden: Principal primero, luego por ID ascendente

### 6. Vista del Producto
**Archivo:** `producto.php`
- Muestra carrusel de imágenes si hay más de 1
- Botones prev/next para navegación
- Indicadores de posición
- Miniaturas clickeables

---

## 📋 Cómo Usar

### Subir Imágenes
1. Haz clic en el botón "Añadir Imágenes"
2. Selecciona hasta 6 imágenes
3. Haz clic en "Subir Imágenes"
4. **Primera vez**: La primera imagen será la principal automáticamente
5. **Imágenes adicionales**: Se añaden como secundarias

### Cambiar Imagen Principal
1. En la galería, haz clic en cualquier imagen
2. Se marcará con borde verde
3. La tabla `productos.imagen` se actualiza automáticamente

### Eliminar Imágenes
1. Haz clic en el botón "×" de cualquier imagen
2. Confirma en el diálogo SweetAlert
3. **Si eliminas la principal**: Otra imagen se marca automáticamente como principal
4. La página se recarga para mostrar cambios

### Ver Producto
1. Navega a cualquier producto desde el listado
2. **Verás todas las imágenes** en un carrusel (si hay más de 1)
3. Usa las flechas ← → o haz clic en las miniaturas para cambiar
4. Los indicadores muestran la posición actual

---

## 🔍 Solución de Problemas

### Solo aparece 1 imagen en producto.php
**Causa:** API no estaba consultando la tabla producto_imagenes
**Solución:** Actualizada `api/productos.php` (líneas 156-168)
- Ahora consulta: `SELECT imagen FROM producto_imagenes WHERE producto_id = ? ORDER BY es_principal DESC, id ASC`
- Fallback a `productos.imagen` si no hay registros

### Solo se guarda 1 imagen de las 3 subidas
**Causa:** Lógica anterior marcaba siempre la primera como principal
**Solución:** Actualizado en línea 1252 de `editar-producto.php`
```javascript
// Solo marca principal si NO hay imágenes existentes
formData.append('es_principal', (i === 0 && existingImages === 0) ? '1' : '0');
```

### Las imágenes no aparecen después de subir
**Verifica:**
1. Tabla existe: `SHOW TABLES LIKE 'producto_imagenes';`
2. Datos insertados: `SELECT * FROM producto_imagenes WHERE producto_id = X;`
3. Permisos de carpeta: `uploads/productos/` debe tener permisos de escritura

### Error "Imagen principal no encontrada"
**Verifica:**
```sql
SELECT COUNT(*) FROM producto_imagenes WHERE producto_id = X AND es_principal = 1;
```
Debe devolver 1. Si devuelve 0:
```sql
UPDATE producto_imagenes SET es_principal = 1 WHERE producto_id = X ORDER BY id ASC LIMIT 1;
```

---

## 🧪 Herramientas de Prueba

### 1. Script de Verificación SQL
**Archivo:** `sql/verificar_imagenes_sistema.sql`
- Ejecuta todas las queries para verificar el estado del sistema
- Muestra productos con múltiples principales
- Busca productos sin imagen principal
- Simula cómo la API carga las imágenes

### 2. Test de API
**URL:** `http://localhost/test-imagenes-api.php?id=X`
- Interfaz visual para probar la API
- Muestra todas las imágenes del producto
- Compara respuesta API vs datos en BD
- Grid de miniaturas con imagen principal destacada
- Cambia de producto con el formulario

### Cómo usar el test:
1. Abre `http://localhost/test-imagenes-api.php?id=1`
2. Verifica que muestre todas las imágenes
3. Comprueba que la primera esté marcada como "⭐ Principal"
4. Revisa la tabla de BD para confirmar datos
5. Prueba con diferentes IDs de productos

---

## 📊 Resumen de Cambios

### Base de Datos
- ✅ Tabla `producto_imagenes` creada
- ✅ Campo `es_principal` para marcar imagen destacada
- ✅ Relación FOREIGN KEY con CASCADE

### Backend (API)
- ✅ `api/upload-producto-imagen.php` - Upload con transacciones
- ✅ `api/delete-producto-imagen.php` - Eliminación segura
- ✅ `api/productos.php` - Carga todas las imágenes (GET individual)
- ✅ `api/productos.php` - Carga imagen principal (GET listado)

### Frontend
- ✅ `editar-producto.php` - Galería dinámica con eliminación
- ✅ `producto.php` - Carrusel de imágenes (ya existía)

### Herramientas
- ✅ `sql/verificar_imagenes_sistema.sql` - Verificación
- ✅ `test-imagenes-api.php` - Test visual de API

#### Cómo funciona editar-producto.php:
1. **Seleccionar múltiples imágenes** (máximo 6)
2. **La primera imagen seleccionada** se marca automáticamente como principal (`es_principal = 1`)
3. **Click en cualquier imagen de la galería** para cambiarla a principal
4. **Botón "Subir Imágenes"** sube todas las imágenes seleccionadas al servidor

#### API actualizada:
- `api/upload-producto-imagen.php` ahora:
  - Acepta parámetro `es_principal` (0 o 1)
  - Si `es_principal = 1`, desmarca todas las demás imágenes del producto
  - Guarda la imagen en `producto_imagenes` con el flag correcto
  - Actualiza la columna `imagen` en `productos` (compatibilidad)

## 🔥 Badge "Trending" en FYP

### Estado Actual:
- ✅ Badge configurado en `index.php` línea 129
- ✅ CSS configurado en `css/fyp-section.css`
- ✅ Se muestra cuando `score_total > 20`

### Si no aparece el badge:

#### Opción 1: Verificar scores
```sql
SELECT p.nombre, ps.score_total 
FROM productos p 
LEFT JOIN producto_scores ps ON p.id = ps.producto_id 
ORDER BY ps.score_total DESC;
```

#### Opción 2: Ajustar umbral
En `index.php` línea 129, cambiar:
```php
<?php if ($producto['score_total'] > 20): ?>
```
Por un valor más bajo (ej: `> 5` o `> 0`)

#### Opción 3: Forzar badge para testing
Temporalmente cambiar a:
```php
<?php if (true): ?> <!-- MOSTRAR SIEMPRE -->
```

### Cómo aumentar el score de un producto:
Los productos obtienen score por:
- **Vistas**: +1 punto cada vez que alguien ve el producto
- **Guardados**: +5 puntos cuando alguien lo guarda
- **Chats iniciados**: +3 puntos por cada chat sobre el producto

Para aumentar manualmente:
```sql
UPDATE producto_scores 
SET total_vistas = 10, total_guardados = 3, total_chats = 2,
    score_total = (10 * 1) + (3 * 5) + (2 * 3)
WHERE producto_id = 1;
```

## 📝 Verificación Final

### Test de Imágenes Múltiples:
1. Ve a cualquier producto tuyo
2. Click en "Editar"
3. Selecciona 2-3 imágenes
4. Click en "Subir Imágenes Seleccionadas"
5. Verifica que todas se guardan
6. Click en diferentes imágenes de la galería para cambiar la principal

### Test de Badge Trending:
1. Asegúrate de tener productos con `score_total > 20`
2. Ve a la página principal (index.php) sin filtros
3. Verifica que aparece el carrusel FYP
4. Busca el badge 🔥 Trending en la esquina superior derecha de las cards

## 🐛 Troubleshooting

### "No se suben las imágenes"
- Verificar permisos en `uploads/productos/` (chmod 777)
- Revisar logs de PHP
- Verificar que la tabla `producto_imagenes` existe

### "Badge Trending no aparece"
- Verificar que `fyp-section.css` está cargado
- Verificar que hay productos con score > 20
- Revisar consola del navegador por errores CSS

### "Galería no muestra imágenes"
- Verificar que hay registros en `producto_imagenes` para ese producto
- Verificar rutas de imágenes en la BD
- Verificar que los archivos existen en `uploads/productos/`
