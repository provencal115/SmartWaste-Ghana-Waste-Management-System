/**
 * SmartWaste AI Customer Assistant — floating chat widget
 */
(function () {
    'use strict';

    const root = document.getElementById('sw-chatbot-root');
    if (!root) return;

    const STORAGE_KEY = 'sw_chat_session';
    const SOUND_KEY = 'sw_chat_sound';
    const baseUrl = window.BASE_URL || '';
    const contactUrl = root.dataset.contact || (baseUrl + 'contact');

    let sessionId = sessionStorage.getItem(STORAGE_KEY);
    if (!sessionId) {
        sessionId = 'sw_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
        sessionStorage.setItem(STORAGE_KEY, sessionId);
    }

    let csrfToken = root.dataset.csrf || '';
    let assistantName = root.dataset.assistant || 'SmartWaste Assistant';
    let soundEnabled = localStorage.getItem(SOUND_KEY) === '1';
    let initialized = false;
    let sending = false;

    const els = {
        launcher: root.querySelector('.sw-chat-launcher'),
        panel: root.querySelector('.sw-chat-panel'),
        messages: root.querySelector('.sw-chat-messages'),
        typing: root.querySelector('.sw-chat-typing'),
        suggestions: root.querySelector('.sw-chat-suggestions'),
        escalation: root.querySelector('.sw-chat-escalation'),
        input: root.querySelector('.sw-chat-input'),
        send: root.querySelector('.sw-chat-send'),
        close: root.querySelector('.sw-chat-close'),
        sound: root.querySelector('.sw-chat-sound'),
        headerTitle: root.querySelector('.sw-chat-header-info h6'),
        launcherLabel: root.querySelector('.sw-chat-launcher-label'),
    };

    function formatTime(dateStr) {
        const d = dateStr ? new Date(dateStr.replace(' ', 'T')) : new Date();
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function linkify(text) {
        const escaped = escapeHtml(text);
        return escaped
            .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>')
            .replace(/\n/g, '<br>');
    }

    function appendMessage(role, text, time) {
        const wrap = document.createElement('div');
        wrap.className = 'sw-chat-msg ' + role;
        wrap.innerHTML =
            '<div class="sw-chat-bubble">' +
            linkify(text) +
            '<span class="sw-chat-time">' + formatTime(time) + '</span>' +
            '</div>';
        els.messages.appendChild(wrap);
        scrollToBottom();
    }

    function scrollToBottom() {
        requestAnimationFrame(function () {
            els.messages.scrollTop = els.messages.scrollHeight;
        });
    }

    function showTyping(show) {
        els.typing.classList.toggle('is-visible', show);
        if (show) scrollToBottom();
    }

    function showEscalation(show) {
        if (els.escalation) {
            els.escalation.hidden = !show;
        }
    }

    function playNotificationSound() {
        if (!soundEnabled) return;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.value = 0.04;
            osc.start();
            osc.stop(ctx.currentTime + 0.12);
        } catch (e) { /* optional */ }
    }

    function renderSuggestions(items) {
        els.suggestions.innerHTML = '';
        (items || []).forEach(function (item) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sw-chat-chip';
            btn.textContent = item.label;
            btn.addEventListener('click', function () {
                sendMessage(item.query);
            });
            els.suggestions.appendChild(btn);
        });
    }

    function togglePanel(open) {
        const isOpen = open !== undefined ? open : !els.panel.classList.contains('is-open');
        els.panel.classList.toggle('is-open', isOpen);
        els.launcher.classList.toggle('is-open', isOpen);

        if (isOpen) {
            els.launcher.innerHTML = '<span class="sw-chat-launcher-inner"><i class="fa-solid fa-chevron-down"></i></span>';
        } else {
            els.launcher.innerHTML =
                '<span class="sw-chat-launcher-inner">' +
                '<i class="fa-solid fa-robot"></i>' +
                '<span class="sw-chat-launcher-label">' + escapeHtml(assistantName) + '</span>' +
                '</span>';
        }

        if (isOpen && !initialized) {
            initChat();
        }
        if (isOpen) {
            els.input.focus();
        }
    }

    async function initChat() {
        try {
            const res = await fetch(
                baseUrl + 'api/chatbot/init&session_id=' + encodeURIComponent(sessionId)
            );
            const data = await res.json();
            if (!data.success) {
                if (data.disabled) {
                    root.remove();
                }
                return;
            }

            if (data.csrf) csrfToken = data.csrf;
            if (data.assistant_name) {
                assistantName = data.assistant_name;
                if (els.headerTitle) els.headerTitle.textContent = assistantName;
            }
            initialized = true;

            els.messages.innerHTML = '';
            showEscalation(false);
            if (data.history && data.history.length) {
                data.history.forEach(function (msg) {
                    appendMessage(msg.role, msg.text, msg.time);
                });
            } else if (data.welcome) {
                appendMessage('bot', data.welcome, new Date().toISOString());
            }

            renderSuggestions(data.suggestions);
        } catch (e) {
            appendMessage('bot', 'Welcome! Ask me about collections, payments, bins, or support.', new Date().toISOString());
        }
    }

    async function sendMessage(text) {
        const message = (text || els.input.value).trim();
        if (!message || sending) return;

        sending = true;
        els.send.disabled = true;
        els.input.value = '';
        showEscalation(false);

        appendMessage('user', message, new Date().toISOString());
        showTyping(true);

        try {
            const res = await fetch(baseUrl + 'api/chatbot/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                    message: message,
                    session_id: sessionId,
                    csrf_token: csrfToken,
                }),
            });

            const data = await res.json();

            if (data.success && data.response) {
                await delay(400 + Math.random() * 400);
                showTyping(false);
                appendMessage('bot', data.response, data.time || new Date().toISOString());
                if (data.escalate) {
                    showEscalation(true);
                    if (els.escalation) {
                        const link = els.escalation.querySelector('.sw-chat-support-btn');
                        if (link && data.contact_url) {
                            link.href = data.contact_url;
                        }
                    }
                }
                playNotificationSound();
            } else {
                showTyping(false);
                appendMessage('bot', data.message || 'Sorry, something went wrong. Please try again.', new Date().toISOString());
            }
        } catch (e) {
            showTyping(false);
            appendMessage('bot', 'Connection error. Please check your network and try again.', new Date().toISOString());
        }

        sending = false;
        els.send.disabled = false;
        els.input.focus();
    }

    function delay(ms) {
        return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }

    els.launcher.addEventListener('click', function () { togglePanel(); });
    els.close.addEventListener('click', function () { togglePanel(false); });

    els.send.addEventListener('click', function () { sendMessage(); });

    els.input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    if (els.sound) {
        els.sound.classList.toggle('is-active', soundEnabled);
        els.sound.addEventListener('click', function () {
            soundEnabled = !soundEnabled;
            localStorage.setItem(SOUND_KEY, soundEnabled ? '1' : '0');
            els.sound.classList.toggle('is-active', soundEnabled);
            els.sound.innerHTML = soundEnabled
                ? '<i class="fa-solid fa-volume-high"></i>'
                : '<i class="fa-solid fa-volume-xmark"></i>';
        });
    }
})();
