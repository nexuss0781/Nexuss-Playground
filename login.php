<?php
session_start();

// Puter OAuth Configuration
const PUTER_CLIENT_ID = 'puter-app-id';
const PUTER_REDIRECT_URI = 'http://' . $_SERVER['HTTP_HOST'] . '/auth_callback.php';

// Generate state for CSRF protection
if (!isset($_SESSION['state'])) {
    $_SESSION['state'] = bin2hex(random_bytes(32));
}

$authUrl = 'https://api.puter.com/auth?client_id=' . PUTER_CLIENT_ID . 
           '&redirect_uri=' . urlencode(PUTER_REDIRECT_URI) .
           '&state=' . $_SESSION['state'] .
           '&response_type=token';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Professional Playground Nexuss Frontier</title>
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
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1a1a2e 100%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-container {
            text-align: center;
            padding: 2rem;
            max-width: 500px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: var(--text-secondary);
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        .login-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .features {
            text-align: left;
            margin-bottom: 2rem;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
        }

        .feature-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .login-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            width: 100%;
            justify-content: center;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }

        .login-btn:active {
            transform: translateY(-1px);
        }

        .footer {
            margin-top: 2rem;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.3;
            animation: particle-float 15s infinite;
        }

        @keyframes particle-float {
            0%, 100% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.3; }
            90% { opacity: 0.3; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🚀</div>
            <div>
                <h1>Nexuss Frontier</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Professional Playground</p>
            </div>
        </div>

        <div class="login-card">
            <p class="subtitle">
                Experience the next generation of AI-powered development with access to cutting-edge models and intelligent tools.
            </p>

            <div class="features">
                <div class="feature">
                    <div class="feature-icon">✓</div>
                    <span>Access to Claude 4.7 Opus, Gemini 3.1 Pro, GPT-5.5 & more</span>
                </div>
                <div class="feature">
                    <div class="feature-icon">✓</div>
                    <span>Full file management with CRUD operations</span>
                </div>
                <div class="feature">
                    <div class="feature-icon">✓</div>
                    <span>Professional markdown & LaTeX math support</span>
                </div>
                <div class="feature">
                    <div class="feature-icon">✓</div>
                    <span>Code highlighting & intelligent assistance</span>
                </div>
                <div class="feature">
                    <div class="feature-icon">✓</div>
                    <span>Conversation history & customizable persona</span>
                </div>
            </div>

            <a href="<?php echo $authUrl; ?>" class="login-btn">
                <span>🔐</span>
                <span>Login with Puter</span>
            </a>

            <p class="footer">
                Secure authentication powered by Puter
            </p>
        </div>
    </div>

    <script>
        // Create particles
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            particlesContainer.appendChild(particle);
        }
    </script>
</body>
</html>
