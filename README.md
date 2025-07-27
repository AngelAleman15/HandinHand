# HandinHand
## Index
# <img src='img/img-documentation/index.png'>
### La página principal se compone de un encabezado el cual permite al usuario navegar por diferentes partes de la página web al hacer click en el logo de la aplicación en la parte superior izquierda para refrescar (reiniciar) la página, con los botónes de la parte superior derecha para navegar por las diferentes secciones.
#### El primer icono pertenece a la parte del perfil de usuario, donde (Con una cuenta iniciada), enviará al usuario a un formulario para que se registre ingresando datos correspondientes pedidos.
#### El segundo icono pertenece a al chat entre usuarios. Al hacer click el botón se desplegará un menu donde el usuario tendrá acceso a chats con otros usuarios para realizar trueques. (Función aún no implementada) 
#### El tercer icono pertenece a la sección del soporte técnico, donde al hacer click, el usuario tendrá acceso a un formulario donde podrá explicar su/s problema/s. (función aún no implementada)
#### El último icono pertenece al menu desplegable de la página. Al darle click se desplegará mostrando un menú donde aparecerán las siguientes opciónes en la parte superior.

## Index Sidebar
# <img src='img/img-documentation/index-sidebar.png'>
### Al lado izquierdo se encuentra un botón que envía al usuario a iniciar sesión a traves de un formulario con el que el usuario pueda iniciar sesión.
### Al lado derecho se encuentra un botón que envía al usuario a la página de registro para que el usuario cree una cuenta para poder utilizar otras funciones de la página, como entrar a su perfil personal.
### Debajo tiene diferentes botónes para ayudar al usuario a filtrar productos según sus preferencias, como por ejemplo, por tipo de ropa (el botón que dice "Ropa"), vehículos, etc. 
### En la parte inferior, se encuentra un botón que envía al usuario a las normas y condiciones impuestas por la página las cuales deben ser leídas con cuidado para asegurar la seguridad de los usuarios.

## Login
# <img src='img/img-documentation/login.png'>
### El login es un formulario corto el cual pide al usuario ingresar su nombre de usuario y contraseña para luego ser enviado nuevamente a la página principal. Además, en caso de que el usuario ya tenga una cuenta registrada y halla olvidado su contraseña tiene debajo del botón "Confirmar" un texto link el cual lo envía a otro formulario, el cual, ayudará al usuario a cambiar su contraseña para la recuperación de su cuenta.
## Register
# <img src='img/img-documentation/register.png'>
### El registrar es un formulario que pide al usuario su nombre, apellido, contraseña, correo electrónico y su número de teléfono para registrarse de forma que pueda tener una seguridad alta con datos especificos de su persona. Además, debajo del botón "Registrar" se encuentra un texto link el cual envía al usuario al formulario de inicio de sesión en caso de que el usuario ya tenga una cuenta registrada.

# Código Fuente
- */css/*: Contiene los estilos generales (style.css) y una carpeta para imágenes utilizadas en el diseño.
- */js/*: Agrupa los scripts de forma temática:
  - login.js: Valida y gestiona el proceso de inicio de sesión.
  - register.js: Contiene la lógica para el formulario de registro.
  - sidebar.js: Controla el comportamiento interactivo de la barra lateral.
  - script.js: Código general reutilizable en varias páginas.
- *Páginas HTML* separadas por funcionalidad (login.html, register.html, user.html, etc.) para mantener responsabilidad única por archivo.