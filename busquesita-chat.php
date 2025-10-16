<?php
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $mensajes = getMensajes(20, $busqueda); // Limitar a 20 productos
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>busqueda de mensajes</title>
</head>
<body>
    <style>
        body {
            background: whitesmoke;
        }
        .search-chat-container {
            position: absolute;
            left: 60%;
            top: 5%;
            width: 20%;
            height: 7vh;
            background-size: cover;
            background-color: rgba(0, 255, 0, 0.4);
            border-radius: 10px;
        }
        input[type="text"] {
            position: absolute;
            left: 2%;
            top: 7%;
            width: 90%;
            height: 4vh;
            border-radius: 3px;
            border: none;
            padding: 10px;
            font-size: 1.2em;
            opacity: 0.7;
            background-color: whitesmoke;
            color: green;
            outline: none;
        }
    </style>
    <form class="search-chat-container" method="GET" action="busquesita-chat.php" style="display: flex; align-items: center;">
        <input type="text" name="busqueda" placeholder="¿A quien buscas?" class="inputnav">
    </form>
</body>
<script src="js/busquesita.js">
</html>
