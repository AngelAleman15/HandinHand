# 🚀 HandinHand - Sistema de Chat en Tiempo Real

## 📋 Descripción
Sistema de mensajería en tiempo real con Socket.IO para la plataforma HandinHand.

---

## ⚙️ Configuración

### **1. Instalar dependencias de Node.js**

```powershell
npm install
```

### **2. Iniciar el servidor Socket.IO**

```powershell
node server.js
```

El servidor se iniciará en el puerto `3001` y mostrará:
```
Servidor accesible en: http://192.168.1.5:3001
```

---

## 🌐 Acceso desde otros dispositivos

### **En la misma red WiFi:**
```
http://192.168.1.5/MisTrabajos/HandinHand/
```

### **Configuración de Socket.IO:**
El archivo `config/chat_server.php` ya está configurado para detectar automáticamente la IP del servidor.

---

## ✅ Verificar que funciona

1. Abre la consola del navegador (F12)
2. Ve a la página de mensajería
3. Deberías ver:
   ```
   🚀 Inicializando sistema de chat...
   📡 Conectando a Socket.IO en: http://192.168.1.5:3001
   ✅ Conectado al servidor de chat
   ```

---

## 🐛 Solución de problemas

### **El chat no recibe mensajes en tiempo real**
1. Verifica que el servidor Node.js esté corriendo:
   ```powershell
   Get-Process -Name "node"
   ```

2. Si no está corriendo, inícialo:
   ```powershell
   node server.js
   ```

3. Recarga la página con **Ctrl + F5** (limpiar caché)

### **Error de conexión Socket.IO**
1. Verifica el firewall de Windows (puerto 3001 debe estar abierto)
2. Revisa que la IP en `config/chat_server.php` coincida con tu IP local

---

## 📁 Estructura de archivos

```
HandinHand/
├── server.js                  # Servidor Socket.IO
├── package.json               # Dependencias Node.js
├── config/
│   └── chat_server.php       # Configuración del servidor de chat
├── js/
│   └── chat.js               # Cliente Socket.IO
├── api/
│   ├── get-messages.php      # Cargar mensajes
│   ├── save-message.php      # Guardar mensajes
│   ├── mark-as-read.php      # Marcar como leídos
│   └── users.php             # Listar usuarios
└── sql/
    ├── unificar_mensajes.sql           # Migración de BD
    └── tablas_complementarias.sql      # Tablas adicionales
```

---

## 🔧 Características

✅ Mensajes en tiempo real con Socket.IO  
✅ Indicadores de usuarios online/offline  
✅ Notificaciones de mensajes no leídos  
✅ Responder a mensajes  
✅ Eliminar historial de chat  
✅ Chatbot Perseo (respuestas automáticas)  

---

## 💡 Notas importantes

- **Mantén el servidor Node.js corriendo** mientras uses el chat
- Para producción, considera usar **PM2** para mantener el servidor activo:
  ```powershell
  npm install -g pm2
  pm2 start server.js --name "handinhand-chat"
  pm2 save
  ```

---

## 📞 Soporte

Para reportar problemas o sugerencias, contacta al equipo de desarrollo.
