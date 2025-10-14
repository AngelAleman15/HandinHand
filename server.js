const express = require('express');
const app = express();
const http = require('http').Server(app);
const io = require('socket.io')(http, {
    cors: {
        origin: "http://localhost",
        methods: ["GET", "POST"]
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
        
        // Obtener el socket del destinatario
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