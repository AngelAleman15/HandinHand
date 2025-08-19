# 📚 Documentación API - HandInHand

## 🔗 URL Base
```
http://localhost/2025PracticasAAleman/HandInHand/api/
```

## 🔐 Autenticación
La mayoría de endpoints requieren autenticación mediante sesión PHP. Los endpoints que requieren autenticación retornarán error 401 si no hay sesión activa.

## 📋 Respuesta Estándar
Todas las respuestas siguen este formato:

### ✅ Éxito
```json
{
    "success": true,
    "message": "Operación exitosa",
    "data": {...},
    "timestamp": "2025-08-18 10:30:00"
}
```

### ❌ Error
```json
{
    "success": false,
    "message": "Descripción del error",
    "timestamp": "2025-08-18 10:30:00"
}
```

---

## 🔐 **AUTENTICACIÓN**

### POST `/api/auth/login.php`
Iniciar sesión

**Body:**
```json
{
    "username": "usuario",
    "password": "contraseña"
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "username": "usuario",
            "fullname": "Nombre Completo",
            "email": "email@ejemplo.com"
        },
        "session_id": "abc123..."
    }
}
```

### POST `/api/auth/register.php`
Registrar nuevo usuario

**Body:**
```json
{
    "fullname": "Nombre Completo",
    "username": "usuario",
    "email": "email@ejemplo.com",
    "phone": "+598123456789",
    "password": "contraseña123",
    "birthdate": "1995-05-15"
}
```

**Validaciones:**
- Email debe tener formato válido (ejemplo@correo.com)
- Contraseña debe tener mínimo 6 caracteres
- Fecha de nacimiento debe indicar mayor de 18 años
- Todos los campos son obligatorios

**Errores específicos:**
- `400`: "El formato del email es incorrecto. Debe ser como: ejemplo@correo.com"
- `400`: "La contraseña es muy corta. Debe tener al menos 6 caracteres"
- `400`: "Debes ser mayor de 18 años para poder registrarte en HandInHand"
- `409`: "El nombre de usuario o email ya está en uso"

### POST `/api/auth/logout.php`
Cerrar sesión (requiere autenticación)

### GET `/api/auth/profile.php`
Obtener perfil del usuario actual (requiere autenticación)

---

## 📦 **PRODUCTOS**

### GET `/api/productos.php`
Obtener lista de productos

**Parámetros opcionales:**
- `busqueda`: Texto a buscar
- `categoria`: Filtrar por categoría
- `usuario`: Productos de un usuario específico
- `limit`: Límite de resultados (default: 20)
- `offset`: Desplazamiento para paginación

**Ejemplo:**
```
GET /api/productos.php?busqueda=zapatos&limit=10&offset=0
```

### GET `/api/productos.php?id=123`
Obtener producto específico

### POST `/api/productos.php`
Crear nuevo producto para intercambio (requiere autenticación)

**Body:**
```json
{
    "nombre": "Guitarra Acústica",
    "descripcion": "Guitarra en buen estado para intercambiar",
    "categoria": "Música",
    "imagen": "img/guitarra.jpg"
}
```

### PUT `/api/productos.php?id=123`
Actualizar producto (requiere autenticación y ser propietario)

**Body:**
```json
{
    "nombre": "Nuevo nombre",
    "estado": "intercambiado"
}
```

### DELETE `/api/productos.php?id=123`
Eliminar producto (requiere autenticación y ser propietario)

---

## 💬 **MENSAJES**

### GET `/api/mensajes.php`
Obtener conversaciones del usuario (requiere autenticación)

### GET `/api/mensajes.php?id=123&contacto=456`
Obtener mensajes de una conversación específica
- `id`: ID del producto
- `contacto`: ID del usuario con quien conversar

### POST `/api/mensajes.php`
Enviar mensaje (requiere autenticación)

**Body:**
```json
{
    "producto_id": 123,
    "destinatario_id": 456,
    "mensaje": "Hola, me interesa tu producto"
}
```

### PUT `/api/mensajes.php?id=123`
Marcar mensajes como leídos (requiere autenticación)

**Body:**
```json
{
    "remitente_id": 456
}
```

---

## 🏷️ **CATEGORÍAS**

### GET `/api/categorias.php`
Obtener todas las categorías con conteo de productos

---

## ⭐ **VALORACIONES**

### GET `/api/valoraciones.php?user=123`
Obtener valoraciones de un usuario

### POST `/api/valoraciones.php`
Crear valoración (requiere autenticación)

**Body:**
```json
{
    "usuario_id": 123,
    "puntuacion": 5,
    "comentario": "Excelente vendedor"
}
```

---

## 🤖 **CHATBOT**

### POST `/api/chatbot.php`
Procesar mensaje del chatbot

**Body:**
```json
{
    "mensaje": "Hola, ¿cómo funciona HandInHand?"
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "respuesta": "¡Hola! HandInHand es una plataforma..."
    }
}
```

---

## 📝 **Ejemplos de Uso con JavaScript**

### Fetch API
```javascript
// Obtener productos
fetch('/api/productos.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log(data.data.productos);
        }
    });

// Crear producto para intercambio
fetch('/api/productos.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        nombre: 'Mi Producto para Intercambio',
        descripcion: 'Descripción del producto',
        categoria: 'Electrónicos'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### jQuery AJAX
```javascript
// Login
$.ajax({
    url: '/api/auth/login.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
        username: 'miusuario',
        password: 'micontraseña'
    }),
    success: function(data) {
        if (data.success) {
            location.reload();
        }
    }
});
```

---

## 🔍 **Códigos de Estado HTTP**

- `200`: Operación exitosa
- `201`: Recurso creado exitosamente
- `400`: Error en los datos enviados
- `401`: No autenticado
- `403`: No autorizado (sin permisos)
- `404`: Recurso no encontrado
- `405`: Método no permitido
- `409`: Conflicto (ej: usuario ya existe)
- `500`: Error interno del servidor

---

## 🔧 **Testing con cURL**

```bash
# Login
curl -X POST http://localhost/2025PracticasAAleman/HandInHand/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username": "test", "password": "123456"}'

# Obtener productos
curl http://localhost/2025PracticasAAleman/HandInHand/api/productos.php

# Chatbot
curl -X POST http://localhost/2025PracticasAAleman/HandInHand/api/chatbot.php \
  -H "Content-Type: application/json" \
  -d '{"mensaje": "Hola"}'
```

---

## 🛡️ **Seguridad**

- Todos los datos de entrada son sanitizados
- Las contraseñas se hashean con `password_hash()`
- Se valida la propiedad de recursos antes de modificarlos
- Headers CORS configurados para desarrollo
- Validación de tipos de datos y campos requeridos

---

## 🎯 **Próximos Pasos**

Para integrar estos endpoints en tu frontend:

1. **Actualizar el chatbot.js** para usar `/api/chatbot.php`
2. **Crear formularios AJAX** para login/registro
3. **Implementar carga dinámica** de productos
4. **Agregar sistema de mensajería** en tiempo real
5. **Mejorar la interfaz** con las nuevas funcionalidades

¡Tu API está lista para usar! 🚀
