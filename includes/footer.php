    <!-- Chatbot -->
    <div id="chatbot-icon">
        <img src="img/Hand(sinfondo).png" style="width: 2.5rem;" alt="chatbot-icon">
    </div>
    <div id="chatbot-container" class="hidden" inert>
        <div id="chatbot-header">
            <span>Perseo</span>
            <button id="close-btn">&times;</button>
        </div>
        <div id="chatbot-body">
            <div id="chatbot-messages"></div>
        </div>
        <div id="chatbot-input-container">
            <input
                type="text"
                id="chatbot-input"
                placeholder="Escribe tu mensaje.."
            />
            <button id="send-btn">Enviar</button>
        </div>
    </div>

    <div class="footer"<?php echo isset($footer_style) ? ' style="' . $footer_style . '"' : ''; ?>>
        <div class="socialcontainer">
            <div class="social"><img src="img/instaicon.png" alt="Icono de Instagram"></div><p class="socialinfo">H_Hand</p>
            <div class="social"><img src="img/xicon.png" alt="Icono de X (Twitter)"></div><p class="socialinfo">H_Hand</p>
            <div class="social"><img src="img/wasaicon.png" alt="Icono de Whatsapp"></div><p class="socialinfo">H_Hand</p>
            <div class="social"><img src="img/phoneicon.png" alt="Icono de telefono"></div><p class="socialinfo">H_Hand</p>
        </div>
        <div class="footerinfo">© 2025 CodeIgnite. Todos los derechos reservados.</div>
    </div>

    <!-- Scripts básicos -->
    <script src="/js/perseo-actions.js?v=<?php echo time(); ?>"></script>
    <script src="/js/dropdownmenu.js?v=<?php echo time(); ?>"></script>
    <script src="/js/chatbot.js?v=<?php echo time(); ?>"></script>
</body>
</html>
