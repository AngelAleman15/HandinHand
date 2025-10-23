# 🔥 Configuración del Firewall para HandinHand Chat

## ⚠️ IMPORTANTE: Ejecuta PowerShell como Administrador

### Paso 1: Abrir PowerShell como Administrador
1. Presiona `Windows + X`
2. Selecciona **"Windows PowerShell (Admin)"** o **"Terminal (Admin)"**

### Paso 2: Ejecutar este comando para abrir el puerto 3001

```powershell
New-NetFirewallRule -DisplayName "HandinHand Socket.IO (TCP 3001)" -Direction Inbound -Protocol TCP -LocalPort 3001 -Action Allow -Profile Private,Public
```

### Paso 3: Verificar que la regla se creó

```powershell
Get-NetFirewallRule -DisplayName "*HandinHand*" | Select-Object DisplayName, Enabled, Direction, Action
```

Deberías ver:
```
DisplayName                          Enabled Direction Action
-----------                          ------- --------- ------
HandinHand Socket.IO (TCP 3001)      True    Inbound   Allow
```

---

## 🔧 Alternativa: Configurar desde la GUI

1. Abre **Firewall de Windows Defender con seguridad avanzada**
   - Presiona `Windows + R`
   - Escribe: `wf.msc`
   - Presiona Enter

2. Click en **"Reglas de entrada"** (panel izquierdo)

3. Click en **"Nueva regla..."** (panel derecho)

4. Selecciona **"Puerto"** → Siguiente

5. Selecciona **TCP** y en "Puertos locales específicos" escribe: **3001**

6. Selecciona **"Permitir la conexión"** → Siguiente

7. Marca **Privado** y **Público** → Siguiente

8. Nombre: **HandinHand Socket.IO**

9. Click en **Finalizar**

---

## ✅ Verificar que funciona

Desde otro dispositivo en la misma red, abre:
```
http://TU_IP:3001/socket.io/socket.io.js
```

Si ves código JavaScript, el puerto está abierto correctamente.

---

## 🐛 Solución de problemas

### El firewall sigue bloqueando

Si Windows Defender Firewall sigue bloqueando, puedes deshabilitarlo temporalmente para pruebas:

**⚠️ SOLO PARA PRUEBAS - NO RECOMENDADO EN PRODUCCIÓN**

```powershell
# Deshabilitar firewall (temporalmente)
Set-NetFirewallProfile -Profile Domain,Public,Private -Enabled False

# Volver a habilitar después de probar
Set-NetFirewallProfile -Profile Domain,Public,Private -Enabled True
```

---

## 📊 Logs del servidor Node.js

Cuando un dispositivo se conecta, deberías ver en la terminal del servidor:

```
Usuario conectado: [socket-id]
👤 Usuario identificado: [user-id] Socket: [socket-id]
```

Si no ves estos logs cuando el otro dispositivo carga la página, significa que Socket.IO no puede conectarse.
