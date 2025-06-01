<?php
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Update last login timestamp
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/index.php' : 'user/index.php'));
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Quiz App</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4F46E5;
            --primary-hover: #4338CA;
            --all-white: #FFFFFF;
            --danger-color: #DC2626;
            --success-color: #059669;
            --border-color: #E5E7EB;
            --text-primary: #1F2937;
            --text-secondary: #4B5563;
            --text-tertiary: #FFFF;
            --bg-primary: #F9FAFB;
            --bg-white: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .app-logo {
            font-size: 2rem;
            color: #ffffff;
            margin-bottom: 1rem;
            text-shadow: 
                0 0 20px rgba(255, 255, 255, 0.5),
                0 0 40px rgba(255, 255, 255, 0.3),
                0 4px 8px rgba(0, 0, 0, 0.3);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
            animation: logoGlow 3s ease-in-out infinite alternate;
        }

        @keyframes logoGlow {
            from {
                text-shadow: 
                    0 0 20px rgba(255, 255, 255, 0.5),
                    0 0 40px rgba(255, 255, 255, 0.3),
                    0 4px 8px rgba(0, 0, 0, 0.3);
            }
            to {
                text-shadow: 
                    0 0 30px rgba(255, 255, 255, 0.8),
                    0 0 60px rgba(255, 255, 255, 0.5),
                    0 4px 8px rgba(0, 0, 0, 0.3);
            }
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
            text-shadow: 
                0 2px 4px rgba(0, 0, 0, 0.3),
                0 0 20px rgba(255, 255, 255, 0.2);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            animation: titleShimmer 4s ease-in-out infinite;
        }

        .login-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
            border-radius: 1px;
        }

        .login-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            font-weight: 400;
            letter-spacing: 0.5px;
            position: relative;
            padding: 0.5rem 0;
        }

        .login-subtitle::before {
            content: '✨';
            position: absolute;
            left: -25px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8rem;
            animation: sparkle 2s ease-in-out infinite;
        }

        .login-subtitle::after {
            content: '✨';
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8rem;
            animation: sparkle 2s ease-in-out infinite 1s;
        }

        @keyframes titleShimmer {
            0%, 100% {
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
                -webkit-background-clip: text;
                background-clip: text;
            }
            50% {
                background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f8fafc 100%);
                -webkit-background-clip: text;
                background-clip: text;
            }
        }

        @keyframes sparkle {
            0%, 100% {
                opacity: 0.4;
                transform: translateY(-50%) scale(0.8);
            }
            50% {
                opacity: 1;
                transform: translateY(-50%) scale(1.2);
            }
        }
        .login-form {
            background: var(--bg-white);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .error-message {
            background: #FEF2F2;
            color: var(--danger-color);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .submit-button {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .submit-button:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .register-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-left: 0.25rem;
        }

        .register-link:hover {
            text-decoration: underline;
        }

        .demo-credentials {
            margin-top: 1rem;
            padding: 1rem;
            background: #F0FDF4;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: var(--success-color);
        }

        .demo-credentials p {
            margin: 0.25rem 0;
        }

        .back-button {
            position: fixed;
            top: 2rem;
            left: 2rem;
            background: white;
            color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1rem;
            }

            .login-form {
                padding: 1.5rem;
            }

            .back-button {
                top: 1rem;
                left: 1rem;
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
        }

        /* Password Input Container */
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input {
            padding-right: 3rem;
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--primary-color);
            background: rgba(79, 70, 229, 0.1);
        }

        .password-toggle:focus {
            outline: none;
            color: var(--primary-color);
        }

        /* Enhanced Design Improvements */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="20" cy="80" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
            pointer-events: none;
        }

        .login-container {
            position: relative;
            z-index: 1;
        }

        .login-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04),
                0 0 0 1px rgba(255, 255, 255, 0.05);
        }

        .app-logo {
            background: linear-gradient(135deg, var(--primary-color), #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .login-title {
            background: linear-gradient(135deg, var(--text-primary), #374151);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-input {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--primary-color);
            box-shadow: 
                0 0 0 3px rgba(79, 70, 229, 0.1),
                0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .submit-button {
            background: linear-gradient(135deg, var(--primary-color), #7C3AED);
            box-shadow: 0 4px 14px 0 rgba(79, 70, 229, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .submit-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .submit-button:hover::before {
            left: 100%;
        }

        .submit-button:hover {
            background: linear-gradient(135deg, var(--primary-hover), #6D28D9);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px 0 rgba(79, 70, 229, 0.4);
        }

        .error-message {
            background: rgba(254, 242, 242, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(220, 38, 38, 0.2);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .back-button {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .demo-credentials {
            background: rgba(240, 253, 244, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(5, 150, 105, 0.2);
        }

        /* Loading Animation for Submit Button */
        .submit-button.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .submit-button.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Enhanced Focus States */
        .form-input:focus,
        .password-toggle:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
        }

        /* Improved Mobile Responsiveness */
        @media (max-width: 480px) {
            .password-toggle {
                right: 0.5rem;
                padding: 0.75rem 0.5rem;
            }
            
            .password-input {
                padding-right: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.html" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Home
    </a>
    <div class="login-container">
        <div class="login-header">
            <div class="app-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="login-title">Welcome to Quizify</h1>
            <p class="login-subtitle">Please sign in to continue</p>
        </div>

        <form class="login-form" method="post" action="">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?=htmlspecialchars($error)?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input 
                    type="text" 
                    id="username"
                    name="username" 
                    class="form-input" 
                    placeholder="Enter your username"
                    required 
                    autofocus
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="password-input-container">
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        class="form-input password-input" 
                        placeholder="Enter your password"
                        required
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="submit-button">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>

            <div class="login-footer">
                Don't have an account?
                <a href="register.php" class="register-link">Register now</a>
                <br>
                <span style="display: block; margin-top: 0.5rem;">
                    Need admin access?
                    <a href="admin_register.php" class="register-link">Register as admin</a>
                </span>
            </div>

            <!-- <div class="demo-credentials">
                <p><strong>Demo Credentials:</strong></p>
                <p>Admin: admin / admin123</p>
                <p>User: user1 / user123</p>
            </div> -->
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Add loading animation to submit button
        document.querySelector('.login-form').addEventListener('submit', function() {
            const submitButton = document.querySelector('.submit-button');
            submitButton.classList.add('loading');
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        });

        // Add smooth focus transitions
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>