<?php
session_start();

// Desactivar caché para desarrollo
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'includes/functions.php';

// Verificar que esté logueado
requireLogin();

// Obtener datos del usuario
$user = getCurrentUser();

// Configuración de la página
$page_title = "Mensajería - HandinHand";
$body_class = "body-messaging";

// Incluir header
include 'includes/header.php';
?>

<style>
/* === ESTILOS MODERNOS PARA MENSAJERÍA === */

/* Ajustar el body para mensajería */
body.body-messaging {
    margin: 0;
    padding-top: 0 !important; /* Se ajustará dinámicamente con JS */
    height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

body.body-messaging .header {
    margin-bottom: 0;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 99999;
}

.messaging-container {
    background: #f5f7fa;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: hidden;
    height: 100vh; /* Se ajustará dinámicamente con JS */
}

/* Contenedor principal del chat */
.chat-main-container {
    display: flex;
    flex: 1;
    overflow: visible;
    height: 100%;
    background: white;
    margin: 0;
    position: relative;
    padding-top: 50px; /* Espacio para el header principal */
}

/* Panel de contactos */
.contacts-panel {
    width: 320px;
    background: white;
    border-right: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.contacts-header {
    background: #f8f9fa;
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
}

.contacts-header h2 {
    margin: 0;
    color: #313C26;
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.contacts-header h2 i {
    color: #A2CB8D;
    font-size: 16px;
}

.contacts-search {
    padding: 10px 15px;
    border-bottom: 1px solid #e9ecef;
}

.search-input {
    width: 100%;
    padding: 8px 12px 8px 35px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    font-size: 13px;
    transition: all 0.3s ease;
    background: white;
    position: relative;
}

.search-wrapper {
    position: relative;
}

.search-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 16px;
}

.search-input:focus {
    outline: none;
    border-color: #A2CB8D;
    background: white;
    box-shadow: 0 0 0 3px rgba(162,203,141,0.1);
}

.contacts-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    margin: 0;
    border-bottom: 1px solid #f5f5f5;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.contact-item:hover {
    background: #f8f9fa;
}

.contact-item.active {
    background: #e8f5e9;
    border-left: 3px solid #A2CB8D;
}

.contact-item.active .contact-name {
    color: #313C26;
    font-weight: 600;
}

.contact-avatar {
    position: relative;
    flex-shrink: 0;
}

.contact-avatar img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #f0f0f0;
}

.status-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.status-indicator.online {
    background: #2ecc71;
}

.status-indicator.offline {
    background: #95a5a6;
}

.unread-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    font-size: 11px;
    font-weight: 700;
    min-width: 22px;
    height: 22px;
    border-radius: 11px;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    box-shadow: 0 3px 10px rgba(231, 76, 60, 0.5);
    animation: bounceIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    z-index: 10;
}

.unread-badge.show {
    display: flex;
}

.unread-badge.pulse {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes bounceIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 3px 10px rgba(231, 76, 60, 0.5);
    }
    50% {
        transform: scale(1.1);
        box-shadow: 0 3px 15px rgba(231, 76, 60, 0.8);
    }
}

.contact-info {
    flex: 1;
    min-width: 0;
}

.contact-name {
    font-weight: 600;
    font-size: 16px;
    color: #2c3e50;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.contact-preview {
    font-size: 13px;
    color: #7f8c8d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
}

.contact-time {
    font-size: 11px;
    color: #95a5a6;
    white-space: nowrap;
}

/* Panel de chat */
.chat-panel {
    flex: 1;
    background: white;
    display: none;
    flex-direction: column;
    height: 100%;
    min-width: 0;
    position: relative;
}

.chat-panel.active {
    display: flex;
}

.chat-header {
    background: linear-gradient(135deg, #313C26, #273122);
    padding: 15px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    flex-shrink: 0;
    height: auto;
    min-height: 70px;
    z-index: 10;
    width: 100%;
}

.chat-header-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.chat-header-avatar {
    position: relative;
}

.chat-header-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid rgba(201,249,155,0.3);
    object-fit: cover;
}

.chat-header-details h3 {
    color: #C9F89B;
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 2px 0;
}

.chat-header-status {
    font-size: 12px;
    color: rgba(255,255,255,0.7);
    display: flex;
    align-items: center;
    gap: 6px;
}

.chat-header-actions {
    display: flex;
    gap: 10px;
}

.chat-header-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: none;
    color: #C9F89B;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    font-size: 18px;
}

.chat-header-btn:hover {
    background: #C9F89B;
    color: #313C26;
    transform: scale(1.1);
}

.chat-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    overflow-x: hidden;
    background: linear-gradient(to bottom, #f8f9fa, #ffffff);
    display: flex;
    flex-direction: column;
    height: auto;
}

.message {
    display: flex;
    gap: 10px;
    max-width: 70%;
    animation: messageSlideIn 0.3s ease;
    align-items: flex-start;
    margin-bottom: 8px;
    background: transparent !important;
    padding: 0 !important;
    transform: none !important;
    box-shadow: none !important;
}

@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message.own {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.message:not(.own) {
    align-self: flex-start;
}

.message-avatar {
    flex-shrink: 0;
    width: 36px;
}

.message-avatar img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}

.message-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-width: calc(100% - 46px);
    background: transparent;
}

.message.own .message-content {
    align-items: flex-end;
}

.message:not(.own) .message-content {
    align-items: flex-start;
}

.message-bubble {
    padding: 8px 14px;
    border-radius: 16px;
    font-size: 15px;
    line-height: 1.4;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    display: inline-block;
    max-width: 100%;
    transition: all 0.2s ease;
}

.message:hover {
    transform: none !important;
    box-shadow: none !important;
}

.message:hover .message-bubble {
    transform: translateY(-2px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}

.message:not(.own) .message-bubble {
    background: #FFFFFF;
    color: #2c3e50;
    border-bottom-left-radius: 4px;
    border: 1px solid #e9ecef;
}

.message.own .message-bubble {
    background: linear-gradient(135deg, #A2CB8D, #C9F89B);
    color: #313C26;
    border-bottom-right-radius: 4px;
}

.message-time {
    font-size: 11px;
    color: #95a5a6;
    padding: 0 4px;
}

.message.own .message-time {
    text-align: right;
}

.message:not(.own) .message-time {
    text-align: left;
}

/* Respuesta a mensaje */
.message-reply-preview {
    background: rgba(162,203,141,0.1);
    border-left: 3px solid #A2CB8D;
    padding: 6px 10px;
    margin-bottom: 6px;
    border-radius: 6px;
    font-size: 13px;
}

.message-reply-preview .reply-username {
    color: #A2CB8D;
    font-weight: 600;
    font-size: 12px;
    margin-bottom: 2px;
}

.message-reply-preview .reply-text {
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 300px;
}

/* Vista previa de respuesta en input */
.reply-preview-container {
    display: none;
    padding: 10px 15px;
    background: #f8f9fa;
    border-left: 3px solid #A2CB8D;
    align-items: center;
    justify-content: space-between;
    margin-bottom: -10px;
}

.reply-preview-container.show {
    display: flex;
}

.reply-preview-info {
    flex: 1;
}

.reply-preview-username {
    color: #A2CB8D;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 2px;
}

.reply-preview-text {
    color: #666;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 400px;
}

.reply-preview-close {
    background: none;
    border: none;
    color: #999;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.reply-preview-close:hover {
    background: rgba(0,0,0,0.05);
    color: #333;
}

/* Menú de opciones del mensaje */
.message-options {
    position: relative;
}

.message-options-btn {
    opacity: 0;
    background: rgba(0,0,0,0.05);
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 16px;
    transition: all 0.2s;
    margin-left: 8px;
}

.message:hover .message-options-btn {
    opacity: 1;
}

.message-options-btn:hover {
    background: rgba(0,0,0,0.1);
}

.message-options-menu {
    display: none;
    position: absolute;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 4px 0;
    min-width: 150px;
    z-index: 1000;
    top: 100%;
    right: 0;
}

.message-options-menu.show {
    display: block;
}

.message-option-item {
    padding: 10px 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #333;
    font-size: 14px;
    transition: background 0.2s;
}

.message-option-item:hover {
    background: #f8f9fa;
}

.message-option-item i {
    width: 18px;
    color: #666;
}

/* Menú de opciones del chat (header) */
.chat-options-menu {
    display: none;
    position: absolute;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 4px 0;
    min-width: 180px;
    z-index: 1000;
    top: 50px;
    right: 10px;
}

.chat-options-menu.show {
    display: block;
}

.chat-option-item {
    padding: 12px 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #333;
    font-size: 14px;
    transition: background 0.2s;
}

.chat-option-item:hover {
    background: #f8f9fa;
}

.chat-option-item.danger {
    color: #dc3545;
}

.chat-option-item.danger:hover {
    background: #fff5f5;
}

.chat-option-item i {
    width: 20px;
    color: inherit;
}

/* Input de chat */
.chat-input-container {
    padding: 20px 25px;
    background: white;
    border-top: 1px solid rgba(0,0,0,0.05);
    display: flex;
    gap: 12px;
    align-items: center;
    flex-shrink: 0;
}

.chat-input-wrapper {
    flex: 1;
    position: relative;
}

.chat-input {
    width: 100%;
    padding: 14px 50px 14px 20px;
    border: 2px solid #e9ecef;
    border-radius: 25px;
    font-size: 15px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.chat-input:focus {
    outline: none;
    border-color: #A2CB8D;
    background: white;
    box-shadow: 0 0 0 3px rgba(162,203,141,0.1);
}

.chat-input:disabled {
    background: #e9ecef;
    cursor: not-allowed;
}

.emoji-btn {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #95a5a6;
    transition: all 0.3s ease;
}

.emoji-btn:hover {
    transform: translateY(-50%) scale(1.2);
    color: #A2CB8D;
}

.send-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #A2CB8D, #C9F89B);
    border: none;
    color: #313C26;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(162,203,141,0.3);
}

.send-btn:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 20px rgba(162,203,141,0.4);
}

.send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Mensaje de bienvenida */
.welcome-screen {
    flex: 1;
    background: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    text-align: center;
}

.welcome-screen.hidden {
    display: none;
}

.welcome-icon {
    font-size: 80px;
    color: #A2CB8D;
    margin-bottom: 25px;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

.welcome-screen h2 {
    color: #313C26;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 12px;
}

.welcome-screen p {
    color: #7f8c8d;
    font-size: 15px;
    max-width: 450px;
    line-height: 1.6;
    margin-bottom: 30px;
}

.welcome-features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 50px;
    max-width: 800px;
}

.welcome-feature {
    background: white;
    padding: 30px 20px;
    border-radius: 20px;
    transition: all 0.3s ease;
    border: 2px solid #f0f0f0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.welcome-feature:hover {
    background: linear-gradient(135deg, #A2CB8D, #C9F89B);
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(162,203,141,0.3);
    border-color: transparent;
}

.welcome-feature:hover i {
    color: white;
    transform: scale(1.1);
}

.welcome-feature:hover h4,
.welcome-feature:hover p {
    color: white;
}

.welcome-feature i {
    font-size: 45px;
    color: #A2CB8D;
    margin-bottom: 18px;
    transition: all 0.3s ease;
    display: block;
}

.welcome-feature h4 {
    color: #313C26;
    font-size: 17px;
    font-weight: 600;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.welcome-feature p {
    color: #7f8c8d;
    font-size: 14px;
    line-height: 1.6;
    transition: all 0.3s ease;
}

/* Scrollbar personalizado */
.contacts-list::-webkit-scrollbar,
.chat-messages::-webkit-scrollbar {
    width: 8px;
}

.contacts-list::-webkit-scrollbar-track,
.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.contacts-list::-webkit-scrollbar-thumb,
.chat-messages::-webkit-scrollbar-thumb {
    background: #A2CB8D;
    border-radius: 10px;
}

.contacts-list::-webkit-scrollbar-thumb:hover,
.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #313C26;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .chat-main-container {
        padding: 20px;
    }
    
    .contacts-panel {
        width: 320px;
    }
    
    .welcome-features {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .messaging-header-content {
        padding: 0 20px;
    }
    
    .messaging-header-left h1 {
        font-size: 22px;
    }
    
    .header-action-btn span {
        display: none;
    }
    
    .chat-main-container {
        padding: 15px;
        flex-direction: column;
        height: auto;
    }
    
    .contacts-panel {
        width: 100%;
        height: auto;
        max-height: 50vh;
    }
    
    .chat-panel,
    .welcome-screen {
        width: 100%;
        min-height: 60vh;
    }
    
    .welcome-screen {
        padding: 40px 20px;
    }
    
    .welcome-icon {
        font-size: 70px;
    }
    
    .welcome-screen h2 {
        font-size: 24px;
    }
    
    .welcome-screen p {
        font-size: 16px;
    }
}

/* Footer anclado al fondo */
.footer {
    margin-top: auto;
    flex-shrink: 0;
}

/* === MODAL DE CONFIRMACIÓN DE ELIMINACIÓN === */
.delete-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 999999;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.2s ease-out;
}

.delete-modal.show {
    display: flex;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.delete-modal-content {
    background: white;
    border-radius: 16px;
    padding: 32px;
    max-width: 420px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease-out;
    text-align: center;
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.delete-modal-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
}

.delete-modal-icon i {
    font-size: 36px;
    color: white;
    animation: pulse 1s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.delete-modal-content h3 {
    margin: 0 0 12px 0;
    color: #2c3e50;
    font-size: 24px;
    font-weight: 700;
}

.delete-modal-content p {
    margin: 0 0 28px 0;
    color: #6c757d;
    font-size: 15px;
    line-height: 1.6;
}

.delete-modal-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.delete-modal-cancel,
.delete-modal-confirm {
    padding: 12px 28px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    flex: 1;
    justify-content: center;
}

.delete-modal-cancel {
    background: #f1f3f5;
    color: #495057;
}

.delete-modal-cancel:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.delete-modal-confirm {
    background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
    color: white;
}

.delete-modal-confirm:hover {
    background: linear-gradient(135deg, #ff5252 0%, #ff3838 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
}

.delete-modal-cancel:active,
.delete-modal-confirm:active {
    transform: translateY(0);
}

/* === NOTIFICACIONES DEL CHAT === */
.chat-notification {
    position: fixed;
    top: 80px;
    right: 20px;
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 999999;
    transform: translateX(400px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    font-size: 15px;
    font-weight: 500;
    max-width: 350px;
}

.chat-notification.show {
    transform: translateX(0);
    opacity: 1;
}

.chat-notification.success {
    border-left: 4px solid #51cf66;
}

.chat-notification.success i {
    color: #51cf66;
    font-size: 20px;
}

.chat-notification.error {
    border-left: 4px solid #ff6b6b;
}

.chat-notification.error i {
    color: #ff6b6b;
    font-size: 20px;
}

.chat-notification span {
    color: #2c3e50;
}
</style>

<div class="messaging-container">
    <!-- Contenedor principal del chat -->
    <div class="chat-main-container">
        <!-- Panel de contactos -->
        <div class="contacts-panel">
            <div class="contacts-header">
                <h2>
                    <i class="fas fa-address-book"></i>
                    Contactos
                </h2>
            </div>
            
            <div class="contacts-search">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" placeholder="Buscar contactos..." id="search-contacts">
                </div>
            </div>
            
            <div class="contacts-list" id="contacts-list">
                <!-- Los contactos se cargarán dinámicamente aquí -->
            </div>
        </div>

        <!-- Pantalla de bienvenida -->
        <div class="welcome-screen" id="welcome-screen">
            <div class="welcome-icon">
                <i class="fas fa-comments"></i>
            </div>
            <h2>¡Bienvenido a HandinHand Mensajes!</h2>
            <p>Conecta con otros usuarios para intercambiar productos y compartir experiencias. Selecciona un contacto para comenzar a chatear.</p>
            
            <div class="welcome-features">
                <div class="welcome-feature">
                    <i class="fas fa-bolt"></i>
                    <h4>Chat en Tiempo Real</h4>
                    <p>Mensajes instantáneos con notificaciones</p>
                </div>
                <div class="welcome-feature">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Seguro y Privado</h4>
                    <p>Tus conversaciones están protegidas</p>
                </div>
                <div class="welcome-feature">
                    <i class="fas fa-exchange-alt"></i>
                    <h4>Fácil Intercambio</h4>
                    <p>Coordina tus trueques fácilmente</p>
                </div>
            </div>
        </div>

        <!-- Panel de chat -->
        <div class="chat-panel" id="chat-panel">
            <div class="chat-header">
                <div class="chat-header-info">
                    <div class="chat-header-avatar">
                        <img src="img/usuario.png" alt="Avatar" id="chat-user-avatar">
                    </div>
                    <div class="chat-header-details">
                        <h3 id="chat-user-name">Usuario</h3>
                        <div class="chat-header-status">
                            <div class="status-indicator offline" id="chat-user-status"></div>
                            <span id="chat-user-status-text">Offline</span>
                        </div>
                    </div>
                </div>
                <div class="chat-header-actions">
                    <button class="chat-header-btn" title="Buscar en conversación">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="chat-header-btn" title="Información del usuario">
                        <i class="fas fa-info-circle"></i>
                    </button>
                    <button class="chat-header-btn" id="chat-options-btn" title="Más opciones">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <!-- Menú de opciones del chat -->
                    <div class="chat-options-menu" id="chat-options-menu">
                        <div class="chat-option-item danger" id="delete-chat-history">
                            <i class="fas fa-trash"></i>
                            <span>Eliminar historial</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-messages" id="chat-messages">
                <!-- Los mensajes se cargarán dinámicamente aquí -->
            </div>

            <!-- Vista previa de respuesta -->
            <div class="reply-preview-container" id="reply-preview">
                <div class="reply-preview-info">
                    <div class="reply-preview-username" id="reply-preview-username"></div>
                    <div class="reply-preview-text" id="reply-preview-text"></div>
                </div>
                <button class="reply-preview-close" id="cancel-reply">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <input 
                        type="text" 
                        class="chat-input" 
                        placeholder="Escribe un mensaje..." 
                        id="message-input"
                        disabled
                    >
                    <button class="emoji-btn" title="Emojis">
                        😊
                    </button>
                </div>
                <button class="send-btn" id="send-btn" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar historial -->
<div id="deleteConfirmModal" class="delete-modal">
    <div class="delete-modal-content">
        <div class="delete-modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>¿Eliminar historial de chat?</h3>
        <p>Esta acción eliminará todos los mensajes de esta conversación solo para ti. El otro usuario aún podrá ver los mensajes.</p>
        <div class="delete-modal-buttons">
            <button class="delete-modal-cancel" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="delete-modal-confirm" onclick="confirmDeleteHistory()">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>

<?php require_once 'config/chat_server.php'; ?>
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<script>
    const CHAT_SERVER_URL = '<?php echo getChatServerUrl(); ?>';
    const CURRENT_USER_ID = '<?php echo $user['id']; ?>';
    const CURRENT_USER_AVATAR = '<?php echo isset($user['avatar_path']) && !empty($user['avatar_path']) ? $user['avatar_path'] : 'img/usuario.png'; ?>';
    
    // Ajustar altura del contenedor basado en el header
    function adjustMessagingContainerHeight() {
        const header = document.querySelector('.header');
        const body = document.body;
        const messagingContainer = document.querySelector('.messaging-container');
        const chatMainContainer = document.querySelector('.chat-main-container');
        
        if (header && messagingContainer) {
            const headerHeight = header.offsetHeight;
            body.style.paddingTop = headerHeight + 'px';
            messagingContainer.style.height = `calc(100vh - ${headerHeight}px)`;
            messagingContainer.style.marginTop = '0';
            
            if (chatMainContainer) {
                chatMainContainer.style.height = '100%';
            }
            
            console.log('📏 Header height:', headerHeight + 'px');
            console.log('📐 Container height:', messagingContainer.style.height);
        }
    }
    
    // Ejecutar al cargar la página
    window.addEventListener('DOMContentLoaded', adjustMessagingContainerHeight);
    
    // Ejecutar también al redimensionar
    window.addEventListener('resize', adjustMessagingContainerHeight);
    
    // Ejecutar después de un pequeño delay para asegurar que todo esté renderizado
    setTimeout(adjustMessagingContainerHeight, 100);
</script>
<script src="js/chat.js?v=<?php echo time(); ?>"></script>
<script src="js/dropdownmenu.js?v=<?php echo time(); ?>"></script>

</body>
</html>
