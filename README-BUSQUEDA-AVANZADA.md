# Sistema de Búsqueda Avanzada - HandinHand

## 📋 Descripción General

Sistema de búsqueda mejorado con filtros dinámicos y capacidad de buscar tanto productos como usuarios. Diseñado con una interfaz moderna inspirada en plataformas de comercio electrónico.

---

## ✨ Características Principales

### 1. **Toggle Productos/Usuarios**
- Botones visuales para cambiar entre búsqueda de productos y usuarios
- Íconos intuitivos (📦 Productos / 👥 Usuarios)
- Actualización dinámica del placeholder del buscador
- Estados visuales activos con colores destacados

### 2. **Filtros para Productos**
- **Categoría**: Electrónicos, Ropa, Calzado, Libros, Deportes, Música, Hogar, Juguetes, Otros
- **Estado**: Disponible, Reservado, Intercambiado
- Panel de filtros desplegable con animación suave
- Botón "Limpiar filtros" para resetear búsqueda

### 3. **Búsqueda de Usuarios**
- Búsqueda por nombre completo o username
- Tarjetas de usuario con:
  - Avatar personalizado
  - Nombre completo y @username
  - Ubicación (si está disponible)
  - Total de productos publicados
  - Total de intercambios realizados
  - Botón "Ver perfil"

### 4. **Interfaz Mejorada**
- Diseño responsive (mobile-first)
- Animaciones CSS suaves
- Colores consistentes con la identidad de HandinHand (#6a994e)
- Hover effects y transiciones
- Iconografía de Font Awesome

---

## 🗂️ Archivos Modificados/Creados

### Archivos Modificados:
1. **index.php**
   - Agregado toggle productos/usuarios
   - Panel de filtros dinámico
   - Renderizado de tarjetas de usuarios
   - JavaScript para manejo de filtros

2. **includes/functions.php**
   - `getProductosFiltrados()`: Búsqueda de productos con filtros
   - `buscarUsuarios()`: Búsqueda de usuarios con detección de columna ubicacion

3. **css/style.css**
   - Estilos para toggle buttons
   - Estilos para panel de filtros
   - Estilos para tarjetas de usuarios
   - Responsive design

### Archivos Creados:
1. **sql/add_ubicacion_usuarios.sql**
   - Agrega columna `ubicacion` a tabla usuarios
   - Actualiza usuarios existentes con ubicación "Montevideo"

---

## 🔧 Instalación

### 1. Ejecutar Script SQL
```bash
# Desde phpMyAdmin o línea de comandos MySQL:
mysql -u tu_usuario -p handinhand < sql/add_ubicacion_usuarios.sql
```

### 2. Verificar Archivos
- Asegurarse de que todos los archivos modificados estén en su lugar
- Verificar que `css/style.css` tenga los nuevos estilos
- Confirmar que `includes/functions.php` tenga las nuevas funciones

### 3. Limpiar Caché del Navegador
- Usar `Ctrl+F5` para forzar recarga
- O agregar `?v=<?php echo time(); ?>` a los CSS (ya incluido)

---

## 📊 Estructura de Datos

### Tabla: `usuarios`
```sql
CREATE TABLE usuarios (
  ...
  ubicacion VARCHAR(100) DEFAULT NULL COMMENT 'Ciudad/localidad del usuario',
  ...
);
```

### Parámetros GET de Búsqueda
```
?busqueda=texto          # Texto a buscar
&tipo=productos|usuarios # Tipo de búsqueda
&categoria=nombre        # Filtro de categoría (solo productos)
&estado=estado           # Filtro de estado (solo productos)
```

---

## 🎨 Guía de Estilos

### Colores Principales:
- **Verde Primario**: `#6a994e` (botones activos, acentos)
- **Verde Secundario**: `#9FC131` (gradientes)
- **Gris Texto**: `#333` (texto principal)
- **Gris Claro**: `#666` (texto secundario)
- **Rojo Acción**: `#dc3545` (botón limpiar)

### Componentes CSS:
- `.search-toggle`: Container de toggle productos/usuarios
- `.toggle-btn`: Botones de toggle
- `.toggle-btn.active`: Estado activo
- `.btn-filtros`: Botón de filtros
- `.filtros-panel`: Panel desplegable de filtros
- `.filtro-grupo`: Grupo label + select
- `.filtro-select`: Selectores de filtros
- `.card-usuario`: Tarjeta de usuario
- `.usuario-avatar-grande`: Avatar circular grande (120px)

---

## 🚀 Uso

### Búsqueda de Productos con Filtros:
1. Usuario hace clic en el botón "📦 Productos" (por defecto activo)
2. Escribe en el buscador: "zapatos"
3. Hace clic en "🔍 Filtros"
4. Selecciona:
   - Categoría: "Calzado"
   - Estado: "Disponible"
5. Hace clic en "Buscar"
6. Resultados filtrados se muestran en tarjetas

### Búsqueda de Usuarios:
1. Usuario hace clic en el botón "👥 Usuarios"
2. El placeholder cambia a "¿A quién buscás?"
3. Escribe en el buscador: "Angel"
4. Hace clic en "Buscar"
5. Tarjetas de usuarios con ese nombre se muestran
6. Usuario puede hacer clic en "Ver perfil"

---

## 🔄 Flujo de Búsqueda

```
┌─────────────────────────────────────┐
│  Usuario en index.php               │
└────────────┬────────────────────────┘
             │
             ▼
┌─────────────────────────────────────┐
│  Selecciona tipo: Productos/Usuarios│
└────────────┬────────────────────────┘
             │
       ┌─────┴─────┐
       │           │
       ▼           ▼
  PRODUCTOS    USUARIOS
       │           │
       ▼           │
  Abre filtros     │
  (opcional)       │
       │           │
       ▼           ▼
  Escribe búsqueda
       │
       ▼
  Envía formulario GET
       │
       ▼
  ┌────────────────┐
  │  index.php     │
  │  procesa $_GET │
  └───────┬────────┘
          │
    ┌─────┴──────┐
    │            │
    ▼            ▼
getProductos  buscarUsuarios
Filtrados()      ()
    │            │
    └─────┬──────┘
          │
          ▼
  Renderiza resultados
```

---

## 🧪 Testing

### Casos de Prueba:

#### 1. Búsqueda de Productos
- [ ] Búsqueda simple (sin filtros)
- [ ] Búsqueda con filtro de categoría
- [ ] Búsqueda con filtro de estado
- [ ] Búsqueda con ambos filtros
- [ ] Limpiar filtros funciona correctamente
- [ ] Sin resultados muestra mensaje apropiado

#### 2. Búsqueda de Usuarios
- [ ] Búsqueda por nombre completo
- [ ] Búsqueda por username
- [ ] Tarjetas muestran avatar correctamente
- [ ] Total de productos se calcula bien
- [ ] Link a perfil funciona
- [ ] Sin resultados muestra mensaje apropiado

#### 3. Toggle Productos/Usuarios
- [ ] Cambia placeholder del input
- [ ] Oculta/muestra filtros según tipo
- [ ] Estados visuales activos correctos
- [ ] Transiciones suaves

#### 4. Responsive
- [ ] Mobile: Toggle en columna
- [ ] Mobile: Filtros en columna
- [ ] Mobile: Tarjetas se adaptan
- [ ] Desktop: Layout horizontal

---

## 📝 Notas Técnicas

### Compatibilidad:
- PHP 7.4+
- MySQL 5.7+
- Font Awesome 6.4.0
- Navegadores modernos (Chrome, Firefox, Safari, Edge)

### Seguridad:
- Todas las consultas usan prepared statements (PDO)
- Sanitización de inputs con `htmlspecialchars()`
- Validación de parámetros GET

### Performance:
- Índices en columnas `username`, `fullname`, `categoria`, `estado`
- Límite de resultados (20 por defecto)
- Lazy loading de avatares con `onerror`

---

## 🔮 Mejoras Futuras (Recomendaciones FYP)

### Sistema de Recomendaciones "Para Ti":
Existen 3 opciones para implementar un sistema tipo TikTok FYP:

#### **Opción 1: Básico (Scoring Simple)**
- Rastrear: vistas, guardados, mensajes enviados
- Fórmula: `score = vistas×1 + guardados×3 + mensajes×5`
- Mostrar top 10 en sección "Recomendados para ti"
- **Implementación**: 1-2 días
- **Complejidad**: Baja

#### **Opción 2: Intermedio (Filtrado Colaborativo)**
- Rastrear interacciones usuario-producto
- "Usuarios que vieron X también vieron Y"
- Basado en similitud de gustos entre usuarios
- **Implementación**: 3-5 días
- **Complejidad**: Media

#### **Opción 3: Avanzado (Machine Learning)**
- Usar TensorFlow.js en frontend
- Entrenar modelo con datos de interacciones
- Predicción en tiempo real
- **Implementación**: 2-3 semanas
- **Complejidad**: Alta

**Recomendación**: Empezar con Opción 1, luego evolucionar a Opción 2.

---

## 👥 Créditos
- Desarrollado para: **HandinHand Platform**
- Basado en: MercadoLibre design patterns
- Fecha: Enero 2025
- Versión: 2.0

---

## 📞 Soporte
Para dudas o problemas:
1. Revisar este README
2. Verificar logs de errores en PHP
3. Comprobar consola del navegador
4. Ejecutar script SQL de ubicacion
