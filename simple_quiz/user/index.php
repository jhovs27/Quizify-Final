<?php
require '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$is_suspended = false;
//add notification
if ($user_id) {
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user && $user['status'] === 'suspended') {
        $is_suspended = true;
    }
}

// Check if current user is top performer
$isTopPerformer = false;
$topPerformerStats = null;

if ($user_id) {
    // Create temporary table for user rankings
    $pdo->query("
        CREATE TEMPORARY TABLE IF NOT EXISTS user_performance AS
        SELECT 
            u.id,
            u.username,
            COUNT(DISTINCT us.quiz_id) as quizzes_taken,
            COALESCE(SUM(us.score), 0) as total_points,
            COALESCE(AVG(
                (us.score * 100.0) / (
                    SELECT COUNT(*) 
                    FROM questions q 
                    WHERE q.quiz_id = us.quiz_id
                )
            ), 0) as avg_percentage
        FROM users u
        LEFT JOIN user_submissions us ON u.id = us.user_id
        WHERE u.role = 'user' AND u.status = 'active'
        GROUP BY u.id, u.username
        HAVING quizzes_taken > 0
        ORDER BY total_points DESC, quizzes_taken DESC, avg_percentage DESC
        LIMIT 1
    ");
    
    // Get the top performer
    $topPerformerQuery = $pdo->query("SELECT * FROM user_performance LIMIT 1");
    $topPerformer = $topPerformerQuery->fetch();
    
    if ($topPerformer && $topPerformer['id'] == $user_id) {
        $isTopPerformer = true;
        $topPerformerStats = $topPerformer;
    }
    
    // Clean up temporary table
    $pdo->query("DROP TEMPORARY TABLE IF EXISTS user_performance");
}

// Fetch all quizzes
$stmt = $pdo->prepare("SELECT quizzes.id, quizzes.title, users.username, quizzes.created_at FROM quizzes JOIN users ON quizzes.created_by = users.id ORDER BY quizzes.created_at DESC");
$stmt->execute();
$quizzes = $stmt->fetchAll();

// Fetch quizzes taken by user
$stmtDone = $pdo->prepare("SELECT quiz_id FROM user_submissions WHERE user_id = ?");
$stmtDone->execute([$user_id]);
$doneQuizIds = $stmtDone->fetchAll(PDO::FETCH_COLUMN);

// Count total and completed quizzes
$totalQuizzes = count($quizzes);
$completedQuizzes = count($doneQuizIds);
$completionRate = $totalQuizzes > 0 ? round(($completedQuizzes / $totalQuizzes) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Dashboard - Quiz App</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-color: #4F46E5;
        --primary-hover: #4338CA;
        --success-color: #059669;
        --danger-color: #DC2626;
        --warning-color: #EAB308;
        --border-color: #E5E7EB;
        --text-primary: #1F2937;
        --text-secondary: #4B5563;
        --bg-primary: #F9FAFB;
        --bg-secondary: #F3F4F6;
        --bg-white: #FFFFFF;
        --sidebar-width: 250px;
        --gold-color: #F59E0B;
        --gold-light: #FEF3C7;
        --gold-dark: #D97706;
        
        /* New sidebar colors */
        --sidebar-bg: linear-gradient(180deg, #2D3748 0%, #1A202C 100%);
        --sidebar-text: #E2E8F0;
        --sidebar-hover: rgba(255, 255, 255, 0.1);
        --sidebar-active: rgba(79, 70, 229, 0.8);
        --sidebar-border: rgba(255, 255, 255, 0.1);
        --sidebar-header-bg: rgba(0, 0, 0, 0.2);
        --sidebar-footer-bg: rgba(0, 0, 0, 0.2);
        --sidebar-icon-color: #A0AEC0;
        --sidebar-active-icon: #FFFFFF;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: var(--bg-primary);
        color: var(--text-primary);
        line-height: 1.5;
    }

    /* Enhanced Sidebar Styles */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        color: var(--sidebar-text);
        padding: 0;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        z-index: 1000;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--sidebar-hover) transparent;
    }

    .sidebar::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background-color: var(--sidebar-hover);
        border-radius: 20px;
    }

    .sidebar-header {
        padding: 1.5rem;
        background: var(--sidebar-header-bg);
        border-bottom: 1px solid var(--sidebar-border);
        margin-bottom: 0;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.2rem;
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .user-name {
        font-weight: 600;
        color: var(--sidebar-text);
        font-size: 1rem;
    }

    .user-role {
        font-size: 0.8rem;
        color: var(--sidebar-icon-color);
        background: rgba(255, 255, 255, 0.1);
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        display: inline-block;
        margin-top: 0.2rem;
    }

    .nav-menu {
        list-style: none;
        padding: 1rem 0.75rem;
    }

    .nav-item {
        margin-bottom: 0.5rem;
        position: relative;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.9rem 1.2rem;
        color: var(--sidebar-text);
        text-decoration: none;
        border-radius: 0.75rem;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .nav-link::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 0;
        background: var(--primary-color);
        border-radius: 0;
        opacity: 0;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        background: var(--sidebar-hover);
        color: white;
        transform: translateX(3px);
    }

    .nav-link.active {
        background: var(--sidebar-active);
        color: white;
        font-weight: 500;
    }

    .nav-link.active::before {
        width: 4px;
        opacity: 1;
    }

    .nav-link i {
        width: 1.5rem;
        text-align: center;
        font-size: 1.1rem;
        color: var(--sidebar-icon-color);
        transition: all 0.2s ease;
        position: relative;
        z-index: 2;
    }

    .nav-link:hover i,
    .nav-link.active i {
        color: var(--sidebar-active-icon);
    }

    .nav-link span {
        position: relative;
        z-index: 2;
    }

    /* Main Content Styles */
    .main-content {
        margin-left: var(--sidebar-width);
        padding: 2rem;
    }

    .page-header {
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1rem;
    }

    /* Top Performer Achievement Card */
    .achievement-card {
        background: linear-gradient(135deg, var(--gold-color), var(--gold-dark));
        color: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        animation: achievementSlideIn 0.8s ease-out;
    }

    .achievement-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        animation: float 6s ease-in-out infinite;
    }

    .achievement-card::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -10%;
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        animation: float 8s ease-in-out infinite reverse;
    }

    .achievement-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        position: relative;
        z-index: 2;
    }

    .trophy-icon {
        font-size: 2.5rem;
        color: #FDE68A;
        animation: trophyBounce 2s ease-in-out infinite;
    }

    .achievement-content {
        position: relative;
        z-index: 2;
    }

    .achievement-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .achievement-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 1rem;
    }

    .achievement-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .achievement-stat {
        text-align: center;
        background: rgba(255, 255, 255, 0.15);
        padding: 1rem;
        border-radius: 0.5rem;
        backdrop-filter: blur(10px);
    }

    .achievement-stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        display: block;
    }

    .achievement-stat-label {
        font-size: 0.75rem;
        opacity: 0.8;
        margin-top: 0.25rem;
    }

    @keyframes achievementSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes trophyBounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-20px);
        }
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--bg-white);
        padding: 1.5rem;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .stat-title {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .stat-description {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-top: 0.25rem;
    }

    /* Table Styles */
    .table-container {
        background: var(--bg-white);
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    th {
        background: var(--bg-secondary);
        font-weight: 600;
        color: var(--text-primary);
    }

    td {
        color: var(--text-secondary);
    }

    .status-done {
        color: var(--success-color);
        background: #F0FDF4;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-notdone {
        color: var(--danger-color);
        background: #FEF2F2;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
    }

    .btn-outline {
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }

    .btn-outline:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        transform: translateY(-1px);
    }

    /* Mobile Styles */
    .menu-toggle {
        display: none;
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 1001;
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 0.5rem;
        border-radius: 0.5rem;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            box-shadow: none;
        }

        .sidebar.active {
            transform: translateX(0);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .main-content {
            margin-left: 0;
            padding: 1rem;
        }

        .menu-toggle {
            display: block;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            min-width: 600px;
        }

        .achievement-card {
            padding: 1.5rem;
        }

        .achievement-title {
            font-size: 1.25rem;
        }

        .achievement-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .trophy-icon {
            font-size: 2rem;
        }
    }
    .footer{
        background: linear-gradient(180deg, #2D3748 0%, #1A202C 100%);
        color: var(--sidebar-text);
        padding: 1rem;
        text-align: center;
        font-size: 0.875rem;
        margin-top: 3rem;
       
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
        background: var(--primary-color);
        border-radius: 50%;
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
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
        background: linear-gradient(135deg, lightblue,rgb(243, 28, 114));
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
        color: var(--primary-color);
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
        background: green;
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
</style>
</head>
<body>
    <nav class="top-navbar">
    <a href="index.php" class="navbar-brand">
        <div class="navbar-logo">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <span class="navbar-title">Quizify Student</span>
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


    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar">
        <div class="sidebar-header">
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <div>
                    <div class="user-name"><?=htmlspecialchars($_SESSION['username'])?></div>
                    <div class="user-role">Student</div>
                </div>
            </div>
        </div>

        <nav>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <!-- Add this inside <ul class="nav-menu"> in your sidebar -->
                <li class="nav-item">
                    <a href="profile_user.php" class="nav-link">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="view_score.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>My Scores</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="rank.php" class="nav-link">
                        <i class="fas fa-trophy"></i>
                        <span>Rankings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <?php if ($is_suspended): ?>
        <div style="
            max-width: 700px;
            margin: 2rem auto 1.5rem auto;
            padding: 1.5rem 2rem;
            background: linear-gradient(90deg, #f87171 0%, #fbbf24 100%);
            color: #fff;
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(239,68,68,0.10);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            font-size: 1.15rem;
            font-weight: 500;
            margin-top: 80px;
            ">
            <i class="fas fa-ban" style="font-size:2.2rem;color:#fff;"></i>
            <div>
                <strong>Your account has been suspended by the admin.</strong>
                <div style="margin-top:0.3rem;font-size:1rem;">
                    You cannot take quizzes at this time. Please contact your administrator for more information.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Dashboard</h1>
        </div>

        <?php if ($isTopPerformer && $topPerformerStats): ?>
        <div class="achievement-card">
            <div class="achievement-header">
                <i class="fas fa-trophy trophy-icon"></i>
                <div class="achievement-content">
                    <div class="achievement-title">🎉 Top Performer!</div>
                    <div class="achievement-subtitle">
                        Congratulations! You are currently the #1 student with the highest total score!
                    </div>
                </div>
            </div>
            <div class="achievement-stats">
                <div class="achievement-stat">
                    <span class="achievement-stat-value"><?= number_format($topPerformerStats['total_points']) ?></span>
                    <div class="achievement-stat-label">Total Points</div>
                </div>
                <div class="achievement-stat">
                    <span class="achievement-stat-value"><?= $topPerformerStats['quizzes_taken'] ?></span>
                    <div class="achievement-stat-label">Quizzes Taken</div>
                </div>
                <div class="achievement-stat">
                    <span class="achievement-stat-value"><?= number_format($topPerformerStats['avg_percentage'], 1) ?>%</span>
                    <div class="achievement-stat-label">Avg Score</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-book"></i>
                    Total Quizzes
                </div>
                <div class="stat-value"><?=$totalQuizzes?></div>
                <div class="stat-description">Available quizzes</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-check-circle"></i>
                    Completed
                </div>
                <div class="stat-value"><?=$completedQuizzes?></div>
                <div class="stat-description">Quizzes completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-chart-pie"></i>
                    Completion Rate
                </div>
                <div class="stat-value"><?=$completionRate?>%</div>
                <div class="stat-description">Of total quizzes</div>
            </div>
        </div>

        <?php if (empty($quizzes)): ?>
            <div class="stat-card" style="text-align: center;">
                <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <p>No quizzes available at the moment.</p>
            </div>
        <?php else: ?>
            <!-- Search Bar -->
            <div style="
                background: var(--bg-white);
                padding: 1.5rem;
                border-radius: 0.75rem 0.75rem 0 0;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                border-bottom: 1px solid var(--border-color);
                margin-bottom: 0;
            ">
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    max-width: 400px;
                ">
                    <div style="
                        position: relative;
                        flex: 1;
                    ">
                        <i class="fas fa-search" style="
                            position: absolute;
                            left: 1rem;
                            top: 50%;
                            transform: translateY(-50%);
                            color: var(--text-secondary);
                            font-size: 0.9rem;
                        "></i>
                        <input 
                            type="text" 
                            id="quizSearch" 
                            placeholder="Search quizzes..." 
                            style="
                                width: 100%;
                                padding: 0.75rem 1rem 0.75rem 2.5rem;
                                border: 1px solid var(--border-color);
                                border-radius: 0.5rem;
                                font-size: 0.9rem;
                                transition: all 0.2s ease;
                                background: var(--bg-white);
                                color: var(--text-primary);
                            "
                            onkeyup="filterQuizzes()"
                        >
                    </div>
                    <button 
                        onclick="clearSearch()" 
                        style="
                            padding: 0.75rem;
                            background: var(--bg-secondary);
                            border: 1px solid var(--border-color);
                            border-radius: 0.5rem;
                            color: var(--text-secondary);
                            cursor: pointer;
                            transition: all 0.2s ease;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        "
                        onmouseover="this.style.background='var(--primary-color)'; this.style.color='white'; this.style.borderColor='var(--primary-color)'"
                        onmouseout="this.style.background='var(--bg-secondary)'; this.style.color='var(--text-secondary)'; this.style.borderColor='var(--border-color)'"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="table-container" style="border-radius: 0 0 0.75rem 0.75rem;">
                <table>
                    <thead>
                        <tr>
                            <th>Quiz Title</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $quiz): ?>
                        <?php $done = in_array($quiz['id'], $doneQuizIds); ?>
                        <tr>
                            <td><?=strip_tags($quiz['title'])?></td>
                            <td><?=htmlspecialchars($quiz['username'])?></td>
                            <td><?=htmlspecialchars($quiz['created_at'])?></td>
                            <td>
                                <span class="status-<?= $done ? 'done' : 'notdone' ?>">
                                    <i class="fas fa-<?= $done ? 'check' : 'times' ?>"></i>
                                    <?= $done ? 'Completed' : 'Not Started' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($is_suspended): ?>
                                    <span style="color:#ef4444;font-weight:600;opacity:0.7;">
                                        <i class="fas fa-ban"></i> Suspended
                                    </span>
                                <?php elseif (!$done): ?>
                                    <a href="take_quiz.php?quiz_id=<?=htmlspecialchars($quiz['id'])?>" class="action-btn btn-primary">
                                        <i class="fas fa-play"></i>
                                        Take Quiz
                                    </a>
                                <?php else: ?>
                                    <a href="view_score.php?quiz_id=<?=htmlspecialchars($quiz['id'])?>" class="action-btn btn-outline">
                                        <i class="fas fa-chart-line"></i>
                                        View Score
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Quizify. All rights reserved.</p>
     </footer>                               
    <script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const menuToggle = document.querySelector('.menu-toggle');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(event.target) && 
            !menuToggle.contains(event.target) &&
            sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });

    function filterQuizzes() {
        const searchInput = document.getElementById('quizSearch');
        const searchTerm = searchInput.value.toLowerCase();
        const tableRows = document.querySelectorAll('tbody tr');
        
        tableRows.forEach(row => {
            const quizTitle = row.cells[0].textContent.toLowerCase();
            const createdBy = row.cells[1].textContent.toLowerCase();
            
            if (quizTitle.includes(searchTerm) || createdBy.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function clearSearch() {
        document.getElementById('quizSearch').value = '';
        filterQuizzes();
    }

    // Add focus styling for search input
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('quizSearch');
        if (searchInput) {
            searchInput.addEventListener('focus', function() {
                this.style.borderColor = 'var(--primary-color)';
                this.style.boxShadow = '0 0 0 3px rgba(79, 70, 229, 0.1)';
            });
            
            searchInput.addEventListener('blur', function() {
                this.style.borderColor = 'var(--border-color)';
                this.style.boxShadow = 'none';
            });
        }
    });
    </script>
</body>
</html>
