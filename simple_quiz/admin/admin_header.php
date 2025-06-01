<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get the current page name for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $page_title ?? 'Admin Panel' ?> - Quiz App</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_styles.css">
    <?php if (isset($additional_css)): ?>
    <style>
        <?= $additional_css ?>

        
    </style>
    <?php endif; ?>
</head>
<body>
    <button class="menu-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <div>
                    <div class="user-name"><?=htmlspecialchars($_SESSION['username'])?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
        </div>

        <nav>
            <ul class="nav-menu">
                <li>
                    <a href="index.php" <?= $current_page === 'index.php' ? 'class="active"' : '' ?>>
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="create_quiz.php" <?= $current_page === 'create_quiz.php' ? 'class="active"' : '' ?>>
                        <i class="fas fa-plus"></i>
                        Create Quiz
                    </a>
                </li>
                <li>
                    <a href="manage_quizzes.php" <?= $current_page === 'manage_quizzes.php' ? 'class="active"' : '' ?>>
                        <i class="fas fa-tasks"></i>
                        Manage Quizzes
                    </a>
                </li>
                <li>
                    <a href="manage_users.php" <?= $current_page === 'manage_users.php' ? 'class="active"' : '' ?>>
                        <i class="fas fa-users"></i>
                        Manage Users
                    </a>
                </li>
                <li>
                    <a href="rank.php" <?= $current_page === 'rank.php' ? 'class="active"' : '' ?>>
                        <i class="fas fa-trophy"></i>
                        Rankings
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
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
                <i class="<?= $page_icon ?? 'fas fa-tachometer-alt' ?>"></i>
                <?= $page_title ?? 'Dashboard' ?>
            </h1>
        </div> 