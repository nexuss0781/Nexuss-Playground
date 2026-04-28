# 🌌 Nexus Pro

> **Advanced AI Chat Interface powered by Puter**  
> A beautiful, zero-backend web application featuring multi-model AI routing (Claude, Gemini, GPT), real-time web search, code intelligence, and local-first data persistence.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Status](https://img.shields.io/badge/status-production--ready-green.svg)]()
[![Puter](https://img.shields.io/badge/Powered%20by-Puter-7b68ee.svg)](https://puter.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)]()

---

## ✨ Features

### 🤖 Multi-Model AI Support
- **Claude · Gemini · GPT**: Seamlessly switch between top AI models with a single click.
- **Smart Routing**: Automatic model selection based on task type.
- **No API Keys Required**: Leverages Puter's unified API for zero-configuration access.

### 🔍 Integrated Web Search
- **DuckDuckGo Proxy**: Built-in PHP proxy for private, uncensored web searches.
- **Real-Time Results**: Fetch current information to enhance AI responses.
- **5-Minute Caching**: File-based caching reduces redundant requests.

### 💻 Code Intelligence
- **Syntax Highlighting**: Beautiful code blocks with Highlight.js.
- **Multi-Language Support**: Auto-detection for 100+ programming languages.
- **Math Rendering**: LaTeX support via KaTeX for scientific notation.

### 🎨 Premium UI/UX
- **Glassmorphism Design**: Translucent cards with backdrop blur effects.
- **Responsive Layout**: Mobile-first design that adapts to any screen.
- **Smooth Animations**: Hardware-accelerated transitions and micro-interactions.
- **Markdown Support**: Rich text rendering with marked.js.

### 🔐 Privacy & Persistence
- **Zero Backend**: No database server required—runs on any PHP host.
- **IndexedDB Storage**: Local-first chat history persists across sessions.
- **Puter Authentication**: Secure OAuth login via Puter.com.
- **InfinityFree Ready**: Compatible with free hosting providers.

---

## 📂 Project Structure

```text
Nexuss-Playground/
├── index.html              # Main entry: Auth, Chat UI, Model Selector
├── assets/
│   ├── css/
│   │   └── nexus-pro.css   # Glassmorphism styles, animations, responsive
│   └── js/
│       ├── nexus-core.js   # Chat engine, IndexedDB, Puter auth, model routing
│       └── nexus-tools.js  # Web search, code tools, utility functions
├── api/
│   └── search-proxy.php    # DuckDuckGo proxy with file caching
├── cache/                  # Writable directory for search result cache
└── README.md               # This file
```

---

## 🚀 Quick Start

### Prerequisites
- A web server with **PHP 7.4+** (Apache, Nginx, or shared hosting).
- Write permissions for the `cache/` directory.
- A [Puter.com](https://puter.com/) account (free) for authentication.

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/nexuss0781/Nexuss-Playground.git
   cd Nexuss-Playground
   ```

2. **Set Cache Permissions**
   ```bash
   chmod 755 cache
   # For local development or if permission issues occur:
   chmod 777 cache
   ```

3. **Deploy**
   - Upload all files to your web server (public_html or equivalent).
   - Or run locally with PHP's built-in server:
     ```bash
     php -S localhost:8000
     ```
   - Navigate to `http://localhost:8000` or your domain.

4. **Sign In**
   - Click "Sign In with Puter" to authenticate.
   - Start chatting with Claude, Gemini, or GPT!

---

## ⚙️ Configuration

### Search Proxy (`api/search-proxy.php`)
| Setting | Default | Description |
|---------|---------|-------------|
| `$cacheTTL` | `300` (5 min) | Cache duration in seconds |
| CORS Headers | `*` | Allow all origins (restrict for production) |

### Frontend Customization
- **Model Order**: Edit `.model-selector` buttons in `index.html`.
- **Theme Colors**: Modify CSS variables in `assets/css/nexus-pro.css`.
- **Token Limits**: Adjust context window in `nexus-core.js`.

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

## 🏗️ Deployment Guide

### Shared Hosting (InfinityFree, 000webhost, etc.)
1. Upload all files via FTP to `htdocs` or `public_html`.
2. Ensure `cache/` folder exists and is writable.
3. Access your domain and log in with Puter.

### VPS / Dedicated Server
```bash
# Clone repo
git clone https://github.com/nexuss0781/Nexuss-Playground.git /var/www/nexus
cd /var/www/nexus

# Set permissions
chown -R www-data:www-data cache/
chmod 755 cache/

# Configure Nginx/Apache to serve PHP
```

### Docker (Optional)
```dockerfile
FROM php:8.2-apache
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/cache
EXPOSE 80
```

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

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- [Puter](https://puter.com/) for the incredible zero-backend cloud OS.
- DuckDuckGo for privacy-focused search results.
- All open-source libraries that make this possible.

---

**Built with ❤️ by nexuss0781**  
*Experience the future of AI interfaces—no backend required.*

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

- Inspired by modern design systems like **GitHub Primer** and **Vercel Geists**.
- Search functionality powered by **DuckDuckGo**.
- Built with ❤️ by **nexuss0781**.

---

<p align="center">
  <i>Made for the future of the web.</i>
</p>
