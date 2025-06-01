<?php
require '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Create admin_settings table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT,
            is_enabled BOOLEAN DEFAULT TRUE,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            updated_by INT,
            FOREIGN KEY (updated_by) REFERENCES users(id)
        )
    ");
    
    // Insert default passkey if not exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO admin_settings (setting_key, setting_value, is_enabled, updated_by) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin_passkey', 'quiz_admin_2025', true, $_SESSION['user_id']]);
} catch (PDOException $e) {
    $error = 'Database setup failed: ' . $e->getMessage();
}

// Get current passkey settings
$stmt = $pdo->prepare("SELECT * FROM admin_settings WHERE setting_key = 'admin_passkey'");
$stmt->execute();
$passkey_setting = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_passkey') {
        $new_passkey = trim($_POST['new_passkey'] ?? '');
        
        if (empty($new_passkey)) {
            $error = 'Passkey cannot be empty';
        } elseif (strlen($new_passkey) < 8) {
            $error = 'Passkey must be at least 8 characters long';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE admin_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = 'admin_passkey'");
                $stmt->execute([$new_passkey, $_SESSION['user_id']]);
                $success = 'Admin passkey updated successfully';
                
                // Refresh the passkey setting
                $stmt = $pdo->prepare("SELECT * FROM admin_settings WHERE setting_key = 'admin_passkey'");
                $stmt->execute();
                $passkey_setting = $stmt->fetch();
            } catch (PDOException $e) {
                $error = 'Failed to update passkey: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'toggle_status') {
        $new_status = isset($_POST['is_enabled']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE admin_settings SET is_enabled = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = 'admin_passkey'");
            $stmt->execute([$new_status, $_SESSION['user_id']]);
            $success = $new_status ? 'Admin passkey requirement enabled' : 'Admin passkey requirement disabled';
            
            // Refresh the passkey setting
            $stmt = $pdo->prepare("SELECT * FROM admin_settings WHERE setting_key = 'admin_passkey'");
            $stmt->execute();
            $passkey_setting = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Failed to update passkey status: ' . $e->getMessage();
        }
    }
}

// Get passkey update history
$stmt = $pdo->prepare("
    SELECT u.username, s.updated_at, s.is_enabled 
    FROM admin_settings s 
    LEFT JOIN users u ON s.updated_by = u.id 
    WHERE s.setting_key = 'admin_passkey' 
    ORDER BY s.updated_at DESC 
    LIMIT 10
");
$stmt->execute();
$update_history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Passkey Management - Quizify</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #ec4899;
            --accent: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --dark-light: #1e293b;
            --gray: #64748b;
            --gray-light: #94a3b8;
            --gray-lighter: #cbd5e1;
            --white: #ffffff;
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
            color: white;
            box-shadow: var(--shadow-lg);
            animation: logoFloat 6s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-5px) rotate(2deg); }
        }

        .brand-text {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-subtitle {
            color: var(--gray-light);
            font-size: 0.875rem;
            font-weight: 400;
            margin-top: 0.25rem;
        }

        .user-profile {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-lg);
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .user-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent), var(--success));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 1.2rem;
            box-shadow: var(--shadow);
        }

        .user-info h4 {
            color: white;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .user-info span {
            color: var(--gray-light);
            font-size: 0.875rem;
            font-weight: 400;
        }

        .nav-menu {
            padding: 1rem 0;
            list-style: none;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: var(--gray-light);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 0 var(--radius-xl) var(--radius-xl) 0;
            margin-right: 1rem;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(99, 102, 241, 0.1);
            color: white;
            transform: translateX(8px);
        }

        .nav-link:hover::before,
        .nav-link.active::before {
            transform: scaleY(1);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.4s ease;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
            background-size: 200% 100%;
            animation: gradientShift 3s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .page-title i {
            font-size: 2rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            padding: 1.5rem 2rem;
            background: rgba(255, 255, 255, 0.5);
            border-bottom: 1px solid rgba(203, 213, 225, 0.3);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .card-header i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .card-body {
            padding: 2rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-lighter);
            border-radius: var(--radius);
            font-size: 1rem;
            color: var(--dark);
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-switch {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: var(--radius);
            border: 2px solid var(--gray-lighter);
            transition: all 0.3s ease;
        }

        .form-switch:hover {
            border-color: var(--primary);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray-lighter);
            transition: 0.4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--success);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .switch-label {
            font-weight: 500;
            color: var(--dark);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            border: none;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            box-shadow: var(--shadow);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Status Badge */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-enabled {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-disabled {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Current Passkey Display */
        .passkey-display {
            background: var(--bg-secondary);
            border: 2px dashed var(--gray-lighter);
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .passkey-value {
            font-family: 'Courier New', monospace;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            background: white;
            padding: 0.75rem 1rem;
            border-radius: var(--radius);
            border: 1px solid var(--gray-lighter);
            margin: 0.5rem 0;
            letter-spacing: 2px;
        }

        /* History Table */
        .history-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .history-table th,
        .history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-lighter);
        }

        .history-table th {
            background: var(--bg-secondary);
            font-weight: 600;
            color: var(--dark);
            font-size: 0.875rem;
        }

        .history-table td {
            color: var(--gray);
        }

        .history-table tbody tr:hover {
            background: var(--bg-secondary);
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: var(--radius);
            width: 48px;
            height: 48px;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            background: white;
            transform: scale(1.05);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.75rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .form-switch {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
        }

        /* Scrollbar Styling */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
    /* Top Navbar */
    .top-navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 70px;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 1001;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
        transition: all 0.3s ease;
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
        color: white;
        text-decoration: none;
    }

    .navbar-logo {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: var(--radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
        animation: logoFloat 6s ease-in-out infinite;
    }

    .navbar-title {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #fff, #e2e8f0);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .navbar-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .navbar-time {
        color: var(--gray-light);
        font-size: 0.875rem;
        font-weight: 500;
    }

    .navbar-user {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: var(--radius);
        color: white;
        transition: all 0.3s ease;
    }

    .navbar-user:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
    }

    .navbar-avatar {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, var(--accent), var(--success));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .navbar-username {
        font-weight: 600;
        font-size: 0.875rem;
    }

    /* Sidebar Adjustment */
    .sidebar {
        top: 70px;
        height: calc(100vh - 70px);
    }

    /* Main Content Adjustment */
    .main-content {
        margin-top: 70px;
    }

    /* Footer */
    .footer {
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        padding: 3rem 2rem 2rem;
        margin-left: 280px;
        margin-top: auto;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
    }

    .footer-section h3 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .footer-section p,
    .footer-section li {
        color: var(--gray-light);
        line-height: 1.6;
        margin-bottom: 0.5rem;
    }

    .footer-section ul {
        list-style: none;
    }

    .footer-section a {
        color: var(--gray-light);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .footer-section a:hover {
        color: white;
    }

    .footer-social {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .social-link {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .social-link:hover {
        background: var(--primary);
        transform: translateY(-2px);
    }

    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: 2rem;
        padding-top: 2rem;
        text-align: center;
        color: var(--gray-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .footer-logo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        color: white;
    }

    .footer-logo i {
        font-size: 1.5rem;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Mobile Menu */
    .mobile-menu-btn {
        display: none;
        position: fixed;
        top: 85px;
        left: 1rem;
        z-index: 1001;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: none;
        border-radius: var(--radius);
        width: 48px;
        height: 48px;
        color: var(--primary);
        font-size: 1.2rem;
        cursor: pointer;
        box-shadow: var(--shadow);
        transition: all 0.3s ease;
    }

    .mobile-menu-btn:hover {
        background: white;
        transform: scale(1.05);
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .footer {
            margin-left: 0;
        }

        .mobile-menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .navbar-actions {
            gap: 0.5rem;
        }

        .navbar-time {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .top-navbar {
            padding: 0 1rem;
        }

        .navbar-title {
            font-size: 1.25rem;
        }

        .footer {
            padding: 2rem 1rem 1.5rem;
        }

        .footer-content {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .footer-bottom {
            flex-direction: column;
            text-align: center;
        }
    }

    @media (max-width: 480px) {
        .navbar-user {
            padding: 0.25rem 0.5rem;
        }

        .navbar-username {
            display: none;
        }
    }

    @keyframes logoFloat {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-5px) rotate(2deg); }
    }

    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="top-navbar">
        <a href="index.php" class="navbar-brand">
            <div class="navbar-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <span class="navbar-title">Quizify Admin</span>
        </a>
        
        <div class="navbar-actions">
            <div class="navbar-time" id="currentTime"></div>
            <div class="navbar-user">
                <div class="navbar-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <span class="navbar-username"><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
        </div>
    </nav>

    <button class="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </button>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="brand-text">Quizify</div>
            <div class="brand-subtitle">Admin Portal</div>
        </div>

        <div class="user-profile">
            <div class="user-card">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <h4><?=htmlspecialchars($_SESSION['username'])?></h4>
                    <span>Administrator</span>
                </div>
            </div>
        </div>

        <nav>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="create_quiz.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        Create Quiz
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_quizzes.php" class="nav-link">
                        <i class="fas fa-tasks"></i>
                        Manage Quizzes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_users.php" class="nav-link">
                        <i class="fas fa-users-cog"></i>
                        Manage Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="rank.php" class="nav-link">
                        <i class="fas fa-trophy"></i>
                        Rankings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_passkey.php" class="nav-link active">
                        <i class="fas fa-key"></i>
                        Admin Passkey
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile_admin.php" class="nav-link">
                        <i class="fas fa-user-shield"></i>
                        Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-key"></i>
                Admin Passkey Management
            </h1>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?=htmlspecialchars($success)?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?=htmlspecialchars($error)?>
            </div>
        <?php endif; ?>

        <!-- Current Passkey Status -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i>
                <h3>Current Passkey Status</h3>
                <div style="margin-left: auto;">
                    <span class="status-badge <?= $passkey_setting['is_enabled'] ? 'status-enabled' : 'status-disabled' ?>">
                        <i class="fas fa-<?= $passkey_setting['is_enabled'] ? 'check' : 'times' ?>"></i>
                        <?= $passkey_setting['is_enabled'] ? 'Enabled' : 'Disabled' ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="passkey-display">
                    <p style="margin-bottom: 0.5rem; color: var(--gray); font-weight: 500;">Current Admin Passkey:</p>
                    <div class="passkey-value" id="passkeyValue" style="filter: blur(4px); cursor: pointer;" onclick="togglePasskeyVisibility()">
                        <?=htmlspecialchars($passkey_setting['setting_value'])?>
                    </div>
                    <p style="margin-top: 0.5rem; color: var(--gray); font-size: 0.875rem;">
                        <i class="fas fa-eye"></i> Click to reveal/hide passkey
                    </p>
                </div>
                
                <p style="color: var(--gray); margin-bottom: 1.5rem;">
                    <?php if ($passkey_setting['is_enabled']): ?>
                        <i class="fas fa-shield-alt" style="color: var(--success);"></i>
                        Admin registration requires this passkey. New admins must provide the correct passkey to register.
                    <?php else: ?>
                        <i class="fas fa-shield-alt" style="color: var(--danger);"></i>
                        Admin registration is open. Anyone can register as an admin without a passkey.
                    <?php endif; ?>
                </p>

                <!-- Toggle Passkey Requirement -->
                <form method="post" style="margin-bottom: 2rem;">
                    <input type="hidden" name="action" value="toggle_status">
                    <div class="form-switch">
                        <label class="switch">
                            <input type="checkbox" name="is_enabled" <?= $passkey_setting['is_enabled'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span class="slider"></span>
                        </label>
                        <div class="switch-label">
                            <strong>Require Passkey for Admin Registration</strong>
                            <div style="font-size: 0.875rem; color: var(--gray); margin-top: 0.25rem;">
                                When enabled, new admin registrations will require the passkey
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Passkey -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit"></i>
                <h3>Update Passkey</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="update_passkey">
                    <div class="form-group">
                        <label class="form-label" for="new_passkey">New Admin Passkey</label>
                        <input 
                            type="password" 
                            id="new_passkey"
                            name="new_passkey" 
                            class="form-input" 
                            placeholder="Enter new passkey (minimum 8 characters)"
                            required
                            minlength="8"
                        >
                        <p style="font-size: 0.875rem; color: var(--gray); margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i>
                            Choose a strong passkey that's difficult to guess. This will be required for all future admin registrations.
                        </p>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Passkey
                    </button>
                </form>
            </div>
        </div>

        <!-- Update History -->
        <?php if (!empty($update_history)): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i>
                <h3>Recent Changes</h3>
            </div>
            <div class="card-body">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Updated By</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($update_history as $history): ?>
                        <tr>
                            <td>
                                <i class="fas fa-user"></i>
                                <?=htmlspecialchars($history['username'] ?? 'System')?>
                            </td>
                            <td>
                                <i class="fas fa-clock"></i>
                                <?=date('M j, Y g:i A', strtotime($history['updated_at']))?>
                            </td>
                            <td>
                                <span class="status-badge <?= $history['is_enabled'] ? 'status-enabled' : 'status-disabled' ?>">
                                    <i class="fas fa-<?= $history['is_enabled'] ? 'check' : 'times' ?>"></i>
                                    <?= $history['is_enabled'] ? 'Enabled' : 'Disabled' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-btn')?.addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            const sidebar = document.querySelector('.sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 1024 && 
                !sidebar.contains(e.target) && 
                !menuBtn.contains(e.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        // Toggle passkey visibility
        function togglePasskeyVisibility() {
            const passkeyValue = document.getElementById('passkeyValue');
            if (passkeyValue.style.filter === 'blur(4px)') {
                passkeyValue.style.filter = 'none';
            } else {
                passkeyValue.style.filter = 'blur(4px)';
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        
    // Update current time in navbar
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour12: true, 
            hour: 'numeric', 
            minute: '2-digit' 
        });
        const timeElement = document.getElementById('currentTime');
        if (timeElement) {
            timeElement.textContent = timeString;
        }
    }

    // Update time immediately and then every minute
    updateTime();
    setInterval(updateTime, 60000);

    // Mobile menu toggle
    document.querySelector('.mobile-menu-btn')?.addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('active');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        const sidebar = document.querySelector('.sidebar');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        
        if (window.innerWidth <= 1024 && 
            !sidebar.contains(e.target) && 
            !menuBtn.contains(e.target) && 
            sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });

    // Smooth scrolling for footer links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    </script>
    <!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>About Quizify</h3>
            <p>Quizify is a comprehensive quiz management platform designed to help educators create, manage, and analyze quiz performance with ease.</p>
            <div class="footer-social">
                <a href="https://www.facebook.com/jhovan.balbuena.16/" class="social-link"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.linkedin.com/in/jhovan-balbuena-230009368/" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://www.instagram.com/jhovsbalbuena/" class="social-link"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
        
        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="create_quiz.php">Create Quiz</a></li>
                <li><a href="manage_quizzes.php">Manage Quizzes</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="rank.php">Rankings</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>Support</h3>
            <ul>
                <li><a href="#">Help Center</a></li>
                <li><a href="#">Documentation</a></li>
                <li><a href="#">Contact Support</a></li>
                <li><a href="#">System Status</a></li>
                <li><a href="#">Privacy Policy</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>Contact Info</h3>
            <p><i class="fas fa-envelope"></i> madelyneway78@gmail.com</p>
            <p> jhovanbalbuena27@gmail.com</p>
            <p><i class="fas fa-phone"></i> +63 9633 316076</p>
            <p><i class="fas fa-map-marker-alt"></i> Silago, Southern Leyte</p>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="footer-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Quizify Admin Portal</span>
        </div>
        <p>&copy; <?= date('Y') ?> Quizify. All rights reserved. Built with ❤️ for educators.</p>
    </div>
</footer>
</body>
</html>
