<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['quiz_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$quiz_id = intval($_POST['quiz_id']);
$answers = $_POST['answers'];

// Fetch questions and correct answers
$stmt = $pdo->prepare("
    SELECT q.*, c.id as choice_id, c.choice_text, c.is_correct 
    FROM questions q 
    LEFT JOIN choices c ON q.id = c.question_id AND c.is_correct = 1
    WHERE q.quiz_id = ?
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

$total_questions = count($questions);
$correct_answers = 0;

// Calculate score
foreach ($questions as $question) {
    $question_id = $question['id'];
    if (!isset($answers[$question_id])) continue;

    $user_answer = $answers[$question_id];
    $correct = false;

    switch ($question['question_type']) {
        case 'multiple_choice':
            // Compare with the correct choice ID
            $correct = ($user_answer == $question['choice_id']);
            break;
            
        case 'true_false':
            // For true/false, compare directly with the correct_answer
            $correct = (strtolower($user_answer) === strtolower($question['correct_answer']));
            break;
            
        case 'identification':
            // Case-insensitive comparison and trim whitespace for identification
            $correct = strtolower(trim($user_answer)) === strtolower(trim($question['correct_answer']));
            break;
    }

    if ($correct) $correct_answers++;
}

$score = ($correct_answers / $total_questions) * 100;

// Save the quiz result
$stmt = $pdo->prepare("INSERT INTO quiz_results (user_id, quiz_id, score, date_taken) VALUES (?, ?, ?, NOW())");
$stmt->execute([$user_id, $quiz_id, $score]);
$result_id = $pdo->lastInsertId();

// Save individual answers
$stmt = $pdo->prepare("INSERT INTO quiz_answers (result_id, question_id, answer_text) VALUES (?, ?, ?)");
foreach ($answers as $question_id => $answer) {
    $stmt->execute([$result_id, $question_id, $answer]);
}

// Redirect to the results page
header("Location: view_result.php?id=" . $result_id);
exit;
?> 