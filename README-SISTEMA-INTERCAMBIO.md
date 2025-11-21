# Sistema de Intercambio de Productos - HandinHand

## 🎯 Descripción
Sistema completo para proponer intercambios de productos entre usuarios, incluyendo chats temporales y gestión de estados.

## 📋 Características Implementadas

### 1. Proponer Intercambio
- ✅ Botón "Proponer intercambio" activado en `producto.php`
- ✅ Modal interactivo para seleccionar producto a ofrecer
- ✅ Solo muestra productos con estado "disponible" o "reservado"
- ✅ Visualización clara de productos con imágenes y categorías

### 2. Gestión de Estados
- ✅ Al proponer intercambio, el producto ofrecido cambia a estado "reservado"
- ✅ Prioriza productos disponibles sobre reservados en la lista
- ✅ Validación de que el producto pertenece al usuario

### 3. Sistema de Chat
- ✅ **Si son amigos**: Abre chat normal existente
- ✅ **Si NO son amigos**: Crea chat temporal automáticamente
  - Duración: 7 días por defecto
  - Se elimina automáticamente al expirar
  - Vinculado al producto relacionado

### 4. Mensaje de Propuesta
El mensaje enviado incluye:
- 🔄 Indicador de propuesta de intercambio
- 📦 Producto ofrecido (del comprador)
- 📦 Producto solicitado (del vendedor)
- 💬 Mensaje opcional personalizado
- ℹ️ Info de que el producto está reservado

### 5. Notificaciones
- ✅ Notificación al vendedor sobre nueva propuesta
- ✅ Enlace directo al chat desde la notificación

## 🚀 Instalación

### Paso 1: Ejecutar Migración de Base de Datos
Accede a través del navegador:
```
http://localhost/MisTrabajos/HandinHand/migrar_sistema_intercambio.php
```

Esto creará/actualizará:
- Tabla `chats_temporales`
- Columnas en tabla `mensajes`: `tipo_mensaje`, `producto_relacionado_id`
- Tabla `notificaciones`

### Paso 2: Verificar Archivos Creados
```
✓ api/get-mis-productos-disponibles.php
✓ api/proponer-intercambio.php
✓ sql/chats_temporales_intercambio.sql
✓ migrar_sistema_intercambio.php (ejecutar solo una vez)
```

### Paso 3: Actualizado
```
✓ producto.php (botón de intercambio + modal + JavaScript)
```

## 📱 Cómo Usar

### Para el Comprador (Usuario que propone):
1. Navega a cualquier producto que te interese
2. Click en "Proponer intercambio"
3. Selecciona uno de tus productos disponibles/reservados
4. (Opcional) Escribe un mensaje personalizado
5. Click en "Enviar propuesta"
6. Serás redirigido al chat automáticamente
7. Tu producto seleccionado cambia a estado "reservado"

### Para el Vendedor (Usuario que recibe):
1. Recibes notificación de nueva propuesta
2. Click en la notificación o accede al chat
3. Ves el mensaje formateado con:
   - Producto que te ofrecen
   - Producto tuyo que solicitan
   - Mensaje opcional
4. Puedes aceptar o rechazar mediante chat

## 🔐 Validaciones Implementadas

- ✅ Usuario debe estar logueado
- ✅ Producto ofrecido debe pertenecer al usuario
- ✅ Producto ofrecido debe estar disponible/reservado
- ✅ Producto solicitado debe existir
- ✅ Vendedor debe ser dueño del producto solicitado
- ✅ No permite intercambios consigo mismo

## 🗄️ Estructura de Base de Datos

### Tabla: chats_temporales
```sql
- id (PK)
- usuario1_id (FK)
- usuario2_id (FK)
- producto_relacionado_id (FK)
- activo (boolean)
- created_at (timestamp)
- expires_at (timestamp, +7 días)
```

### Tabla: mensajes (columnas añadidas)
```sql
- tipo_mensaje (varchar: 'normal', 'propuesta_intercambio')
- producto_relacionado_id (FK a productos)
```

### Tabla: notificaciones
```sql
- id (PK)
- usuario_id (FK)
- tipo (varchar)
- titulo (varchar)
- mensaje (text)
- enlace (varchar)
- leida (boolean)
- created_at (timestamp)
```

## 🎨 Estilos CSS Incluidos

Los estilos del modal de intercambio se inyectan dinámicamente:
- Diseño responsive
- Animaciones suaves
- Indicadores visuales de selección
- Badges de estado de productos
- Efectos hover y selección

## 🔄 Flujo Completo

```
1. Usuario A ve producto de Usuario B
   ↓
2. Click "Proponer intercambio"
   ↓
3. Modal muestra productos de Usuario A
   ↓
4. Selecciona producto + mensaje opcional
   ↓
5. Sistema verifica amistad
   ↓
   ├─ SON AMIGOS → Usa chat existente
   └─ NO SON AMIGOS → Crea chat temporal
   ↓
6. Producto de A cambia a "reservado"
   ↓
7. Mensaje de propuesta enviado a B
   ↓
8. B recibe notificación
   ↓
9. Redirección automática al chat
```

## ⚠️ Notas Importantes

1. **Chats Temporales**: Expiran automáticamente después de 7 días
2. **Productos Reservados**: El vendedor puede ver que están reservados pero el sistema no los bloquea totalmente
3. **Mensajes**: Los mensajes de tipo "propuesta_intercambio" tienen formato especial
4. **Notificaciones**: Se crean automáticamente, necesitas un sistema de visualización en el frontend

## 🔧 Próximas Mejoras Sugeridas

- [ ] Sistema para aceptar/rechazar propuestas formalmente
- [ ] Historial de intercambios completados
- [ ] Rating de intercambios
- [ ] Limpieza automática de chats temporales expirados (cron job)
- [ ] Contador de propuestas pendientes
- [ ] Galería de fotos en el mensaje de propuesta

## 📝 Mantenimiento

### Limpiar chats temporales expirados (ejecutar periódicamente):
```sql
DELETE FROM chats_temporales 
WHERE expires_at < NOW() AND activo = 1;
```

## 🐛 Debugging

Si algo no funciona:
1. Verifica la consola del navegador (F12)
2. Revisa los logs PHP de Apache
3. Verifica que la migración se ejecutó correctamente
4. Asegúrate de que las tablas existen en la BD

## 📞 Soporte

Si encuentras algún problema o necesitas ayuda, revisa:
- Consola del navegador
- Logs de PHP
- Estructura de la base de datos

---

**Creado**: 6 de noviembre de 2025
**Versión**: 1.0.0
