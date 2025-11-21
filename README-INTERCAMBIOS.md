# Sistema de Propuestas de Intercambio con Imágenes

## 🎯 Características Implementadas

### 1. **Mensajes de Intercambio con Imágenes**
- Cuando se propone un intercambio, el mensaje muestra:
  - ✅ Imagen del producto solicitado
  - ✅ Imagen del producto ofrecido
  - ✅ Diseño tipo WhatsApp con tarjetas visuales
  - ✅ Mensaje adicional del usuario (opcional)

### 2. **Banner de Propuesta Pendiente**
- Aparece en la parte superior del chat cuando hay una propuesta pendiente
- Muestra:
  - 📦 Miniaturas de ambos productos
  - ⏳ Estado de la propuesta
  - 🎮 Botones de acción (Aceptar/Rechazar/Contraoferta/Cancelar)

### 3. **Gestión de Propuestas**
- **Para el receptor:**
  - ✅ Aceptar intercambio
  - ❌ Rechazar intercambio
  - 🔄 Hacer contraoferta (seleccionar otro producto)
  
- **Para el solicitante:**
  - 🚫 Cancelar propuesta
  - ⏳ Ver estado de la propuesta

## 📋 Instalación

### Paso 1: Crear la tabla en la base de datos

Ejecuta uno de estos métodos:

**Opción A: Desde el navegador**
```
http://localhost/crear-tabla-propuestas.php
```

**Opción B: Desde phpMyAdmin**
Ejecuta el contenido del archivo `sql/propuestas_intercambio.sql`

### Paso 2: Verificar archivos

Asegúrate de que existen estos archivos nuevos:
- ✅ `sql/propuestas_intercambio.sql`
- ✅ `api/gestionar-propuesta.php`
- ✅ `api/obtener-propuestas-pendientes.php`
- ✅ `js/intercambios.js`
- ✅ `crear-tabla-propuestas.php`

### Paso 3: Limpiar caché del navegador

Presiona `Ctrl + F5` o `Cmd + Shift + R` para forzar la recarga de los archivos JavaScript y CSS.

## 🎮 Uso

### Proponer un Intercambio

1. Ve a un producto que te interese
2. Haz clic en "Proponer Intercambio"
3. Selecciona uno de tus productos
4. Escribe un mensaje opcional
5. Envía la propuesta

### Ver Propuesta en el Chat

1. El mensaje aparecerá con:
   - 🖼️ Imagen del producto que ofreces
   - 🔄 Icono de intercambio
   - 🖼️ Imagen del producto que quieres
   - 💬 Tu mensaje adicional

2. En la parte superior del chat aparecerá un banner morado con la propuesta pendiente

### Gestionar Propuesta (Receptor)

Desde el banner superior:
- **Aceptar**: Confirma el intercambio
- **Rechazar**: Declina la oferta (libera tu producto)
- **Contraoferta**: Ofrece otro producto tuyo en su lugar

### Cancelar Propuesta (Solicitante)

Si cambiaste de opinión:
- Haz clic en "Cancelar" en el banner
- Tu producto volverá a estar disponible

## 🎨 Diseño

### Colores del Sistema
- **Banner**: Gradiente morado (#667eea → #764ba2)
- **Tarjetas de productos**: Fondo blanco con sombras
- **Botones**:
  - Aceptar: Verde (#4CAF50)
  - Rechazar: Rojo (#f44336)
  - Contraoferta: Naranja (#FF9800)

### Responsive
- ✅ Desktop: Diseño de 3 columnas (ofrecido | ↔️ | solicitado)
- ✅ Mobile: Adaptado para pantallas pequeñas

## 🔧 Estructura Técnica

### Base de Datos

**Tabla: `propuestas_intercambio`**
```sql
- id (PK)
- producto_solicitado_id (FK)
- producto_ofrecido_id (FK)
- solicitante_id (FK usuarios)
- receptor_id (FK usuarios)
- mensaje_id (FK mensajes)
- mensaje (TEXT)
- estado (ENUM: pendiente|aceptada|rechazada|contraoferta|cancelada)
- created_at, updated_at
```

### APIs

1. **`api/proponer-intercambio.php`** (modificado)
   - Guarda propuesta en tabla dedicada
   - Crea mensaje JSON con datos de productos
   - Incluye imágenes en el mensaje

2. **`api/gestionar-propuesta.php`** (nuevo)
   - Aceptar/rechazar/contraoferta/cancelar
   - Actualiza estados de productos
   - Envía notificaciones

3. **`api/obtener-propuestas-pendientes.php`** (nuevo)
   - Obtiene propuestas pendientes entre dos usuarios
   - Retorna datos completos de productos con imágenes

### JavaScript

**`js/intercambios.js`**
- `renderPropuestaIntercambio()`: Renderiza mensaje con imágenes
- `loadPropuestasPendientes()`: Carga banner de propuestas
- `gestionarPropuesta()`: Maneja acciones del usuario
- `mostrarBannerPropuesta()`: Muestra/oculta banner

**`js/chat.js`** (modificado)
- Detecta tipo de mensaje `propuesta_intercambio`
- Llama a `renderPropuestaIntercambio()` para mensajes especiales
- Carga propuestas pendientes al seleccionar usuario

## 🐛 Solución de Problemas

### El banner no aparece
- Verifica que la tabla `propuestas_intercambio` existe
- Revisa la consola del navegador (F12)
- Asegúrate de que `js/intercambios.js` se carga correctamente

### Las imágenes no se muestran
- Verifica que los productos tienen imagen asignada
- Revisa las rutas de las imágenes en la tabla `productos`
- La imagen placeholder es `img/placeholder-producto.jpg`

### Error "propuesta no encontrada"
- Verifica que la propuesta existe y está en estado 'pendiente'
- Revisa que el usuario tenga permisos (receptor o solicitante)

## 📝 Próximas Mejoras

- [ ] Modal para seleccionar producto en contraoferta
- [ ] Historial de propuestas
- [ ] Notificaciones push
- [ ] Contador de propuestas pendientes
- [ ] Filtros de propuestas por estado

## 👨‍💻 Desarrollo

**Archivos Modificados:**
- `api/proponer-intercambio.php`
- `api/get-messages.php`
- `js/chat.js`
- `mensajeria.php`

**Archivos Nuevos:**
- `sql/propuestas_intercambio.sql`
- `api/gestionar-propuesta.php`
- `api/obtener-propuestas-pendientes.php`
- `js/intercambios.js`
- `crear-tabla-propuestas.php`

---

**Versión:** 1.0.0  
**Fecha:** 7 de noviembre de 2025
