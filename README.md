Alejosantos007: En esta documentacion:
# 🤝 HandinHand - Plataforma de Intercambio y Trueque

![HandinHand](img/Hand(sinfondo).png)

**"Reutilizá, Intercambiá, Conectá"**

HandinHand es una plataforma web de intercambio y trueque diseñada para fomentar la economía circular y la reutilización de productos. Los usuarios pueden publicar artículos que ya no necesitan y conectarse con otros para realizar intercambios, promoviendo un consumo más sostenible y consciente.

---

## 📋 Tabla de Contenidos

- [Características Principales](#-características-principales)
- [Tecnologías Utilizadas](#-tecnologías-utilizadas)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Base de Datos](#-base-de-datos)
- [Funcionalidades](#-funcionalidades)
- [Sistema de Mensajería en Tiempo Real](#-sistema-de-mensajería-en-tiempo-real)
- [Chatbot Perseo](#-chatbot-perseo)
- [API Endpoints](#-api-endpoints)
- [Uso](#-uso)
- [Capturas de Pantalla](#-capturas-de-pantalla)
- [Contribución](#-contribución)
- [Licencia](#-licencia)

---

## ✨ Características Principales

- 🔐 **Sistema de Autenticación Completo**
  - Registro de usuarios
  - Inicio de sesión seguro
  - Gestión de perfiles
  - Carga de avatares personalizados

- 📦 **Gestión de Productos**
  - Publicación de productos para intercambio
  - Múltiples imágenes por producto (hasta 3)
  - Carrusel interactivo con navegación
  - Categorización por tipo de producto
  - Búsqueda y filtrado avanzado
  - Edición y eliminación de productos propios

- 💬 **Sistema de Mensajería en Tiempo Real**
  - Chat instantáneo mediante WebSockets (Socket.IO)
  - Notificaciones en tiempo real
  - Contador de mensajes no leídos
  - Indicadores visuales de mensajes nuevos

- 🤖 **Chatbot Inteligente "Perseo"**
  - Asistente virtual con respuestas automáticas
  - Detección de palabras clave
  - Respuestas contextuales predefinidas
  - Integración transparente en el chat

- ⭐ **Sistema de Valoraciones**
  - Calificación de vendedores (1-5 estrellas)
  - Valoración desde la página de producto
  - Interfaz interactiva con estrellas
  - Promedio de valoraciones visible en perfiles

- 🎯 **Acciones en Productos**
  - 💚 Agregar a favoritos (toggle visual)
  - 📤 Compartir productos (Web Share API)
  - 🚩 Sistema de denuncias
  - 💬 Contactar vendedor directo
  - 🔄 Proponer intercambio (próximamente)

- 🔍 **Búsqueda y Filtros**
  - Búsqueda por nombre de producto
  - Filtrado por categorías
  - Resultados en tiempo real

---

## 🛠 Tecnologías Utilizadas

### Backend
- **PHP 7.4+** - Lenguaje principal del servidor
- **MySQL** - Base de datos relacional
- **PDO** - Capa de abstracción de base de datos

### Frontend
- **HTML5** - Estructura
- **CSS3** - Estilos y diseño responsive
- **JavaScript (Vanilla)** - Interactividad del cliente

### Tiempo Real
- **Node.js** - Servidor de WebSockets
- **Express.js 5.1.0** - Framework web para Node.js
- **Socket.IO 4.8.1** - Comunicación bidireccional en tiempo real
- **MySQL2 3.15.2** - Driver de MySQL para Node.js

### Librerías y Dependencias
- **CORS 2.8.5** - Manejo de políticas de origen cruzado

---

## 📁 Estructura del Proyecto

```
HandinHand/
│
├── api/                          # Endpoints de la API REST
│   ├── auth/                     # Autenticación
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── register.php
│   │   └── profile.php
│   ├── categorias.php            # Gestión de categorías
│   ├── chatbot.php               # Lógica del chatbot
│   ├── get-messages.php          # Obtener mensajes de chat
│   ├── get-total-unread.php      # Contador total de no leídos
│   ├── get-unread-count.php      # Contador por conversación
│   ├── mark-as-read.php          # Marcar mensajes como leídos
│   ├── mensajes.php              # Envío de mensajes
│   ├── perseo-auto-reply.php     # Respuestas automáticas de Perseo
│   ├── productos.php             # CRUD de productos
│   ├── save-message.php          # Guardar mensajes
│   ├── search.php                # Búsqueda de productos
│   ├── update-profile.php        # Actualizar perfil
│   ├── upload-avatar.php         # Subir avatar
│   ├── users.php                 # Gestión de usuarios
│   └── valoraciones.php          # Sistema de valoraciones
│
├── config/                       # Configuración
│   ├── database.php              # Configuración de base de datos PHP
│   └── chat_server.php           # Configuración del servidor WebSocket
│
├── css/                          # Estilos
│   ├── style.css                 # Estilos principales
│   └── perseo-actions.css        # Estilos del chatbot Perseo
│
├── img/                          # Imágenes y recursos
│   ├── productos/                # Imágenes de productos
│   └── [iconos y recursos visuales]
│
├── includes/                     # Componentes reutilizables PHP
│   ├── header.php                # Cabecera con navegación
│   ├── footer.php                # Pie de página
│   └── functions.php             # Funciones auxiliares
│
├── js/                           # Scripts JavaScript
│   ├── chat.js                   # Lógica del chat y WebSocket
│   ├── chatbot.js                # Interacción con Perseo
│   ├── notifications.js          # Sistema de notificaciones
│   ├── dropdownmenu.js           # Menú desplegable
│   └── perseo-actions.js         # Acciones del chatbot
│
├── sql/                          # Scripts SQL
│   └── handinhand.sql            # Script completo de base de datos (estructura y migraciones)
│
├── uploads/                      # Archivos subidos
│   └── avatars/                  # Avatares de usuarios
│
├── node_modules/                 # Dependencias de Node.js
│
├── index.php                     # Página principal (catálogo)
├── iniciarsesion.php            # Página de inicio de sesión
├── registrar.php                # Página de registro
├── perfil.php                   # Perfil de usuario
├── mensajeria.php               # Interfaz de mensajería
├── mis-productos.php            # Gestión de productos del usuario
├── editar-producto.php          # Editar productos
├── logout.php                   # Cerrar sesión
├── error404.php                 # Página de error 404
│
├── server.js                    # Servidor WebSocket (Node.js)
├── package.json                 # Dependencias de Node.js
├── package-lock.json            # Lockfile de dependencias
├── handinhand.sql              # Script completo de base de datos
├── configuracion.php           # Configuraciones generales
├── run_perseo_migration.php    # Script de migración de Perseo
├── verify_migration.php        # Verificación de migración
└── README.md                   # Este archivo

```

---

## 🚀 Instalación

### Requisitos Previos

- **XAMPP**, **WAMP**, **LAMP** o cualquier servidor con:
  - PHP 7.4 o superior
  - MySQL 5.7 o superior
  - Apache 2.4 o superior
- **Node.js 14+** y **npm** (para el servidor WebSocket)
- Navegador web moderno

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/AngelAleman15/HandinHand.git
   cd HandinHand
   ```

2. **Configurar la base de datos**
   - Crear una base de datos llamada `handinhand`
   - Importar el archivo SQL:
     ```bash
     mysql -u root -p handinhand < handinhand.sql
     ```
   - O usar phpMyAdmin para importar `handinhand.sql`

3. **Configurar la conexión a la base de datos**

   Editar `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'handinhand');
   define('DB_USER', 'tu_usuario');
   define('DB_PASS', 'tu_contraseña');
   ```

4. **Instalar dependencias de Node.js**
   ```bash
   npm install
   ```

5. **Iniciar el servidor Apache y MySQL**
   - Iniciar XAMPP/WAMP/LAMP
   - Asegurarse de que Apache y MySQL estén corriendo

6. **Iniciar el servidor WebSocket**
   ```bash
   node server.js
   ```
   O usar npm:
   ```bash
   npm start
   ```

7. **Acceder a la aplicación**
   - Abrir el navegador en: `http://localhost/HandinHand/`

---

## ⚙️ Configuración

### Configuración de Base de Datos

**Archivo:** `config/database.php`

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'handinhand');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Configuración del Servidor WebSocket

**Archivo:** `server.js`

```javascript
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'handinhand'
};
```

**Puerto del servidor:** Por defecto corre en el puerto `3000`

### Configuración de CORS

Si necesitas permitir otros orígenes para el WebSocket, edita en `server.js`:

```javascript
const io = require('socket.io')(http, {
    cors: {
        origin: "http://tu-dominio.com",
        methods: ["GET", "POST"]
    }
});
```

---

## 🗄️ Base de Datos

### Base de Datos

Toda la estructura y migraciones de la base de datos están contenidas en el archivo `handinhand.sql`. Este script incluye la creación de todas las tablas principales, relaciones, índices y migraciones necesarias para el funcionamiento completo del sistema.

**Principales tablas incluidas:**
- usuarios
- productos
- categorias
- mensajes
- valoraciones
- favoritos / productos_favoritos
- amistades
- solicitudes_amistad
- estadisticas_usuario

Consulta el archivo `sql/handinhand.sql` para ver la definición completa y actualizada de la base de datos.

---

## 🎯 Funcionalidades

### 1. Autenticación y Perfiles

#### Registro de Usuarios
- Formulario de registro con validación
- Hash seguro de contraseñas (password_hash)
- Validación de email único
- Campos: nombre, email, contraseña, teléfono, ubicación

#### Inicio de Sesión
- Autenticación mediante email y contraseña
- Sesiones PHP seguras
- Redirección automática según estado de sesión

#### Perfil de Usuario
- Visualización de información personal
- Edición de datos del perfil
- Carga de avatar personalizado
- Visualización de valoraciones recibidas
- Promedio de calificación (estrellas)

### 2. Gestión de Productos

#### Publicar Productos
- Formulario de creación con:
  - Nombre del producto
  - Descripción detallada
  - Selección de categoría
  - Carga de imagen
- Validación de campos obligatorios
- Estado inicial: "activo"

#### Mis Productos
- Lista de productos publicados por el usuario
- Opciones: Editar, Eliminar, Ver
- Indicador de estado (activo/inactivo/intercambiado)

#### Editar Productos
- Modificación de información
- Cambio de imagen
- Actualización de estado

#### Buscar Productos
- Barra de búsqueda en la página principal
- Búsqueda por nombre de producto
- Resultados en tiempo real

### 3. Sistema de Mensajería en Tiempo Real

#### Características del Chat
- **WebSocket bidireccional** con Socket.IO
- **Actualización instantánea** de mensajes
- **Lista de usuarios** disponibles para chatear
- **Historial de conversaciones** persistente
- **Indicador de mensajes no leídos** por conversación
- **Notificaciones visuales** con badges dinámicos

#### Funcionalidades
- Envío y recepción de mensajes en tiempo real
- Scroll automático a los mensajes más recientes
- Marcado de mensajes como leídos
- Contador total de mensajes no leídos en header
- Diseño responsive para móviles y desktop

### 4. Chatbot "Perseo" 🤖

#### Características
- **Respuestas automáticas inteligentes**
- **Detección de palabras clave** en mensajes de usuarios
- **Base de conocimiento** con respuestas predefinidas
- **Integración transparente** en el sistema de chat
- **Indicador visual** de mensajes automáticos

#### Palabras Clave Reconocidas
- Saludos: hola, buenos días, buenas tardes, buenas noches
- Ayuda: ayuda, help, necesito ayuda
- Información: cómo funciona, qué es esto, información
- Intercambio: intercambio, canje, trueque
- Categorías: categorías, tipos de productos
- Contacto: contacto, comunicarse
- Perfil: perfil, cuenta, usuario
- Y muchas más...

#### Respuestas Automáticas
Perseo puede responder preguntas sobre:
- Funcionamiento de la plataforma
- Proceso de intercambio
- Gestión de productos
- Categorías disponibles
- Contacto y soporte

### 5. Sistema de Valoraciones

#### Calificar Usuarios
- Puntuación de 1 a 5 estrellas
- Comentario opcional
- Solo usuarios registrados pueden valorar
- Un usuario solo puede valorar una vez a otro

#### Visualización de Valoraciones
- Promedio de calificaciones en perfil
- Lista de valoraciones recibidas
- Nombre del valorador y fecha
- Comentarios de valoración

---

## 🔌 API Endpoints

### Autenticación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/auth/register.php` | Registrar nuevo usuario |
| POST | `/api/auth/login.php` | Iniciar sesión |
| POST | `/api/auth/logout.php` | Cerrar sesión |
| GET | `/api/auth/profile.php` | Obtener perfil del usuario |

### Productos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/productos.php` | Listar productos |
| POST | `/api/productos.php` | Crear producto |
| PUT | `/api/productos.php` | Actualizar producto |
| DELETE | `/api/productos.php` | Eliminar producto |
| GET | `/api/search.php` | Buscar productos |

### Mensajería

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/get-messages.php` | Obtener mensajes de conversación |
| POST | `/api/save-message.php` | Guardar mensaje |
| GET | `/api/get-unread-count.php` | Contador de no leídos por usuario |
| GET | `/api/get-total-unread.php` | Contador total de no leídos |
| POST | `/api/mark-as-read.php` | Marcar mensajes como leídos |
| POST | `/api/perseo-auto-reply.php` | Procesar respuesta automática |

### Usuarios

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/users.php` | Listar usuarios disponibles |
| POST | `/api/update-profile.php` | Actualizar perfil |
| POST | `/api/upload-avatar.php` | Subir avatar |

### Categorías

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/categorias.php` | Listar categorías |

### Valoraciones

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/valoraciones.php` | Obtener valoraciones |
| POST | `/api/valoraciones.php` | Crear valoración |

### Chatbot

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/chatbot.php` | Interacción con Perseo |

---

## 💻 Uso

### 1. Registro e Inicio de Sesión

1. Accede a `http://localhost/HandinHand/`
2. Haz clic en "Registrarse"
3. Completa el formulario con tus datos
4. Inicia sesión con tu email y contraseña

### 2. Publicar un Producto

1. Inicia sesión
2. Ve a "Mis Productos"
3. Haz clic en "Publicar Nuevo Producto"
4. Completa el formulario:
   - Nombre del producto
   - Descripción
   - Categoría
   - Imagen
5. Guarda el producto

### 3. Buscar Productos

1. En la página principal, usa la barra de búsqueda
2. Escribe el nombre del producto que buscas
3. Haz clic en "Buscar"
4. Explora los resultados

### 4. Iniciar una Conversación

1. En la página de un producto, haz clic en el perfil del vendedor
2. Ve a "Mensajería" en el menú
3. Selecciona el usuario con quien quieres chatear
4. Escribe tu mensaje y presiona Enter o clic en "Enviar"

### 5. Usar el Chatbot Perseo

1. En el chat, escribe palabras clave como:
   - "hola" para saludar
   - "ayuda" para obtener asistencia
   - "cómo funciona" para información
2. Perseo responderá automáticamente
3. Los mensajes de Perseo tienen un indicador visual

### 6. Valorar a un Usuario

1. Ve al perfil del usuario que quieres valorar
2. Selecciona el número de estrellas (1-5)
3. Escribe un comentario (opcional)
4. Envía la valoración

---

## 📸 Capturas de Pantalla

> **Nota:** Agregar capturas de pantalla de tu aplicación acá para mostrar:
> - Página principal con productos
> - Interfaz de chat
> - Perfil de usuario
> - Formulario de publicación de productos
> - Sistema de valoraciones

---

## 🔒 Seguridad

### Medidas Implementadas

- ✅ **Hash de contraseñas** con `password_hash()` de PHP
- ✅ **Prevención de SQL Injection** mediante PDO con prepared statements
- ✅ **Prevención de XSS** con `htmlspecialchars()`
- ✅ **Validación de sesiones** en todas las páginas protegidas
- ✅ **Validación de entrada** en formularios
- ✅ **CORS configurado** para el servidor WebSocket
- ✅ **Gestión segura de archivos** en uploads

### Medidas a Implementar

- Cambiar las credenciales de base de datos en producción
- Usar HTTPS en producción
- Implementar rate limiting en endpoints críticos
- Configurar adecuadamente los permisos de archivos

---

## 🚧 Trabajo Futuro

### Funcionalidades Planificadas

- [ ] Sistema de intercambio directo con confirmación
- [ ] Notificaciones push en navegador
- [ ] Historial de intercambios
- [ ] Sistema de reportes y moderación
- [ ] Búsqueda avanzada con filtros múltiples
- [ ] Geolocalización para intercambios locales
- [ ] App móvil nativa (React Native/Flutter)
- [ ] Sistema de reputación más complejo
- [ ] Integración con redes sociales
- [ ] Panel de administración

---

## 🐛 Reportar Problemas

Si encuentras un bug o tienes una sugerencia:

1. Ve a la sección de [Issues](https://github.com/AngelAleman15/HandinHand/issues)
2. Verifica que el problema no haya sido reportado
3. Crea un nuevo issue con:
   - Descripción clara del problema
   - Pasos para reproducirlo
   - Comportamiento esperado vs actual
   - Screenshots si es aplicable

---

## 📄 Licencia

Este proyecto está bajo la Licencia ISC.

---

## 👨‍💻 Autores

**Ángel Alemán** - *Desarrollador Principal*
- GitHub: [@AngelAleman15](https://github.com/AngelAleman15)

**Alejo Santos** - *Desarrollador Principal y Tester*
- GitHub: [@AlejoSantos007](https://github.com/AlejoSantos007)

**Proyecto:** [HandinHand](https://github.com/AngelAleman15/HandinHand)

---

## 🌟 Estado del Proyecto

![Status](https://img.shields.io/badge/status-active-success.svg)
![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)
![Node](https://img.shields.io/badge/Node.js-14+-339933.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1.svg)
![Socket.IO](https://img.shields.io/badge/Socket.IO-4.8.1-010101.svg)

---

<div align="center">
  <p>Hecho con ❤️ para promover la economía circular</p>
  <p><strong>HandinHand © 2025</strong></p>
</div>

1. Análisis de rendimiento

  1.1 ***Métricas clave (FCP, LCP, TBT)***
    - FCP -
      El FCP (First contentful Paint) es una métrica utilizada en el desarrollo web que mide el tiempo más rápido en el que se tarda en cargar un elemento del sitio web.
    - LCP -
      El LCP (Largest Contentful Paint) es la métrica utilizada en el desarrollo web para verificar la velocidad a la que el archivo más grande del sitio web tarda en aparecer.
    - TBT -
      El TBT (Total Blocking Time) es la métrica utilizada en el desarrollo web para medir cuanto tarda el navegador en volverse un sitio interactivo para el usuario y dejando de ser simplemente contenido visible.

  1.2 ***Herramientas de medición utilizadas***

    Para realizar la medición FCP, LCP y TBT de HandInHand, el equipo de desarrollo ha optado por usar la herramienta de medición proporcionada por Chrome para desarrolladores web llamada "Lighthouse" con el fin de verificar los problemas de forma sencilla debido a su interfaz amigable y la especificación de los problemas del sitio. Especificando cual es el problema exacto, dando una advertencia, una posible mejora o una posible falta en el código.

2. Librerías

  2.1 ***CSS (SweetAlert2)***
    En el apartado Frontend. Los desarrolladores se enfocaron en dar al sitio web de HandInHand una vista agradable y fácil de navegar.
    Esto se logró gracias a la librería SweetAlert2 en el apartado de CSS para la vista del sitio utilizando alertas para el guardado de procesos como la creación de un producto, edición de datos del perfil de usuario y secciones no disponible del sitio.

  EJEMPLO:
    SweetAlert2 :Mostrar notificación toast al resaltar el botón de cambiar contraseña (perfil.php):
Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'info',
    title: 'Aquí puedes cambiar tu contraseña',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    background: '#C9F89B',
    color: '#313C26'
});

  EJEMPLO 2:
  Modal para editar información personal
Swal.fire({
    title: '✏️ Editar Información Personal',
    html: <div> ...campos de edición... </div>,
    showCancelButton: true,
    confirmButtonText: 'Guardar',
    preConfirm: () => { /* validaciones */ }
});


  2.2 ***CropperJS***
    El equipo de desarrollo implementó esta liberaria con el fin de utilizar un sistema de foto de perfil para que los usuarios tengan una forma de identificarse y personalizar su perfil como usuarios registrados.
    Esta libería permite redimensionar imágenes con un mínimo de 100px x 100px y un máximo de 250px x 250px. Esta librería tambien se utilizó para las imágenes de los produtos que los usuarios públican en el sitio.


  2.3 ***Socket.IO JS***
  Socket.IO se implementó en HandinHand con el fin de dar a los usuarios la posibilidad de conversar entre ellos para poder realizar trueques en tiempo real.

  Estas librerías se utilizaron con el fin de que el usuario tenga un a navegación y experiencia agradables durante su transcurso por el sitio de HandInhand, buscando una navegabilidad que cualquier persona encuentre fácil de utilizar, diseños que sean amigables a la vista junto a sistemas que faciliten la comunicacion entre ellos, ya sea para charlar o para intercambiar productos.

  EJEMPLO:
  Conexión y registro de usuario en el chat (chat.js):
socket = io(window.CHAT_SERVER_URL, { transports: ['websocket', 'polling'] });
socket.emit('user_connected', window.CURRENT_USER_ID);
Eventos en tiempo real para escribir y recibir mensajes (chat.js):
socket.emit('typing', { to: currentChatUserId, from: CURRENT_USER_ID });
socket.on('chat_message', (data) => {
    handleIncomingMessage(data);
});

3. Comparación

***Producto anterior***
[Imagen anterior]
  HandInHand, en su pre-finalización tuvo resultados mediocres y medianamente aceptables.
  El sitio presentaba problemas de carga inicial en su SI, teniendo una velocidad de 3.2 segundos de demora para cargar el sitio. Un aceptable FCP de 0.8 segundos para verificar la carga del contenido del sitio web. Un LCP de 1.5 segundos para cargar el contenido más grande de la página, pero, logra un TBT de 0.0 segundos. No entorpeciendo la navegación de los usuarios a través de la página.

***producto nuevo***
[Imagen nueva]

  En sus versiónes pre-finalización y su versión de lanzamiento muestra una notoria mejora en sus métricas de FCP, LCP y TBT, logrando un una web mucho más rápida gracias a su mejora en los apartados de su última versión como SI (Speed Index) (Con una mejora de 2.7 segundos con respecto a su versión anterior con 3.2 segundos), FCP (Con una mejora de 0.3 segundos con respecto a su versión anterior que era de 0.8 segundos), LCP (Con una mejora de 0.1 segundo con respecto a su versión anterior con 1.5 segundos) con respecto a su versión anterior, mientras que su TBT se mantiene en 0.0 segundos

## 🚀 Optimización de Rendimiento

### 📊 Análisis de Rendimiento

#### Métricas Clave
- **FCP (First Contentful Paint)** - Mide el tiempo más rápido en que se carga el primer elemento del sitio web
- **LCP (Largest Contentful Paint)** - Verifica la velocidad de carga del elemento más grande de la página
- **TBT (Total Blocking Time)** - Mide el tiempo que tarda el navegador en volverse interactivo

#### 🔧 Herramientas de Medición
Utilizamos **Chrome Lighthouse** para analizar el rendimiento debido a:
- Interfaz amigable
- Identificación específica de problemas
- Sugerencias de mejoras detalladas
- Verificación de código

### 📚 Librerías de Optimización

#### SweetAlert2
Implementada para mejorar la experiencia de usuario con:
- Alertas visuales modernas
- Notificaciones toast
- Modales de edición
- Feedback de acciones

```javascript
// Ejemplo de notificación toast
Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'info',
    title: 'Aquí puedes cambiar tu contraseña',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    background: '#C9F89B',
    color: '#313C26'
});
```

#### CropperJS
- Sistema de gestión de imágenes de perfil
- Redimensionamiento controlado (100px-250px)
- Optimización de imágenes de productos
- Mejora en tiempos de carga

#### Socket.IO
- Comunicación en tiempo real optimizada
- Sistema de chat eficiente
- Conexión bidireccional

```javascript
// Ejemplo de implementación Socket.IO
socket = io(window.CHAT_SERVER_URL, {
    transports: ['websocket', 'polling']
});
socket.emit('user_connected', window.CURRENT_USER_ID);
```

### 📈 Comparativa de Rendimiento

#### Versión Anterior
- Speed Index (SI): 3.2s
- First Contentful Paint (FCP): 0.8s
- Largest Contentful Paint (LCP): 1.5s
- Total Blocking Time (TBT): 0.0s

#### Versión Actual
- Speed Index (SI): 0.5s ⬇️ (-2.7s)
- First Contentful Paint (FCP): 0.5s ⬇️ (-0.3s)
- Largest Contentful Paint (LCP): 1.4s ⬇️ (-0.1s)
- Total Blocking Time (TBT): 0.0s ➡️ (sin cambios)

