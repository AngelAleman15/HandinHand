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
    padding-top: 70px; /* Espacio para el header principal */
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
    display: block;
    text-decoration: none;
    transition: all 0.3s ease;
}

.chat-header-avatar:hover {
    transform: scale(1.05);
}

.chat-header-avatar:hover img {
    border-color: rgba(201,249,155,0.8);
    box-shadow: 0 0 0 3px rgba(201,249,155,0.2);
}

.chat-header-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid rgba(201,249,155,0.3);
    object-fit: cover;
    transition: all 0.3s ease;
    cursor: pointer;
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
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

.message-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
    cursor: text;
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
    background: rgba(162,203,141,0.15);
    border-left: 3px solid #A2CB8D;
    padding: 6px 10px;
    margin-bottom: 6px;
    border-radius: 6px;
    font-size: 13px;
}

/* Respuesta en mensajes recibidos (otros usuarios) */
.message.received .message-reply-preview {
    background: rgba(162,203,141,0.2);
    border-left: 3px solid #A2CB8D;
}

.message.received .message-reply-preview .reply-username {
    color: #5d8a4a;
    font-weight: 700;
    font-size: 12px;
    margin-bottom: 2px;
}

.message.received .message-reply-preview .reply-text {
    color: #2d3748;
    font-weight: 500;
}

/* Respuesta en mensajes enviados (míos) */
.message.own .message-reply-preview {
    background: rgba(0, 0, 0, 0.25) !important;
    border-left: 4px solid rgba(0, 0, 0, 0.5) !important;
    padding: 8px 12px !important;
}

.message.own .message-reply-preview .reply-username {
    color: #2E3925 !important;
    font-weight: 800 !important;
    font-size: 12px !important;
    margin-bottom: 3px !important;
}

.message.own .message-reply-preview .reply-text {
    color: #2d3748 !important;
    font-weight: 600 !important;
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
    position: fixed;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 4px 0;
    min-width: 150px;
    z-index: 10000;
}

.message-options-menu.show {
    display: block;
}

.message-options-menu.show-above {
    transform: translateY(-100%);
    margin-top: -4px;
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

.message-option-item.danger {
    color: #dc3545;
}

.message-option-item.danger:hover {
    background: #fff5f5;
}

.message-option-item i {
    width: 18px;
    color: #666;
}

.message-option-item.danger i {
    color: #dc3545;
}

/* Indicador de mensaje editado */
.message-edited {
    font-size: 11px;
    color: #999;
    font-style: italic;
    margin-left: 6px;
}

.message.own .message-edited {
    color: rgba(49, 60, 38, 0.6);
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
    z-index: 10;
}

.emoji-btn:hover {
    transform: translateY(-50%) scale(1.2);
    color: #A2CB8D;
}

/* Emoji Picker */
.emoji-picker {
    position: fixed;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05);
    padding: 0;
    display: none;
    z-index: 10001;
    max-width: 360px;
    width: 95%;
    overflow: hidden;
    animation: emojiPickerSlide 0.25s ease-out;
}

@keyframes emojiPickerSlide {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.emoji-picker.show {
    display: block;
}

.emoji-picker-header {
    display: flex;
    gap: 4px;
    padding: 12px;
    background: linear-gradient(135deg, #A2CB8D 0%, #C9F89B 100%);
    overflow-x: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.5) transparent;
    border-bottom: 2px solid rgba(255,255,255,0.3);
}

.emoji-picker-header::-webkit-scrollbar {
    height: 4px;
}

.emoji-picker-header::-webkit-scrollbar-track {
    background: transparent;
}

.emoji-picker-header::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.4);
    border-radius: 2px;
}

.emoji-picker-header::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.6);
}

.emoji-category-btn {
    background: rgba(255,255,255,0.3);
    border: 1px solid rgba(255,255,255,0.4);
    border-radius: 10px;
    padding: 8px 14px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
    color: #313C26;
    flex-shrink: 0;
    backdrop-filter: blur(10px);
}

.emoji-category-btn:hover {
    background: rgba(255,255,255,0.5);
    border-color: rgba(255,255,255,0.6);
    transform: translateY(-1px);
}

.emoji-category-btn.active {
    background: white;
    color: #313C26;
    border-color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    font-weight: 600;
}

.emoji-picker-content {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 4px;
    max-height: 280px;
    overflow-y: auto;
    padding: 12px;
    scrollbar-width: thin;
    scrollbar-color: #C9F89B #f0f0f0;
}

.emoji-picker-content::-webkit-scrollbar {
    width: 6px;
}

.emoji-picker-content::-webkit-scrollbar-track {
    background: #f0f0f0;
    border-radius: 3px;
}

.emoji-picker-content::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #A2CB8D, #C9F89B);
    border-radius: 3px;
}

.emoji-picker-content::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #8bb777, #b5e685);
}

.emoji-item {
    font-size: 28px;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-align: center;
    background: transparent;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
}

.emoji-item:hover {
    background: linear-gradient(135deg, rgba(162,203,141,0.2), rgba(201,248,155,0.2));
    transform: scale(1.25);
    box-shadow: 0 4px 12px rgba(162,203,141,0.3);
    border-radius: 10px;
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
    padding-top: 80px; /* Espacio para el header */
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

/* === MODAL DE EDICIÓN DE MENSAJE === */
.edit-modal {
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
    padding-top: 80px; /* Espacio para el header */
}

.edit-modal.show {
    display: flex;
}

.edit-modal-content {
    background: white;
    border-radius: 16px;
    max-width: 550px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease-out;
    overflow: hidden;
}

.edit-modal-header {
    background: linear-gradient(135deg, #A2CB8D, #C9F89B);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}

.edit-modal-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.edit-modal-icon i {
    font-size: 20px;
    color: #313C26;
}

.edit-modal-header h3 {
    margin: 0;
    color: #313C26;
    font-size: 20px;
    font-weight: 700;
    flex: 1;
}

.edit-modal-close {
    background: rgba(255, 255, 255, 0.3);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #313C26;
    font-size: 16px;
    transition: all 0.3s ease;
}

.edit-modal-close:hover {
    background: rgba(255, 255, 255, 0.5);
    transform: rotate(90deg);
}

.edit-modal-body {
    padding: 24px;
}

.edit-message-input {
    width: 100%;
    padding: 14px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 15px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    resize: vertical;
    min-height: 100px;
    max-height: 300px;
    transition: all 0.3s ease;
}

.edit-message-input:focus {
    outline: none;
    border-color: #A2CB8D;
    box-shadow: 0 0 0 3px rgba(162, 203, 141, 0.1);
}

.edit-message-counter {
    text-align: right;
    margin-top: 8px;
    font-size: 13px;
    color: #999;
}

.edit-message-counter.warning {
    color: #ff9800;
    font-weight: 600;
}

.edit-message-counter.error {
    color: #f44336;
    font-weight: 700;
}

.edit-modal-footer {
    padding: 16px 24px;
    background: #f8f9fa;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.edit-modal-cancel,
.edit-modal-save {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.edit-modal-cancel {
    background: #e9ecef;
    color: #495057;
}

.edit-modal-cancel:hover {
    background: #dee2e6;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.edit-modal-save {
    background: linear-gradient(135deg, #A2CB8D, #C9F89B);
    color: #313C26;
}

.edit-modal-save:hover {
    background: linear-gradient(135deg, #8DB87A, #B8E788);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(162, 203, 141, 0.4);
}

.edit-modal-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.edit-modal-save:active,
.edit-modal-cancel:active {
    transform: translateY(0);
}

/* === MODAL DE ELIMINACIÓN DE MENSAJE === */
.delete-message-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 999999;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.2s ease-out;
    padding-top: 80px; /* Espacio para el header */
}

.delete-message-modal.show {
    display: flex;
}

.delete-message-modal-content {
    background: white;
    border-radius: 16px;
    max-width: 420px;
    width: 90%;
    padding: 32px 28px 28px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    animation: slideUp 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    text-align: center;
    position: relative;
}

.delete-message-modal-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    background: #fff5f5;
    border: 3px solid #fee;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.delete-message-modal-icon i {
    font-size: 28px;
    color: #e74c3c;
}

.delete-message-modal-content h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 22px;
    font-weight: 600;
}

.delete-message-modal-content p {
    margin: 0 0 24px 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.6;
}

.delete-message-preview {
    background: #f8f9fa;
    border-left: 3px solid #e74c3c;
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    text-align: left;
}

.delete-message-preview i {
    color: #95a5a6;
    font-size: 16px;
    margin-top: 2px;
    flex-shrink: 0;
}

.delete-message-preview span {
    color: #495057;
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.delete-message-modal-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.delete-message-modal-cancel,
.delete-message-modal-confirm {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    flex: 1;
    justify-content: center;
    max-width: 160px;
}

.delete-message-modal-cancel {
    background: #f1f3f5;
    color: #495057;
    border: 1px solid #dee2e6;
}

.delete-message-modal-cancel:hover {
    background: #e9ecef;
    border-color: #ced4da;
}

.delete-message-modal-confirm {
    background: #e74c3c;
    color: white;
}

.delete-message-modal-confirm:hover {
    background: #c0392b;
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.delete-message-modal-cancel:active,
.delete-message-modal-confirm:active {
    transform: scale(0.98);
}

/* === ESTILOS PARA CONTACTOS NO-AMIGOS === */

/* Botón de rechazar contacto */
.btn-rechazar-contacto {
    background: transparent;
    border: none;
    color: #dc3545;
    font-size: 18px;
    cursor: pointer;
    padding: 5px;
    margin-top: 5px;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    opacity: 0.7;
}

.btn-rechazar-contacto:hover {
    background: #dc3545;
    color: white;
    opacity: 1;
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.btn-rechazar-contacto:active {
    transform: scale(0.95);
}

/* Badge de no-amigo */
.badge-no-amigo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 6px;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(243, 156, 18, 0.2);
}

/* Estilo para contactos no-amigos */
.contact-item.no-amigo {
    background: linear-gradient(135deg, #fff9e6 0%, #fff 100%);
    border-left: 3px solid #f39c12;
}

.contact-item.no-amigo:hover {
    background: linear-gradient(135deg, #fff3cd 0%, #fff9e6 100%);
}

/* Ajustar el meta cuando hay botón de rechazar */
.contact-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: center;
    gap: 4px;
}

/* === ESTILOS PARA PROPUESTAS DE INTERCAMBIO === */

/* Banner de propuesta pendiente */
.propuesta-banner {
    display: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { transform: translateY(-100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.propuesta-banner-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.propuesta-banner-icon {
    font-size: 24px;
    background: rgba(255,255,255,0.2);
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.propuesta-banner-info {
    flex: 1;
}

.propuesta-banner-title {
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 15px;
}

.propuesta-banner-productos {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    opacity: 0.95;
}

.producto-mini {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.15);
    padding: 4px 10px;
    border-radius: 20px;
}

.producto-mini img {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    object-fit: cover;
}

.propuesta-banner-actions {
    display: flex;
    gap: 8px;
}

.btn-propuesta-accion {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-aceptar {
    background: #4CAF50;
    color: white;
}

.btn-aceptar:hover {
    background: #45a049;
    transform: translateY(-1px);
}

.btn-rechazar {
    background: #f44336;
    color: white;
}

.btn-rechazar:hover {
    background: #da190b;
    transform: translateY(-1px);
}

.btn-contraoferta {
    background: #FF9800;
    color: white;
}

.btn-contraoferta:hover {
    background: #F57C00;
    transform: translateY(-1px);
}

.btn-cancelar {
    background: rgba(255,255,255,0.2);
    color: white;
}

.btn-cancelar:hover {
    background: rgba(255,255,255,0.3);
}

/* Mensaje de propuesta en el chat */
.propuesta-intercambio {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 12px;
    padding: 16px;
    margin: 8px 0;
    max-width: 100%;
}

.propuesta-header {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #667eea;
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.propuesta-header i {
    font-size: 18px;
}

.propuesta-body {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 15px;
    align-items: center;
    margin-bottom: 12px;
}

.producto-card {
    background: white;
    border-radius: 10px;
    padding: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s;
}

.producto-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.producto-label {
    font-size: 11px;
    font-weight: 600;
    color: #667eea;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.producto-imagen {
    width: 100%;
    height: 120px;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 8px;
    background: #f5f5f5;
}

.producto-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.producto-nombre {
    font-size: 13px;
    font-weight: 600;
    color: #2d3748;
    text-align: center;
    line-height: 1.3;
}

.intercambio-icon {
    color: #667eea;
    font-size: 24px;
    animation: pulseIcon 2s infinite;
}

@keyframes pulseIcon {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.propuesta-mensaje {
    background: rgba(102, 126, 234, 0.1);
    border-left: 3px solid #667eea;
    padding: 10px 12px;
    border-radius: 6px;
    font-size: 13px;
    color: #4a5568;
    display: flex;
    align-items: start;
    gap: 8px;
}

.propuesta-mensaje i {
    color: #667eea;
    margin-top: 2px;
}

/* Burbuja de mensaje para propuestas */
.message-bubble.propuesta-bubble {
    background: transparent !important;
    padding: 0 !important;
    max-width: 450px;
}

/* Estilos para propuestas antiguas (formato texto) */
.propuesta-antigua {
    background: linear-gradient(135deg, #e8eaf6 0%, #c5cae9 100%);
}

.propuesta-contenido-antiguo {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin: 10px 0;
    font-size: 13px;
    line-height: 1.6;
    color: #2d3748;
    white-space: pre-wrap;
}

.propuesta-nota {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #666;
    font-size: 11px;
    opacity: 0.8;
    margin-top: 8px;
}

.propuesta-nota i {
    font-size: 12px;
}

/* Botones de acción en mensajes de propuesta */
.propuesta-acciones {
    display: flex;
    gap: 8px;
    padding: 12px 0 0 0;
    border-top: 1px solid rgba(102, 126, 234, 0.2);
    margin-top: 12px;
    flex-wrap: wrap;
}

.btn-propuesta {
    flex: 1;
    min-width: 90px;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-propuesta i {
    font-size: 14px;
}

.btn-aceptar {
    background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
    color: white;
}

.btn-aceptar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
}

.btn-contraoferta {
    background: linear-gradient(135deg, #ff9800 0%, #fb8c00 100%);
    color: white;
}

.btn-contraoferta:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
}

.btn-rechazar {
    background: linear-gradient(135deg, #f44336 0%, #e53935 100%);
    color: white;
}

.btn-rechazar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
}

.btn-cancelar {
    background: linear-gradient(135deg, #9e9e9e 0%, #757575 100%);
    color: white;
}

.btn-cancelar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(158, 158, 158, 0.4);
}

/* Estados de propuesta */
.propuesta-estado {
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 12px;
}

.estado-aceptada {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    color: #2e7d32;
    border: 2px solid #4caf50;
}

.estado-rechazada {
    background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
    color: #c62828;
    border: 2px solid #f44336;
}

.estado-completada {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    color: #1565c0;
    border: 2px solid #2196f3;
}

.propuesta-estado i {
    font-size: 18px;
}

/* Responsive para botones de propuesta */
@media (max-width: 480px) {
    .propuesta-acciones {
        flex-direction: column;
    }
    
    .btn-propuesta {
        width: 100%;
    }
}

/* ============ ESTILOS PARA COORDINACIÓN DE INTERCAMBIOS ============ */

/* Modal de coordinación */
.modal-coordinacion {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal-coordinacion.show {
    opacity: 1;
}

.modal-coordinacion-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUpFadeIn 0.3s ease;
}

@keyframes slideUpFadeIn {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-coordinacion-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 24px;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-coordinacion-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-cerrar-modal {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.btn-cerrar-modal:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-coordinacion-body {
    padding: 24px;
}

.productos-intercambio-resumen {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #e3e8ee 100%);
    border-radius: 12px;
    margin-bottom: 24px;
}

.producto-resumen {
    flex: 1;
    text-align: center;
}

.producto-resumen img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-bottom: 8px;
}

.producto-resumen p {
    font-size: 13px;
    font-weight: 600;
    color: #2d3748;
    margin: 0;
}

.icono-intercambio {
    padding: 0 20px;
    color: #667eea;
    font-size: 24px;
    animation: pulseIcon 2s infinite;
}

.coordinacion-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #2d3748;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group label i {
    color: #667eea;
}

.form-control {
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-hint {
    font-size: 12px;
    color: #718096;
    font-style: italic;
}

.coordinacion-info {
    background: #fff8e1;
    border-left: 4px solid #ffd54f;
    padding: 12px 16px;
    border-radius: 8px;
    display: flex;
    gap: 12px;
    align-items: start;
    margin-top: 12px;
}

.coordinacion-info i {
    color: #ffa000;
    font-size: 18px;
    margin-top: 2px;
}

.coordinacion-info p {
    margin: 0;
    font-size: 13px;
    color: #5d4037;
    line-height: 1.5;
}

.modal-coordinacion-footer {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn-modal-secundario,
.btn-modal-primario {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-modal-secundario {
    background: #e2e8f0;
    color: #4a5568;
}

.btn-modal-secundario:hover {
    background: #cbd5e0;
}

.btn-modal-primario {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-modal-primario:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

/* Panel de coordinación activa en el banner */
.coordinacion-activa {
    flex-direction: column;
    align-items: stretch !important;
    gap: 16px;
}

.coordinacion-detalles {
    width: 100%;
}

.coordinacion-detalles h4 {
    margin: 0 0 16px 0;
    font-size: 16px;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 8px;
}

.coordinacion-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.info-item {
    background: rgba(255, 255, 255, 0.7);
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-item.info-full {
    grid-column: 1 / -1;
}

.info-item i {
    color: #667eea;
    font-size: 16px;
}

.info-item strong {
    color: #2d3748;
    margin-right: 4px;
}

.confirmacion-estados {
    display: flex;
    gap: 12px;
    margin-top: 12px;
}

.estado-confirmacion {
    flex: 1;
    padding: 12px;
    background: rgba(255, 255, 255, 0.5);
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #718096;
    font-weight: 500;
}

.estado-confirmacion.confirmado {
    background: #e6ffed;
    border-color: #4caf50;
    color: #2e7d32;
}

.estado-confirmacion i {
    font-size: 18px;
}

.estado-confirmacion.confirmado i {
    color: #4caf50;
}

.coordinacion-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-coordinacion {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-confirmar {
    background: #4caf50;
    color: white;
}

.btn-confirmar:hover {
    background: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
}

.btn-proponer-cambio {
    background: #ff9800;
    color: white;
}

.btn-proponer-cambio:hover {
    background: #fb8c00;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
}

.btn-marcar-realizado {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-marcar-realizado:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.intercambio-confirmado,
.esperando-confirmacion {
    width: 100%;
    padding: 16px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
    text-align: center;
}

.intercambio-confirmado {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}

.intercambio-confirmado i {
    font-size: 32px;
    color: #4caf50;
    margin-bottom: 8px;
}

.intercambio-confirmado p {
    margin: 8px 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: #2e7d32;
}

.esperando-confirmacion i {
    font-size: 24px;
    color: #ff9800;
    margin-bottom: 8px;
}

.esperando-confirmacion p {
    margin: 8px 0 0 0;
    font-size: 14px;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .modal-coordinacion-content {
        width: 95%;
        margin: 10px;
    }
    
    .productos-intercambio-resumen {
        flex-direction: column;
        gap: 16px;
    }
    
    .icono-intercambio {
        transform: rotate(90deg);
        padding: 12px 0;
    }
    
    .coordinacion-info-grid {
        grid-template-columns: 1fr;
    }
    
    .confirmacion-estados {
        flex-direction: column;
    }
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
            <div class="contacts-tabs" style="display: flex; border-bottom: 1px solid #e9ecef;">
                <button id="tab-contactos" class="contacts-tab active" style="flex:1; padding: 10px; border:none; background:#f8f9fa; cursor:pointer; font-weight:600;">Contactos</button>
                <button id="tab-solicitudes" class="contacts-tab" style="flex:1; padding: 10px; border:none; background:#f8f9fa; cursor:pointer; font-weight:600;">Solicitudes</button>
            </div>
            <div class="contacts-search" id="contacts-search-wrapper">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" placeholder="Buscar contactos..." id="search-contacts">
                </div>
            </div>
            <div class="contacts-list" id="contacts-list">
                <!-- Los contactos se cargarán dinámicamente aquí -->
            </div>
            <div class="solicitudes-list" id="solicitudes-list" style="display:none; flex:1; overflow-y:auto; padding:10px;">
                <!-- Las solicitudes de amistad se cargarán aquí -->
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
                    <a href="#" class="chat-header-avatar" id="chat-header-avatar-link" title="Ver perfil">
                        <img src="img/usuario.svg" alt="Avatar" id="chat-user-avatar" onerror="this.src='img/usuario.svg'">
                    </a>
                    <div class="chat-header-details">
                        <h3 id="chat-user-name">Usuario</h3>
                        <div class="chat-header-status">
                            <div class="status-indicator offline" id="chat-user-status"></div>
                            <span id="chat-user-status-text">Offline</span>
                        </div>
                    </div>
                </div>
                <div class="chat-header-actions">
                    <button class="chat-header-btn" title="Buscar en conversación" id="search-icon">
                        <i class="fas fa-search" id="search-icon"></i>
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
                    <button class="emoji-btn" id="emoji-btn" title="Emojis">
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

<!-- Emoji Picker -->
<div class="emoji-picker" id="emoji-picker">
    <div class="emoji-picker-header" id="emoji-categories">
        <button class="emoji-category-btn active" data-category="smileys">😊 Caritas</button>
        <button class="emoji-category-btn" data-category="gestures">👋 Gestos</button>
        <button class="emoji-category-btn" data-category="animals">🐶 Animales</button>
        <button class="emoji-category-btn" data-category="food">🍕 Comida</button>
        <button class="emoji-category-btn" data-category="activities">⚽ Actividades</button>
        <button class="emoji-category-btn" data-category="objects">💡 Objetos</button>
        <button class="emoji-category-btn" data-category="symbols">❤️ Símbolos</button>
    </div>
    <div class="emoji-picker-content" id="emoji-content"></div>
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

<!-- Modal para editar mensaje -->
<div id="editMessageModal" class="edit-modal">
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <div class="edit-modal-icon">
                <i class="fas fa-edit"></i>
            </div>
            <h3>Editar mensaje</h3>
            <button class="edit-modal-close" onclick="closeEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="edit-modal-body">
            <textarea 
                id="edit-message-textarea" 
                class="edit-message-input" 
                placeholder="Escribe tu mensaje..."
                rows="4"
            ></textarea>
            <div class="edit-message-counter">
                <span id="edit-char-count">0</span>/2000 caracteres
            </div>
        </div>
        <div class="edit-modal-footer">
            <button class="edit-modal-cancel" onclick="closeEditModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="edit-modal-save" onclick="saveEditedMessage()">
                <i class="fas fa-check"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal para eliminar mensaje -->
<div id="deleteMessageModal" class="delete-message-modal">
    <div class="delete-message-modal-content">
        <div class="delete-message-modal-icon">
            <i class="fas fa-trash-alt"></i>
        </div>
        <h3 id="delete-message-title">¿Eliminar mensaje?</h3>
        <p id="delete-message-description">Esta acción no se puede deshacer.</p>
        <div class="delete-message-preview" id="delete-message-preview">
            <i class="fas fa-comment"></i>
            <span id="delete-message-text"></span>
        </div>
        <div class="delete-message-modal-buttons">
            <button class="delete-message-modal-cancel" onclick="closeDeleteMessageModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="delete-message-modal-confirm" onclick="confirmDeleteMessage()">
                <i class="fas fa-trash"></i> <span id="delete-button-text">Eliminar</span>
            </button>
        </div>
    </div>
</div>

<?php require_once 'config/chat_server.php'; ?>
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<script>
    const CHAT_SERVER_URL = '<?php echo getChatServerUrl(); ?>';
    const CURRENT_USER_ID = '<?php echo $user['id']; ?>';
    const CURRENT_USER_AVATAR = '<?php echo isset($user['avatar_path']) && !empty($user['avatar_path']) ? $user['avatar_path'] : 'img/usuario.svg'; ?>';
    
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
<script>
// Auto-abrir chat si viene un parámetro 'user' en la URL
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('user');
    if (userId) {
        // Esperar a que el sistema de chat esté inicializado
        const waitForChat = setInterval(() => {
            if (typeof window.selectUserById === 'function') {
                clearInterval(waitForChat);
                
                console.log('✅ Sistema de chat listo, buscando usuario...');
                
                // Esperar un poco más para que los contactos se carguen
                setTimeout(() => {
                    // Buscar el contacto en la lista
                    const contactItem = document.querySelector(`.contact-item[data-user-id="${userId}"]`);
                    
                    if (contactItem) {
                        console.log('👤 Usuario encontrado en contactos, abriendo chat...');
                        contactItem.click();
                        
                        // Limpiar la URL sin recargar la página
                        window.history.replaceState({}, document.title, window.location.pathname);
                    } else {
                        console.log('⚠️ Usuario no encontrado en contactos, cargando datos...');
                        
                        // Si no está en los contactos, obtener sus datos de la API
                        fetch(`/api/users.php?solo_amigos=false`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    const user = data.users.find(u => u.id == userId);
                                    
                                    if (user) {
                                        console.log('✅ Datos del usuario obtenidos, abriendo chat...');
                                        
                                        // Verificar si existe la función selectUser
                                        if (typeof window.selectUserById === 'function') {
                                            window.selectUserById(user.id, user.username, user.avatar);
                                        } else {
                                            console.error('❌ Función selectUserById no disponible');
                                        }
                                        
                                        // Limpiar la URL
                                        window.history.replaceState({}, document.title, window.location.pathname);
                                    } else {
                                        console.error('❌ Usuario no encontrado en la API');
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('❌ Error al obtener datos del usuario:', error);
                            });
                    }
                }, 1000); // Esperar 1 segundo para que se carguen los contactos
            }
        }, 100);
        // Timeout de seguridad (10 segundos)
        setTimeout(() => {
            clearInterval(waitForChat);
        }, 10000);
    }
});
</script>
<script src="js/intercambios.js?v=<?php echo time(); ?>"></script>
<script src="js/dropdownmenu.js?v=<?php echo time(); ?>"></script>

</body>
</html>
