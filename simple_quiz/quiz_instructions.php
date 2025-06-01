<?php
require 'config.php';

if (!isset($_GET['quiz_id'])) {
    die('Quiz ID not provided');
}

$quiz_id = intval($_GET['quiz_id']);

// Fetch quiz instructions
$stmt = $conn->prepare("SELECT title, instructions FROM quizzes WHERE id = ?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();
$quiz = $result->fetch_assoc();

if (!$quiz) {
    die('Quiz not found');
}
?>

<div class="instructions-popup">
    <h2><?= htmlspecialchars($quiz['title']) ?> - Instructions</h2>
    <div class="instructions-content">
        <?= $quiz['instructions'] ?>
    </div>
    <button class="start-quiz-btn" onclick="startQuiz(<?= $quiz_id ?>)">
        Start Quiz
    </button>
</div>

<script>
function startQuiz(quizId) {
    window.location.href = `take_quiz.php?id=${quizId}`;
}
</script>

<style>
.instructions-popup {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    max-width: 600px;
    width: 100%;
}

.instructions-popup h2 {
    color: var(--text-primary);
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
}

.instructions-content {
    margin-bottom: 2rem;
    line-height: 1.6;
    color: var(--text-primary);
}

.start-quiz-btn {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.start-quiz-btn:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
}
</style> 