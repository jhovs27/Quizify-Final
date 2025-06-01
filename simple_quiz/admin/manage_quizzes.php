<?php
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle quiz deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $quiz_id = $_POST['quiz_id'] ?? null;

    if ($quiz_id) {
        $pdo->prepare("DELETE FROM user_submissions WHERE quiz_id = ?")->execute([$quiz_id]);
        $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$quiz_id]);
        $pdo->prepare("DELETE FROM quizzes WHERE id = ?")->execute([$quiz_id]);

        // Set a success message
        $_SESSION['message'] = 'Quiz deleted successfully.';
        header('Location: manage_quizzes.php');
        exit;
    } else {
        $_SESSION['error'] = 'Failed to delete the quiz.';
    }
}

// Fetch all quizzes with their statistics
$stmt = $pdo->query("
    SELECT 
        q.*,
        COUNT(DISTINCT us.user_id) as total_attempts,
        COALESCE(AVG(us.score), 0) as avg_score,
        COUNT(DISTINCT que.id) as total_questions
    FROM quizzes q
    LEFT JOIN user_submissions us ON q.id = us.quiz_id
    LEFT JOIN questions que ON q.id = que.quiz_id
    GROUP BY q.id
    ORDER BY q.created_at DESC
");
$quizzes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Quizzes - Admin - Quizify</title>
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
            padding: 1.25rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            position: relative;
            animation: slideInDown 0.4s ease-out;
            display: flex;
            align-items: center;
            gap: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #b91c1c;
            border-left: 4px solid var(--danger);
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Enhanced Table Styles */
        .quizzes-table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            animation: fadeInScale 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .quizzes-table-container::before {
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

        .quizzes-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .quizzes-table th {
            background: rgba(255, 255, 255, 0.8);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid rgba(203, 213, 225, 0.5);
            position: relative;
            transition: all 0.3s ease;
        }

        .quizzes-table th:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .quizzes-table th:hover:after {
            transform: scaleX(0.8);
        }

        .quizzes-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(203, 213, 225, 0.3);
            color: var(--dark);
            font-size: 0.9375rem;
        }

        .quizzes-table tbody tr {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.6);
        }

        .quizzes-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .quizzes-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Enhanced Action Buttons */
        .action-btn {
            padding: 0.75rem;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-right: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            box-shadow: var(--shadow-sm);
        }

        .btn-edit {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger);
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, var(--danger), #b91c1c);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        /* Enhanced Stats Badges */
        .stats-badge {
            background: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: var(--radius);
            font-size: 0.875rem;
            color: var(--dark);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border: 1px solid rgba(203, 213, 225, 0.3);
        }

        .stats-badge:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            background: white;
        }

        .stats-badge i {
            color: var(--primary);
        }

        .stats-badge.questions i {
            color: var(--secondary);
        }

        .stats-badge.attempts i {
            color: var(--accent);
        }

        .stats-badge.score i {
            color: var(--success);
        }

        .stats-badge.date i {
            color: var(--warning);
        }

        /* Quiz Title Styling */
        .quiz-title {
            font-weight: 600;
            color: var(--dark);
            transition: all 0.3s ease;
            position: relative;
            display: inline-block;
            padding-bottom: 2px;
        }

        .quiz-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: left;
        }

        .quizzes-table tr:hover .quiz-title:after {
            transform: scaleX(1);
        }

        /* Create Quiz Button */
        .create-quiz-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: var(--radius);
            padding: 1rem 2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            font-size: 1rem;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
            font-family: 'Poppins', sans-serif;
        }

        .create-quiz-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), #6d28d9);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: var(--radius-lg);
            margin: 2rem 0;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray-light);
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 2rem;
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

            .quizzes-table {
                display: block;
                overflow-x: auto;
            }

            .quizzes-table th,
            .quizzes-table td {
                padding: 1rem;
            }

            .stats-badge {
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
            }

            .action-btn {
                width: 36px;
                height: 36px;
                padding: 0.5rem;
                margin-right: 0.5rem;
            }
        }

        /* Animation Enhancements */
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile menu toggle
            const menuBtn = document.querySelector('.mobile-menu-btn');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuBtn) {
                menuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 1024 && 
                    !sidebar.contains(e.target) && 
                    !menuBtn.contains(e.target) && 
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length) {
                setTimeout(() => {
                    alerts.forEach(alert => {
                        alert.style.opacity = '0';
                        alert.style.transform = 'translateY(-20px)';
                        alert.style.transition = 'all 0.5s ease';
                        setTimeout(() => {
                            alert.style.display = 'none';
                        }, 500);
                    });
                }, 5000);
            }

            // Confirm delete action
            const deleteForms = document.querySelectorAll('.delete-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!confirm('Are you sure you want to delete this quiz? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
        });
        
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
                    <a href="manage_quizzes.php" class="nav-link active">
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
                    <a href="admin_passkey.php" class="nav-link">
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
                <i class="fas fa-tasks"></i>
                Manage Quizzes
            </h1>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['message'] ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <a href="create_quiz.php" class="create-quiz-btn">
            <i class="fas fa-plus"></i> Create New Quiz
        </a>

        <?php if (count($quizzes) > 0): ?>
            <div class="quizzes-table-container">
                <table class="quizzes-table">
                    <thead>
                        <tr>
                            <th>Quiz Title</th>
                            <th>Questions</th>
                            <th>Attempts</th>
                            <th>Avg. Score</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td>
                                    <div class="quiz-title">
                                        <?=htmlspecialchars($quiz['title'])?>
                                    </div>
                                </td>
                                <td>
                                    <span class="stats-badge questions">
                                        <i class="fas fa-list"></i>
                                        <?=number_format($quiz['total_questions'])?>
                                    </span>
                                </td>
                                <td>
                                    <span class="stats-badge attempts">
                                        <i class="fas fa-users"></i>
                                        <?=number_format($quiz['total_attempts'])?>
                                    </span>
                                </td>
                                <td>
                                    <span class="stats-badge score">
                                        <i class="fas fa-chart-line"></i>
                                        <?=number_format($quiz['avg_score'], 1)?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="stats-badge date">
                                        <i class="fas fa-calendar"></i>
                                        <?=date('Y-m-d', strtotime($quiz['created_at']))?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_quiz.php?id=<?= $quiz['id'] ?>" class="action-btn btn-edit" title="Edit Quiz">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post" class="delete-form" style="display: inline-block;">
                                        <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="action-btn btn-delete" title="Delete Quiz">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Quizzes Found</h3>
                <p>You haven't created any quizzes yet. Get started by creating your first quiz!</p>
                <a href="create_quiz.php" class="create-quiz-btn">
                    <i class="fas fa-plus"></i> Create Your First Quiz
                </a>
            </div>
        <?php endif; ?>
    </main>
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
