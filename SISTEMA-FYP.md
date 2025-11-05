# 📊 Sistema FYP (For You Page) - Documentación

## 🎯 Descripción General

El sistema **FYP (For You Page)** implementa un algoritmo de recomendaciones personalizado combinando dos enfoques:

1. **Scoring Simple**: Calcula puntuación basada en interacciones (vistas, guardados, chats, valoraciones)
2. **Filtrado Colaborativo**: Recomienda productos vistos por usuarios con gustos similares

---

## 📁 Estructura de Archivos

### Base de Datos
- `sql/crear_sistema_fyp.sql` - Esquema completo (5 tablas + 2 procedimientos + 1 vista)
- `sql/seed_fyp_data.sql` - Datos de prueba

### Backend
- `api/fyp.php` - API RESTful para interacciones
- `includes/functions.php::getProductosRecomendados()` - Obtener recomendaciones

### Frontend
- `css/fyp-section.css` - Estilos visuales
- `js/fyp-tracking.js` - Sistema de tracking automático

---

## 🗄️ Tablas de Base de Datos

### 1. `producto_vistas`
Rastrea cada visualización de producto.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT AUTO_INCREMENT | ID único |
| `producto_id` | INT | Producto visto |
| `usuario_id` | INT NULL | Usuario (si está logueado) |
| `session_id` | VARCHAR(100) NULL | Sesión anónima |
| `duracion_segundos` | INT | Tiempo de visualización |
| `fecha_vista` | DATETIME | Timestamp |

### 2. `producto_guardados`
Productos guardados como favoritos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT AUTO_INCREMENT | ID único |
| `producto_id` | INT | Producto guardado |
| `usuario_id` | INT | Usuario propietario |
| `fecha_guardado` | DATETIME | Timestamp |

**Constraint:** UNIQUE(producto_id, usuario_id)

### 3. `producto_chats`
Chats iniciados desde productos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT AUTO_INCREMENT | ID único |
| `producto_id` | INT | Producto origen |
| `usuario_id` | INT | Usuario que inicia |
| `vendedor_id` | INT | Vendedor contactado |
| `fecha_chat` | DATETIME | Timestamp |

### 4. `producto_scores`
Puntuaciones agregadas por producto.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `producto_id` | INT PRIMARY KEY | Producto |
| `total_vistas` | INT | Contador de vistas |
| `total_guardados` | INT | Contador de guardados |
| `total_chats` | INT | Contador de chats |
| `score_total` | INT | Puntuación calculada |
| `ultima_actualizacion` | DATETIME | Última actualización |

**Fórmula Score:**
```
score_total = (vistas × 1) + (guardados × 3) + (chats × 5) + (valoraciones × 2)
```

### 5. `producto_similitudes`
Relaciones entre productos (filtrado colaborativo).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `producto_a_id` | INT | Producto A |
| `producto_b_id` | INT | Producto B |
| `similitud_score` | INT | Veces visto juntos |
| `ultima_actualizacion` | DATETIME | Timestamp |

**Constraint:** PRIMARY KEY(producto_a_id, producto_b_id)

---

## 🔧 Procedimientos Almacenados

### `actualizar_scores_productos()`
Recalcula todos los scores de productos.

**Uso:**
```sql
CALL actualizar_scores_productos();
```

**Función:**
- Cuenta vistas, guardados, chats por producto
- Calcula score_total con fórmula ponderada
- Actualiza tabla `producto_scores`

### `calcular_similitudes_productos()`
Encuentra productos vistos juntos por mismos usuarios.

**Uso:**
```sql
CALL calcular_similitudes_productos();
```

**Función:**
- Cruza vistas de usuarios
- Cuenta co-ocurrencias
- Actualiza `producto_similitudes`

---

## 📡 API Endpoints (`api/fyp.php`)

### GET - Obtener Recomendaciones
```
GET /api/fyp.php?accion=recomendados&limite=8
```
**Respuesta:**
```json
{
  "success": true,
  "productos": [
    {
      "id": 123,
      "nombre": "Laptop HP",
      "score_total": 45,
      "total_vistas": 20,
      "total_guardados": 5,
      ...
    }
  ]
}
```

**Lógica:**
- Usuario logueado: considera categorías vistas (+50 score bonus)
- Usuario anónimo: muestra trending global

### GET - Productos Similares
```
GET /api/fyp.php?accion=similares&producto_id=123&limite=5
```
**Respuesta:**
```json
{
  "success": true,
  "productos_similares": [...]
}
```

### GET - Productos Guardados
```
GET /api/fyp.php?accion=guardados
```
**Respuesta:**
```json
{
  "success": true,
  "productos": [...]
}
```

### POST - Registrar Vista
```
POST /api/fyp.php
{
  "accion": "vista",
  "producto_id": 123,
  "duracion_segundos": 15
}
```

### POST - Guardar Producto
```
POST /api/fyp.php
{
  "accion": "guardar",
  "producto_id": 123
}
```

### POST - Registrar Chat
```
POST /api/fyp.php
{
  "accion": "chat",
  "producto_id": 123,
  "vendedor_id": 456
}
```

### DELETE - Quitar Guardado
```
DELETE /api/fyp.php?producto_id=123
```

---

## 🎨 Interfaz de Usuario

### Sección "Para Ti" (`index.php`)

```html
<div class="fyp-section">
  <div class="fyp-header">
    <h2 class="fyp-title">⭐ Para Ti</h2>
    <p class="fyp-subtitle">Recomendaciones personalizadas</p>
  </div>
  
  <div class="fyp-container">
    <!-- Cards con productos recomendados -->
    <div class="fyp-card" data-producto-id="123">
      <!-- Badge trending si score > 20 -->
      <span class="badge-trending">🔥 Trending</span>
      
      <!-- Imagen -->
      <div class="card-image-container">
        <img src="..." alt="...">
      </div>
      
      <!-- Contenido -->
      <div class="card-content">
        <h3 class="card-title">Laptop HP</h3>
        <p class="card-description">...</p>
        
        <!-- Estadísticas -->
        <div class="card-stats">
          <span class="card-stat views">
            <i class="fas fa-eye"></i> 20
          </span>
          <span class="card-stat hearts">
            <i class="fas fa-heart"></i> 5
          </span>
        </div>
      </div>
    </div>
  </div>
</div>
```

### Tracking Automático (`js/fyp-tracking.js`)

El sistema rastrea automáticamente:

✅ **Vistas**: Al hacer click en card (2 seg) o al entrar a producto (3+ seg)  
✅ **Guardados**: Botón corazón (`.btn-guardar`)  
✅ **Chats**: Al iniciar conversación (`.btn-chat`)

**Ejemplo manual:**
```javascript
// Registrar vista
FYPTracking.registrarVista(123, 10);

// Guardar producto
FYPTracking.guardarProducto(123);

// Registrar chat
FYPTracking.registrarChat(123, 456);
```

---

## 🚀 Mantenimiento

### Actualizar Scores (Recomendado: cron job diario)
```sql
CALL actualizar_scores_productos();
CALL calcular_similitudes_productos();
```

### Ver Estadísticas
```sql
-- Top productos por score
SELECT p.nombre, ps.score_total, ps.total_vistas, ps.total_guardados
FROM producto_scores ps
JOIN productos p ON ps.producto_id = p.id
ORDER BY ps.score_total DESC
LIMIT 10;

-- Productos más guardados
SELECT p.nombre, COUNT(*) as guardados
FROM producto_guardados pg
JOIN productos p ON pg.producto_id = p.id
GROUP BY pg.producto_id
ORDER BY guardados DESC
LIMIT 10;
```

### Limpiar Datos Antiguos
```sql
-- Eliminar vistas de hace más de 90 días
DELETE FROM producto_vistas 
WHERE fecha_vista < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Recalcular scores
CALL actualizar_scores_productos();
```

---

## 📈 Fórmula de Puntuación

### Pesos Actuales
| Interacción | Peso | Justificación |
|-------------|------|---------------|
| Vista | ×1 | Interacción más básica |
| Guardado | ×3 | Interés moderado |
| Chat | ×5 | Alta intención de compra |
| Valoración | ×2 | Engagement post-compra |

### Ajustar Pesos
Editar en `sql/crear_sistema_fyp.sql` línea ~115:
```sql
SET score_total = (total_vistas * 1) + (total_guardados * 3) + 
                  (total_chats * 5) + (total_valoraciones * 2);
```

---

## 🎯 Personalización

### Factores de Personalización

1. **Categorías Preferidas**: +50 score a productos de categorías vistas
2. **Exclusión de Vistas Recientes**: No mostrar productos vistos en 24h
3. **Filtrado Colaborativo**: "Usuarios que vieron X también vieron Y"

### Modificar en `includes/functions.php::getProductosRecomendados()`

```php
// Cambiar días de exclusión (línea ~110)
AND p.id NOT IN (
    SELECT producto_id FROM producto_vistas 
    WHERE usuario_id = ? 
    AND fecha_vista >= DATE_SUB(NOW(), INTERVAL 7 DAY)  -- Cambiar aquí
)
```

---

## 🔒 Seguridad

- ✅ Prepared statements (PDO)
- ✅ Validación de IDs numéricos
- ✅ Control de sesiones
- ✅ Foreign keys con CASCADE
- ✅ UNIQUE constraints

---

## 📊 Métricas Clave

### KPIs del Sistema
- **CTR (Click-Through Rate)**: vistas / impresiones
- **Tasa de Guardado**: guardados / vistas
- **Tasa de Conversión**: chats / guardados
- **Score Promedio**: AVG(score_total)

### Query de Métricas
```sql
SELECT 
    COUNT(DISTINCT producto_id) as productos_activos,
    SUM(total_vistas) as vistas_totales,
    SUM(total_guardados) as guardados_totales,
    SUM(total_chats) as chats_totales,
    AVG(score_total) as score_promedio,
    MAX(score_total) as score_maximo
FROM producto_scores;
```

---

## 🐛 Troubleshooting

### Problema: No aparecen recomendaciones
**Solución:**
```sql
-- Verificar que existen scores
SELECT COUNT(*) FROM producto_scores WHERE score_total > 0;

-- Si es 0, ejecutar:
CALL actualizar_scores_productos();
```

### Problema: Siempre muestra los mismos productos
**Solución:** Añadir variación aleatoria en `getProductosRecomendados()`:
```php
ORDER BY 
    CASE WHEN p.categoria IN ($placeholders) THEN 1 ELSE 2 END,
    ps.score_total DESC,
    RAND(),  -- Añadir aleatoriedad
    p.created_at DESC
```

### Problema: Tracking no funciona
**Solución:** Verificar en consola del navegador:
```javascript
console.log(window.FYPTracking); // Debe estar definido
FYPTracking.registrarVista(1, 5); // Probar manualmente
```

---

## 📝 Próximas Mejoras

- [ ] Decay temporal (reducir peso de interacciones antiguas)
- [ ] Machine Learning: TensorFlow.js para predicciones
- [ ] A/B Testing de pesos de scoring
- [ ] Dashboard de analytics para admin
- [ ] Notificaciones de productos recomendados
- [ ] Exportar historial de recomendaciones

---

## 📚 Referencias

- Algoritmo inspirado en: TikTok FYP, YouTube Recommendations
- Filtrado colaborativo: Item-based collaborative filtering
- Stack: PHP 7.4+, MySQL 8.3, Vanilla JS

---

**Última actualización:** <?php echo date('Y-m-d'); ?>  
**Versión:** 1.0.0
