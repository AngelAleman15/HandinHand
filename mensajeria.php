<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajeria</title>

</head>
<body>
<style>
    html {
        width: 100%;
        height: 100%;
    }
    body {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        background: linear-gradient(45deg, #005C53, #9FC131);

    }
    .chat-container {
        display: flex;
        width: 100%;
        height: 100%;
        flex-direction: row;
        gap: 10px;
    }
    .chat-user-opt {
        width: 100%;
        height: 10%;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: flex-end;
        background-color: #005C5380;
        border-radius: 10px;
    }
    .chat-user-opt img {
        background-color: whitesmoke;
        width: 3vw;
        height: 5vh;
        cursor: pointer;
        padding: 1%;
        border-radius: 30%;
        transition: ease-in-out 0.3s;
    }
    .chat-user-opt img:hover {
        transform: scale(1.1);
        background-color: greenyellow;
        border: solid 2px whitesmoke;
    }

    .user-contacts {
        width: 35%;
        height: 100%;
        background-color: #005C5380;
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        flex-direction: column;
    }

    .users-chat-contacts {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 30%;
        height: 100%;
    }
    .wrapper {
        width: 90%;
        height: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .log-out-chat-section {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 100%;
    }
    .user-profile {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        width: 4vw;
        height: 5vh;
        border-radius: 50px;
    }
    .user-profile img {
        width: 65%;
        height: 100%;
        border-radius: 50%;
        background-color: whitesmoke;
        cursor: pointer;
        transition: ease-in-out 0.3s;
        background-size: cover;
    }
    .contact-info {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-around;
        width: 91.8%;
        height: 10vh;
        background-color: greenyellow;
        border-radius: 20px;
        padding: 2%;
        padding-right: 4%;
        margin: 1%;
        transition: ease-in-out 0.1s;
    }
    .contact-info img {
        width: 5vw;
        height: 8vh;
        background-color: whitesmoke;
        border-radius: 50%;
        cursor: pointer;
    }
    .contact-info:hover {
        background-color: yellowgreen;
    }
    .contact-name-message {
        color: white;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        flex-direction: column;
        width: 20vw;
        height: 20vh;
        font-size: 3vh;
        font-family: Arial, Helvetica, sans-serif;
    }
    .contact-message {
        position: relative;
        width: 100%;
        height: 100%;
        font-family: Arial, Helvetica, sans-serif;
        color: black;
        font-size: 1rem;
        margin-left: 5%;
    }
    .contact-name {
        width: 100%;
        height: 20%;
        font-weight: bold;
        font-size: 1.5rem;
        margin: 3vh;
    }

    .chat-utu {
        width: 100%;
        height: 100%;
        display: grid;
        grid-template-rows: 10% 80% 10%;
        background-color: #005C5380;
        border-radius: 10px 0px 10px 0px;
    }
    .chat-header {
        width: 100%;
        height: 10vh;
        background-color: #005C5380;
        margin: 0%;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        border-radius: 10px 0px 0px 0px;
    }
    .contact-pf {
        width: 20%;
        height: 100%;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .contact-pf img {
        width: 5vw;
        height: 7vh;
        border-radius: 50%;
        cursor: pointer;
        transition: ease-in-out 0.3s;
        background-size: cover;
    }
    .chat-header-contact-name {
        color: greenyellow;
        font-size: 3vh;
        font-family: Arial, Helvetica, sans-serif;
    }
    .chat-header-options {
        width: 10vw;
        height: 6vh;
        background-color: whitesmoke;
        border-radius: 50px;
        cursor: pointer;
        display: flex;
        justify-content: space-around;
        align-items: center;
    }
    .chat-header-options img {
        width: 4vh;
        height: 4vh;
        cursor: pointer;
    }
    .header-option {
        width: 4vh;
        height: 4vh;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: ease-in-out 0.3s;
    }
    .header-option:hover {
        background: linear-gradient(45deg, #005C53, #9FC131);
        transform: scale(1.1);
    }
    .options-container {
        display: flex;
        flex-direction: row;
        justify-content: space-around;
        align-items: center;
        width: 100%;
        height: 100%;
    }
    .bubbles-container {
        width: 95%;
        height: 80vh;
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 2%;
        overflow-y: auto;
    }
    .user-bubble {
        max-width: auto;
        background-color: greenyellow;
        color: black;
        padding: 10px;
        border-radius: 15px 15px 0px 15px;
        align-self: flex-end;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 1.2rem;

    }
    .contact-bubble {
        max-width: auto;
        background-color: whitesmoke;
        color: black;
        padding: 10px;
        border-radius: 15px 15px 15px 0px;
        align-self: flex-start;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 1.2rem;
    }
    .chat-input {
        width: 100%;
        height: 10vh;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 10px;
    }
    .chat-input input {
        outline: none;
        width: 90%;
        height: 5vh;
        border-radius: 20px;
        border: none;
        font-size: 1.2rem;
        font-family: Arial, Helvetica, sans-serif;
    }
</style>

<div class="chat-container">
    <div class="user-contacts">
        <div class="contact-info">
            <div class="contact-pf">
                <img src="img/profile-example.png" alt="contact">
            </div>
            <div class="contact-name-message">
                <div class="contact-name">
                    <h3>Alejo García</h3>
                </div>
                <div class="contact-message">
                    <p>Che, ayer vi a...</p>
                </div>
            </div>
        </div>
        <div class="chat-user-opt">
            <div class="wrapper">
                <a href='#'>
                    <div class="users-chat-contacts">
                        <img src="img/usuario.png" alt="Contacts">
                    </div>
                </a>
                <a href='perfil.php'>
                    <div class="user-profile">
                        <img src="img/profile-example.png" alt="Config">
                    </div>
                </a>
                <a href='index.php'>
                    <div class="log-out-chat-section">
                        <img src="img/logout.png" alt="Logout">
                    </div>
                </a>
            </div>
        </div>
    </div>
    <div class="chat-utu">
        <div class="chat-header">
            <div class="options-container">
                <div class="contact-pf">
                    <img src="img/profile-example.png" alt="contact">
                </div>
                <div class="chat-header-contact-name">
                    <h2>Alejo García</h2>
                </div>
                <div class="chat-header-options">
                    <div class="header-option">
                        <img src="img/earth-globe.png" alt="Geolocate">
                    </div>
                    <div class="header-option">
                        <img src="img/earth-globe.png" alt="Call">
                    </div>
                    <div class="header-option">
                        <img src="img/earth-globe.png" alt="Report">
                    </div>
                </div>
            </div>
        </div>
        <div class="bubbles-container">
            <div class="user-bubble">
                <p>Hola, ¿cómo estás?</p>
            </div>
            <div class="contact-bubble">
                <p>¡Hola! Estoy bien, gracias. ¿Y tú?</p>
            </div>
        </div>
        <div class="chat-input">
            <input type="text" placeholder="Escribe un mensaje...">
        </div>
    </div>
</div>
</body>
<script src="js/chat.js"></script>
</html>
