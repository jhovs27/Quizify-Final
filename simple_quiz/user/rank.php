<?php
require '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$quiz_id = $_GET['quiz_id'] ?? null;

// Calculate overall ranking - UPDATED to prioritize total points first
function calculateOverallRanking($pdo) {
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.username,
            COUNT(DISTINCT us.quiz_id) as quizzes_taken,
            SUM(us.score) as total_score,
            ROUND(AVG(
                (us.score * 100.0) / (
                    SELECT COUNT(*) 
                    FROM questions q 
                    WHERE q.quiz_id = us.quiz_id
                )
            ), 2) as avg_percentage
        FROM users u
        LEFT JOIN user_submissions us ON u.id = us.user_id
        WHERE u.role = 'user'
        GROUP BY u.id, u.username
        ORDER BY total_score DESC, avg_percentage DESC, quizzes_taken DESC
    ");
    return $stmt->fetchAll();
}

// Calculate quiz-specific ranking
function calculateQuizRanking($pdo, $quiz_id) {
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            us.score,
            ROUND((us.score * 100.0) / (
                SELECT COUNT(*) 
                FROM questions 
                WHERE quiz_id = ?
            ), 2) as percentage,
            us.submitted_at
        FROM users u
        JOIN user_submissions us ON u.id = us.user_id
        WHERE us.quiz_id = ?
        ORDER BY us.score DESC, us.submitted_at ASC
    ");
    $stmt->execute([$quiz_id, $quiz_id]);
    return $stmt->fetchAll();
}

// Get quiz details if quiz_id is provided
$quiz = null;
if ($quiz_id) {
    $stmtQuiz = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmtQuiz->execute([$quiz_id]);
    $quiz = $stmtQuiz->fetch();
    if (!$quiz) {
        die('Quiz not found.');
    }
}

// Get rankings
$rankings = $quiz_id ? calculateQuizRanking($pdo, $quiz_id) : calculateOverallRanking($pdo);

// Find user's rank
$userRank = 1;
foreach ($rankings as $index => $rank) {
    if ($rank['id'] == $user_id) {
        $userRank = $index + 1;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= $quiz_id ? "Quiz Rankings - " . strip_tags($quiz['title']) : "Overall Rankings" ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Reuse existing CSS variables and base styles */
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
        --gold: #FFD700;
        --silver: #C0C0C0;
        --bronze: #CD7F32;
        
        /* Enhanced sidebar colors */
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

    .main-content {
        margin-left: var(--sidebar-width);
        padding: 2rem;
    }

    /* Search Bar Styles */
    .search-container {
        background: var(--bg-white);
        padding: 1.5rem;
        border-radius: 1rem 1rem 0 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 0;
        border-bottom: 1px solid var(--border-color);
    }

    .search-wrapper {
        display: flex;
        align-items: center;
        gap: 1rem;
        max-width: 400px;
    }

    .search-input-wrapper {
        position: relative;
        flex: 1;
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .search-input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        background: var(--bg-white);
        color: var(--text-primary);
    }

    .search-input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        outline: none;
    }

    .search-clear-btn {
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
    }

    .search-clear-btn:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    /* New Ranking Styles */
    .ranking-header {
        background: var(--bg-white);
        padding: 2rem;
        border-radius: 1rem;
        margin-bottom: 2rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .user-rank {
        font-size: 3rem;
        font-weight: 800;
        color: var(--primary-color);
        margin: 1rem 0;
    }

    .rank-card {
        background: var(--bg-white);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s ease;
    }

    .rank-card:hover {
        transform: translateY(-2px);
    }

    .rank-position {
        font-size: 1.5rem;
        font-weight: 700;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .rank-1 {
        background: var(--gold);
        color: var(--text-primary);
    }

    .rank-2 {
        background: var(--silver);
        color: var(--text-primary);
    }

    .rank-3 {
        background: var(--bronze);
        color: var(--text-primary);
    }

    .rank-info {
        flex: 1;
    }

    .rank-name {
        font-weight: 600;
        color: var(--text-primary);
    }

    .rank-stats {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    .rank-total-points {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 1rem;
        border-left: 1px solid var(--border-color);
        border-right: 1px solid var(--border-color);
    }

    .total-points-value {
        font-weight: 700;
        font-size: 1.25rem;
        color: var(--primary-color);
    }

    .total-points-label {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    .rank-score {
        font-weight: 600;
        color: var(--primary-color);
        font-size: 1.125rem;
        min-width: 100px;
        text-align: right;
    }

    .current-user {
        background: var(--primary-color);
        color: white;
    }

    .current-user .rank-name,
    .current-user .rank-stats,
    .current-user .total-points-label,
    .current-user .rank-score {
        color: white;
    }

    .current-user .total-points-value {
        color: white;
    }

    .current-user .rank-total-points {
        border-color: rgba(255, 255, 255, 0.2);
    }

    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--bg-white);
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        color: var(--text-secondary);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s ease;
        margin-top: 1rem;
    }

    .back-button:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        transform: translateX(-2px);
    }

    .page-title {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1rem;
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

        .search-wrapper {
            max-width: 100%;
        }

        .rank-card {
            flex-wrap: wrap;
        }

        .rank-total-points {
            order: 3;
            width: 100%;
            border-left: none;
            border-right: none;
            border-top: 1px solid var(--border-color);
            padding-top: 0.75rem;
            margin-top: 0.75rem;
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
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
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
                    <a href="rank.php" class="nav-link active">
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

    <div class="main-content">
        <div class="ranking-header">
            <h1 class="page-title">
                <?= $quiz_id ? htmlspecialchars($quiz['title']) . " Rankings" : "Overall Rankings" ?>
            </h1>
            <?php if ($quiz_id): ?>
                <a href="rank.php" class="back-button">
                    <i class="fas fa-trophy"></i>
                    View Overall Rankings
                </a>
            <?php endif; ?>
            <div class="user-rank">
                Your Rank: #<?=htmlspecialchars($userRank)?>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <div class="search-wrapper">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input 
                        type="text" 
                        id="rankingSearch" 
                        class="search-input"
                        placeholder="Search by username..." 
                        onkeyup="filterRankings()"
                    >
                </div>
                <button 
                    onclick="clearSearch()" 
                    class="search-clear-btn"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div id="rankingsList">
            <?php foreach ($rankings as $index => $rank): 
                $isCurrentUser = $rank['id'] == $user_id;
                $position = $index + 1;
            ?>
                <div class="rank-card <?= $isCurrentUser ? 'current-user' : '' ?>" data-username="<?=strtolower(htmlspecialchars($rank['username']))?>">
                    <div class="rank-position <?= $position <= 3 ? "rank-{$position}" : '' ?>">
                        <?= $position ?>
                    </div>
                    <div class="rank-info">
                        <div class="rank-name"><?=htmlspecialchars($rank['username'])?></div>
                        <div class="rank-stats">
                            <?php if ($quiz_id): ?>
                                Completed: <?=date('M j, Y', strtotime($rank['submitted_at']))?>
                            <?php else: ?>
                                Quizzes Taken: <?=htmlspecialchars($rank['quizzes_taken'])?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Total Points Column (Center) -->
                    <?php if (!$quiz_id): ?>
                    <div class="rank-total-points">
                        <div class="total-points-value"><?=htmlspecialchars($rank['total_score'])?></div>
                        <div class="total-points-label">Total Points</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="rank-score">
                        <?php if ($quiz_id): ?>
                            <?=htmlspecialchars($rank['score'])?> points
                        <?php else: ?>
                          <?= htmlspecialchars($rank['avg_percentage'] ?? '', ENT_QUOTES, 'UTF-8') ?>%

                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Quizify. All rights reserved.</p>
    </footer>                           
    <script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }

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

    // Search functionality
    function filterRankings() {
        const searchInput = document.getElementById('rankingSearch');
        const searchTerm = searchInput.value.toLowerCase();
        const rankCards = document.querySelectorAll('.rank-card');
        
        rankCards.forEach(card => {
            const username = card.getAttribute('data-username');
            
            if (username.includes(searchTerm)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function clearSearch() {
        document.getElementById('rankingSearch').value = '';
        filterRankings();
    }

    // Add focus styling for search input
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('rankingSearch');
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
