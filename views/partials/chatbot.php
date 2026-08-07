<?php
$aiSettings = chatbotSettings();
if (empty($aiSettings['enabled'])) {
    return;
}

$user = Auth::user();
$isResident = ($user['role_name'] ?? '') === 'resident';
$assistantName = e(trim($aiSettings['assistant_name'] ?? '') ?: 'SmartWaste Assistant');
?>
<link href="<?= asset('css/chatbot.css') ?>" rel="stylesheet">
<div id="sw-chatbot-root"
     data-csrf="<?= e(Csrf::token()) ?>"
     data-assistant="<?= $assistantName ?>"
     data-resident="<?= $isResident ? '1' : '0' ?>"
     data-contact="<?= e(baseUrl('contact')) ?>">
    <div class="sw-chat-panel" role="dialog" aria-label="<?= $assistantName ?>">
        <div class="sw-chat-header">
            <img src="<?= e(siteLogo()) ?>" alt="SmartWaste" class="sw-chat-header-logo" width="40" height="40" loading="lazy">
            <div class="sw-chat-header-info">
                <h6><?= $assistantName ?></h6>
                <span class="sw-chat-status">
                    <span class="sw-chat-status-dot" aria-hidden="true"></span>
                    Online
                </span>
            </div>
            <div class="sw-chat-header-actions">
                <button type="button" class="sw-chat-icon-btn sw-chat-sound" title="Toggle sound" aria-label="Toggle notification sound">
                    <i class="fa-solid fa-volume-xmark"></i>
                </button>
                <button type="button" class="sw-chat-icon-btn sw-chat-close" title="Close chat" aria-label="Close chat">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <div class="sw-chat-messages" aria-live="polite"></div>
        <div class="sw-chat-typing" aria-hidden="true">
            <div class="sw-chat-typing-bubble">
                <span></span><span></span><span></span>
            </div>
        </div>
        <div class="sw-chat-suggestions"></div>
        <div class="sw-chat-escalation" hidden>
            <a href="<?= e(baseUrl('contact')) ?>" class="sw-chat-support-btn">
                <i class="fa-solid fa-headset"></i> Contact Support
            </a>
        </div>
        <div class="sw-chat-input-area">
            <textarea class="sw-chat-input" rows="1" placeholder="Ask about collections, payments, bins…" maxlength="2000" aria-label="Chat message"></textarea>
            <button type="button" class="sw-chat-send" aria-label="Send message">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
        <div class="sw-chat-powered">SmartWaste AI · Customer assistant</div>
    </div>
    <button type="button" class="sw-chat-launcher" aria-label="Open <?= $assistantName ?>">
        <span class="sw-chat-launcher-inner">
            <i class="fa-solid fa-robot"></i>
            <span class="sw-chat-launcher-label"><?= $assistantName ?></span>
        </span>
    </button>
</div>
<script src="<?= asset('js/chatbot.js') ?>"></script>
