const express = require('express');
const app = express();
const http = require('http').Server(app);
const io = require('socket.io')(http, {
    cors: {
        origin: "*",
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
app.use(cors());
app.use(express.json());

// Almacenar usuarios conectados
const connectedUsers = new Map();

// Socket.io
io.on('connection', (socket) => {

    // Evento typing
    socket.on('typing', ({ to, from }) => {
        const receiverSocket = connectedUsers.get(String(to));
        if (receiverSocket) {
            io.to(receiverSocket).emit('typing', { from });
        }
    });
    socket.on('stop_typing', ({ to, from }) => {
        const receiverSocket = connectedUsers.get(String(to));
        if (receiverSocket) {
            io.to(receiverSocket).emit('stop_typing', { from });
        }
    });
    console.log('Usuario conectado:', socket.id);

    // Cuando un usuario se identifica
    socket.on('user_connected', async (userId) => {
        console.log('👤 Evento user_connected recibido:', userId, 'Socket:', socket.id);
        try {
            if (!userId) {
                console.warn('   ⚠️ user_connected recibido sin userId válido');
                return;
            }
            const userKey = String(userId);
            const oldSocketId = connectedUsers.get(userKey);
            if (oldSocketId && oldSocketId !== socket.id) {
                console.log('   ⚠️ Usuario ya tenía un socket, eliminando socket anterior:', oldSocketId, '->', socket.id);
                // Opcional: desconectar el socket anterior si sigue activo
                // io.sockets.sockets.get(oldSocketId)?.disconnect(true);
            }
            connectedUsers.set(userKey, socket.id);
            console.log('   ✅ Usuario registrado en connectedUsers:', userKey, '->', socket.id);
            console.log('   📊 Usuarios conectados:', Array.from(connectedUsers.entries()));
            io.emit('users_online', Array.from(connectedUsers.keys()));
        } catch (err) {
            console.error('   ❌ Error en user_connected:', err);
        }
    });

    // Cuando un usuario envía un mensaje
    socket.on('chat_message', async (data) => {
    const serverEmitTime = Date.now();
    console.log('📨 Mensaje recibido:', data, '| EmitTime:', serverEmitTime);
        console.log('   Emisor:', data.sender_id, 'Receptor:', data.receiver_id);
        console.log('   📊 Map actual de usuarios:', Array.from(connectedUsers.entries()));
        
        // Obtener el socket del destinatario y del emisor
            const receiverSocket = connectedUsers.get(String(data.receiver_id));
            const senderSocket = connectedUsers.get(String(data.sender_id));
        
        console.log('   Socket receptor (' + data.receiver_id + '):', receiverSocket || 'No encontrado');
        console.log('   Socket emisor (' + data.sender_id + '):', senderSocket || 'No encontrado');
        
        // Enviar el mensaje al destinatario
        if (receiverSocket) {
            console.log('   ✅ Enviando mensaje al receptor en socket:', receiverSocket, '| EmitTime:', serverEmitTime);
            io.to(receiverSocket).emit('chat_message', { ...data, serverEmitTime });
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
        let removedUser = null;
        for (const [userId, socketId] of connectedUsers.entries()) {
            if (socketId === socket.id) {
                connectedUsers.delete(userId);
                removedUser = userId;
                break;
            }
        }
        if (removedUser) {
            console.log('   🗑️ Usuario eliminado de connectedUsers:', removedUser);
        } else {
            console.log('   ⚠️ Desconexión de socket no asociado a ningún usuario registrado');
        }
        console.log('   📊 Usuarios conectados tras desconexión:', Array.from(connectedUsers.entries()));
        io.emit('users_online', Array.from(connectedUsers.keys()));
    });
});

// Iniciar servidor
const PORT = 3001;
const HOST = '0.0.0.0'; // Esto hace que escuche en todas las interfaces de red
http.listen(PORT, HOST, () => {
    // Obtener la IP local del servidor
    const { networkInterfaces } = require('os');
    const nets = networkInterfaces();
    const results = {};

    for (const name of Object.keys(nets)) {
        for (const net of nets[name]) {
            // Skip over non-IPv4 and internal (i.e. 127.0.0.1) addresses
            if (net.family === 'IPv4' && !net.internal) {
                console.log(`Servidor accesible en: http://${net.address}:${PORT}`);
            }
        }
    }
});