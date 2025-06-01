<?php
require '../config.php';
session_start();

function parseTimeLimit($input) {
    $input = strtolower(trim($input));
    $hours = 0; $minutes = 0;
    if (preg_match('/(\d+)\s*h/', $input, $h)) $hours = (int)$h[1];
    if (preg_match('/(\d+)\s*m/', $input, $m)) $minutes = (int)$m[1];
    return $hours * 3600 + $minutes * 60;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $title = $_POST['quiz_title'] ?? '';
        $instructions = $_POST['quiz_instructions'] ?? '';
        $time_limit_minutes = intval($_POST['time_limit'] ?? 0);
        $time_limit_seconds = $time_limit_minutes * 60;

        // Validate time limit
        if ($time_limit_seconds < 60 || $time_limit_seconds > 10800) {
            throw new Exception('Time limit must be between 1 minute and 180 minutes.');
        }

        // Insert quiz
        $stmt = $pdo->prepare("INSERT INTO quizzes (title, instructions, time_limit, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $instructions, $time_limit_seconds, $_SESSION['user_id']]);
        $quiz_id = $pdo->lastInsertId();

        // Process questions
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $q_index => $question) {
                // Insert question
                $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, correct_answer) VALUES (?, ?, ?, ?)");
                
                $questionType = $question['type'];
                $correctAnswer = null;
                
                switch ($questionType) {
                    case 'multiple_choice':
                        if (isset($question['correct']) && isset($question['choices'])) {
                            $correctAnswer = $question['correct'];
                        }
                        break;
                    case 'true_false':
                        $correctAnswer = $question['correct'];
                        break;
                    case 'identification':
                        $correctAnswer = $question['correct'];
                        break;
                }
                
                $stmt->execute([$quiz_id, $question['text'], $questionType, $correctAnswer]);
                $question_id = $pdo->lastInsertId();

                // Process choices for multiple choice questions
                if ($questionType === 'multiple_choice' && isset($question['choices']) && is_array($question['choices'])) {
                    foreach ($question['choices'] as $c_index => $choice_text) {
                        $is_correct = isset($question['correct']) && ($question['correct'] == ($c_index + 1));
                        $stmt = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $choice_text, $is_correct]);
                    }
                }
            }
        }

        $pdo->commit();
        $_SESSION['success_message'] = 'Quiz created successfully!';
        header('Location: manage_quizzes.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Error creating quiz: ' . $e->getMessage();
        header('Location: create_quiz.php');
        exit;
    }
} else {
    header('Location: create_quiz.php');
    exit;
}
?>