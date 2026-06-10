/* Chat PWA - lógica del cliente */
(() => {
    'use strict';

    const API = 'api.php';
    const app = document.getElementById('app');

    // Pantallas
    const authScreen = document.getElementById('auth');
    const chatScreen = document.getElementById('chat');

    // Auth
    const tabs = document.querySelectorAll('.tab');
    const authForm = document.getElementById('auth-form');
    const authError = document.getElementById('auth-error');
    const authSubmit = document.getElementById('auth-submit');
    let mode = 'login';

    // Chat
    const messagesEl = document.getElementById('messages');
    const sendForm = document.getElementById('send-form');
    const msgInput = document.getElementById('msg-input');
    const whoEl = document.getElementById('who');
    const logoutBtn = document.getElementById('logout');

    let lastId = 0;
    let pollTimer = null;
    let pollInterval = 8000; // 8s base — seguro para hosting compartido
    let pollErrors = 0;
    let me = app.dataset.username || '';

    // ---------- Helpers de red ----------
    async function api(action, data, method = 'POST') {
        const opts = { method, headers: {} };
        if (method === 'POST') {
            opts.body = new URLSearchParams(data || {});
        }
        const url = method === 'GET' && data
            ? `${API}?action=${action}&${new URLSearchParams(data)}`
            : `${API}?action=${action}`;
        const res = await fetch(url, opts);
        let json;
        try { json = await res.json(); } catch { json = {}; }
        if (!res.ok) throw new Error(json.error || 'Error de red');
        return json;
    }

    // ---------- Auth UI ----------
    tabs.forEach(t => t.addEventListener('click', () => {
        tabs.forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        mode = t.dataset.tab;
        authSubmit.textContent = mode === 'login' ? 'Entrar' : 'Crear cuenta';
        authError.textContent = '';
    }));

    authForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        authError.textContent = '';
        authSubmit.disabled = true;
        const fd = new FormData(authForm);
        try {
            const r = await api(mode, {
                username: fd.get('username'),
                password: fd.get('password'),
            });
            me = r.user.username;
            startChat();
        } catch (err) {
            authError.textContent = err.message;
        } finally {
            authSubmit.disabled = false;
        }
    });

    // ---------- Chat ----------
    function startChat() {
        app.dataset.logged = '1';
        authScreen.classList.add('hidden');
        chatScreen.classList.remove('hidden');
        whoEl.textContent = '@' + me;
        messagesEl.innerHTML = '';
        lastId = 0;
        loadMessages(true);
        stopPolling();
        pollErrors = 0;
        pollInterval = 8000;
        schedulePoll();
        msgInput.focus();
    }

    function stopPolling() {
        if (pollTimer) clearTimeout(pollTimer);
        pollTimer = null;
    }

    function schedulePoll() {
        stopPolling();
        pollTimer = setTimeout(async () => {
            await loadMessages();
            schedulePoll();
        }, pollInterval);
    }

    function isNearBottom() {
        return messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 120;
    }

    function fmtTime(iso) {
        // El backend devuelve "YYYY-MM-DD HH:MM:SS" (UTC en SQLite)
        const d = new Date(iso.replace(' ', 'T') + (iso.includes('T') ? '' : 'Z'));
        if (isNaN(d)) return '';
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    async function loadMessages(initial = false) {
        try {
            const r = await api('messages', { since: lastId }, 'GET');
            if (!r.messages.length) return;
            const stick = initial || isNearBottom();
            for (const m of r.messages) {
                appendMessage(m);
                lastId = Math.max(lastId, m.id);
            }
            if (stick) messagesEl.scrollTop = messagesEl.scrollHeight;
        } catch (err) {
            if (String(err.message).includes('No autenticado')) {
                stopPolling();
                showAuth();
                return;
            }
            // Backoff exponencial ante errores (rate limit, red, etc.)
            pollErrors++;
            pollInterval = Math.min(8000 * Math.pow(2, pollErrors), 60000);
            return;
        }
        // Éxito: volver al intervalo base
        pollErrors = 0;
        pollInterval = 8000;
    }

    function appendMessage(m) {
        const el = document.createElement('div');
        el.className = 'msg ' + (m.mine ? 'mine' : 'other');
        const who = m.mine ? 'Tú' : escapeHtml(m.username);
        el.innerHTML = `<span class="meta">${who} · ${fmtTime(m.created_at)}</span>${escapeHtml(m.body)}`;
        messagesEl.appendChild(el);
    }

    sendForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = msgInput.value.trim();
        if (!body) return;
        msgInput.value = '';
        try {
            await api('send', { body });
            await loadMessages();
            messagesEl.scrollTop = messagesEl.scrollHeight;
        } catch (err) {
            msgInput.value = body; // restaurar si falla
            alert(err.message);
        }
    });

    logoutBtn.addEventListener('click', async () => {
        stopPolling();
        try { await api('logout'); } catch {}
        showAuth();
    });

    function showAuth() {
        app.dataset.logged = '0';
        chatScreen.classList.add('hidden');
        authScreen.classList.remove('hidden');
        authForm.reset();
    }

    // ---------- Arranque ----------
    if (app.dataset.logged === '1') {
        startChat();
    }
})();
