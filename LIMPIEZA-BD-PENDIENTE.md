# 🧹 Limpieza y Mejoras Pendientes de la Base de Datos

## ❌ PROBLEMAS IDENTIFICADOS

### 1. 🔄 **COLUMNAS DUPLICADAS en tabla `mensajes`**

La tabla `mensajes` tiene **COLUMNAS REDUNDANTES** que ocupan espacio innecesario:

#### Columnas NUEVAS (en uso):
- ✅ `sender_id` - Usuario que envía
- ✅ `receiver_id` - Usuario que recibe  
- ✅ `message` - Contenido del mensaje
- ✅ `is_read` - Si fue leído

#### Columnas VIEJAS (OBSOLETAS - duplicadas):
- ❌ `remitente_id` - DUPLICADO de `sender_id`
- ❌ `destinatario_id` - DUPLICADO de `receiver_id`
- ❌ `mensaje` - DUPLICADO de `message`
- ❌ `leido` - DUPLICADO de `is_read`

**Impacto:**
- Desperdicia espacio en disco (cada mensaje usa el DOBLE de espacio)
- Confusión al desarrollar (¿cuál columna usar?)
- Más lento al hacer queries (más datos que leer)

---

### 2. 📍 **FALTA funcionalidad de UBICACIONES**

La tabla `productos` **NO tiene** columna de ubicación. Los usuarios no pueden:
- Especificar dónde se encuentra el producto
- Filtrar productos por ubicación/departamento
- Ver productos cercanos

**Columnas faltantes:**
- `departamento` - Departamento de Uruguay (Montevideo, Canelones, etc.)
- `ciudad` - Ciudad específica (opcional)

---

### 3. 🗑️ **TABLAS SIN USO APARENTE**

Estas tablas existen pero no encontré código que las use:

#### `producto_vistas`
- ¿Propósito? Rastrear vistas de productos
- ¿En uso? No encontrado en código PHP
- ¿Decisión? Verificar si se usa en algún lado

#### `producto_guardados`  
- ¿Propósito? Productos favoritos/guardados
- ¿En uso? No encontrado en código PHP
- ¿Decisión? Verificar si hay funcionalidad de favoritos

#### `producto_chats`
- ¿Propósito? Desconocido (chats sobre productos?)
- ¿En uso? No encontrado en código PHP
- ¿Decisión? Probablemente eliminar si hay `chats_temporales`

#### `producto_scores`
- ¿Propósito? Sistema de puntuación de productos
- ¿En uso? Hay stored procedure `actualizar_scores_productos`
- ¿Decisión? Verificar si se ejecuta

#### `producto_similitudes`
- ¿Propósito? Recomendaciones de productos similares
- ¿En uso? Hay stored procedure `calcular_similitudes_productos`
- ¿Decisión? Verificar si se ejecuta

---

### 4. 🔧 **STORED PROCEDURES sin uso**

```sql
actualizar_scores_productos()
calcular_similitudes_productos()
```

**Problema:** Existen pero no veo dónde se llamen desde PHP.

---

## ✅ SOLUCIONES PROPUESTAS

### 🎯 **FASE 1: Agregar Ubicaciones (INMEDIATO)**

**Archivo:** `agregar_ubicaciones.sql`

```sql
-- 1. Agregar columnas de ubicación a productos
ALTER TABLE productos 
ADD COLUMN departamento ENUM(
    'Artigas', 'Canelones', 'Cerro Largo', 'Colonia', 
    'Durazno', 'Flores', 'Florida', 'Lavalleja', 
    'Maldonado', 'Montevideo', 'Paysandú', 'Río Negro', 
    'Rivera', 'Rocha', 'Salto', 'San José', 
    'Soriano', 'Tacuarembó', 'Treinta y Tres'
) DEFAULT 'Montevideo' AFTER categoria,
ADD COLUMN ciudad VARCHAR(100) DEFAULT NULL AFTER departamento,
ADD INDEX idx_departamento (departamento);

-- 2. Crear tabla de ubicaciones predefinidas (ciudades por departamento)
CREATE TABLE IF NOT EXISTS ubicaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    departamento VARCHAR(50) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_ubicacion (departamento, ciudad),
    INDEX idx_departamento (departamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Insertar ciudades principales por departamento
INSERT INTO ubicaciones (departamento, ciudad) VALUES
-- Montevideo
('Montevideo', 'Centro'),
('Montevideo', 'Ciudad Vieja'),
('Montevideo', 'Pocitos'),
('Montevideo', 'Punta Carretas'),
('Montevideo', 'Carrasco'),
('Montevideo', 'Malvín'),
('Montevideo', 'Buceo'),
('Montevideo', 'Parque Rodó'),
('Montevideo', 'Cordón'),
('Montevideo', 'Tres Cruces'),

-- Canelones
('Canelones', 'Ciudad de la Costa'),
('Canelones', 'Las Piedras'),
('Canelones', 'Pando'),
('Canelones', 'La Paz'),
('Canelones', 'Progreso'),
('Canelones', 'Sauce'),
('Canelones', 'Santa Lucía'),

-- Maldonado
('Maldonado', 'Punta del Este'),
('Maldonado', 'Maldonado'),
('Maldonado', 'San Carlos'),
('Maldonado', 'Piriápolis'),

-- Salto
('Salto', 'Salto'),

-- Paysandú
('Paysandú', 'Paysandú'),

-- Rivera
('Rivera', 'Rivera'),

-- Tacuarembó
('Tacuarembó', 'Tacuarembó'),

-- Rocha
('Rocha', 'Rocha'),
('Rocha', 'La Paloma'),
('Rocha', 'Chuy'),

-- Colonia
('Colonia', 'Colonia del Sacramento'),
('Colonia', 'Carmelo'),

-- Soriano
('Soriano', 'Mercedes'),

-- Durazno
('Durazno', 'Durazno'),

-- Florida
('Florida', 'Florida'),

-- San José
('San José', 'San José de Mayo'),
('San José', 'Ciudad del Plata'),

-- Flores
('Flores', 'Trinidad'),

-- Lavalleja
('Lavalleja', 'Minas'),

-- Cerro Largo
('Cerro Largo', 'Melo'),

-- Treinta y Tres
('Treinta y Tres', 'Treinta y Tres'),

-- Río Negro
('Río Negro', 'Fray Bentos'),

-- Artigas
('Artigas', 'Artigas');
```

**Cambios necesarios en PHP:**
1. ✅ Modificar formulario de crear/editar producto
2. ✅ Agregar filtro por ubicación en búsqueda
3. ✅ Mostrar ubicación en tarjeta de producto

---

### 🎯 **FASE 2: Limpiar Columnas Duplicadas (DESPUÉS DE VERIFICAR)**

**ADVERTENCIA:** ⚠️ **NO ejecutar hasta verificar que TODO el código usa las columnas NUEVAS**

**Archivo:** `limpiar_columnas_duplicadas.sql`

```sql
-- ⚠️ IMPORTANTE: Antes de ejecutar este script:
-- 1. Verificar que TODO el código PHP use sender_id/receiver_id/message
-- 2. Hacer BACKUP de la base de datos
-- 3. Probar en entorno de desarrollo primero

-- Verificación: Buscar código que use columnas viejas
-- grep -r "remitente_id" api/
-- grep -r "destinatario_id" api/
-- grep -r "INSERT.*mensaje[^s]" api/  (buscar INSERT que use 'mensaje' sin 's')

-- Una vez verificado, eliminar columnas obsoletas:
ALTER TABLE mensajes
DROP COLUMN remitente_id,
DROP COLUMN destinatario_id,
DROP COLUMN mensaje,
DROP COLUMN leido;

-- Nota: Esto liberará espacio significativo en la tabla mensajes
```

---

### 🎯 **FASE 3: Revisar Tablas de Análitica (OPCIONAL)**

**Archivo:** `revisar_analitica.sql`

```sql
-- Verificar si estas tablas se usan:

-- 1. Verificar si hay datos en producto_vistas
SELECT COUNT(*) as total_vistas FROM producto_vistas;

-- 2. Verificar si hay datos en producto_guardados
SELECT COUNT(*) as total_guardados FROM producto_guardados;

-- 3. Verificar si hay datos en producto_chats
SELECT COUNT(*) as total_chats FROM producto_chats;

-- 4. Verificar si hay datos en producto_scores
SELECT COUNT(*) as total_scores FROM producto_scores;

-- 5. Verificar si hay datos en producto_similitudes
SELECT COUNT(*) as total_similitudes FROM producto_similitudes;

-- Si todas regresan 0, considerar eliminar:
-- DROP TABLE IF EXISTS producto_vistas;
-- DROP TABLE IF EXISTS producto_guardados;
-- DROP TABLE IF EXISTS producto_chats;
-- DROP TABLE IF EXISTS producto_scores;
-- DROP TABLE IF EXISTS producto_similitudes;
```

---

## 📋 PLAN DE ACCIÓN RECOMENDADO

### ✅ **HACER AHORA (Prioridad Alta)**

1. **Agregar sistema de ubicaciones:**
   - Ejecutar `agregar_ubicaciones.sql`
   - Modificar formulario de crear producto
   - Modificar formulario de editar producto
   - Agregar filtro de búsqueda por ubicación

### ⏳ **HACER DESPUÉS (Prioridad Media)**

2. **Auditar uso de columnas:**
   - Buscar en TODO el código PHP si se usan `remitente_id`, `destinatario_id`, `mensaje`, `leido`
   - Reemplazarlas por las nuevas si existen
   
3. **Limpiar columnas duplicadas:**
   - Una vez verificado que NO se usan, ejecutar script de limpieza
   - HACER BACKUP antes

### 🔍 **INVESTIGAR (Prioridad Baja)**

4. **Revisar tablas de analítica:**
   - Ver si `producto_vistas`, `producto_guardados`, etc. tienen datos
   - Decidir si eliminarlas o implementar la funcionalidad completa

---

## 💾 ESTIMACIÓN DE ESPACIO LIBERADO

Asumiendo **878 mensajes** en la tabla (según tu export):

**Columnas duplicadas:**
- `remitente_id`: 4 bytes × 878 = 3.5 KB
- `destinatario_id`: 4 bytes × 878 = 3.5 KB  
- `mensaje`: ~500 bytes promedio × 878 = **439 KB**
- `leido`: 1 byte × 878 = 0.9 KB

**Total a liberar:** ~450 KB (y creciendo con cada nuevo mensaje)

---

## 🚀 ¿Quieres que implemente las ubicaciones AHORA?

Te puedo crear:
1. ✅ Script SQL para agregar ubicaciones
2. ✅ Modificar formulario de crear/editar producto
3. ✅ Agregar select de departamento y ciudad
4. ✅ Filtro de búsqueda por ubicación
5. ✅ Mostrar ubicación en tarjetas de producto

**¿Procedemos con las ubicaciones?** 📍
