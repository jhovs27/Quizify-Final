<?php
require 'config.php';

// Verify if the user is already logged in as an admin
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$error = '';
$success = '';

// Get passkey settings from database
$passkey_required = true;
$admin_secret_key = 'quiz_admin_2025'; // Default fallback

try {
    $stmt = $pdo->prepare("SELECT setting_value, is_enabled FROM admin_settings WHERE setting_key = 'admin_passkey'");
    $stmt->execute();
    $passkey_setting = $stmt->fetch();
    
    if ($passkey_setting) {
        $passkey_required = (bool)$passkey_setting['is_enabled'];
        $admin_secret_key = $passkey_setting['setting_value'];
    }
} catch (PDOException $e) {
    // If table doesn't exist or error occurs, use defaults
    $passkey_required = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $secret_key = $_POST['secret_key'] ?? '';
    $email = $_POST['email'] ?? '';

    // Enhanced validation for admin registration
    if (!$username || !$password || !$confirm_password || !$email) {
        $error = 'All required fields must be filled';
    } elseif ($passkey_required && (!$secret_key || $secret_key !== $admin_secret_key)) {
        $error = 'Invalid admin secret key';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $error = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already exists';
        } else {
            // Create new admin user
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
                $stmt->execute([$username, $hashed_password, $email]);
                $success = 'Admin registration successful! You can now login.';
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Quiz App</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4F46E5;
            --primary-hover: #4338CA;
            --danger-color: #DC2626;
            --success-color: #059669;
            --warning-color: #F59E0B;
            --border-color: #E5E7EB;
            --text-primary: #1F2937;
            --text-secondary: #4B5563;
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

        .register-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .app-logo {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .register-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .register-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .register-form {
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

        .success-message {
            background: #F0FDF4;
            color: var(--success-color);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .warning-message {
            background: #FFFBEB;
            color: var(--warning-color);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #FDE68A;
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

        .register-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .login-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-left: 0.25rem;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .password-requirements {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--bg-primary);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .password-requirements ul {
            list-style-type: none;
            margin-top: 0.5rem;
        }

        .password-requirements li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .password-requirements i {
            font-size: 0.75rem;
        }

        .secret-key-info {
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-style: italic;
        }

        .passkey-disabled-notice {
            background: linear-gradient(135deg, #F0FDF4, #DCFCE7);
            border: 1px solid #BBF7D0;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .passkey-disabled-notice i {
            font-size: 1.5rem;
            color: var(--success-color);
            margin-bottom: 0.5rem;
        }

        .passkey-disabled-notice h4 {
            color: var(--success-color);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .passkey-disabled-notice p {
            color: #166534;
            font-size: 0.875rem;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 1rem;
            }

            .register-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="app-logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="register-title">Admin Registration</h1>
            <p class="register-subtitle">Create an administrator account</p>
        </div>

        <form class="register-form" method="post" action="">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?=htmlspecialchars($error)?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?=htmlspecialchars($success)?>
                </div>
            <?php endif; ?>

            <?php if (!$passkey_required): ?>
                <div class="passkey-disabled-notice">
                    <i class="fas fa-unlock"></i>
                    <h4>Open Registration</h4>
                    <p>Admin passkey requirement is currently disabled. You can register without a secret key.</p>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input 
                    type="text" 
                    id="username"
                    name="username" 
                    class="form-input" 
                    placeholder="Choose an admin username"
                    required 
                    autofocus
                    value="<?=htmlspecialchars($_POST['username'] ?? '')?>"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input 
                    type="email" 
                    id="email"
                    name="email" 
                    class="form-input" 
                    placeholder="Enter your email address"
                    required
                    value="<?=htmlspecialchars($_POST['email'] ?? '')?>"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    class="form-input" 
                    placeholder="Create a strong password"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password"
                    name="confirm_password" 
                    class="form-input" 
                    placeholder="Confirm your password"
                    required
                >
            </div>

            <?php if ($passkey_required): ?>
            <div class="form-group" id="passkey-group">
                <label class="form-label" for="secret_key">Admin Secret Key *</label>
                <input 
                    type="password" 
                    id="secret_key"
                    name="secret_key" 
                    class="form-input" 
                    placeholder="Enter the admin secret key"
                    required
                >
                <div class="secret-key-info">Contact the system administrator for the secret key</div>
            </div>
            <?php endif; ?>

            <button type="submit" class="submit-button">
                <i class="fas fa-user-shield"></i>
                Create Admin Account
            </button>

            <div class="register-footer">
                Already have an account?
                <a href="login.php" class="login-link">Sign in</a>
            </div>

            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li><i class="fas fa-check"></i> At least 8 characters long</li>
                    <li><i class="fas fa-check"></i> One uppercase letter</li>
                    <li><i class="fas fa-check"></i> One lowercase letter</li>
                    <li><i class="fas fa-check"></i> One number</li>
                    <li><i class="fas fa-check"></i> One special character (@$!%*?&)</li>
                </ul>
            </div>
        </form>
    </div>
    <script>
        // Check passkey requirement status periodically
        setInterval(checkPasskeyStatus, 5000);

        function checkPasskeyStatus() {
            fetch('check_passkey_status.php')
                .then(response => response.json())
                .then(data => {
                    const passkeyGroup = document.getElementById('passkey-group');
                    const notice = document.querySelector('.passkey-disabled-notice');
                    
                    if (data.passkey_required) {
                        if (!passkeyGroup && !notice) {
                            location.reload(); // Reload if status changed
                        }
                    } else {
                        if (passkeyGroup && !notice) {
                            location.reload(); // Reload if status changed
                        }
                    }
                })
                .catch(error => console.log('Status check failed'));
        }
    </script>
</body>
</html>
