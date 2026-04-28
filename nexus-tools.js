/**
 * Nexus Pro — Tool Framework & Implementations
 * Powered by Puter.js | InfinityFree-deployable
 * 
 * Tools:
 *  - web_search: Search the web via PHP proxy or direct fetch
 *  - code_analyze: Parse code structure (functions, classes, imports)
 *  - file_context: Ingest files into conversation context
 */

const ToolManager = {
    registry: {},
    active: new Set(),
    fileAttachments: [],

    init() {
        this.register('web_search', WebSearchTool);
        this.register('code_analyze', CodeAnalyzeTool);
        this.register('file_context', FileContextTool);
        this.setupDragDrop();
    },

    register(name, toolClass) {
        this.registry[name] = new toolClass();
    },

    toggle(name) {
        const btn = document.getElementById('toolToggle' + name.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(''));
        const badge = document.getElementById('badge' + name.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(''));

        if (this.active.has(name)) {
            this.active.delete(name);
            if (btn) btn.classList.remove('active');
            if (badge) { badge.textContent = 'OFF'; badge.style.background = 'rgba(255,255,255,0.06)'; badge.style.color = 'var(--text-muted)'; }
        } else {
            this.active.add(name);
            if (btn) btn.classList.add('active');
            if (badge) { badge.textContent = 'ON'; badge.style.background = 'var(--accent-primary)'; badge.style.color = 'white'; }
        }
        this.updateStatusText();
    },

    updateStatusText() {
        const el = document.getElementById('toolStatusText');
        if (!el) return;
        const names = Array.from(this.active).map(n => {
            if (n === 'web_search') return 'Web Search';
            if (n === 'code_analyze') return 'Code Intel';
            if (n === 'file_context') return 'File Context';
            return n;
        });
        el.textContent = names.length ? names.join(' · ') + ' active' : 'No tools active';
    },

    isActive(name) {
        return this.active.has(name);
    },

    async execute(name, params, messageId) {
        const tool = this.registry[name];
        if (!tool) throw new Error('Unknown tool: ' + name);
        return await tool.run(params, messageId);
    },

    getToolDescriptions() {
        return Array.from(this.active).map(name => {
            const tool = this.registry[name];
            return tool ? tool.getDescription() : null;
        }).filter(Boolean);
    },

    // File handling
    handleFiles(files) {
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.fileAttachments.push({ name: file.name, content: e.target.result, size: file.size });
                this.renderAttachments();
                UI.toast('File attached: ' + file.name, 'success');
            };
            reader.readAsText(file);
        });
        document.getElementById('fileInput').value = '';
    },

    removeAttachment(index) {
        this.fileAttachments.splice(index, 1);
        this.renderAttachments();
    },

    renderAttachments() {
        const container = document.getElementById('inputAttachments');
        if (!container) return;
        container.innerHTML = this.fileAttachments.map((f, i) => `
            <span class="input-attachment">
                <span class="file-ext">${f.name.split('.').pop()}</span>
                ${f.name}
                <span class="remove-file" onclick="ToolManager.removeAttachment(${i})">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </span>
            </span>
        `).join('');
    },

    clearAttachments() {
        this.fileAttachments = [];
        this.renderAttachments();
    },

    setupDragDrop() {
        const dropZone = document.getElementById('dropZone');
        const container = document.getElementById('messagesContainer');
        if (!container) return;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            container.addEventListener(eventName, (e) => {
                e.preventDefault(); e.stopPropagation();
            }, false);
        });

        container.addEventListener('dragenter', () => dropZone?.classList.add('drag-over'));
        container.addEventListener('dragleave', (e) => {
            if (e.relatedTarget && !container.contains(e.relatedTarget)) dropZone?.classList.remove('drag-over');
        });
        container.addEventListener('drop', (e) => {
            dropZone?.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length) this.handleFiles(files);
        });
    },

    getFileContext() {
        if (!this.fileAttachments.length) return '';
        return this.fileAttachments.map(f =>
            `--- FILE: ${f.name} ---\n${f.content}\n--- END FILE ---`
        ).join('\n\n');
    }
};

/* ============================================================
   WEB SEARCH TOOL
   ============================================================ */
class WebSearchTool {
    getDescription() {
        return {
            name: 'web_search',
            description: 'Search the web for current information, news, documentation, or facts. Use when the user asks about recent events, specific facts, or current data.',
            parameters: {
                type: 'object',
                properties: {
                    query: { type: 'string', description: 'The search query string' }
                },
                required: ['query']
            }
        };
    }

    async run(params, messageId) {
        const query = params.query || params;
        this.renderCard(messageId, 'search', 'Web Search', query, 'running');

        try {
            // Try PHP proxy first (InfinityFree-compatible)
            let results = await this.searchViaProxy(query);
            if (!results || !results.length) {
                // Fallback: try direct DuckDuckGo scraping (may fail due to CORS)
                results = await this.searchDirect(query);
            }
            if (!results || !results.length) {
                results = [{ title: 'No results found', snippet: 'The search returned no results. Try a different query.', url: '#' }];
            }

            this.renderCard(messageId, 'search', 'Web Search', query, 'done', results);
            return { query, results };
        } catch (err) {
            this.renderCard(messageId, 'search', 'Web Search', query, 'error', [{ title: 'Error', snippet: err.message, url: '#' }]);
            throw err;
        }
    }

    async searchViaProxy(query) {
        const resp = await fetch('api/search-proxy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'q=' + encodeURIComponent(query)
        });
        if (!resp.ok) return null;
        const data = await resp.json();
        return data.results || null;
    }

    async searchDirect(query) {
        // Client-side fallback using DuckDuckGo lite (CORS-dependent, often blocked)
        try {
            const resp = await fetch('https://duckduckgo.com/html/?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'text/html' }
            });
            const html = await resp.text();
            return this.parseDuckDuckGo(html);
        } catch { return null; }
    }

    parseDuckDuckGo(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const results = [];
        const items = doc.querySelectorAll('.result');
        items.forEach((item, idx) => {
            if (idx >= 5) return;
            const titleEl = item.querySelector('.result__a');
            const snippetEl = item.querySelector('.result__snippet');
            if (titleEl && snippetEl) {
                results.push({
                    title: titleEl.textContent.trim(),
                    url: titleEl.href,
                    snippet: snippetEl.textContent.trim()
                });
            }
        });
        return results;
    }

    renderCard(messageId, iconType, title, query, status, results = null) {
        const container = document.getElementById('messagesList');
        let card = document.getElementById('tool-card-' + messageId);
        if (!card) {
            card = document.createElement('div');
            card.className = 'tool-card';
            card.id = 'tool-card-' + messageId;
            container.appendChild(card);
        }

        const statusClass = status;
        const statusText = status === 'running' ? 'Searching...' : status === 'done' ? 'Complete' : 'Failed';
        const iconMap = { search: '🔍', code: '🧠', file: '📎' };

        let bodyHtml = '';
        if (results) {
            bodyHtml = '<div class="tool-results">' + results.map(r => `
                <div class="tool-result-item">
                    <div class="result-title">${this.escapeHtml(r.title)}</div>
                    <a class="result-url" href="${this.escapeHtml(r.url)}" target="_blank" rel="noopener">${this.escapeHtml(r.url.substring(0, 80))}</a>
                    <div class="result-snippet">${this.escapeHtml(r.snippet)}</div>
                </div>
            `).join('') + '</div>';
        }

        card.innerHTML = `
            <div class="tool-card-header ${status !== 'running' ? 'expanded' : ''}">
                <div class="tool-icon ${iconType}">${iconMap[iconType]}</div>
                <div>
                    <div class="tool-title">${title}</div>
                    <div class="tool-subtitle">${this.escapeHtml(query)}</div>
                </div>
                <span class="tool-status ${statusClass}">${statusText}</span>
            </div>
            ${bodyHtml ? `<div class="tool-card-body">${bodyHtml}</div>` : ''}
        `;
        UI.scrollToBottom();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

/* ============================================================
   CODE ANALYZE TOOL
   ============================================================ */
class CodeAnalyzeTool {
    getDescription() {
        return {
            name: 'code_analyze',
            description: 'Analyze code structure to extract functions, classes, imports, and dependencies. Use when the user shares code and wants structural insights, refactoring suggestions, or complexity analysis.',
            parameters: {
                type: 'object',
                properties: {
                    code: { type: 'string', description: 'The code to analyze' },
                    language: { type: 'string', description: 'Programming language (optional, auto-detected if omitted)' }
                },
                required: ['code']
            }
        };
    }

    async run(params, messageId) {
        const code = params.code || params;
        const lang = params.language || this.detectLanguage(code);
        this.renderCard(messageId, 'code', 'Code Analysis', lang || 'Auto-detected', 'running');

        const analysis = this.analyze(code, lang);

        this.renderCard(messageId, 'code', 'Code Analysis', lang || 'Auto-detected', 'done', [{
            title: 'Structure Overview',
            snippet: analysis.summary,
            url: '#'
        }]);

        return { language: lang, analysis };
    }

    detectLanguage(code) {
        if (code.includes('import React') || code.includes('jsx')) return 'jsx';
        if (code.includes('def ') && code.includes(':')) return 'python';
        if (code.includes('function') || code.includes('const ') || code.includes('=>')) return 'javascript';
        if (code.includes('<?php')) return 'php';
        if (code.includes('package main') || code.includes('func ')) return 'go';
        if (code.includes('fn ') || code.includes('use ')) return 'rust';
        if (code.includes('#include') || code.includes('int main(')) return 'c/cpp';
        if (code.includes('public class') || code.includes('private static')) return 'java';
        return 'unknown';
    }

    analyze(code, lang) {
        const lines = code.split('\n');
        const functions = [];
        const classes = [];
        const imports = [];
        let complexity = 0;

        // Simple regex-based extraction (no AST parser needed for client-side)
        const funcPatterns = [
            /(?:async\s+)?function\s+(\w+)\s*\(/g,
            /const\s+(\w+)\s*=\s*(?:async\s*)?\([^)]*\)\s*=>/g,
            /def\s+(\w+)\s*\(/g,
            /(\w+)\s*\([^)]*\)\s*{/g,
            /func\s+(\w+)\s*\(/g,
            /public\s+(?:static\s+)?(?:\w+)\s+(\w+)\s*\(/g
        ];

        const classPatterns = [
            /class\s+(\w+)(?:\s*\(|\s*{/g,
            /class\s+(\w+)\s*\(/g,
            /public\s+class\s+(\w+)/g
        ];

        const importPatterns = [
            /(?:import|from|require|use|#include|using)\s+(.+)/g,
            /import\s+{([^}]+)}/g
        ];

        lines.forEach(line => {
            funcPatterns.forEach(p => {
                let m; while ((m = p.exec(line)) !== null) {
                    if (m[1] && !['if','while','for','switch','catch'].includes(m[1])) functions.push(m[1]);
                }
            });
            classPatterns.forEach(p => {
                let m; while ((m = p.exec(line)) !== null) { if (m[1]) classes.push(m[1]); }
            });
            importPatterns.forEach(p => {
                let m; while ((m = p.exec(line)) !== null) { imports.push(m[1]?.trim() || m[0]); }
            });
            if (/\b(if|else|for|while|switch|case|catch|&&|\|\|)\b/.test(line)) complexity++;
        });

        const summary = [
            `Language: ${lang}`,
            `Lines: ${lines.length}`,
            `Functions: ${[...new Set(functions)].join(', ') || 'None detected'}`,
            `Classes: ${[...new Set(classes)].join(', ') || 'None detected'}`,
            `Imports/Dependencies: ${imports.length}`,
            `Estimated Complexity: ${complexity} branches`
        ].join('\n');

        return { functions: [...new Set(functions)], classes: [...new Set(classes)], imports: [...new Set(imports)], lineCount: lines.length, complexity, summary };
    }

    renderCard(messageId, iconType, title, subtitle, status, results = null) {
        const container = document.getElementById('messagesList');
        let card = document.getElementById('tool-card-' + messageId);
        if (!card) {
            card = document.createElement('div');
            card.className = 'tool-card';
            card.id = 'tool-card-' + messageId;
            container.appendChild(card);
        }

        const statusText = status === 'running' ? 'Analyzing...' : status === 'done' ? 'Complete' : 'Failed';
        const iconMap = { search: '🔍', code: '🧠', file: '📎' };

        let bodyHtml = '';
        if (results) {
            bodyHtml = '<div class="tool-results">' + results.map(r => `
                <div class="tool-result-item">
                    <div class="result-title">${r.title}</div>
                    <pre>${r.snippet}</pre>
                </div>
            `).join('') + '</div>';
        }

        card.innerHTML = `
            <div class="tool-card-header ${status !== 'running' ? 'expanded' : ''}">
                <div class="tool-icon ${iconType}">${iconMap[iconType]}</div>
                <div>
                    <div class="tool-title">${title}</div>
                    <div class="tool-subtitle">${subtitle}</div>
                </div>
                <span class="tool-status ${status}">${statusText}</span>
            </div>
            ${bodyHtml ? `<div class="tool-card-body">${bodyHtml}</div>` : ''}
        `;
        UI.scrollToBottom();
    }
}

/* ============================================================
   FILE CONTEXT TOOL
   ============================================================ */
class FileContextTool {
    getDescription() {
        return {
            name: 'file_context',
            description: 'Read and summarize attached files. Use when the user has uploaded files and wants to understand their contents or relationships.',
            parameters: {
                type: 'object',
                properties: {
                    files: { type: 'array', items: { type: 'string' }, description: 'List of filenames to summarize' }
                },
                required: ['files']
            }
        };
    }

    async run(params, messageId) {
        const files = params.files || [];
        const attachments = ToolManager.fileAttachments.filter(f => files.includes(f.name) || files.length === 0);

        this.renderCard(messageId, 'file', 'File Context', `${attachments.length} file(s)`, 'running');

        const summaries = attachments.map(f => ({
            name: f.name,
            size: f.size,
            lines: f.content.split('\n').length,
            preview: f.content.substring(0, 500)
        }));

        this.renderCard(messageId, 'file', 'File Context', `${attachments.length} file(s)`, 'done', summaries.map(s => ({
            title: s.name + ` (${s.lines} lines, ${this.formatBytes(s.size)})`,
            snippet: s.preview + (s.preview.length >= 500 ? '...' : ''),
            url: '#'
        })));

        return { files: summaries };
    }

    formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
        return (bytes/(1024*1024)).toFixed(1) + ' MB';
    }

    renderCard(messageId, iconType, title, subtitle, status, results = null) {
        const container = document.getElementById('messagesList');
        let card = document.getElementById('tool-card-' + messageId);
        if (!card) {
            card = document.createElement('div');
            card.className = 'tool-card';
            card.id = 'tool-card-' + messageId;
            container.appendChild(card);
        }

        const statusText = status === 'running' ? 'Reading...' : status === 'done' ? 'Loaded' : 'Failed';
        const iconMap = { search: '🔍', code: '🧠', file: '📎' };

        let bodyHtml = '';
        if (results) {
            bodyHtml = '<div class="tool-results">' + results.map(r => `
                <div class="tool-result-item">
                    <div class="result-title">${r.title}</div>
                    <div class="result-snippet"><pre style="margin-top:6px;background:#0d0d14;padding:8px;border-radius:6px;font-size:11px">${r.snippet.replace(/</g, '&lt;')}</pre></div>
                </div>
            `).join('') + '</div>';
        }

        card.innerHTML = `
            <div class="tool-card-header ${status !== 'running' ? 'expanded' : ''}">
                <div class="tool-icon ${iconType}">${iconMap[iconType]}</div>
                <div>
                    <div class="tool-title">${title}</div>
                    <div class="tool-subtitle">${subtitle}</div>
                </div>
                <span class="tool-status ${status}">${statusText}</span>
            </div>
            ${bodyHtml ? `<div class="tool-card-body">${bodyHtml}</div>` : ''}
        `;
        UI.scrollToBottom();
    }
}

/* ============================================================
   SEARCH MANAGER (Global Chat Search)
   ============================================================ */
const SearchManager = {
    async query(text) {
        const resultsEl = document.getElementById('searchResults');
        if (!text.trim()) { resultsEl.innerHTML = ''; return; }

        const chats = await DB.getAllChats();
        const matches = [];
        const lower = text.toLowerCase();

        chats.forEach(chat => {
            const titleMatch = (chat.title || '').toLowerCase().includes(lower);
            const msgMatch = (chat.messages || []).some(m => (m.content || '').toLowerCase().includes(lower));
            if (titleMatch || msgMatch) {
                const previewMsg = (chat.messages || []).find(m => (m.content || '').toLowerCase().includes(lower));
                matches.push({
                    id: chat.id,
                    title: chat.title || 'New Chat',
                    preview: previewMsg ? previewMsg.content.substring(0, 120) + '...' : (chat.messages[0]?.content?.substring(0, 120) + '...' || ''),
                    date: new Date(chat.updatedAt).toLocaleDateString(),
                    model: chat.model || 'claude'
                });
            }
        });

        resultsEl.innerHTML = matches.map(m => `
            <div class="search-result-item" onclick="ChatEngine.loadChat('${m.id}'); UI.closeSearch();">
                <div class="search-result-title">${m.title}</div>
                <div class="search-result-preview">${m.preview}</div>
                <div class="search-result-meta">${m.date} · ${m.model}</div>
            </div>
        `).join('') || '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px">No results found</div>';
    }
};
