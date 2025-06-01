<?php
require '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_quiz.php');
    exit;
}

$quiz_title = $_POST['quiz_title'] ?? '';
$questions = $_POST['questions'] ?? [];

if (!$quiz_title) {
    die('Quiz title is required.');
}
if (empty($questions)) {
    die('You must add at least one question.');
}

// Allowed tags and attributes for sanitization
function sanitize_html($html) {
    // Allow these tags
    $allowed_tags = '<p><br><b><strong><i><em><u><ul><ol><li><img><div><span>';
    // Allow src, alt, title, style for img and general tags
    $html = strip_tags($html, $allowed_tags);

    // Further attribute sanitization could be done here if desired (e.g. using DOMDocument)
    // For simplicity, this cleans script tags by strip_tags above

    return $html;
}

try {
    $pdo->beginTransaction();

    $stmtQuiz = $pdo->prepare("INSERT INTO quizzes (title, created_by) VALUES (?, ?)");
    $stmtQuiz->execute([$quiz_title, $_SESSION['user_id']]);
    $quiz_id = $pdo->lastInsertId();

    $stmtQuestion = $pdo->prepare("INSERT INTO questions (quiz_id, question_text) VALUES (?, ?)");
    $stmtChoice = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");

    foreach ($questions as $q) {
        $question_text_raw = $q['text'];
        if (!$question_text_raw) {
            throw new Exception('Question text cannot be empty.');
        }
        $question_text = sanitize_html($question_text_raw);
        $stmtQuestion->execute([$quiz_id, $question_text]);
        $question_id = $pdo->lastInsertId();

        $choices = $q['choices'];
        $correct_index = $q['correct']; // 1-based index

        if (empty($choices) || !isset($correct_index)) {
            throw new Exception('Invalid choices or correct answer missing.');
        }

        foreach ($choices as $idx => $choice_text_raw) {
            if (!$choice_text_raw) {
                throw new Exception('Choice text cannot be empty.');
            }
            $choice_text = sanitize_html($choice_text_raw);
            $is_correct = (($idx + 1) == $correct_index) ? 1 : 0;
            $stmtChoice->execute([$question_id, $choice_text, $is_correct]);
        }
    }

    $pdo->commit();
    header('Location: manage_quizzes.php?msg=Quiz created successfully');
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error saving quiz: " . $e->getMessage());
}
?>