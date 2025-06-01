<?php
require '../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_id'])) {
    $quiz_id = $_POST['quiz_id'];

    // Delete related data first (choices, questions, submissions)
    $pdo->prepare("DELETE FROM user_submissions WHERE quiz_id = ?")->execute([$quiz_id]);
    $pdo->prepare("DELETE FROM choices WHERE question_id IN (SELECT id FROM questions WHERE quiz_id = ?)")->execute([$quiz_id]);
    $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$quiz_id]);
    $pdo->prepare("DELETE FROM quizzes WHERE id = ?")->execute([$quiz_id]);

    $_SESSION['message'] = 'Quiz deleted successfully.';
} else {
    $_SESSION['error'] = 'Invalid request or missing quiz ID.';
}

header('Location: manage_quizzes.php');
exit;