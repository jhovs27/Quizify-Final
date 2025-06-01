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