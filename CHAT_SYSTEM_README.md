# Sistema de Mensajes Leídos/No Leídos

## 📋 Resumen de Implementación

Se ha implementado un sistema completo de seguimiento de mensajes leídos y no leídos en el chat de HandinHand.

## 🎯 Características Implementadas

### 1. Base de Datos
- ✅ Columna `is_read` (boolean) para indicar si un mensaje fue leído
- ✅ Columna `read_at` (timestamp) para registrar cuándo se leyó
- ✅ Campos actualizados de `remitente_id/destinatario_id` a `sender_id/receiver_id`
- ✅ Índices optimizados para consultas rápidas

### 2. APIs Nuevas

#### `api/get-unread-count.php`
- Obtiene el conteo de mensajes no leídos por cada usuario
- Devuelve un objeto con `user_id => count`
- Se llama al cargar la lista de contactos

#### `api/mark-as-read.php`
- Marca todos los mensajes de un remitente como leídos
- Se llama automáticamente al abrir un chat
- Actualiza el timestamp `read_at`

### 3. Interfaz de Usuario

#### Badge de Mensajes No Leídos
```css
- Ubicación: Esquina superior derecha del avatar del contacto
- Color: Rojo gradiente (#e74c3c → #c0392b)
- Animación: Efecto bounce al aparecer, pulse constante
- Límite: Muestra hasta 15, luego "+15"
```

#### Comportamiento
1. **Al cargar**: Se muestran todos los badges con mensajes pendientes
2. **Al recibir mensaje**: 
   - Si el chat NO está abierto → incrementa badge
   - Si el chat SÍ está abierto → marca como leído automáticamente
3. **Al abrir chat**: 
   - Oculta el badge
   - Marca todos los mensajes como leídos
   - Llama a la API `mark-as-read.php`

### 4. Socket.io Actualizado
- Detecta si un mensaje es para el chat actual
- Incrementa badges solo para chats no activos
- Marca automáticamente como leído si el chat está abierto

## 📁 Archivos Modificados

### Nuevos Archivos
- `api/get-unread-count.php` - API para conteo de no leídos
- `api/mark-as-read.php` - API para marcar como leído
- `update_chat_system.sql` - Migración de base de datos
- `run_migration.php` - Script de migración

### Archivos Actualizados
- `api/save-message.php` - Guarda con `is_read = 0`
- `api/get-messages.php` - Incluye campo `is_read`
- `js/chat.js` - Lógica de badges y marcado de leídos
- `mensajeria.php` - Estilos CSS para badges

## 🎨 Estilos CSS Agregados

```css
.unread-badge {
    - Posicionamiento absoluto en avatar
    - Gradiente rojo con sombra
    - Animación bounce + pulse
    - min-width: 20px, height: 20px
    - Font size: 11px, bold
}
```

## 🔄 Flujo de Funcionamiento

1. **Usuario A envía mensaje a Usuario B**
   → Mensaje se guarda con `is_read = 0`

2. **Usuario B está en otro chat o fuera del sistema**
   → Badge aparece en el avatar de Usuario A
   → Muestra número de mensajes pendientes

3. **Usuario B abre el chat con Usuario A**
   → Badge desaparece con animación
   → API marca todos los mensajes como `is_read = 1`
   → Registra `read_at = NOW()`

4. **Usuario B recibe mensaje mientras está en el chat**
   → Mensaje se marca automáticamente como leído
   → No aparece badge

## 🚀 Características Adicionales

- **Límite visual**: Muestra "+15" cuando hay más de 15 mensajes
- **Efecto pulse**: Badge pulsa constantemente para llamar la atención
- **Animación bounce**: Efecto visual al incrementar el contador
- **Responsive**: Badge se adapta al tamaño del avatar
- **Oculto en seleccionado**: No muestra badge en el chat activo

## 📊 Optimizaciones

- Índices de base de datos para consultas rápidas
- Consultas SQL optimizadas con condiciones específicas
- Actualización en lote de mensajes leídos
- Cache visual para evitar recalcular constantemente

## 🐛 Pruebas Recomendadas

1. Abrir dos navegadores con usuarios diferentes
2. Enviar mensajes entre ellos
3. Verificar que aparezcan los badges
4. Abrir el chat y verificar que desaparezcan
5. Enviar mensajes mientras el chat está abierto
6. Verificar que NO aparezcan badges
7. Enviar más de 15 mensajes sin leer
8. Verificar que muestre "+15"

## 🎯 Próximas Mejoras Posibles

- [ ] Notificación de escritura ("Usuario está escribiendo...")
- [ ] Doble check (enviado/leído) estilo WhatsApp
- [ ] Notificaciones push del navegador
- [ ] Sonido al recibir mensajes
- [ ] Preview del último mensaje en la lista de contactos
- [ ] Timestamp del último mensaje
- [ ] Filtro de chats con mensajes no leídos

---

**Estado**: ✅ Implementado y funcionando
**Versión**: 1.0
**Fecha**: 13 de Octubre, 2025
