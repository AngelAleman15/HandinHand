const express = require('express');
const app = express();
const http = require('http').Server(app);
// Configuración de Socket.IO
// Permitir conexiones desde No-IP (handinhand.sytes.net) y localhost:3000
const io = require('socket.io')(http, {
    cors: {
        origin: [
            "http://handinhand.sytes.net",
            "http://localhost:3000"
        ],
        methods: ["GET", "POST"],
        credentials: true
    }
});
const mysql = require('mysql2/promise');
const cors = require('cors');

// Configuración de la base de datos
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'handinhand'
};

// Middleware
// Configuración de CORS para Express
// Permitir solicitudes desde No-IP y localhost:3000
app.use(cors({
    origin: [
        "http://handinhand.sytes.net",
        "http://localhost:3000"
    ],
    credentials: true
}));
app.use(express.json());

// Almacenar usuarios conectados
const connectedUsers = new Map();

// Socket.io
io.on('connection', (socket) => {
    console.log('Usuario conectado:', socket.id);

    // Cuando un usuario se identifica
    socket.on('user_connected', async (userId) => {
        console.log('👤 Usuario identificado:', userId, 'Socket:', socket.id);
        
        // Si el usuario ya estaba conectado, eliminar el socket antiguo
        const oldSocketId = connectedUsers.get(userId);
        if (oldSocketId && oldSocketId !== socket.id) {
            console.log('   ⚠️ Usuario ya tenía un socket, actualizando:', oldSocketId, '->', socket.id);
        }
        
        // Guardar el nuevo socket
        connectedUsers.set(userId, socket.id);
        
        console.log('   📊 Usuarios conectados:', Array.from(connectedUsers.entries()));
        
        // Notificar a todos los usuarios conectados
        io.emit('users_online', Array.from(connectedUsers.keys()));
    });

    // Cuando un usuario envía un mensaje
    socket.on('chat_message', async (data) => {
        console.log('📨 Mensaje recibido:', data);
        console.log('   Emisor:', data.sender_id, 'Receptor:', data.receiver_id);
        console.log('   📊 Map actual de usuarios:', Array.from(connectedUsers.entries()));
        
        // Obtener el socket del destinatario y del emisor
        const receiverSocket = connectedUsers.get(data.receiver_id.toString());
        const senderSocket = connectedUsers.get(data.sender_id.toString());
        
        console.log('   Socket receptor (' + data.receiver_id + '):', receiverSocket || 'No encontrado');
        console.log('   Socket emisor (' + data.sender_id + '):', senderSocket || 'No encontrado');
        
        // Enviar el mensaje al destinatario
        if (receiverSocket) {
            console.log('   ✅ Enviando mensaje al receptor en socket:', receiverSocket);
            io.to(receiverSocket).emit('chat_message', data);
        } else {
            console.log('   ❌ Receptor NO encontrado en connectedUsers');
        }
        
        // Enviar confirmación al emisor para que vea su propio mensaje
        if (senderSocket) {
            console.log('   ✅ Enviando confirmación al emisor en socket:', senderSocket);
            io.to(senderSocket).emit('chat_message', data);
        } else {
            console.log('   ❌ Emisor NO encontrado en connectedUsers');
        }
    });

    // Cuando un usuario edita un mensaje
    socket.on('message_edited', async (data) => {
        console.log('✏️ Mensaje editado:', data);
        
        const receiverSocket = connectedUsers.get(data.receiver_id.toString());
        
        if (receiverSocket) {
            console.log('   ✅ Notificando edición al receptor en socket:', receiverSocket);
            io.to(receiverSocket).emit('message_edited', data);
        } else {
            console.log('   ❌ Receptor NO encontrado para notificar edición');
        }
    });

    // Cuando un usuario elimina un mensaje
    socket.on('message_deleted', async (data) => {
        console.log('🗑️ Mensaje eliminado:', data);
        
        const receiverSocket = connectedUsers.get(data.receiver_id.toString());
        
        if (receiverSocket) {
            console.log('   ✅ Notificando eliminación al receptor en socket:', receiverSocket);
            io.to(receiverSocket).emit('message_deleted', data);
        } else {
            console.log('   ❌ Receptor NO encontrado para notificar eliminación');
        }
    });

    // Cuando un usuario se desconecta
    socket.on('disconnect', () => {
        console.log('Usuario desconectado:', socket.id);
        // Encontrar y eliminar el usuario desconectado
        for (const [userId, socketId] of connectedUsers.entries()) {
            if (socketId === socket.id) {
                connectedUsers.delete(userId);
                break;
            }
        }
        // Notificar a todos los usuarios conectados
        io.emit('users_online', Array.from(connectedUsers.keys()));
    });
});

// Endpoint para emitir mensajes automáticos desde PHP
app.post('/api/emit-message', (req, res) => {
    const data = req.body;
    if (!data || !data.receiver_id) {
        return res.status(400).json({ success: false, message: 'Datos incompletos' });
    }

    const receiverSocket = connectedUsers.get(data.receiver_id.toString());
    if (receiverSocket) {
        io.to(receiverSocket).emit('chat_message', data);
        res.json({ success: true, message: 'Mensaje emitido por Socket.IO' });
    } else {
        res.status(404).json({ success: false, message: 'Usuario no conectado' });
    }
});

// Iniciar servidor
// Puerto configurable por variable de entorno (No-IP o local)
const PORT = process.env.PORT || 3001;
const HOST = '0.0.0.0'; // Escucha en todas las interfaces de red (requisito No-IP)

// Ruta raíz para verificación desde navegador
// Permite comprobar que el servidor está activo desde cualquier dominio permitido
app.get('/', (req, res) => {
    res.send(`
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>HandinHand - Servidor Node.js</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #ccc; padding: 32px; }
                h1 { color: #e91e63; }
                p { color: #333; }
                .badge { font-size: 2rem; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>HandinHand 🚀</h1>
                <p>¡Bienvenido! El servidor Node.js está activo y listo para recibir conexiones de chat en tiempo real.</p>
                <p class="badge">🤖 Mensajería, Perseo y más...</p>
                <hr>
                <p>Accede a las funciones de chat desde la app web.<br>
                <small>Si ves esta página, el backend Node.js está funcionando correctamente.</small></p>
            </div>
        </body>
        </html>
    `);
});

http.listen(PORT, HOST, () => {
    // Obtener la IP local del servidor
    const { networkInterfaces } = require('os');
    const nets = networkInterfaces();
    for (const name of Object.keys(nets)) {
        for (const net of nets[name]) {
            if (net.family === 'IPv4' && !net.internal) {
                console.log(`Servidor accesible en: http://${net.address}:${PORT}`);
            }
        }
    }
    // Comentario: El servidor ahora acepta conexiones desde cualquier IP y puerto dinámico, ideal para No-IP.
});