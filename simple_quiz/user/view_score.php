<?php
require '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$quiz_id = $_GET['quiz_id'] ?? null;

// If a quiz_id is provided, show detailed score for that quiz
if ($quiz_id) {
    $stmtQuiz = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmtQuiz->execute([$quiz_id]);
    $quiz = $stmtQuiz->fetch();
    if (!$quiz) {
        die('Quiz not found.');
    }

    // Fetch user's submission for this quiz
    $stmtSub = $pdo->prepare("SELECT * FROM user_submissions WHERE user_id = ? AND quiz_id = ?");
    $stmtSub->execute([$user_id, $quiz_id]);
    $submission = $stmtSub->fetch();
    if (!$submission) {
        die('You have not taken this quiz yet.');
    }

    // Calculate percentage
    $score_percentage = 0;
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ?");
    $stmtTotal->execute([$quiz_id]);
    $total_questions = $stmtTotal->fetchColumn();
    if ($total_questions > 0) {
        $score_percentage = round(($submission['score'] / $total_questions) * 100);
    }

    // Fetch questions and user's answers with correctness
    $stmtQuestions = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
    $stmtQuestions->execute([$quiz_id]);
    $questions = $stmtQuestions->fetchAll();

    $stmtUserAnswers = $pdo->prepare("
        SELECT ua.question_id, ua.choice_id, c.choice_text, c.is_correct
        FROM user_answers ua
        JOIN choices c ON ua.choice_id = c.id
        WHERE ua.submission_id = ?
    ");
    $stmtUserAnswers->execute([$submission['id']]);
    $userAnswers = [];
    foreach ($stmtUserAnswers->fetchAll() as $ua) {
        $userAnswers[$ua['question_id']] = $ua;
    }
}
else {
    // Show list of quizzes user has taken with scores
    $stmt = $pdo->prepare("
        SELECT us.*, q.title 
        FROM user_submissions us
        JOIN quizzes q ON us.quiz_id = q.id
        WHERE us.user_id = ?
        ORDER BY us.submitted_at DESC
    ");
    $stmt->execute([$user_id]);
    $submissions = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= $quiz_id ? "Quiz Score - " . strip_tags($quiz['title']) : "My Quiz Scores" ?></title>
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

    /* Main Content Styles */
    .main-content {
        margin-left: var(--sidebar-width);
        padding: 2rem;
    }

    .page-header {
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
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

    /* Score Card Styles */
    .score-card {
        background: var(--bg-white);
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .score-circle {
        width: 150px;
        height: 150px;
        margin: 0 auto 1.5rem;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-color);
        background: conic-gradient(
            var(--primary-color) <?= $score_percentage ?? 0 ?>%,
            var(--border-color) <?= $score_percentage ?? 0 ?>% 100%
        );
        border-radius: 50%;
    }

    .score-circle::before {
        content: '';
        position: absolute;
        inset: 10px;
        background: var(--bg-white);
        border-radius: 50%;
    }

    .score-value {
        position: relative;
        z-index: 1;
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

    .correct {
        color: var(--success-color);
        background: #F0FDF4;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .wrong {
        color: var(--danger-color);
        background: #FEF2F2;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
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

    .empty-state {
        text-align: center;
        padding: 3rem;
        background: var(--bg-white);
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .empty-state i {
        font-size: 3rem;
        color: var(--text-secondary);
        margin-bottom: 1rem;
    }

    .empty-state p {
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
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

        .table-container {
            overflow-x: auto;
        }

        table {
            min-width: 600px;
        }

        .score-circle {
            width: 120px;
            height: 120px;
            font-size: 2rem;
        }
    }
    .footer{
        background: linear-gradient(180deg, #2D3748 0%, #1A202C 100%);
        color: var(--sidebar-text);
        padding: 1rem;
        text-align: center;
        font-size: 0.875rem;
        margin-top: 30rem;
       
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
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        border-radius: 50%;
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
                 <!-- Add this inside <ul class="nav-menu"> in your sidebar -->
                <li class="nav-item">
                    <a href="profile_user.php" class="nav-link">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="view_score.php" class="nav-link active">
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

    <div class="main-content">
        <?php if ($quiz_id): ?>
            <div class="page-header">
                <a href="view_score.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to My Scores
                </a>
            </div>
            
            <h1 class="page-title"><?=strip_tags($quiz['title'])?></h1>
            
            <div class="score-card">
                <div class="score-circle">
                    <div class="score-value"><?=htmlspecialchars($submission['score'])?></div>
                </div>
                <div style="color: var(--text-secondary);">out of <?=htmlspecialchars($total_questions)?> points</div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Your Answer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): 
                            $uAnswer = $userAnswers[$q['id']] ?? null;
                            $user_choice_text = $uAnswer['choice_text'] ?? 'No Answer';
                            $is_correct = $uAnswer['is_correct'] ?? 0;
                        ?>
                        <tr>
                            <td><?= $q['question_text'] ?></td>
                            <td><?= $user_choice_text ?></td>
                            <td>
                                <span class="<?= $is_correct ? 'correct' : 'wrong' ?>">
                                    <i class="fas fa-<?= $is_correct ? 'check' : 'times' ?>"></i>
                                    <?= $is_correct ? 'Correct' : 'Incorrect' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <h1 class="page-title">My Quiz Scores</h1>
            
            <?php if (empty($submissions)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <p>You haven't taken any quizzes yet.</p>
                    <a href="index.php" class="action-btn btn-primary">
                        <i class="fas fa-play"></i>
                        Take a Quiz
                    </a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Quiz Title</th>
                                <th>Score</th>
                                <th>Date Taken</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td><?=strip_tags($sub['title'])?></td>
                                <td>
                                    <span class="correct">
                                        <i class="fas fa-star"></i>
                                        <?=htmlspecialchars($sub['score'])?>
                                    </span>
                                </td>
                                <td><?=htmlspecialchars($sub['submitted_at'])?></td>
                                <td>
                                    <a href="view_score.php?quiz_id=<?=htmlspecialchars($sub['quiz_id'])?>" class="action-btn btn-primary">
                                        <i class="fas fa-eye"></i>
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
    </script>
      
</body>
</html>
