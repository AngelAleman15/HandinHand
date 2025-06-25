HandinHand Frontend
<div align="center"> <img src="img/Hand(sinfondo).png" alt="HandinHand Logo" width="200">
"Reutilizá, intercambiá, conectá"

Una plataforma web moderna para el intercambio y trueque de productos entre usuarios.

</div>
📋 Tabla de Contenidos
Descripción
Características
Estructura del Proyecto
Tecnologías
Instalación
Uso
Páginas
API Reference
Screenshots
Roadmap
Contribuir
Licencia
🚀 Descripción
HandinHand es una aplicación web frontend desarrollada para facilitar el intercambio de productos entre usuarios, promoviendo la reutilización y el consumo responsable. La plataforma ofrece una interfaz intuitiva y moderna para conectar personas que desean intercambiar sus pertenencias.

✨ Características
🎯 Funcionalidades Principales
Catálogo de Productos: Visualización dinámica de productos disponibles para intercambio
Sistema de Usuarios: Registro e inicio de sesión completo
Navegación Intuitiva: Header con iconos y menú lateral desplegable
Búsqueda: Barra de búsqueda integrada (UI implementada)
Responsive Design: Totalmente adaptable a dispositivos móviles y desktop
🔧 Características Técnicas
Diseño Responsivo: Mobile-first con breakpoints optimizados
Validación de Formularios: Validación client-side completa
LocalStorage: Persistencia de datos de usuario
Animaciones CSS: Transiciones suaves y efectos hover
Grid Layout: Sistema de grillas adaptable para productos
🗂️ Estructura del Proyecto
HandinHand/
├── 📁 .vscode/
│   └── settings.json           # Configuración del editor
├── 📁 css/
│   └── style.css              # Estilos principales
├── 📁 js/
│   ├── script.js              # Lógica principal y productos
│   ├── sidebar.js             # Funcionalidad del menú lateral
│   ├── login.js               # Gestión de inicio de sesión
│   └── register.js            # Gestión de registro
├── 📁 img/                    # Recursos gráficos
│   ├── Hand(sinfondo).png     # Logo principal
│   ├── user-icon.png          # Iconos de interfaz
│   └── ...                    # Imágenes de productos
├── 📄 index.html              # Página principal
├── 📄 login.html              # Página de inicio de sesión
├── 📄 register.html           # Página de registro
├── 📄 support.html            # Página de soporte
├── 📄 user.html               # Página de perfil de usuario
└── 📄 README.md               # Este archivo
🛠 Tecnologías
HTML5 - Estructura semántica
CSS3 - Estilos, Flexbox, Grid, Animaciones
JavaScript ES6+ - Lógica de aplicación
Bootstrap 5.3.7 - Framework CSS (CDN)
LocalStorage API - Persistencia de datos
📦 Instalación
Prerequisitos
Navegador web moderno
Servidor web local (opcional)
Pasos de Instalación
Clonar el repositorio
bash
git clone https://github.com/tu-usuario/handinhand-frontend.git
cd handinhand-frontend
Abrir con Live Server
bash
# Si tienes Live Server instalado
live-server
O abrir directamente
Abrir index.html en tu navegador
Para mejor funcionalidad, usar un servidor local
🎮 Uso
Navegación Principal
Logo: Click para volver al inicio
Usuario: Acceso a registro/perfil
Chat: Comunicación entre usuarios (próximamente)
Soporte: Centro de ayuda
Menú: Navegación completa y filtros
Registro de Usuario
Click en el icono de usuario
Completar formulario de registro
Validación automática de edad (+18)
Redirección automática al login
Inicio de Sesión
Usuario de prueba: Angel / 12345
Integración con usuarios registrados
📱 Páginas
🏠 index.html - Página Principal
Header con navegación
Barra de búsqueda destacada
Grid de productos dinámico
Menú lateral con categorías
Footer con redes sociales
🔐 login.html - Inicio de Sesión
Formulario de autenticación
Validación de campos
Manejo de errores
Enlace de recuperación
📝 register.html - Registro
Formulario completo de registro
Validaciones client-side
Verificación de mayoría de edad
Prevención de emails duplicados
🆘 support.html - Soporte
Estructura básica implementada
Próximas funcionalidades de soporte
🔌 API Reference
LocalStorage Schema
javascript
// Usuarios Registrados
{
  "registeredUsers": [
    {
      "username": "email@example.com",
      "password": "password123",
      "name": "Nombre",
      "surname": "Apellido",
      "phone": "123456789",
      "birthdate": "1990-01-01"
    }
  ]
}
Productos Predefinidos
javascript
const products = [
  {
    img: 'img/producto.jpg',
    title: "Nombre del Producto",
    description: "Descripción detallada..."
  }
];
📸 Screenshots
Desktop
<details> <summary>Ver capturas de pantalla</summary>
Página PrincipalMostrar imagen

Menu LateralMostrar imagen

LoginMostrar imagen

RegistroMostrar imagen

</details>
Responsive Breakpoints
Desktop: > 1200px - 5 columnas
Tablet: 900px - 1200px - 3 columnas
Mobile Large: 600px - 900px - 2 columnas
Mobile: < 600px - 1 columna
🗺 Roadmap
✅ Completado
 Interfaz responsive
 Sistema de registro/login
 Catálogo de productos
 Navegación funcional
 Validaciones de formularios
🚧 En Progreso
 Sistema de chat entre usuarios
 Funcionalidad de búsqueda
 Filtros por categoría
 Página de perfil de usuario
📋 Futuras Funcionalidades
 Subida de imágenes de productos
 Sistema de ratings/reviews
 Notificaciones push
 Geolocalización para intercambios locales
 Integración con redes sociales
 Dashboard de administración
👥 Contribuir
Proceso de Contribución
Fork el proyecto
Crear una rama feature (git checkout -b feature/AmazingFeature)
Commit los cambios (git commit -m 'Add some AmazingFeature')
Push a la rama (git push origin feature/AmazingFeature)
Abrir un Pull Request
Estándares de Código
HTML: Semántico y accesible
CSS: BEM methodology preferida
JavaScript: ES6+ con comentarios descriptivos
Commits: Conventional commits
Reportar Bugs
Usar el sistema de Issues con el template:

**Descripción del Bug**
Descripción clara del problema

**Pasos para Reproducir**
1. Ir a '...'
2. Click en '....'
3. Ver error

**Comportamiento Esperado**
Descripción de lo que debería pasar

**Screenshots**
Si aplica, agregar screenshots
🔧 Configuración de Desarrollo
VSCode Settings
json
{
    "clock.active": true,
    "liveServer.settings.port": 5501
}
Extensiones Recomendadas
Live Server
Prettier
Auto Rename Tag
CSS Peek
🚨 Problemas Conocidos
 Contraseñas almacenadas en texto plano
 Falta validación de formato de email
 Botón de chat no funcional
 Página de soporte incompleta
📊 Performance
Lighthouse Score: 90+ (Performance)
Responsive: 100% compatible
Browser Support: Chrome 70+, Firefox 65+, Safari 12+
👨‍💻 Autores
Code Ignite Team - Desarrollo inicial
📄 Licencia
Este proyecto está bajo la Licencia MIT - ver el archivo LICENSE.md para detalles.

🙏 Agradecimientos
Bootstrap team por el framework CSS
Comunidad de desarrolladores web
Beta testers y usuarios
<div align="center"> <p>Hecho con ❤️ por Code Ignite</p> <p>Copyright 2025 por Code Ignite</p> </div>
