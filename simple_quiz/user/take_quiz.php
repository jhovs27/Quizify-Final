<?php
require '../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user && $user['status'] === 'suspended') {
        echo '<div class="alert alert-danger" style="margin:1rem 0;">Your account has been suspended by the admin. You cannot take quizzes at this time.</div>';
        exit;
    }
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$quiz_id = $_GET['quiz_id'] ?? null;
if (!$quiz_id) {
    die('Quiz ID not specified.');
}

$user_id = $_SESSION['user_id'];

// Check if user already submitted this quiz
$stmtCheck = $pdo->prepare("SELECT id FROM user_submissions WHERE quiz_id = ? AND user_id = ?");
$stmtCheck->execute([$quiz_id, $user_id]);
if ($stmtCheck->fetch()) {
    // Already done, show message with link to view score
    $stmtQuizTitle = $pdo->prepare("SELECT title FROM quizzes WHERE id = ?");
    $stmtQuizTitle->execute([$quiz_id]);
    $quizTitle = $stmtQuizTitle->fetchColumn();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>Quiz Already Completed</title>
      <style>
        body {font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; background:#f9f9f9; text-align:center;}
        .box {background:#fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.15);}
        a {color:#007bff; text-decoration:none; font-weight:bold;}
        a:hover {text-decoration:underline;}
      </style>
    </head>
    <body>
      <div class="box">
        <h1>You have already completed this quiz:</h1>
        <h2><?=htmlspecialchars($quizTitle)?></h2>
        <p>You cannot retake the quiz.</p>
        <p><a href="view_score.php?quiz_id=<?=htmlspecialchars($quiz_id)?>">View Your Score</a></p>
        <p><a href="index.php">Back to Quizzes</a></p>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Start new attempt or get existing attempt
$currentTime = time();
$stmtAttempt = $pdo->prepare("INSERT INTO quiz_attempts (quiz_id, user_id, start_time) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), start_time=VALUES(start_time)");
$stmtAttempt->execute([$quiz_id, $user_id, $currentTime]);
$attempt_id = $pdo->lastInsertId();

// Fetch quiz details
$stmtQuiz = $pdo->prepare("SELECT *, time_limit as seconds_limit FROM quizzes WHERE id = ?");
$stmtQuiz->execute([$quiz_id]);
$quiz = $stmtQuiz->fetch();
if (!$quiz) {
    die('Quiz not found.');
}

// Fetch questions and choices
$stmtQuestions = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmtQuestions->execute([$quiz_id]);
$questions = $stmtQuestions->fetchAll();

$questionsWithChoices = [];
$stmtChoices = $pdo->prepare("SELECT * FROM choices WHERE question_id = ?");
foreach ($questions as $question) {
    $stmtChoices->execute([$question['id']]);
    $choices = $stmtChoices->fetchAll();
    $questionsWithChoices[] = [
        'question' => $question,
        'choices' => $choices
    ];
}

// Function to safely display HTML content
function displayContent($content) {
    // Convert &nbsp; to regular spaces
    $content = str_replace('&nbsp;', ' ', $content);
    
    // Convert multiple spaces to single spaces
    $content = preg_replace('/\s+/', ' ', $content);
    
    // Trim whitespace
    $content = trim($content);
    
    // Don't decode other HTML entities - keep them as-is so they display correctly
    // Don't strip tags - just return the cleaned content
    return $content;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Take Quiz: <?=strip_tags($quiz['title'])?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    :root {
        --primary-color: #4F46E5;
        --primary-hover: #4338CA;
        --warning-bg: #FEF3C7;
        --warning-text: #92400E;
        --danger-bg: #FEE2E2;
        --danger-text: #991B1B;
        --success-color: #059669;
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
        color: var(--text-primary);
        line-height: 1.5;
        padding: 2rem;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        position: relative;
    }

    .quiz-header {
        margin-bottom: 2rem;
        text-align: center;
    }

    h1 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1rem;
    }

    .quiz-info {
        color: var(--text-secondary);
        font-size: 0.875rem;
        margin-bottom: 2rem;
    }

    /* Quit Button Styles */
    #quitBtn {
        position: fixed;
        top: 1.5rem;
        left: 1.5rem;
        background: #ef4444;
        color: white;
        border: none;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        z-index: 1001;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    #quitBtn:hover {
        background: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 8px 15px -3px rgba(239, 68, 68, 0.4);
    }

    #quitBtn i {
        font-size: 1rem;
    }

    #timer {
        position: fixed;
        top: 1.5rem;
        right: 1.5rem;
        background: var(--bg-white);
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        font-size: 1.125rem;
        font-weight: 600;
        z-index: 1000;
        transition: all 0.3s ease;
        border: 2px solid var(--border-color);
    }

    #timer.warning {
        background: var(--warning-bg);
        color: var(--warning-text);
        border-color: var(--warning-text);
    }

    #timer.danger {
        background: var(--danger-bg);
        color: var(--danger-text);
        border-color: var(--danger-text);
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.03); }
        100% { transform: scale(1); }
    }

    .quiz-form {
        background: var(--bg-white);
        padding: 2rem;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .question-block {
        margin-bottom: 2.5rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid var(--border-color);
    }

    .question-block:last-child {
        border-bottom: none;
        margin-bottom: 1.5rem;
        padding-bottom: 0;
    }

    .question-text {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: start;
        gap: 0.5rem;
        line-height: 1.6;
    }

    .question-number {
        background: var(--primary-color);
        color: white;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 0.875rem;
        flex-shrink: 0;
        margin-top: 0.1rem;
    }

    .question-content {
        flex: 1;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* Style for displaying HTML entities as text */
    .question-content,
    .choice-item label {
        white-space: pre-wrap;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* Style for formatted content */
    .question-text p {
        margin-bottom: 0.5rem;
    }

    .question-text ul, .question-text ol {
        margin-left: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .question-text li {
        margin-bottom: 0.25rem;
    }

    .question-text strong, .question-text b {
        font-weight: 700;
    }

    .question-text em, .question-text i {
        font-style: italic;
    }

    .question-text u {
        text-decoration: underline;
    }

    /* Style for formatted choice content */
    .choice-item label p {
        margin-bottom: 0.25rem;
    }

    .choice-item label ul, .choice-item label ol {
        margin-left: 1rem;
        margin-bottom: 0.25rem;
    }

    .choice-item label li {
        margin-bottom: 0.1rem;
    }

    .choice-item label strong, .choice-item label b {
        font-weight: 700;
    }

    .choice-item label em, .choice-item label i {
        font-style: italic;
    }

    .choice-item label u {
        text-decoration: underline;
    }

    /* Style for formatted instructions content */
    .instructions-content strong, .instructions-content b {
        font-weight: 700;
    }

    .instructions-content em, .instructions-content i {
        font-style: italic;
    }

    .instructions-content u {
        text-decoration: underline;
    }

    .choices-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-left: 2.5rem;
    }

    .choice-item {
        position: relative;
        padding: 0.75rem 1rem;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .choice-item:hover {
        background: #F3F4F6;
        border-color: var(--primary-color);
    }

    .choice-item input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .choice-item label {
        display: block;
        cursor: pointer;
        padding-left: 2rem;
        position: relative;
        line-height: 1.5;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .choice-item label::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.4rem;
        width: 18px;
        height: 18px;
        border: 2px solid var(--border-color);
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .choice-item input[type="radio"]:checked + label::before {
        border-color: var(--primary-color);
        background: var(--primary-color);
        box-shadow: inset 0 0 0 4px var(--bg-white);
    }

    /* Style for formatted choice content */
    .choice-item label p {
        margin-bottom: 0.25rem;
    }

    .choice-item label ul, .choice-item label ol {
        margin-left: 1rem;
        margin-bottom: 0.25rem;
    }

    .choice-item label li {
        margin-bottom: 0.1rem;
    }

    .choice-item label strong, .choice-item label b {
        font-weight: 700;
    }

    .choice-item label em, .choice-item label i {
        font-style: italic;
    }

    .choice-item label u {
        text-decoration: underline;
    }

    .submit-btn {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: background-color 0.2s ease;
    }

    .submit-btn:hover {
        background: var(--primary-hover);
    }

    /* Instructions Popup Styles */
    .instructions-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .instructions-popup {
        background: var(--bg-white);
        padding: 2.5rem;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 600px;
        width: 100%;
        max-height: 80vh;
        overflow-y: auto;
        position: relative;
        animation: slideInScale 0.3s ease-out;
    }

    @keyframes slideInScale {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .instructions-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--border-color);
    }

    .instructions-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .instructions-subtitle {
        color: var(--text-secondary);
        font-size: 0.875rem;
    }

    .instructions-content {
        margin-bottom: 2rem;
        line-height: 1.6;
        color: var(--text-primary);
    }

    .instructions-content h1,
    .instructions-content h2,
    .instructions-content h3 {
        margin-top: 1rem;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .instructions-content p {
        margin-bottom: 1rem;
    }

    .instructions-content ul,
    .instructions-content ol {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }

    .instructions-content li {
        margin-bottom: 0.25rem;
    }

    .instructions-content strong, .instructions-content b {
        font-weight: 700;
    }

    .instructions-content em, .instructions-content i {
        font-style: italic;
    }

    .instructions-content u {
        text-decoration: underline;
    }

    .countdown-section {
        text-align: center;
        padding: 1.5rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 12px;
        margin-bottom: 1rem;
    }

    .countdown-text {
        font-size: 1rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }

    .countdown-timer {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .countdown-progress {
        width: 100%;
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
    }

    .countdown-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
        border-radius: 3px;
        transition: width 1s linear;
    }

    .start-quiz-btn {
        background: var(--success-color);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        font-size: 1rem;
        transition: all 0.2s ease;
    }

    .start-quiz-btn:hover {
        background: #047857;
        transform: translateY(-1px);
    }

    /* Quit Confirmation Modal Styles */
    .quit-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .quit-modal {
        background: var(--bg-white);
        padding: 2rem;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 450px;
        width: 100%;
        text-align: center;
        animation: slideInScale 0.3s ease-out;
    }

    .quit-icon {
        width: 64px;
        height: 64px;
        background: #fee2e2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }

    .quit-icon i {
        font-size: 1.5rem;
        color: #dc2626;
    }

    .quit-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .quit-message {
        color: var(--text-secondary);
        margin-bottom: 2rem;
        line-height: 1.5;
    }

    .quit-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    .quit-btn-cancel {
        background: #f3f4f6;
        color: var(--text-primary);
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        flex: 1;
    }

    .quit-btn-cancel:hover {
        background: #e5e7eb;
    }

    .quit-btn-confirm {
        background: #dc2626;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        flex: 1;
    }

    .quit-btn-confirm:hover {
        background: #b91c1c;
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }

        .quiz-form {
            padding: 1.5rem;
        }

        #quitBtn {
            top: 1rem;
            left: 1rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }

        #timer {
            position: sticky;
            top: 0;
            right: 0;
            width: 100%;
            text-align: center;
            border-radius: 0;
            margin: -1rem -1rem 1rem -1rem;
            width: calc(100% + 2rem);
        }

        .instructions-popup,
        .quit-modal {
            padding: 1.5rem;
            margin: 1rem;
        }

        .countdown-timer {
            font-size: 1.5rem;
        }

        .quit-actions {
            flex-direction: column;
        }
    }
</style>
</head>
<body>
    <!-- Quit Button -->
    <button id="quitBtn" onclick="showQuitConfirmation()" style="display: none;">
        <i class="fas fa-sign-out-alt"></i>
        Quit
    </button>

    <!-- Quit Confirmation Modal -->
    <div id="quitOverlay" class="quit-overlay" style="display: none;">
        <div class="quit-modal">
            <div class="quit-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="quit-title">Are you sure you want to quit?</h3>
            <p class="quit-message">
                If you quit now, your progress will be lost. 
                This action cannot be undone.
            </p>
            <div class="quit-actions">
                <button class="quit-btn-cancel" onclick="hideQuitConfirmation()">
                    Cancel
                </button>
                <button class="quit-btn-confirm" onclick="confirmQuit()">
                    Yes, Quit Quiz
                </button>
            </div>
        </div>
    </div>

    <!-- Instructions Popup -->
    <div id="instructionsOverlay" class="instructions-overlay">
        <div class="instructions-popup">
            <div class="instructions-header">
                <h2 class="instructions-title">
                    <i class="fas fa-info-circle"></i>
                    Quiz Instructions
                </h2>
                <p class="instructions-subtitle">Please read carefully before starting</p>
            </div>
            
            <div class="instructions-content">
                <?= $quiz['instructions'] ? displayContent($quiz['instructions']) : '<p>No specific instructions provided. Please answer all questions to the best of your ability.</p>' ?>
            </div>
            
            <div class="countdown-section">
                <div class="countdown-text">Quiz will start automatically in:</div>
                <div id="countdownDisplay" class="countdown-timer">10</div>
                <div class="countdown-progress">
                    <div id="countdownProgress" class="countdown-progress-bar" style="width: 100%;"></div>
                </div>
            </div>
            
            <button id="startQuizBtn" class="start-quiz-btn" onclick="startQuiz()">
                Start Quiz Now
            </button>
        </div>
    </div>

    <div class="container">
        <div id="timer" style="display: none;">Time Remaining: <span id="time-display">--:--</span></div>
        
        <div class="quiz-header">
            <h1>Quiz: <?=strip_tags($quiz['title'])?></h1>
            <div class="quiz-info">Please answer all questions before submitting.</div>
        </div>

        <form method="post" action="submit_quiz.php" id="quiz-form" class="quiz-form">
            <input type="hidden" name="quiz_id" value="<?=htmlspecialchars($quiz_id)?>" />
            <input type="hidden" name="attempt_id" value="<?=htmlspecialchars($attempt_id)?>" />
            
            <?php foreach ($questionsWithChoices as $index => $q): ?>
            <div class="question-block">
                <div class="question-text">
                    <span class="question-number"><?=($index + 1)?></span>
                    <div class="question-content"><?= displayContent($q['question']['question_text']) ?></div>
                </div>
                <div class="choices-list">
                    <?php if ($q['question']['type'] === 'multiple_choice'): ?>
                        <?php foreach ($q['choices'] as $choice): ?>
                            <div class="choice-item">
                                <input type="radio" 
                                       id="choice_<?= $choice['id'] ?>" 
                                       name="answers[<?= $q['question']['id'] ?>]" 
                                       value="<?= $choice['id'] ?>" 
                                       required>
                                <label for="choice_<?= $choice['id'] ?>"><?= displayContent($choice['choice_text']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($q['question']['type'] === 'true_false'): ?>
                        <div class="choice-item">
                            <input type="radio" 
                                   id="true_<?= $q['question']['id'] ?>" 
                                   name="answers[<?= $q['question']['id'] ?>]" 
                                   value="true" 
                                   required>
                            <label for="true_<?= $q['question']['id'] ?>">True</label>
                        </div>
                        <div class="choice-item">
                            <input type="radio" 
                                   id="false_<?= $q['question']['id'] ?>" 
                                   name="answers[<?= $q['question']['id'] ?>]" 
                                   value="false" 
                                   required>
                            <label for="false_<?= $q['question']['id'] ?>">False</label>
                        </div>
                    <?php elseif ($q['question']['type'] === 'text_input'): ?>
                        <div class="choice-item">
                            <label for="text_<?= $q['question']['id'] ?>">Your Answer:</label>
                            <input type="text" 
                                   id="text_<?= $q['question']['id'] ?>" 
                                   name="answers[<?= $q['question']['id'] ?>]" 
                                   placeholder="Type your answer here" 
                                   required>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" class="submit-btn">Submit Quiz</button>
        </form>
    </div>

<script>
// Instructions popup functionality
let instructionsCountdown = 10;
let countdownInterval;
let timerStarted = false;

// Timer functionality
const timeLimit = <?= (int)($quiz['seconds_limit'] ?? 0) ?>;
let timeLeft = timeLimit;
let timerInterval;

const timerDisplay = document.getElementById('time-display');
const timerElement = document.getElementById('timer');
const quizForm = document.getElementById('quiz-form');

// Quit functionality
function showQuitConfirmation() {
    document.getElementById('quitOverlay').style.display = 'flex';
}

function hideQuitConfirmation() {
    document.getElementById('quitOverlay').style.display = 'none';
}

function confirmQuit() {
    // Clear all intervals
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    
    // Remove beforeunload warning
    window.onbeforeunload = null;
    
    // Redirect to quiz list or dashboard
    window.location.href = 'index.php';
}

function startInstructionsCountdown() {
    const countdownDisplay = document.getElementById('countdownDisplay');
    const countdownProgress = document.getElementById('countdownProgress');
    
    countdownInterval = setInterval(() => {
        instructionsCountdown--;
        countdownDisplay.textContent = instructionsCountdown;
        
        // Update progress bar
        const progressPercent = (instructionsCountdown / 10) * 100;
        countdownProgress.style.width = progressPercent + '%';
        
        if (instructionsCountdown <= 0) {
            clearInterval(countdownInterval);
            startQuiz();
        }
    }, 1000);
}

function startQuiz() {
    // Clear countdown interval if running
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    // Hide instructions popup
    document.getElementById('instructionsOverlay').style.display = 'none';
    
    // Show quit button and timer
    document.getElementById('quitBtn').style.display = 'flex';
    document.getElementById('timer').style.display = 'block';
    
    if (!timerStarted) {
        timerStarted = true;
        startQuizTimer();
    }
}

function startQuizTimer() {
    function updateTimer() {
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            alert('Time is up! Your quiz will be submitted automatically.');
            quizForm.submit();
            return;
        }

        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        // Warning states
        if (timeLeft <= 300 && timeLeft > 60) { // Last 5 minutes
            timerElement.className = 'warning';
        } else if (timeLeft <= 60) { // Last minute
            timerElement.className = 'danger';
        } else {
            timerElement.className = '';
        }

        timeLeft--;
    }

    // Update timer every second
    timerInterval = setInterval(updateTimer, 1000);
    updateTimer(); // Initial call
}

// Start instructions countdown when page loads
window.addEventListener('load', function() {
    startInstructionsCountdown();
});

// Prevent form resubmission
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Warn before leaving page (only after quiz starts)
window.onbeforeunload = function() {
    if (timerStarted) {
        return "Are you sure you want to leave? Your quiz progress will be lost!";
    }
};

// Remove warning when submitting form
quizForm.addEventListener('submit', function() {
    window.onbeforeunload = null;
});

// Close quit modal when clicking outside
document.getElementById('quitOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        hideQuitConfirmation();
    }
});

// Handle ESC key to close quit modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('quitOverlay').style.display === 'flex') {
        hideQuitConfirmation();
    }
});
</script>
</body>
</html>
