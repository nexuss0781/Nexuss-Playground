/**
 * Nexus Pro — Core Engine
 * Auth · Chat · Persistence · Streaming · Branching
 * Powered by Puter.js | InfinityFree-deployable
 */

/* ============================================================
   CONFIGURATION
   ============================================================ */
const CONFIG = {
    models: {
        claude: { name: 'Claude', fullName: 'Claude Sonnet', color: 'var(--claude)', puterModel: 'claude-sonnet-4-20250514', contextWindow: 200000 },
        gemini: { name: 'Gemini', fullName: 'Gemini Pro', color: 'var(--gemini)', puterModel: 'gemini-2.5-pro', contextWindow: 1000000 },
        gpt:    { name: 'GPT',    fullName: 'GPT-4.1',      color: 'var(--gpt)',    puterModel: 'gpt-4.1',            contextWindow: 128000 }
    },
    dbName: 'NexusProDB',
    dbVersion: 1,
    maxStoredChats: 200,
    autoSaveDelay: 600
};

/* ============================================================
   INDEXEDDB LAYER
   ============================================================ */
const DB = {
    db: null,

    async init() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(CONFIG.dbName, CONFIG.dbVersion);
            req.onerror = () => reject(req.error);
            req.onsuccess = () => { this.db = req.result; resolve(); };
            req.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains('chats')) {
                    const store = db.createObjectStore('chats', { keyPath: 'id' });
                    store.createIndex('updatedAt', 'updatedAt', { unique: false });
                }
                if (!db.objectStoreNames.contains('settings')) {
                    db.createObjectStore('settings', { keyPath: 'key' });
                }
            };
        });
    },

    async saveChat(chat) {
        chat.updatedAt = Date.now();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('chats', 'readwrite');
            const store = tx.objectStore('chats');
            const req = store.put(chat);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    },

    async getChat(id) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('chats', 'readonly');
            const store = tx.objectStore('chats');
            const req = store.get(id);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    },

    async getAllChats() {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('chats', 'readonly');
            const store = tx.objectStore('chats');
            const idx = store.index('updatedAt');
            const req = idx.openCursor(null, 'prev');
            const chats = [];
            req.onsuccess = (e) => {
                const cursor = e.target.result;
                if (cursor && chats.length < CONFIG.maxStoredChats) {
                    chats.push(cursor.value);
                    cursor.continue();
                } else {
                    resolve(chats);
                }
            };
            req.onerror = () => reject(req.error);
        });
    },

    async deleteChat(id) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('chats', 'readwrite');
            const store = tx.objectStore('chats');
            const req = store.delete(id);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    },

    async clearAllChats() {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('chats', 'readwrite');
            const store = tx.objectStore('chats');
            const req = store.clear();
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    },

    async getSetting(key) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('settings', 'readonly');
            const store = tx.objectStore('settings');
            const req = store.get(key);
            req.onsuccess = () => resolve(req.result?.value);
            req.onerror = () => reject(req.error);
        });
    },

    async setSetting(key, value) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('settings', 'readwrite');
            const store = tx.objectStore('settings');
            const req = store.put({ key, value });
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    }
};

/* ============================================================
   AUTHENTICATION
   ============================================================ */
const NexusAuth = {
    user: null,

    async init() {
        try {
            const user = await puter.auth.getUser();
            if (user) { this.user = user; showApp(user); }
            else { document.getElementById('authScreen').classList.remove('hidden'); }
        } catch (e) {
            document.getElementById('authScreen').classList.remove('hidden');
        }
    },

    async login() {
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="12" stroke-linecap="round"/></svg> Connecting...`;
        try {
            const user = await puter.auth.signIn();
            this.user = user;
            showApp(user);
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg> Sign In with Puter`;
            UI.toast('Sign in failed: ' + e.message, 'error');
        }
    },

    async logout() {
        await puter.auth.signOut();
        location.reload();
    }
};

function showApp(user) {
    document.getElementById('authScreen').classList.add('hidden');
    document.getElementById('app').classList.remove('hidden');
    document.getElementById('userName').textContent = user.username || 'User';
    document.getElementById('userEmail').textContent = user.email || 'Authenticated';
    document.getElementById('userAvatar').textContent = (user.username || 'U')[0].toUpperCase();
    DataManager.loadSettings();
    ChatEngine.init();
    ToolManager.init();
}

/* ============================================================
   CHAT ENGINE
   ============================================================ */
const ChatEngine = {
    currentModel: 'claude',
    currentChatId: null,
    currentBranchId: 'main',
    isGenerating: false,
    abortController: null,
    autoSaveTimer: null,
    chats: {},

    async init() {
        await DB.init();
        const chats = await DB.getAllChats();
        this.renderSidebar(chats);
        this.startNewChat();
    },

    selectModel(model) {
        this.currentModel = model;
        document.querySelectorAll('.model-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.model === model);
        });
        const cfg = CONFIG.models[model];
        document.getElementById('currentModelBadge').innerHTML = `
            <span class="model-dot" style="background:${cfg.color};box-shadow:0 0 6px ${cfg.color}"></span>
            <span id="modelBadgeText">${cfg.fullName}</span>
        `;
        // Update active chat model
        const chat = this.chats[this.currentChatId];
        if (chat) { chat.model = model; this.debouncedSave(); }
    },

    startNewChat() {
        this.currentChatId = 'chat_' + Date.now();
        this.currentBranchId = 'main';
        const chat = {
            id: this.currentChatId,
            title: 'New Chat',
            model: this.currentModel,
            createdAt: Date.now(),
            updatedAt: Date.now(),
            messages: [],
            branches: { main: [] }
        };
        this.chats[this.currentChatId] = chat;
        this.renderChat(chat);
        this.updateHeaderBranch('');
        document.getElementById('messageInput').focus();
    },

    async loadChat(id) {
        const chat = await DB.getChat(id);
        if (!chat) return;
        this.chats[id] = chat;
        this.currentChatId = id;
        this.currentBranchId = 'main';
        this.currentModel = chat.model || 'claude';
        this.selectModel(this.currentModel);
        this.renderChat(chat);
        this.updateHeaderBranch('');
        UI.toggleSidebar(false);
    },

    renderChat(chat) {
        document.getElementById('welcomeScreen').classList.add('hidden');
        const list = document.getElementById('messagesList');
        list.innerHTML = '';
        const branch = chat.branches?.[this.currentBranchId] || chat.messages || [];
        branch.forEach((msg, idx) => this.renderMessage(msg, idx));
        this.updateTokenBar(branch);
        this.highlightActiveSidebar(chat.id);
    },

    renderSidebar(chats) {
        const history = document.getElementById('chatHistory');
        history.innerHTML = chats.map(c => {
            const firstUser = (c.messages || []).find(m => m.role === 'user');
            const title = c.title || (firstUser ? firstUser.content.substring(0, 36) + '...' : 'New Chat');
            const hasBranches = Object.keys(c.branches || {}).length > 1;
            return `
                <div class="chat-history-item ${c.id === this.currentChatId ? 'active' : ''}" data-chat-id="${c.id}" onclick="ChatEngine.loadChat('${c.id}')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span class="chat-title">${this.escapeHtml(title)}</span>
                    ${hasBranches ? '<span class="chat-branch-indicator" title="Has branches"></span>' : ''}
                    <span class="chat-meta">${CONFIG.models[c.model]?.name || 'AI'}</span>
                </div>
            `;
        }).join('');
    },

    highlightActiveSidebar(id) {
        document.querySelectorAll('.chat-history-item').forEach(el => {
            el.classList.toggle('active', el.dataset.chatId === id);
        });
    },

    updateHeaderBranch(label) {
        const el = document.getElementById('headerBranch');
        el.textContent = label ? 'Branch: ' + label : '';
    },

    sendQuick(text) {
        const input = document.getElementById('messageInput');
        input.value = text;
        this.sendMessage();
    },

    async sendMessage() {
        const input = document.getElementById('messageInput');
        let text = input.value.trim();
        if (!text || this.isGenerating) return;

        // Append file context if present
        const fileCtx = ToolManager.getFileContext();
        if (fileCtx) {
            text += '\n\n' + fileCtx;
            ToolManager.clearAttachments();
        }

        const chat = this.chats[this.currentChatId];
        if (!chat) return;

        document.getElementById('welcomeScreen').classList.add('hidden');

        // Add user message
        const userMsg = { id: 'msg_' + Date.now(), role: 'user', content: text, timestamp: Date.now() };
        this.appendToBranch(userMsg);
        this.renderMessage(userMsg);
        this.updateChatTitle(text);

        input.value = '';
        input.style.height = 'auto';

        // Prepare tool context
        const toolDescs = ToolManager.getToolDescriptions();
        const toolPrompt = toolDescs.length ? this.buildToolPrompt(toolDescs) : '';

        // Start generation
        const msgId = 'msg_' + Date.now();
        const typingId = 'typing_' + msgId;
        this.showTyping(typingId);
        this.isGenerating = true;
        document.getElementById('sendBtn').disabled = true;
        this.abortController = new AbortController();

        try {
            const cfg = CONFIG.models[this.currentModel];
            const systemPrompt = await DataManager.getSystemPrompt(this.currentModel);

            const messages = [];
            if (systemPrompt) messages.push({ role: 'system', content: systemPrompt });
            if (toolPrompt) messages.push({ role: 'system', content: toolPrompt });
            messages.push(...this.getBranchMessages().map(m => ({ role: m.role, content: m.content })));

            const response = await puter.ai.chat(text, {
                model: cfg.puterModel,
                stream: true
            });

            this.removeTyping(typingId);

            // Create assistant message container
            const assistantMsg = { id: msgId, role: 'assistant', content: '', model: this.currentModel, timestamp: Date.now(), toolsUsed: [] };
            this.appendToBranch(assistantMsg);

            const msgDiv = document.createElement('div');
            msgDiv.className = 'message';
            msgDiv.id = msgId;
            msgDiv.innerHTML = `
                <div class="message-header">
                    <div class="message-avatar assistant" style="color:${cfg.color};border-color:${cfg.color}30;">◈</div>
                    <span class="message-author" style="color:${cfg.color};">${cfg.name}</span>
                    <span class="message-time">Just now</span>
                    <div class="message-actions">
                        <button class="msg-action-btn" onclick="ChatEngine.copyMessage('${msgId}')" title="Copy">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                        <button class="msg-action-btn" onclick="ChatEngine.regenerateMessage('${msgId}')" title="Regenerate">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        </button>
                    </div>
                </div>
                <div class="message-content" id="${msgId}-content"><span class="streaming-cursor"></span></div>
            `;
            document.getElementById('messagesList').appendChild(msgDiv);

            let fullText = '';
            let buffer = '';
            let lastRender = 0;

            if (response && typeof response[Symbol.asyncIterator] === 'function') {
                for await (const part of response) {
                    if (this.abortController.signal.aborted) break;
                    const chunk = part?.text || part?.content || '';
                    fullText += chunk;
                    buffer += chunk;

                    // Throttle DOM updates to ~30fps
                    const now = performance.now();
                    if (now - lastRender > 33) {
                        this.renderMarkdown(msgId + '-content', fullText, true);
                        lastRender = now;
                        buffer = '';
                    }
                }
            } else {
                fullText = response?.message?.content || response?.text || 'No response received.';
            }

            // Detect and execute tool calls
            const toolCalls = this.extractToolCalls(fullText);
            if (toolCalls.length && ToolManager.isActive(toolCalls[0].name)) {
                for (const call of toolCalls) {
                    assistantMsg.toolsUsed.push(call.name);
                    try {
                        const result = await ToolManager.execute(call.name, call.params, msgId);
                        // Inject tool result as system message for context
                        const toolResultMsg = {
                            id: 'msg_' + Date.now(),
                            role: 'system',
                            content: `[Tool result: ${call.name}]\n${JSON.stringify(result, null, 2)}`,
                            timestamp: Date.now(),
                            hidden: true
                        };
                        this.appendToBranch(toolResultMsg);
                    } catch (err) {
                        console.error('Tool error:', err);
                    }
                }
            }

            this.renderMarkdown(msgId + '-content', fullText, false);
            assistantMsg.content = fullText;
            this.debouncedSave();
            this.updateTokenBar(this.getBranchMessages());

        } catch (error) {
            this.removeTyping(typingId);
            if (error.name !== 'AbortError') {
                this.renderError(msgId, error.message || 'Failed to get response.');
            }
        } finally {
            this.isGenerating = false;
            document.getElementById('sendBtn').disabled = false;
            this.abortController = null;
            UI.scrollToBottom();
        }
    },

    stopGeneration() {
        if (this.abortController) {
            this.abortController.abort();
            this.isGenerating = false;
            document.getElementById('sendBtn').disabled = false;
        }
    },

    buildToolPrompt(descs) {
        return `You have access to the following tools. When you need to use a tool, output a JSON object in this exact format (no markdown fences, no extra text):\n{"tool": "tool_name", "params": {}}\n\nAvailable tools:\n` +
            descs.map(d => `- ${d.name}: ${d.description}\n  Parameters: ${JSON.stringify(d.parameters)}`).join('\n') +
            `\n\nAfter receiving tool results, incorporate them naturally into your response.`;
    },

    extractToolCalls(text) {
        const calls = [];
        // Match JSON tool calls
        const regex = /{\s*["']tool["']\s*:\s*["']([^"']+)["']\s*,\s*["']params["']\s*:\s*({[\s\S]*?})\s*}/g;
        let match;
        while ((match = regex.exec(text)) !== null) {
            try {
                const params = JSON.parse(match[2]);
                calls.push({ name: match[1], params });
            } catch { /* invalid JSON */ }
        }
        // Also try simpler format: {"tool":"name","params":{...}}
        const simpleRegex = /{"tool":"([^"]+)","params":({[^}]+})}/g;
        while ((match = simpleRegex.exec(text)) !== null) {
            try {
                const params = JSON.parse(match[2]);
                calls.push({ name: match[1], params });
            } catch { /* invalid JSON */ }
        }
        return calls;
    },

    appendToBranch(msg) {
        const chat = this.chats[this.currentChatId];
        if (!chat) return;
        if (!chat.branches) chat.branches = { main: [] };
        if (!chat.branches[this.currentBranchId]) chat.branches[this.currentBranchId] = [];
        chat.branches[this.currentBranchId].push(msg);
        chat.messages = chat.branches[this.currentBranchId]; // sync for backward compat
    },

    getBranchMessages() {
        const chat = this.chats[this.currentChatId];
        if (!chat) return [];
        return chat.branches?.[this.currentBranchId] || chat.messages || [];
    },

    renderMessage(msg, idx) {
        const list = document.getElementById('messagesList');
        const isUser = msg.role === 'user';
        const cfg = CONFIG.models[msg.model || this.currentModel] || CONFIG.models.claude;
        const isError = msg.role === 'error';

        const div = document.createElement('div');
        div.className = 'message';
        div.id = msg.id || ('msg_' + idx);
        div.innerHTML = `
            <div class="message-header">
                <div class="message-avatar ${isUser ? 'user' : 'assistant'}" ${!isUser ? `style="color:${isError ? 'var(--error)' : cfg.color};border-color:${isError ? 'var(--error)' : cfg.color}30;"` : ''}>
                    ${isUser ? '👤' : '◈'}
                </div>
                <span class="message-author" ${!isUser ? `style="color:${isError ? 'var(--error)' : cfg.color};"` : ''}>
                    ${isUser ? 'You' : (isError ? 'Error' : cfg.name)}
                </span>
                <span class="message-time">${this.formatTime(msg.timestamp)}</span>
                ${isUser ? '' : `
                <div class="message-actions">
                    <button class="msg-action-btn" onclick="ChatEngine.copyMessage('${msg.id || ('msg_' + idx)}')" title="Copy">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                    ${msg.role === 'assistant' ? `
                    <button class="msg-action-btn" onclick="ChatEngine.regenerateMessage('${msg.id || ('msg_' + idx)}')" title="Regenerate">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    </button>
                    ` : ''}
                </div>`}
            </div>
            <div class="message-content" id="${msg.id || ('msg_' + idx)}-content"></div>
        `;
        list.appendChild(div);
        this.renderMarkdown((msg.id || ('msg_' + idx)) + '-content', msg.content, false);
    },

    renderMarkdown(elementId, text, streaming) {
        const el = document.getElementById(elementId);
        if (!el) return;
        // Sanitize basic HTML - marked handles most, but we trust the source (Puter AI)
        let html = marked.parse(text, { breaks: true, gfm: true });
        if (streaming) html += '<span class="streaming-cursor"></span>';
        el.innerHTML = html;

        // Post-process code blocks
        el.querySelectorAll('pre code').forEach(block => {
            const lang = block.className.replace('language-', '');
            const pre = block.parentElement;
            if (!pre.querySelector('.code-header')) {
                const header = document.createElement('div');
                header.className = 'code-header';
                header.innerHTML = `
                    <span>${lang || 'code'}</span>
                    <div class="code-header-actions">
                        <button class="code-header-btn" onclick="ChatEngine.copyCode(this)">Copy</button>
                        <button class="code-header-btn" onclick="ChatEngine.downloadCode(this, '${lang || 'txt'}')">Download</button>
                    </div>
                `;
                pre.className = 'code-block';
                pre.insertBefore(header, block);
            }
        });

        // Highlight
        if (window.hljs) el.querySelectorAll('pre code').forEach(block => hljs.highlightElement(block));

        // KaTeX
        if (window.renderMathInElement) {
            renderMathInElement(el, { delimiters: [{left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}] });
        }
    },

    showTyping(id) {
        const cfg = CONFIG.models[this.currentModel];
        const div = document.createElement('div');
        div.className = 'message';
        div.id = id;
        div.innerHTML = `
            <div class="message-header">
                <div class="message-avatar assistant" style="color:${cfg.color};">◈</div>
                <span class="message-author" style="color:${cfg.color};">${cfg.name}</span>
            </div>
            <div class="message-content">
                <div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>
            </div>
        `;
        document.getElementById('messagesList').appendChild(div);
        UI.scrollToBottom();
    },

    removeTyping(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    },

    renderError(id, message) {
        const list = document.getElementById('messagesList');
        const div = document.createElement('div');
        div.className = 'message';
        div.id = id;
        div.innerHTML = `
            <div class="message-header">
                <div class="message-avatar assistant" style="color:var(--error);border-color:var(--error)30;">◈</div>
                <span class="message-author" style="color:var(--error);">Error</span>
                <span class="message-time">Just now</span>
            </div>
            <div class="message-content">${marked.parse('**Error:** ' + message)}</div>
        `;
        list.appendChild(div);
        UI.scrollToBottom();
    },

    updateChatTitle(firstMessage) {
        const chat = this.chats[this.currentChatId];
        if (chat && chat.title === 'New Chat') {
            chat.title = firstMessage.substring(0, 45) + (firstMessage.length > 45 ? '...' : '');
            this.renderSidebar(Object.values(this.chats));
        }
    },

    updateTokenBar(messages) {
        // Rough estimation: 1 token ≈ 4 chars for English
        const text = messages.map(m => m.content).join('');
        const estimated = Math.ceil(text.length / 4);
        const max = CONFIG.models[this.currentModel].contextWindow;
        const pct = Math.min((estimated / max) * 100, 100);
        document.getElementById('tokenFill').style.width = pct + '%';
        document.getElementById('tokenLabel').textContent = `${(estimated/1000).toFixed(1)}k / ${(max/1000).toFixed(0)}k`;
        // Color warning
        if (pct > 80) document.getElementById('tokenFill').style.background = 'linear-gradient(90deg, var(--error), var(--warning))';
        else if (pct > 50) document.getElementById('tokenFill').style.background = 'linear-gradient(90deg, var(--warning), var(--accent-primary))';
        else document.getElementById('tokenFill').style.background = 'linear-gradient(90deg, var(--accent-primary), var(--accent-secondary))';
    },

    debouncedSave() {
        clearTimeout(this.autoSaveTimer);
        this.autoSaveTimer = setTimeout(() => {
            const chat = this.chats[this.currentChatId];
            if (chat) DB.saveChat(chat);
        }, CONFIG.autoSaveDelay);
    },

    copyMessage(msgId) {
        const chat = this.chats[this.currentChatId];
        const msg = this.getBranchMessages().find(m => m.id === msgId);
        if (msg) {
            navigator.clipboard.writeText(msg.content);
            UI.toast('Copied to clipboard', 'success');
        }
    },

    copyCode(btn) {
        const code = btn.closest('.code-block').querySelector('code');
        navigator.clipboard.writeText(code.textContent);
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = 'Copy', 1500);
    },

    downloadCode(btn, ext) {
        const code = btn.closest('.code-block').querySelector('code').textContent;
        const blob = new Blob([code], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'code.' + ext;
        a.click();
        URL.revokeObjectURL(url);
    },

    async regenerateMessage(msgId) {
        const messages = this.getBranchMessages();
        const idx = messages.findIndex(m => m.id === msgId);
        if (idx <= 0) return;
        // Find preceding user message
        let userIdx = idx - 1;
        while (userIdx >= 0 && messages[userIdx].role !== 'user') userIdx--;
        if (userIdx < 0) return;

        // Create branch
        const chat = this.chats[this.currentChatId];
        const branchId = 'branch_' + Date.now();
        chat.branches[branchId] = messages.slice(0, userIdx + 1);
        this.currentBranchId = branchId;
        this.updateHeaderBranch(branchId.replace('branch_', 'v'));

        // Re-render from branch
        this.renderChat(chat);
        this.debouncedSave();

        // Trigger new response
        const userMsg = messages[userIdx];
        document.getElementById('messageInput').value = userMsg.content;
        this.sendMessage();
    },

    formatTime(ts) {
        if (!ts) return 'Just now';
        const d = new Date(ts);
        const now = new Date();
        const diff = (now - d) / 1000;
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff/60) + 'm ago';
        if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
        return d.toLocaleDateString();
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

/* ============================================================
   UI HELPERS
   ============================================================ */
const UI = {
    sidebarOpen: false,

    handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            ChatEngine.sendMessage();
        }
        if (e.key === 'Escape') {
            this.closeSearch();
            this.closeSettings();
            if (ChatEngine.isGenerating) ChatEngine.stopGeneration();
        }
    },

    autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 160) + 'px';
    },

    handlePaste(e) {
        const items = e.clipboardData?.items;
        if (!items) return;
        for (const item of items) {
            if (item.kind === 'file') {
                const file = item.getAsFile();
                if (file) ToolManager.handleFiles([file]);
            }
        }
    },

    toggleSidebar(force) {
        const sb = document.getElementById('sidebar');
        const ov = document.getElementById('sidebarOverlay');
        this.sidebarOpen = force !== undefined ? force : !this.sidebarOpen;
        sb.classList.toggle('open', this.sidebarOpen);
        ov.classList.toggle('hidden', !this.sidebarOpen);
    },

    openSettings() {
        document.getElementById('settingsModal').classList.add('open');
    },

    closeSettings() {
        document.getElementById('settingsModal').classList.remove('open');
    },

    openSearch() {
        document.getElementById('searchModal').classList.add('open');
        setTimeout(() => document.getElementById('globalSearchInput').focus(), 100);
    },

    closeSearch() {
        document.getElementById('searchModal').classList.remove('open');
        document.getElementById('globalSearchInput').value = '';
        document.getElementById('searchResults').innerHTML = '';
    },

    scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        const anchor = document.getElementById('bottomAnchor');
        if (anchor) anchor.scrollIntoView({ behavior: 'smooth', block: 'end' });
    },

    toast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;
        const icons = { success: '✓', error: '✕', info: 'ℹ' };
        toast.innerHTML = `<span style="font-weight:700">${icons[type]}</span> ${message}`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

/* ============================================================
   DATA MANAGER (Settings, Export, Import)
   ============================================================ */
const DataManager = {
    async loadSettings() {
        const prompts = await DB.getSetting('systemPrompts') || {};
        Object.keys(prompts).forEach(key => {
            const el = document.getElementById('prompt-' + key);
            if (el) el.value = prompts[key];
        });
    },

    async saveSettings() {
        const prompts = {
            claude: document.getElementById('prompt-claude').value.trim(),
            gemini: document.getElementById('prompt-gemini').value.trim(),
            gpt: document.getElementById('prompt-gpt').value.trim()
        };
        await DB.setSetting('systemPrompts', prompts);
        UI.closeSettings();
        UI.toast('Settings saved', 'success');
    },

    async getSystemPrompt(model) {
        const prompts = await DB.getSetting('systemPrompts') || {};
        return prompts[model] || '';
    },

    async exportAll() {
        const chats = await DB.getAllChats();
        const blob = new Blob([JSON.stringify(chats, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'nexus-pro-backup-' + new Date().toISOString().slice(0,10) + '.json';
        a.click();
        URL.revokeObjectURL(url);
        UI.toast('Exported ' + chats.length + ' chats', 'success');
    },

    async importFile(input) {
        const file = input.files[0];
        if (!file) return;
        const text = await file.text();
        try {
            const chats = JSON.parse(text);
            if (!Array.isArray(chats)) throw new Error('Invalid format');
            for (const chat of chats) {
                if (chat.id) await DB.saveChat(chat);
            }
            UI.toast('Imported ' + chats.length + ' chats', 'success');
            ChatEngine.init();
        } catch (e) {
            UI.toast('Import failed: ' + e.message, 'error');
        }
        input.value = '';
    },

    async clearAll() {
        if (!confirm('Permanently delete ALL conversations? This cannot be undone.')) return;
        await DB.clearAllChats();
        ChatEngine.chats = {};
        ChatEngine.startNewChat();
        UI.toast('All data cleared', 'info');
    }
};

/* ============================================================
   INITIALIZATION
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    marked.setOptions({ breaks: true, gfm: true, headerIds: false });

    // Close modals on outside click
    document.getElementById('settingsModal')?.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) UI.closeSettings();
    });
    document.getElementById('searchModal')?.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) UI.closeSearch();
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            UI.openSearch();
        }
    });

    NexusAuth.init();
});
