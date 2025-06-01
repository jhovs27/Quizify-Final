<?php
require '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fetch total registered users (excluding admin)
$stmtUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stmtUsers->execute();
$totalUsers = $stmtUsers->fetchColumn();

// Fetch total quizzes created
$stmtQuizzes = $pdo->prepare("SELECT COUNT(*) FROM quizzes");
$stmtQuizzes->execute();
$totalQuizzes = $stmtQuizzes->fetchColumn();

// Total uploaded quizzes same as totalQuizzes
$totalUploaded = $totalQuizzes;

// Get most recent quiz details
$stmtRecentQuiz = $pdo->prepare("SELECT * FROM quizzes ORDER BY created_at DESC LIMIT 1");
$stmtRecentQuiz->execute();
$recentQuiz = $stmtRecentQuiz->fetch();

$usersTaken = 0;
$passedUsers = 0;
$passedPercentage = 0;
$failedPercentage = 0;

if ($recentQuiz) {
    $quiz_id = $recentQuiz['id'];

    // Count users who took this quiz
    $stmtUsersTaken = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_submissions WHERE quiz_id = ?");
    $stmtUsersTaken->execute([$quiz_id]);
    $usersTaken = (int)$stmtUsersTaken->fetchColumn();

    // Get total number of questions in quiz
    $stmtQuestionsCount = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ?");
    $stmtQuestionsCount->execute([$quiz_id]);
    $totalQuestions = (int)$stmtQuestionsCount->fetchColumn();

    if ($totalQuestions > 0) {
        // Count passes: where score >= 50% of total questions
        $passingScore = ceil($totalQuestions / 2);

        $stmtPassCount = $pdo->prepare("SELECT COUNT(*) FROM user_submissions WHERE quiz_id = ? AND score >= ?");
        $stmtPassCount->execute([$quiz_id, $passingScore]);
        $passedUsers = (int)$stmtPassCount->fetchColumn();

        $failedUsers = $usersTaken - $passedUsers;
        if ($usersTaken > 0) {
            $passedPercentage = round(100 * $passedUsers / $usersTaken, 2);
            $failedPercentage = round(100 * $failedUsers / $usersTaken, 2);
        }
    }
}

// Fetch top scorer across all quizzes
$stmtTopScorer = $pdo->prepare("
    SELECT u.username, u.email, SUM(us.score) as total_score, COUNT(us.id) as attempts,
           ROUND(SUM(us.score) / NULLIF(COUNT(us.id),0), 2) as avg_score
    FROM users u
    JOIN user_submissions us ON u.id = us.user_id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY total_score DESC, avg_score DESC
    LIMIT 1
");
$stmtTopScorer->execute();
$topScorer = $stmtTopScorer->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Quizify Admin - Dashboard</title>
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
            display: flex;
            flex-direction: column;
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

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: 280px;
            height: calc(100vh - 70px);
            background: rgba(15, 23, 42, 0.95); 
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }

        .sidebar-content {
            padding: 1.5rem 0;
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
            margin-top: 70px;
            padding: 2rem;
            min-height: calc(100vh - 70px);
            transition: all 0.4s ease;
            flex: 1;
        }

        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
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

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .header-text h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .header-text p {
            color: var(--gray);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

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

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* Champion Spotlight */
        .champion-spotlight {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
            border-radius: var(--radius-xl);
            padding: 2.5rem;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(251, 191, 36, 0.3);
        }

        .champion-spotlight::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: spotlightRotate 10s linear infinite;
        }

        @keyframes spotlightRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .champion-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .champion-trophy {
            font-size: 5rem;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.3));
            animation: trophyBounce 4s ease-in-out infinite;
        }

        @keyframes trophyBounce {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-10px) scale(1.05); }
        }

        .champion-details h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .champion-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .champion-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .champion-email {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .champion-stats {
            display: flex;
            gap: 2rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .champion-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            transition: all 0.3s ease;
        }

        .stat-card:nth-child(1)::before { background: linear-gradient(90deg, var(--primary), var(--accent)); }
        .stat-card:nth-child(2)::before { background: linear-gradient(90deg, var(--success), #22c55e); }
        .stat-card:nth-child(3)::before { background: linear-gradient(90deg, var(--warning), #fb923c); }
        .stat-card:nth-child(4)::before { background: linear-gradient(90deg, var(--secondary), #a855f7); }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        .stat-card:hover::before {
            height: 6px;
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stat-card:nth-child(1) .stat-icon { background: linear-gradient(135deg, var(--primary), var(--accent)); }
        .stat-card:nth-child(2) .stat-icon { background: linear-gradient(135deg, var(--success), #22c55e); }
        .stat-card:nth-child(3) .stat-icon { background: linear-gradient(135deg, var(--warning), #fb923c); }
        .stat-card:nth-child(4) .stat-icon { background: linear-gradient(135deg, var(--secondary), #a855f7); }

        .stat-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .stat-card:hover .stat-icon::before {
            left: 100%;
        }

        .stat-title {
            font-size: 1rem;
            color: var(--gray);
            font-weight: 600;
            text-align: right;
        }

        .stat-value {
            font-size: 3rem;
            font-weight: 900;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-description {
            color: var(--gray);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .trend-up { color: var(--success); }
        .trend-down { color: var(--danger); }

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
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content,
            .footer {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .header-actions {
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

            .main-content {
                padding: 1rem;
            }

            .dashboard-header {
                padding: 1.5rem;
            }

            .header-text h1 {
                font-size: 2rem;
            }

            .champion-content {
                flex-direction: column;
                text-align: center;
            }

            .champion-trophy {
                font-size: 4rem;
            }

            .champion-stats {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-value {
                font-size: 2.5rem;
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
            .champion-spotlight {
                padding: 1.5rem;
            }

            .champion-details h2 {
                font-size: 1.5rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }

            .navbar-user {
                padding: 0.25rem 0.5rem;
            }

            .navbar-username {
                display: none;
            }
        }

        /* Loading Animation */
        .loading-shimmer {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-5px) rotate(2deg); }
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
        <div class="sidebar-content">
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">
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
        </div>
    </aside>

    <main class="main-content">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-text">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back! Here's what's happening with your quizzes today.</p>
                </div>
                <div class="header-actions">
                    <a href="create_quiz.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        New Quiz
                    </a>
                    <a href="manage_quizzes.php" class="btn btn-outline">
                        <i class="fas fa-cog"></i>
                        Manage
                    </a>
                </div>
            </div>
        </header>

        <?php if ($topScorer): ?>
        <section class="champion-spotlight">
            <div class="champion-content">
                <div class="champion-trophy">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="champion-details">
                    <h2>
                        🏆 Top Performer
                        <span class="champion-badge">
                            <i class="fas fa-crown"></i> Champion
                        </span>
                    </h2>
                    <div class="champion-name">
                        <i class="fas fa-user-star"></i>
                        <?=htmlspecialchars($topScorer['username'])?>
                    </div>
                    <div class="champion-email">
                        <i class="fas fa-envelope"></i>
                        <?=htmlspecialchars($topScorer['email'])?>
                    </div>
                    <div class="champion-stats">
                        <div class="champion-stat">
                            <i class="fas fa-star"></i>
                            Total: <?=number_format($topScorer['total_score'])?>
                        </div>
                        <div class="champion-stat">
                            <i class="fas fa-chart-bar"></i>
                            Average: <?=number_format($topScorer['avg_score'],2)?>
                        </div>
                        <div class="champion-stat">
                            <i class="fas fa-play-circle"></i>
                            Attempts: <?=number_format($topScorer['attempts'])?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-title">Total Users</div>
                </div>
                <div class="stat-value"><?=number_format($totalUsers)?></div>
                <div class="stat-description">Registered learners</div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    +12% this month
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-title">Total Quizzes</div>
                </div>
                <div class="stat-value"><?=number_format($totalQuizzes)?></div>
                <div class="stat-description">Active quizzes</div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    +5 new this week
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="stat-title">Success Rate</div>
                </div>
                <div class="stat-value"><?=number_format($passedPercentage)?>%</div>
                <div class="stat-description">Latest quiz performance</div>
                <div class="stat-trend <?= $passedPercentage >= 70 ? 'trend-up' : 'trend-down' ?>">
                    <i class="fas fa-arrow-<?= $passedPercentage >= 70 ? 'up' : 'down' ?>"></i>
                    <?= $passedPercentage >= 70 ? 'Excellent' : 'Needs improvement' ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-title">Participants</div>
                </div>
                <div class="stat-value"><?=number_format($usersTaken)?></div>
                <div class="stat-description">Latest quiz takers</div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    Active engagement
                </div>
            </div>
        </section>
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
            <p>&copy; 2024 Quizify. All rights reserved. Built with ❤️ for educators.</p>
        </div>
    </footer>

    <script>
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

        // Smooth page load animation
        window.addEventListener('load', () => {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });

        // Add intersection observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe stat cards for scroll animations
        document.querySelectorAll('.stat-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });

        // Add dynamic time-based greeting
        const updateGreeting = () => {
            const hour = new Date().getHours();
            const headerText = document.querySelector('.header-text p');
            let greeting = 'Welcome back!';
            
            if (hour < 12) greeting = 'Good morning!';
            else if (hour < 18) greeting = 'Good afternoon!';
            else greeting = 'Good evening!';
            
            if (headerText) {
                headerText.textContent = `${greeting} Here's what's happening with your quizzes today.`;
            }
        };

        updateGreeting();

        // Add keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                document.querySelector('.sidebar').classList.toggle('active');
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
</body>
</html>