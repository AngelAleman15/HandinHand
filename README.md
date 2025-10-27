# 🤝 HandinHand - Plataforma de Intercambio y Trueque

![HandinHand](img/Hand(sinfondo).png)

**"Reutilizá, Intercambiá, Conectá"**

HandinHand es una plataforma web para publicar, buscar e intercambiar productos, con mensajería en tiempo real, sistema de valoraciones y asistente virtual.

---

## 📋 Tabla de Contenidos
- [Características Principales](#características-principales)
- [Instalación y Configuración](#instalación-y-configuración)
- [Mensajería y Chatbot](#mensajería-y-chatbot)
- [API y Endpoints](#api-y-endpoints)
- [Infraestructura y Red](#infraestructura-y-red)
- [Base de Datos](#base-de-datos)
- [Contribución](#contribución)

---

## ✨ Características Principales
- Registro, login y gestión de perfiles con avatar
- Publicación, edición y eliminación de productos
- Categorización, búsqueda y filtrado avanzado
- Ubicación para intercambio
- Sistema de favoritos para compradores
- Valoraciones y comentarios de usuarios
- Chat en tiempo real (Socket.IO)
- Chatbot "Perseo" integrado

---

## 🚀 Instalación y Configuración

### Requisitos
- PHP 7.4+
- MySQL
- Node.js (para chat en tiempo real)
- Servidor web (WAMP/LAMP/XAMPP recomendado)

### 1. Clonar el repositorio y configurar entorno
```bash
git clone ...
cd HandinHand
```

### 2. Instalar dependencias Node.js para chat
```bash
npm install
```

### 3. Configurar base de datos
- Importa el archivo `handinhand.sql` en tu MySQL
- Configura credenciales en `config/database.php`

### 4. Iniciar servidor de chat
```bash
node server.js
```

### 5. Acceso local
- http://localhost/MisTrabajos/HandinHand/

---

## 💬 Mensajería y Chatbot
- El chat funciona en tiempo real usando Socket.IO (Node.js)
- El chatbot Perseo responde automáticamente a ciertas palabras clave
- Notificaciones y contador de mensajes no leídos

#### Problemas comunes
- Si el chat no conecta, revisa el puerto 3001 (ver sección de red)
- Si no ves mensajes en tiempo real, reinicia `node server.js` y recarga la página

---

## 🔌 Infraestructura y Red

### Port Forwarding
- Abre el puerto 3001 en tu router para acceso externo al chat
- Verifica el firewall de Windows (ver ejemplo abajo)

### Firewall
- Ejecuta como administrador:
```powershell
New-NetFirewallRule -DisplayName "HandinHand Socket.IO (TCP 3001)" -Direction Inbound -Protocol TCP -LocalPort 3001 -Action Allow -Profile Private,Public
```

---

## 🛠️ API y Endpoints
- La API está en `/api/`
- Requiere autenticación por sesión PHP
- Respuestas estándar:
```json
{
  "success": true,
  "message": "...",
  "data": {...}
}
```

### Endpoints principales
- `POST /api/auth/login.php` - Login
- `POST /api/auth/register.php` - Registro
- `GET /api/productos.php` - Listar productos
- `GET /api/productos.php?id=ID` - Ver producto
- `POST /api/productos.php` - Crear producto
- `PUT /api/productos.php?id=ID` - Editar producto
- `DELETE /api/productos.php?id=ID` - Eliminar producto
- `POST /api/favorito-producto.php` - Marcar/desmarcar favorito

---

## 🗄️ Base de Datos
- Tablas principales: `usuarios`, `productos`, `mensajes`, `valoraciones`, `amistades`, `favoritos`
- Migraciones SQL en carpeta `/sql/`

---

## 🤖 Contribución
- Forkea el repo y haz un pull request
- Reporta bugs o mejoras en Issues

---

## 📸 Capturas de Pantalla
(Agrega aquí screenshots de la app en funcionamiento)

---

## 📝 Licencia
MIT
