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
$time_limit = $_POST['time_limit'] ?? 30; // Default 30 minutes

if (!$quiz_title) {
    die('Quiz title is required.');
}
if (empty($questions)) {
    die('You must add at least one question.');
}

try {
    $pdo->beginTransaction();

    // Insert quiz
    $stmt = $pdo->prepare("INSERT INTO quizzes (title, created_by, time_limit) VALUES (?, ?, ?)");
    $stmt->execute([$quiz_title, $_SESSION['user_id'], $time_limit]);
    $quiz_id = $pdo->lastInsertId();

    // Process questions
    foreach ($questions as $q_index => $question) {
        // Insert question
        $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, correct_answer) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $quiz_id, 
            $question['text'], 
            $question['type'],
            $question['type'] === 'true_false' ? $question['correct'] : null
        ]);
        $question_id = $pdo->lastInsertId();

        switch ($question['type']) {
            case 'multiple_choice':
                if (isset($question['choices']) && is_array($question['choices'])) {
                    foreach ($question['choices'] as $c_index => $choice_text) {
                        $is_correct = isset($question['correct']) && ($c_index + 1) == $question['correct'];
                        $stmt = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $choice_text, $is_correct]);
                    }
                }
                break;

            case 'true_false':
                // Insert True and False as choices
                $stmt = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, 'True', $question['correct'] === 'true']);
                $stmt->execute([$question_id, 'False', $question['correct'] === 'false']);
                break;

            case 'identification':
                // The correct answer is already stored in the questions table
                break;
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