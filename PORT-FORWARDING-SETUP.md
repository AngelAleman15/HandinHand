# 🌐 Configuración de Port Forwarding para HandinHand

## 📊 Información de tu red

- **IP Local del servidor**: `192.168.1.5`
- **IP Pública**: `179.25.80.137`
- **Puertos necesarios**: 80 (Apache/PHP) y 3001 (Socket.IO)

---

## ✅ Estado actual

| Servicio | Puerto | Estado | Acción requerida |
|----------|--------|--------|------------------|
| Apache/PHP | 80 | ✅ Configurado | Ninguna |
| Socket.IO | 3001 | ❌ Falta configurar | ⚠️ **Agregar port forwarding** |

---

## 🔧 Paso 1: Configurar Port Forwarding en el Router

### Accede a tu router:
1. Abre un navegador y ve a: `http://192.168.1.1` (o `http://192.168.0.1`)
2. Ingresa tu usuario y contraseña de administrador

### Busca la sección de Port Forwarding:
Puede estar en:
- **Port Forwarding**
- **Virtual Server**
- **NAT**
- **Aplicaciones y Juegos**
- **Reenvío de puertos**

### Agrega una nueva regla con estos datos:

```
Nombre/Descripción: HandinHand Socket.IO
Tipo de servicio: TCP
Puerto externo/WAN: 3001
Puerto interno/LAN: 3001
IP del servidor: 192.168.1.5
Estado: Habilitado/Activo
```

### Guarda y reinicia el router si es necesario

---

## 🧪 Paso 2: Verificar que el puerto esté abierto

### Desde un dispositivo EXTERNO a tu red WiFi:

1. **Verifica que el servidor Node.js esté corriendo**:
   - En tu PC, asegúrate de que el servidor Socket.IO esté activo
   - Deberías ver en la terminal: `Servidor Socket.IO corriendo en http://192.168.1.5:3001`

2. **Prueba desde tu móvil con datos celulares** (NO WiFi):
   
   Abre el navegador y ve a:
   ```
   http://179.25.80.137:3001/socket.io/socket.io.js
   ```
   
   ✅ **Si funciona**: Verás código JavaScript
   ❌ **Si no funciona**: Verás un error de conexión

3. **Herramienta online** (opcional):
   
   Ve a: https://www.yougetsignal.com/tools/open-ports/
   
   - IP Address: `179.25.80.137`
   - Port Number: `3001`
   - Click en "Check"
   
   ✅ Debería decir: **"Port 3001 is open"**

---

## 📱 Paso 3: Probar el chat desde el móvil

### Desde tu móvil CON DATOS CELULARES (NO WiFi):

1. Abre el navegador en tu móvil
2. Ve a la URL de tu aplicación usando tu IP pública:
   ```
   http://179.25.80.137/MisTrabajos/HandinHand/mensajeria.php
   ```

3. Abre la **Consola del navegador** (si puedes en móvil):
   - En Chrome Android: Menu → Más herramientas → Herramientas de desarrollador
   - Busca mensajes como: `Socket.IO conectado exitosamente`

4. **Envía un mensaje** y verifica que llegue en tiempo real a tu PC

---

## 🔥 Paso 4: Configurar el Firewall de Windows

Asegúrate de que el puerto 3001 esté abierto en tu PC:

### Método 1: PowerShell (como Administrador)

```powershell
New-NetFirewallRule -DisplayName "HandinHand Socket.IO (TCP 3001)" -Direction Inbound -Protocol TCP -LocalPort 3001 -Action Allow -Profile Private,Public
```

### Método 2: Interfaz gráfica

1. `Windows + R` → Escribe: `wf.msc` → Enter
2. Click en **"Reglas de entrada"**
3. Click en **"Nueva regla..."**
4. Tipo: **Puerto** → TCP → Puerto: **3001**
5. Acción: **Permitir la conexión**
6. Perfil: **Privado** y **Público**
7. Nombre: **HandinHand Socket.IO**

---

## 🐛 Solución de problemas

### El móvil no se conecta a Socket.IO

**Verificar en orden**:

1. ✅ **Servidor Node.js corriendo** en tu PC
   ```bash
   # En PowerShell
   cd c:\wamp64\www\MisTrabajos\HandinHand
   node server.js
   ```

2. ✅ **Puerto 3001 abierto en el Firewall de Windows**
   ```powershell
   Get-NetFirewallRule -DisplayName "*HandinHand*" | Select-Object DisplayName, Enabled, Action
   ```

3. ✅ **Port Forwarding configurado en el router** para el puerto 3001

4. ✅ **IP pública correcta** (puede cambiar si tu ISP usa IP dinámica)
   ```bash
   # Verificar IP actual
   curl https://api.ipify.org
   ```

### Mi IP pública cambió

Si tu proveedor de Internet te asigna IP dinámica, tu IP pública puede cambiar.

**Solución rápida**:
1. Obtén tu nueva IP pública:
   ```powershell
   (Invoke-WebRequest -Uri "https://api.ipify.org" -UseBasicParsing).Content
   ```

2. Actualiza el archivo `config/chat_server.php`:
   ```php
   define('CHAT_SERVER_IP_PUBLIC', 'TU_NUEVA_IP');
   ```

**Solución permanente**: Usa un servicio de DNS dinámico como:
- No-IP (https://www.noip.com/)
- DuckDNS (https://www.duckdns.org/)
- DynDNS

---

## 📝 Notas importantes

### Seguridad
⚠️ Al abrir puertos en tu router, estás exponiendo servicios a Internet. Asegúrate de:
- Usar autenticación en tu aplicación
- Validar todos los datos de entrada
- Mantener tu sistema actualizado
- Considerar usar HTTPS en el futuro

### IP dinámica
Si tu IP pública cambia frecuentemente:
- Actualiza `CHAT_SERVER_IP_PUBLIC` en `config/chat_server.php`
- O configura un servicio de DNS dinámico

### Testing
- **Pruebas locales** (desde WiFi): Usa `192.168.1.5`
- **Pruebas externas** (desde datos móviles): Usa `179.25.80.137`
- La aplicación detecta automáticamente qué IP usar según el origen de la conexión

---

## ✅ Checklist final

Antes de probar desde el móvil, verifica:

- [ ] Servidor Node.js corriendo (`node server.js`)
- [ ] Puerto 3001 abierto en Windows Firewall
- [ ] Port Forwarding de puerto 3001 configurado en el router
- [ ] `config/chat_server.php` actualizado con IP pública
- [ ] Apache/WAMP corriendo
- [ ] Móvil usando DATOS CELULARES (no WiFi)

---

## 🎯 URLs para probar

### Desde tu PC (red local):
- Chat: `http://192.168.1.5/MisTrabajos/HandinHand/mensajeria.php`
- Socket.IO: `http://192.168.1.5:3001/socket.io/socket.io.js`

### Desde móvil con datos celulares:
- Chat: `http://179.25.80.137/MisTrabajos/HandinHand/mensajeria.php`
- Socket.IO: `http://179.25.80.137:3001/socket.io/socket.io.js`

---

## 📞 ¿Necesitas ayuda?

Si algo no funciona, verifica los logs del servidor Node.js:
- Deberías ver: `Usuario conectado: [socket-id]` cuando alguien se conecta
- Si no ves ese mensaje, el problema es de conectividad de red
