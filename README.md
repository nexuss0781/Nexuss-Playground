# ◈ Nexus Pro

<div align="center">

**Advanced AI Chat & Tools — Powered by Puter**

A beautiful, zero-backend web application featuring multi-model AI routing, real-time web search, code intelligence, and local-first data persistence.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Status](https://img.shields.io/badge/status-production--ready-green.svg)]()
[![Puter](https://img.shields.io/badge/Powered%20by-Puter-7b68ee.svg)](https://puter.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)]()

![Preview](https://via.placeholder.com/800x450/1a1a2e/7b68ee?text=Nexus+Pro+Interface)

</div>

---

## ✨ Features

### 🤖 Multi-Model AI Support
| Model | Provider | Best For |
|-------|----------|----------|
| **Claude** | Anthropic | Reasoning, code review, long context |
| **Gemini** | Google | Creative tasks, multimodal understanding |
| **GPT** | OpenAI | General purpose, tool use, APIs |

- **One-Click Switching**: Change models mid-conversation without losing context.
- **Smart Defaults**: Each model has optimized system prompts.
- **Zero Configuration**: Puter handles all API keys automatically.

### 🔍 Integrated Web Search
```
┌─────────────────────────────────────┐
│  🔍 Web Search Tool (Toggleable)   │
├─────────────────────────────────────┤
│  • DuckDuckGo privacy proxy        │
│  • Real-time results in chat       │
│  • 5-minute intelligent cache      │
│  • No external dependencies        │
└─────────────────────────────────────┘
```

### 💻 Code Intelligence
- **Syntax Highlighting**: 100+ languages via Highlight.js (Atom One Dark theme)
- **File Attachments**: Drag & drop `.js`, `.py`, `.ts`, `.html`, `.css`, `.json`, `.md`
- **Math Typesetting**: Full LaTeX support with KaTeX
- **Markdown Rendering**: Tables, lists, blockquotes, and more

### 🎨 Premium UI/UX
- **Glassmorphism**: Frosted glass cards with backdrop blur
- **Responsive**: Mobile sidebar with overlay, touch-friendly controls
- **Animations**: Smooth 60fps transitions using CSS transforms
- **Dark Theme**: Carefully tuned color palette for reduced eye strain

### 🔐 Privacy & Persistence
- **Local-First**: All chat history stored in browser IndexedDB
- **No Server Database**: Works on any PHP-enabled host (shared, VPS, static + proxy)
- **Secure Auth**: OAuth 2.0 via Puter.com — no passwords stored
- **CORS-Ready**: Configurable proxy headers for production deployment

---

## 🚀 Quick Start

### Prerequisites
- ✅ Web server with **PHP 7.4+** (Apache, Nginx, or shared hosting)
- ✅ Write permissions for the `cache/` directory
- ✅ [Puter.com](https://puter.com/) account (free) for authentication

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/nexuss0781/Nexuss-Playground.git
cd Nexuss-Playground

# 2. Set cache permissions
chmod 755 cache

# 3. Run locally (optional)
php -S localhost:8000
```

Then navigate to `http://localhost:8000` or upload to your web server.

### Deploy to Hosting

| Provider | Steps |
|----------|-------|
| **InfinityFree** | Upload via FTP to `htdocs/`, ensure `cache/` exists |
| **VPS/Dedicated** | Clone repo, `chown www-data cache/`, configure Nginx/Apache |
| **Docker** | Use included Dockerfile, expose port 80 |

---

## ⚙️ Configuration

### Search Proxy (`api/search-proxy.php`)

| Setting | Default | Description |
|---------|---------|-------------|
| `$cacheTTL` | `300` (5 min) | Cache duration in seconds |
| CORS Headers | `*` | Allow all origins (restrict for production) |

### Frontend Customization

- **Model Order**: Edit `.model-selector` buttons in `index.html`
- **Theme Colors**: Modify CSS variables in `assets/css/nexus-pro.css`
- **Token Limits**: Adjust context window in `nexus-core.js`

---

## 🧩 Key Components

| File | Responsibility |
|------|----------------|
| `index.html` | Auth screen, app shell, sidebar, chat interface, modals |
| `nexus-core.js` | Puter auth, IndexedDB CRUD, chat state, model switching, markdown rendering |
| `nexus-tools.js` | Tool manager, web search API, code analysis helpers |
| `search-proxy.php` | POST endpoint for DuckDuckGo scraping, JSON response, cache logic |
| `nexus-pro.css` | CSS variables, glassmorphism, responsive breakpoints, animations |

---

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3 (Custom Properties), Vanilla ES6+ JavaScript
- **Backend**: PHP 7.4+ (stateless proxy only)
- **Libraries**: 
  - [Puter.js](https://docs.puter.com/) — Authentication & AI API
  - [Marked](https://marked.js.org/) — Markdown parsing
  - [Highlight.js](https://highlightjs.org/) — Syntax highlighting
  - [KaTeX](https://katex.org/) — Math typesetting
- **Storage**: Browser IndexedDB + File-based cache

---

## 📱 Browser Support

| Browser | Version |
|---------|---------|
| Chrome | 90+ |
| Firefox | 88+ |
| Safari | 14+ |
| Edge | 90+ |

*Requires modern browser features: IndexedDB, Fetch API, ES6 Modules*

---

## 🔒 Security Notes

- The search proxy sanitizes input (max 200 chars, MD5 cache keys).
- Puter handles all authentication tokens securely.
- No sensitive data is stored server-side.
- For production, restrict CORS origins in `search-proxy.php`.

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a feature branch (`git checkout -b feature/amazing-feature`).
3. Commit your changes (`git commit -m 'Add amazing feature'`).
4. Push to the branch (`git push origin feature/amazing-feature`).
5. Open a Pull Request.

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- [Puter](https://puter.com/) for the incredible zero-backend cloud OS.
- DuckDuckGo for privacy-focused search results.
- All open-source libraries that make this possible.

---

<div align="center">

**Built with ❤️ by nexuss0781**  
*Experience the future of AI interfaces—no backend required.*

</div>
