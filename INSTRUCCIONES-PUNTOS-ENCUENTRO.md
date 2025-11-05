# 📍 Sistema de Puntos de Encuentro con Google Maps

## 🎯 Descripción

Sistema completo para que los vendedores puedan establecer **puntos de encuentro seguros** donde realizar el intercambio de productos. Incluye:

- ✅ Mapa interactivo de **Google Maps**
- ✅ Múltiples ubicaciones por producto
- ✅ Marcador de punto "principal"
- ✅ Detalles de cada ubicación (horario, referencias, etc.)
- ✅ Navegación con Google Maps ("Cómo llegar")
- ✅ API completo para gestionar puntos

---

## 🚀 INSTALACIÓN

### Paso 1: Ejecutar Script SQL

1. Abre **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Selecciona la base de datos **handinhand**
3. Ve a la pestaña **SQL**
4. Copia y pega TODO el contenido de:
   ```
   sql/crear_puntos_encuentro.sql
   ```
5. Haz clic en **Continuar**
6. Verifica que veas el mensaje: `"Tabla de puntos de encuentro creada exitosamente!"`

### Paso 2: Verificar la Instalación

1. En phpMyAdmin, ve a la tabla **`puntos_encuentro`**
2. Deberías ver **6 puntos de encuentro** de ejemplo:
   - 2 para el Smartphone Samsung (id: 1)
   - 2 para las Zapatillas Nike (id: 2)
   - 2 para la Guitarra (id: 3)

---

## 🗺️ Características del Sistema

### Estructura de Datos

Cada punto de encuentro contiene:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| `nombre` | Nombre del lugar | "Starbucks Ágora Mall" |
| `descripcion` | Detalles adicionales | "Punto céntrico y seguro" |
| `direccion` | Dirección completa | "Ágora Mall, Av. JFK" |
| `latitud` | Coordenada GPS | 18.47155600 |
| `longitud` | Coordenada GPS | -69.94044400 |
| `referencia` | Punto de referencia | "Primer nivel, entrada principal" |
| `horario_sugerido` | Horario recomendado | "Lun-Dom 10am-8pm" |
| `es_principal` | ¿Es el punto principal? | 1 (Sí) o 0 (No) |

### Funcionalidades del Mapa

1. **Marcadores inteligentes**
   - Punto principal: Círculo verde con estrella ★
   - Puntos secundarios: Marcador numerado (1, 2, 3...)

2. **Info Windows**
   - Click en marcador → Muestra detalles completos
   - Botón "Cómo llegar" → Abre Google Maps con navegación

3. **Auto-ajuste**
   - Si hay 1 punto: Zoom fijo en ese punto
   - Si hay múltiples: Ajusta zoom para mostrar todos

4. **Interactividad**
   - Click en tarjeta de punto → Centra mapa y hace bounce al marcador
   - Botón "Ver en mapa" → Scroll suave al mapa

---

## 🔧 API Endpoints

### GET `/api/puntos-encuentro.php`

Obtener puntos de encuentro de un producto.

**Parámetros:**
```
?producto_id=1
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "puntos_encuentro": [
      {
        "id": 1,
        "producto_id": 1,
        "nombre": "Starbucks Ágora Mall",
        "descripcion": "Punto céntrico y seguro",
        "direccion": "Ágora Mall, Av. JFK",
        "latitud": "18.47155600",
        "longitud": "-69.94044400",
        "referencia": "Primer nivel",
        "horario_sugerido": "Lun-Dom 10am-8pm",
        "es_principal": 1
      }
    ],
    "total": 2
  }
}
```

### POST `/api/puntos-encuentro.php`

Crear nuevo punto de encuentro (requiere autenticación).

**Body (JSON):**
```json
{
  "producto_id": 1,
  "nombre": "Starbucks Centro",
  "descripcion": "Lugar seguro y céntrico",
  "direccion": "Av. Principal, Santo Domingo",
  "latitud": 18.4860,
  "longitud": -69.9312,
  "referencia": "Frente al banco",
  "horario_sugerido": "Lun-Vie 9am-6pm",
  "es_principal": 0
}
```

### PUT `/api/puntos-encuentro.php`

Actualizar punto de encuentro (requiere ser dueño del producto).

**Body (JSON):**
```json
{
  "punto_id": 1,
  "nombre": "Nuevo nombre",
  "es_principal": 1
}
```

### DELETE `/api/puntos-encuentro.php`

Eliminar punto de encuentro.

**Body (JSON):**
```json
{
  "punto_id": 1
}
```

---

## 🎨 Vista en el Producto

Al abrir un producto (`producto.php?id=1`), verás:

1. **Sección "Puntos de encuentro sugeridos"**
   - Lista de tarjetas con cada punto
   - Tarjetas con fondo verde claro para puntos principales
   - Iconos distintivos y badges

2. **Mapa interactivo**
   - Se muestra debajo de la lista
   - Marcadores clickeables
   - Zoom y navegación completos

3. **Información detallada**
   - Nombre del lugar
   - Dirección completa
   - Descripción
   - Referencias
   - Horarios sugeridos

---

## 📍 Ubicaciones de Ejemplo

El sistema incluye puntos de encuentro en **Santo Domingo, RD**:

### Producto 1 (Smartphone):
- ⭐ **Starbucks Ágora Mall** (Principal)
- McDonald's Blue Mall

### Producto 2 (Zapatillas):
- ⭐ **Parque Mirador Sur** (Principal)
- Plaza Central

### Producto 3 (Guitarra):
- ⭐ **Sambil Santo Domingo** (Principal)
- Malecón de Santo Domingo

---

## 🔐 Seguridad

- ✅ Solo el dueño del producto puede agregar/editar/eliminar puntos
- ✅ Validación de coordenadas GPS (lat: -90 a 90, lng: -180 a 180)
- ✅ Verificación de autenticación (requireAuth)
- ✅ Prevención de SQL injection (PDO prepared statements)
- ✅ Validación de campos requeridos

---

## 🎯 Cómo Usar

### Para Vendedores (futuro):
1. Crear/editar producto
2. Agregar puntos de encuentro
3. Marcar uno como "principal"
4. Especificar horarios y referencias

### Para Compradores:
1. Ver producto
2. Revisar puntos de encuentro sugeridos
3. Ver ubicaciones en mapa
4. Hacer click en "Cómo llegar" para navegación GPS

---

## 🔑 Google Maps API Key

El proyecto usa una API Key de Google Maps:
```
AIzaSyBWN8PfBhFGdEPMPd6qgfMrGvxhHgdRNHs
```

**IMPORTANTE**: Esta es una key de prueba. Para producción:

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto
3. Habilita **Maps JavaScript API**
4. Crea credenciales (API Key)
5. Restringe la key por dominio (handinhand.sytes.net)
6. Reemplaza la key en `producto.php` línea ~300

---

## 🐛 Solución de Problemas

### El mapa no se muestra
- Verifica que la API key sea válida
- Abre la consola del navegador (F12) para ver errores
- Asegúrate de tener conexión a internet

### Error "Cannot read property 'maps' of undefined"
- El script de Google Maps no ha cargado completamente
- Verifica la key de API
- Revisa la consola para errores de CORS o límite de cuota

### Los puntos no aparecen
- Ejecuta el script SQL `crear_puntos_encuentro.sql`
- Verifica en phpMyAdmin que existan registros en `puntos_encuentro`
- Revisa la consola del navegador para errores de API

### Coordenadas incorrectas
Para obtener coordenadas GPS correctas:
1. Ve a [Google Maps](https://maps.google.com)
2. Haz click derecho en el lugar
3. Click en las coordenadas que aparecen
4. Se copian al portapapeles

---

## 📱 Responsive

El sistema es completamente responsive:
- **Desktop**: Mapa de 400px de alto
- **Tablet**: Mapa de 350px de alto
- **Mobile**: Mapa de 300px de alto

---

## ✅ Checklist de Verificación

- [ ] Script SQL ejecutado en phpMyAdmin
- [ ] Tabla `puntos_encuentro` creada
- [ ] 6 puntos de ejemplo insertados
- [ ] API `api/puntos-encuentro.php` funciona
- [ ] Mapa se muestra en `producto.php`
- [ ] Marcadores aparecen correctamente
- [ ] Info windows funcionan
- [ ] Botón "Cómo llegar" abre Google Maps
- [ ] Click en tarjeta centra el mapa
- [ ] Animación bounce funciona

---

**Fecha**: 5 de noviembre de 2025  
**Sistema**: HandinHand - Puntos de Encuentro  
**Tecnologías**: Google Maps JavaScript API, PHP, MySQL
