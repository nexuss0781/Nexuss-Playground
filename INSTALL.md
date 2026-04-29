# Professional Playground Nexuss Frontier

A beautiful, interactive web application providing access to cutting-edge AI models with full file management capabilities.

## Features

### 🤖 Multi-Provider AI Access
**Puter Models:**
- Claude 4.7 Opus
- Gemini 3.1 Pro
- GPT-5.5
- DeepSeek-V4 Pro
- GLM 5.1

**MiniMax Models:**
- MiniMax-M2.7 (Tag Free)
- MiniMax-M2.5 (Tag Free)

### 📁 Full File Management
- Create, Read, Update, Delete files and folders
- Move and copy operations
- Visual file explorer with icons
- Protected system files

### 💬 Chat Interface
- Professional markdown rendering
- Syntax highlighting for code (Highlight.js)
- Mathematics formula preview (MathJax/LaTeX)
- Conversation history
- Context-aware responses

### ⚙️ Singleton System Prompt
- Reads from `PROMPT.md` in root directory
- Customizable AI persona
- Consistent behavior across sessions

### 🔐 Secure Authentication
- Puter OAuth integration
- Session-based authentication
- CSRF protection

## Installation

### Requirements
- PHP 7.4 or higher
- cURL extension enabled
- Web server (Apache/Nginx)

### Setup Steps

1. **Clone/Deploy** the application to your web server

2. **Configure Puter OAuth** (in `login.php`):
   ```php
   const PUTER_CLIENT_ID = 'your-puter-app-id';
   ```

3. **Set Permissions**:
   ```bash
   chmod 755 history/
   chmod 644 *.php
   chmod 644 api/*.php
   ```

4. **Customize System Prompt** (optional):
   Edit `PROMPT.md` to define your AI's persona and behavior

5. **Access the Application**:
   - Navigate to your domain
   - Click "Login with Puter"
   - Authorize the application

## File Structure

```
/workspace
├── index.php           # Main application
├── login.php           # Login page with CTA
├── auth_callback.php   # OAuth callback handler
├── PROMPT.md           # System prompt configuration
├── api/
│   ├── chat.php        # AI chat endpoint
│   ├── files.php       # File CRUD operations
│   └── history.php     # Conversation history
├── history/            # Saved chat history
└── assets/             # Static assets
```

## Usage

### Chat
1. Select provider (Puter or MiniMax)
2. Choose model from dropdown
3. Type message and press Enter (Shift+Enter for new line)
4. View formatted response with code highlighting

### File Management
- **New File**: Click 📄 button
- **New Folder**: Click 📁 button
- **Refresh**: Click 🔄 button
- **View/Edit**: Click on any file
- **File Icons**: Automatic based on extension

### History
- Click "History" in sidebar
- Browse past conversations
- Click to restore any conversation

### Settings
- Click "Settings" in sidebar
- View current system prompt from PROMPT.md

## API Endpoints

### POST /api/chat.php
Send messages to AI models
```json
{
  "message": "Hello",
  "provider": "puter|minimax",
  "model": "model-id",
  "history": [],
  "system_prompt": "..."
}
```

### GET /api/files.php?action=list
List all files and folders

### POST /api/files.php?action=create
Create new file or folder
```json
{
  "name": "filename.txt",
  "type": "file|folder",
  "content": "file content"
}
```

### POST /api/history.php?action=save
Save conversation to history

## Security Notes

- All file operations are restricted to the application directory
- System files are protected from deletion
- Authentication required for all API endpoints
- Session-based user tracking

## Technologies Used

- **Frontend**: Vanilla JavaScript, CSS Grid/Flexbox
- **Markdown**: Marked.js
- **Code Highlighting**: Highlight.js (GitHub Dark theme)
- **Math Rendering**: MathJax 3
- **Backend**: PHP 7.4+
- **Authentication**: Puter OAuth

## License

See LICENSE file in repository.

---

**Professional Playground Nexuss Frontier** - Empowering developers with AI-powered tools.
