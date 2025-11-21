    <!-- Chatbot -->
    <div id="chatbot-icon" title="¡Hola! Soy Perseo, tu asistente inteligente">
        <div style="background: linear-gradient(135deg, #005C53, #042940); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,92,83,0.4); border: 3px solid #9FC131; overflow: hidden;">
            <img src="img/Hand(sinfondo).png" alt="Perseo" style="width: 42px; height: 42px; object-fit: contain;">
        </div>
    </div>
    <div id="chatbot-container" class="hidden" inert>
        <div id="chatbot-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 24px; animation: wave 1s ease-in-out infinite;">🤖</span>
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 18px; font-weight: bold;">Perseo</span>
                        <span style="display: inline-block; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px rgba(34, 197, 94, 0.6); animation: pulse 2s ease-in-out infinite;" title="En línea"></span>
                    </div>
                    <div style="font-size: 11px; opacity: 0.8; font-weight: normal; margin-top: 2px;">Tu asistente inteligente</div>
                </div>
            </div>
            <button id="close-btn">&times;</button>
        </div>
        <div id="chatbot-body">
            <div id="chatbot-messages"></div>
        </div>
        <div id="chatbot-input-container">
            <input
                type="text"
                id="chatbot-input"
                placeholder="Escribe tu mensaje aquí..."
                autocomplete="off"
            />
            <button id="send-btn" title="Enviar mensaje">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                </svg>
            </button>
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
    <script src="<?php echo $base_url; ?>/js/perseo-actions.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $base_url; ?>/js/dropdownmenu.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $base_url; ?>/js/chatbot.js?v=<?php echo time(); ?>"></script>
</body>
</html>
