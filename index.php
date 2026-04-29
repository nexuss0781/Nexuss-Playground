<?php
session_start();

// Configuration
const PUTER_BASE_URL = 'https://api.puter.com';
const MINIMAX_API_KEY = 'sk-api-fiKYJEzfZGECNQGiyxWx7X0GHsbNvHzMRfS5tLJlzE3ikhMrggLWwzCndl0XNlCJokXD7j_qRMx7am3UpEtQRzqeQM47PO2_SX-QZh4MJqym5EinBKOwf48';
const HISTORY_DIR = __DIR__ . '/history/';
const PROMPT_FILE = __DIR__ . '/PROMPT.md';

// Available Models
$models = [
    'puter' => [
        ['id' => 'claude-4-7-opus', 'name' => 'Claude 4.7 Opus'],
        ['id' => 'gemini-3-1-pro', 'name' => 'Gemini 3.1 Pro'],
        ['id' => 'gpt-5-5', 'name' => 'GPT-5.5'],
        ['id' => 'deepseek-v4-pro', 'name' => 'DeepSeek-V4 Pro'],
        ['id' => 'glm-5-1', 'name' => 'GLM 5.1'],
    ],
    'minimax' => [
        ['id' => 'minimax-m2-7', 'name' => 'MiniMax-M2.7 (Tag Free)'],
        ['id' => 'minimax-m2-5', 'name' => 'MiniMax-M2.5 (Tag Free)'],
    ]
];

// Check authentication
if (!isset($_SESSION['puter_user']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get system prompt from PROMPT.md
$systemPrompt = "You are a professional coding assistant and creative partner. Help users with coding tasks, file management, and provide thoughtful responses.";
if (file_exists(PROMPT_FILE)) {
    $systemPrompt = file_get_contents(PROMPT_FILE);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Playground Nexuss Frontier</title>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #ec4899;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border: #475569;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1a1a2e 100%);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .app-container {
            display: grid;
            grid-template-columns: 280px 1fr 320px;
            height: 100vh;
            gap: 1px;
            background: var(--border);
        }

        /* Sidebar */
        .sidebar {
            background: var(--bg-dark);
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            overflow-y: auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--bg-card);
            border-radius: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .nav-section {
            margin-top: 1rem;
        }

        .nav-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            padding: 0 0.5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-secondary);
        }

        .nav-item:hover, .nav-item.active {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .nav-item i {
            width: 20px;
            text-align: center;
        }

        /* Main Chat Area */
        .main-area {
            background: var(--bg-dark);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--bg-card);
        }

        .model-selector {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        select {
            background: var(--bg-dark);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            max-width: 85%;
            padding: 1rem 1.25rem;
            border-radius: 16px;
            line-height: 1.6;
        }

        .message.user {
            align-self: flex-end;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-bottom-right-radius: 4px;
        }

        .message.assistant {
            align-self: flex-start;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-bottom-left-radius: 4px;
        }

        .message-content {
            word-wrap: break-word;
        }

        .message-content pre {
            background: var(--bg-dark);
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            margin: 0.75rem 0;
        }

        .message-content code {
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.9em;
        }

        .message-content p {
            margin-bottom: 0.75rem;
        }

        .message-content p:last-child {
            margin-bottom: 0;
        }

        .chat-input-area {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            background: var(--bg-card);
        }

        .input-wrapper {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }

        textarea {
            flex: 1;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.875rem 1rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            resize: none;
            min-height: 56px;
            max-height: 200px;
            font-family: inherit;
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .send-btn {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .send-btn:hover {
            transform: scale(1.05);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* File Explorer Panel */
        .file-panel {
            background: var(--bg-dark);
            padding: 1rem;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .panel-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .file-tree {
            flex: 1;
            overflow-y: auto;
        }

        .file-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .file-item:hover {
            background: var(--bg-hover);
        }

        .file-item.selected {
            background: var(--primary);
        }

        .file-item.folder {
            color: var(--warning);
        }

        .file-item.file {
            color: var(--text-secondary);
        }

        .file-icon {
            width: 20px;
            text-align: center;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            min-width: 400px;
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .form-input {
            width: 100%;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.75rem;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .btn-group {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        /* Loading */
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 1rem;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: var(--text-secondary);
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-dark);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        .logout-btn {
            background: var(--error);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .code-tool-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <div class="logo-icon">🚀</div>
                <span>Nexuss Frontier</span>
            </div>

            <div class="user-info">
                <div class="user-avatar" id="userAvatar"></div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 0.9rem;" id="userName"></div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary);" id="userEmail"></div>
                </div>
                <button class="logout-btn" onclick="location.href='?logout=1'">Logout</button>
            </div>

            <nav class="nav-section">
                <div class="nav-title">Menu</div>
                <div class="nav-item active">
                    <span>💬</span>
                    <span>Chat</span>
                </div>
                <div class="nav-item" onclick="showHistory()">
                    <span>📜</span>
                    <span>History</span>
                </div>
                <div class="nav-item" onclick="openSettings()">
                    <span>⚙️</span>
                    <span>Settings</span>
                </div>
            </nav>

            <nav class="nav-section">
                <div class="nav-title">Models</div>
                <div class="nav-item" data-provider="puter">
                    <span>🔷</span>
                    <span>Puter AI</span>
                </div>
                <div class="nav-item" data-provider="minimax">
                    <span>🟣</span>
                    <span>MiniMax</span>
                </div>
            </nav>
        </aside>

        <!-- Main Chat Area -->
        <main class="main-area">
            <header class="chat-header">
                <div class="model-selector">
                    <label style="color: var(--text-secondary); font-size: 0.9rem;">Provider:</label>
                    <select id="providerSelect" onchange="updateModelOptions()">
                        <option value="puter">Puter</option>
                        <option value="minimax">MiniMax</option>
                    </select>
                    <label style="color: var(--text-secondary); font-size: 0.9rem; margin-left: 1rem;">Model:</label>
                    <select id="modelSelect"></select>
                </div>
                <button class="code-tool-btn" onclick="openCodeTool()">🛠️ Code Tool</button>
            </header>

            <div class="chat-messages" id="chatMessages">
                <div class="message assistant">
                    <div class="message-content">
                        Welcome to Professional Playground Nexuss Frontier! 🚀<br><br>
                        I'm your AI assistant powered by cutting-edge models. I can help you with:<br>
                        • Writing and debugging code<br>
                        • Managing files and folders<br>
                        • Answering questions<br>
                        • Mathematical computations (LaTeX supported)<br><br>
                        How can I assist you today?
                    </div>
                </div>
            </div>

            <div class="chat-input-area">
                <div class="input-wrapper">
                    <textarea 
                        id="messageInput" 
                        placeholder="Type your message... (Shift+Enter for new line)"
                        onkeydown="handleKeyDown(event)"
                        rows="1"
                    ></textarea>
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                        ➤
                    </button>
                </div>
            </div>
        </main>

        <!-- File Explorer Panel -->
        <aside class="file-panel">
            <div class="panel-header">
                <div class="panel-title">📁 File Explorer</div>
                <div class="file-actions">
                    <button class="action-btn" onclick="createFile()" title="New File">📄</button>
                    <button class="action-btn" onclick="createFolder()" title="New Folder">📁</button>
                    <button class="action-btn" onclick="refreshFiles()" title="Refresh">🔄</button>
                </div>
            </div>
            <div class="file-tree" id="fileTree"></div>
        </aside>
    </div>

    <!-- File Operations Modal -->
    <div class="modal-overlay" id="fileModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">File Operation</div>
                <button class="modal-close" onclick="closeModal('fileModal')">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" class="form-input" id="fileName" placeholder="Enter name">
            </div>
            <div class="form-group" id="contentGroup" style="display: none;">
                <label class="form-label">Content</label>
                <textarea class="form-input" id="fileContent" rows="10" placeholder="File content"></textarea>
            </div>
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="closeModal('fileModal')">Cancel</button>
                <button class="btn btn-primary" id="modalAction">Save</button>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal-overlay" id="settingsModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">⚙️ Settings</div>
                <button class="modal-close" onclick="closeModal('settingsModal')">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">System Prompt (from PROMPT.md)</label>
                <textarea class="form-input" id="systemPromptDisplay" rows="6" readonly></textarea>
            </div>
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="closeModal('settingsModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div class="modal-overlay" id="historyModal">
        <div class="modal" style="min-width: 600px;">
            <div class="modal-header">
                <div class="modal-title">📜 Chat History</div>
                <button class="modal-close" onclick="closeModal('historyModal')">&times;</button>
            </div>
            <div id="historyList" style="max-height: 400px; overflow-y: auto;"></div>
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="closeModal('historyModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Initialize
        const userData = <?php echo json_encode($_SESSION['puter_user'] ?? []); ?>;
        const systemPrompt = <?php echo json_encode($systemPrompt); ?>;

        document.getElementById('userName').textContent = userData.username || 'User';
        document.getElementById('userEmail').textContent = userData.email || '';
        document.getElementById('userAvatar').textContent = (userData.username || 'U')[0].toUpperCase();
        document.getElementById('systemPromptDisplay').value = systemPrompt;

        // Model options
        const models = <?php echo json_encode($models); ?>;

        function updateModelOptions() {
            const provider = document.getElementById('providerSelect').value;
            const modelSelect = document.getElementById('modelSelect');
            modelSelect.innerHTML = '';
            
            models[provider].forEach(model => {
                const option = document.createElement('option');
                option.value = model.id;
                option.textContent = model.name;
                modelSelect.appendChild(option);
            });
        }

        updateModelOptions();

        // Chat functionality
        let conversationHistory = [];

        function handleKeyDown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
        }

        document.getElementById('messageInput').addEventListener('input', function() {
            autoResize(this);
        });

        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendBtn');
            const message = input.value.trim();

            if (!message) return;

            // Add user message
            addMessage(message, 'user');
            conversationHistory.push({ role: 'user', content: message });
            input.value = '';
            autoResize(input);

            // Show loading
            sendBtn.disabled = true;
            const loadingId = showLoading();

            try {
                const provider = document.getElementById('providerSelect').value;
                const model = document.getElementById('modelSelect').value;

                const response = await fetch('api/chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: message,
                        provider: provider,
                        model: model,
                        history: conversationHistory,
                        system_prompt: systemPrompt
                    })
                });

                const data = await response.json();
                removeLoading(loadingId);

                if (data.success) {
                    addMessage(data.response, 'assistant');
                    conversationHistory.push({ role: 'assistant', content: data.response });
                    
                    // Save to history
                    saveToHistory(message, data.response);
                } else {
                    addMessage('Error: ' + data.error, 'assistant');
                }
            } catch (error) {
                removeLoading(loadingId);
                addMessage('Error: ' + error.message, 'assistant');
            }

            sendBtn.disabled = false;
        }

        function addMessage(content, role) {
            const messagesDiv = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${role}`;
            
            if (role === 'assistant') {
                // Parse markdown
                const parsed = marked.parse(content);
                messageDiv.innerHTML = `<div class="message-content">${parsed}</div>`;
                
                // Highlight code
                messageDiv.querySelectorAll('pre code').forEach((block) => {
                    hljs.highlightElement(block);
                });
                
                // Render math
                if (window.MathJax) {
                    MathJax.typesetPromise([messageDiv]);
                }
            } else {
                messageDiv.innerHTML = `<div class="message-content">${escapeHtml(content)}</div>`;
            }
            
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showLoading() {
            const messagesDiv = document.getElementById('chatMessages');
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'message assistant';
            loadingDiv.id = 'loading-' + Date.now();
            loadingDiv.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
            messagesDiv.appendChild(loadingDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            return loadingDiv.id;
        }

        function removeLoading(id) {
            const loading = document.getElementById(id);
            if (loading) loading.remove();
        }

        // File operations
        let currentOperation = null;
        let selectedFile = null;

        async function refreshFiles() {
            try {
                const response = await fetch('api/files.php?action=list');
                const data = await response.json();
                
                const fileTree = document.getElementById('fileTree');
                fileTree.innerHTML = '';
                
                if (data.success) {
                    data.files.forEach(file => {
                        const item = createFileItem(file);
                        fileTree.appendChild(item);
                    });
                }
            } catch (error) {
                console.error('Error loading files:', error);
            }
        }

        function createFileItem(file) {
            const div = document.createElement('div');
            div.className = `file-item ${file.type}`;
            div.dataset.path = file.path;
            
            const icon = file.type === 'folder' ? '📁' : getFileIcon(file.name);
            div.innerHTML = `<span class="file-icon">${icon}</span><span>${file.name}</span>`;
            
            div.onclick = () => handleFileClick(file);
            
            return div;
        }

        function getFileIcon(name) {
            const ext = name.split('.').pop().toLowerCase();
            const icons = {
                'php': '🐘', 'js': '📜', 'py': '🐍', 'html': '🌐',
                'css': '🎨', 'json': '📋', 'md': '📝', 'txt': '📄'
            };
            return icons[ext] || '📄';
        }

        function handleFileClick(file) {
            selectedFile = file;
            document.querySelectorAll('.file-item').forEach(el => el.classList.remove('selected'));
            event.target.closest('.file-item').classList.add('selected');
            
            if (file.type === 'file') {
                viewFile(file);
            }
        }

        async function viewFile(file) {
            try {
                const response = await fetch(`api/files.php?action=read&path=${encodeURIComponent(file.path)}`);
                const data = await response.json();
                
                if (data.success) {
                    openModal('fileModal');
                    document.getElementById('modalTitle').textContent = `View: ${file.name}`;
                    document.getElementById('fileName').value = file.name;
                    document.getElementById('fileName').disabled = true;
                    document.getElementById('contentGroup').style.display = 'block';
                    document.getElementById('fileContent').value = data.content;
                    document.getElementById('fileContent').disabled = true;
                    document.getElementById('modalAction').textContent = 'Close';
                    document.getElementById('modalAction').onclick = () => closeModal('fileModal');
                    currentOperation = 'view';
                }
            } catch (error) {
                alert('Error loading file: ' + error.message);
            }
        }

        function createFile() {
            openModal('fileModal');
            document.getElementById('modalTitle').textContent = 'New File';
            document.getElementById('fileName').value = '';
            document.getElementById('fileName').disabled = false;
            document.getElementById('contentGroup').style.display = 'block';
            document.getElementById('fileContent').value = '';
            document.getElementById('fileContent').disabled = false;
            document.getElementById('modalAction').textContent = 'Create';
            document.getElementById('modalAction').onclick = executeCreateFile;
            currentOperation = 'create_file';
        }

        async function executeCreateFile() {
            const name = document.getElementById('fileName').value.trim();
            const content = document.getElementById('fileContent').value;
            
            if (!name) {
                alert('Please enter a filename');
                return;
            }
            
            try {
                const response = await fetch('api/files.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: name, content: content, type: 'file' })
                });
                
                const data = await response.json();
                if (data.success) {
                    closeModal('fileModal');
                    refreshFiles();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        function createFolder() {
            openModal('fileModal');
            document.getElementById('modalTitle').textContent = 'New Folder';
            document.getElementById('fileName').value = '';
            document.getElementById('fileName').disabled = false;
            document.getElementById('contentGroup').style.display = 'none';
            document.getElementById('modalAction').textContent = 'Create';
            document.getElementById('modalAction').onclick = executeCreateFolder;
            currentOperation = 'create_folder';
        }

        async function executeCreateFolder() {
            const name = document.getElementById('fileName').value.trim();
            
            if (!name) {
                alert('Please enter a folder name');
                return;
            }
            
            try {
                const response = await fetch('api/files.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: name, type: 'folder' })
                });
                
                const data = await response.json();
                if (data.success) {
                    closeModal('fileModal');
                    refreshFiles();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        // History
        async function showHistory() {
            openModal('historyModal');
            try {
                const response = await fetch('api/history.php?action=list');
                const data = await response.json();
                
                const historyList = document.getElementById('historyList');
                historyList.innerHTML = '';
                
                if (data.success && data.history.length > 0) {
                    data.history.forEach(item => {
                        const div = document.createElement('div');
                        div.style.padding = '1rem';
                        div.style.borderBottom = '1px solid var(--border)';
                        div.style.cursor = 'pointer';
                        div.innerHTML = `
                            <div style="font-weight: 600; margin-bottom: 0.5rem;">${escapeHtml(item.user_message.substring(0, 100))}${item.user_message.length > 100 ? '...' : ''}</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">${new Date(item.timestamp).toLocaleString()}</div>
                        `;
                        div.onclick = () => loadHistory(item);
                        historyList.appendChild(div);
                    });
                } else {
                    historyList.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-secondary);">No history yet</div>';
                }
            } catch (error) {
                console.error('Error loading history:', error);
            }
        }

        function loadHistory(item) {
            document.getElementById('chatMessages').innerHTML = '';
            addMessage(item.user_message, 'user');
            addMessage(item.assistant_message, 'assistant');
            conversationHistory = [
                { role: 'user', content: item.user_message },
                { role: 'assistant', content: item.assistant_message }
            ];
            closeModal('historyModal');
        }

        async function saveToHistory(userMessage, assistantMessage) {
            try {
                await fetch('api/history.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_message: userMessage,
                        assistant_message: assistantMessage
                    })
                });
            } catch (error) {
                console.error('Error saving history:', error);
            }
        }

        // Settings
        function openSettings() {
            openModal('settingsModal');
        }

        // Code Tool
        function openCodeTool() {
            const snippet = `// Quick Code Snippet
function helloWorld() {
    console.log("Hello from Nexuss Frontier! 🚀");
}

// Math Example: E = mc²
// LaTeX: $\\int_0^\\infty e^{-x} dx = 1$

helloWorld();`;
            
            navigator.clipboard.writeText(snippet).then(() => {
                alert('Code snippet copied to clipboard!');
            });
        }

        // Modal utilities
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            // Reset form
            if (id === 'fileModal') {
                document.getElementById('fileName').disabled = false;
                document.getElementById('fileContent').disabled = false;
            }
        }

        // Initialize file tree
        refreshFiles();

        // Close modals on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
