# 🌌 Nexus Pro

> **Next-Generation Web Interface & Search Engine Proxy**  
> A modern, responsive, and privacy-focused web application featuring a sleek UI, real-time chat capabilities, and a secure search proxy.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Status](https://img.shields.io/badge/status-production-ready-green.svg)]()
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)]()
[![JavaScript](https://img.shields.io/badge/JS-ES6+-yellow.svg)]()

---

## ✨ Features

### 🎨 Modern User Interface
- **Glassmorphism Design**: Beautiful translucent cards with blur effects.
- **Responsive Layout**: Fully adaptive mobile-first design.
- **Dark/Light Mode**: Seamless theme switching with persistence.
- **Smooth Animations**: Hardware-accelerated transitions and micro-interactions.

### 🔒 Privacy-First Search
- **DuckDuckGo Proxy**: Secure backend proxy for anonymous searches.
- **Server-Side Caching**: Redis-compatible file caching for faster repeated queries.
- **CORS Handling**: Configurable cross-origin resource sharing.

### 💬 Real-Time Chat Engine
- **IndexedDB Storage**: Local-first data persistence for offline capability.
- **Context Awareness**: Intelligent conversation history management.
- **Tool Integration**: Extensible plugin system for custom commands.

### 🛠️ Developer Experience
- **Modular Architecture**: Clean separation of concerns (Core, Tools, UI).
- **Zero Dependencies**: Vanilla JS/CSS/PHP for maximum performance.
- **Easy Deployment**: Drop-in ready for any Apache/Nginx + PHP server.

---

## 📂 Project Structure

```text
Nexuss-Playground/
├── index.html              # Main entry point & UI structure
├── assets/
│   ├── css/
│   │   └── nexus-pro.css   # Core styles, variables, animations
│   └── js/
│       ├── nexus-core.js   # App logic, DB, Chat engine
│       └── nexus-tools.js  # Utility functions, Search manager
├── api/
│   └── search-proxy.php    # Backend search proxy & cache handler
├── cache/                  # Writable directory for search cache
└── README.md               # This file
```

---

## 🚀 Quick Start

### Prerequisites
- A web server with **PHP 7.4+** support (Apache, Nginx, or Caddy).
- Write permissions for the `cache/` directory.

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/nexuss0781/Nexuss-Playground.git
   cd Nexuss-Playground
   ```

2. **Set Permissions**
   Ensure the `cache` folder is writable by the web server:
   ```bash
   chmod 755 cache
   # Or for local development
   chmod 777 cache
   ```

3. **Launch**
   Open `index.html` in your browser or serve via PHP:
   ```bash
   php -S localhost:8000
   ```
   Navigate to `http://localhost:8000`.

---

## ⚙️ Configuration

### Search Proxy (`api/search-proxy.php`)
- **Cache Duration**: Modify `$cache_ttl` to adjust how long search results are stored.
- **Allowed Origins**: Edit the `$allowed_origins` array to restrict API access.

### Frontend (`assets/js/nexus-core.js`)
- **API Endpoint**: Update `SEARCH_API_URL` if moving the proxy to a different path.
- **Theme**: Default theme preference can be set in `localStorage`.

---

## 🧩 Key Components

| Component | Description |
| :--- | :--- |
| **`nexus-core.js`** | The brain of the app. Handles IndexedDB, state management, and the chat interface logic. |
| **`nexus-tools.js`** | The utility belt. Manages search requests, formatting, and helper functions. |
| **`search-proxy.php`** | The gatekeeper. Fetches external search results securely and manages server-side caching. |
| **`nexus-pro.css`** | The look & feel. Defines CSS variables, glassmorphism classes, and responsive breakpoints. |

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
