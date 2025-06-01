<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$quiz_id = intval($_GET['id']);

// Fetch quiz details
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: index.php');
    exit;
}

// Create attempt record
$currentTime = time();
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("INSERT INTO quiz_attempts (quiz_id, user_id, start_time) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), start_time=NOW()");
$stmt->execute([$quiz_id, $user_id]);
$attempt_id = $pdo->lastInsertId();

// Fetch all questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// For multiple choice and true/false questions, fetch their choices
$stmt = $pdo->prepare("SELECT * FROM choices WHERE question_id = ? ORDER BY id");
foreach ($questions as $key => $question) {
    if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false') {
        $stmt->execute([$question['id']]);
        $questions[$key]['choices'] = $stmt->fetchAll();
        
        // If it's a true/false question and no choices exist, create them
        if ($question['question_type'] === 'true_false' && empty($questions[$key]['choices'])) {
            // Insert True and False choices
            $stmtInsert = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
            $stmtInsert->execute([$question['id'], 'True', $question['correct_answer'] === 'true' ? 1 : 0]);
            $stmtInsert->execute([$question['id'], 'False', $question['correct_answer'] === 'false' ? 1 : 0]);
            
            // Fetch the newly created choices
            $stmt->execute([$question['id']]);
            $questions[$key]['choices'] = $stmt->fetchAll();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['title']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4F46E5;
            --primary-hover: #4338CA;
            --danger-color: #EF4444;
            --text-primary: #1F2937;
            --text-secondary: #6B7280;
            --bg-white: #FFFFFF;
            --bg-secondary: #F3F4F6;
            --border-color: #E5E7EB;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.5;
            color: var(--text-primary);
            background: var(--bg-secondary);
            margin: 0;
            padding: 2rem;
        }

        .quiz-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }

        .quiz-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .quiz-header h1 {
            color: var(--text-primary);
            font-size: 1.875rem;
            margin-bottom: 0.5rem;
        }

        .timer {
            font-size: 1.25rem;
            color: var(--primary-color);
            font-weight: 600;
            margin-top: 1rem;
        }

        .question-block {
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .question-text {
            font-size: 1.125rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .question-number {
            background: var(--primary-color);
            color: white;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .choices {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-left: 2.5rem;
        }

        .choice-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .choice-item:hover {
            border-color: var(--primary-color);
            background: #F3F4F6;
        }

        .choice-item input[type="radio"] {
            width: 1.25rem;
            height: 1.25rem;
            accent-color: var(--primary-color);
        }

        .choice-item label {
            cursor: pointer;
            flex: 1;
        }

        /* True/False Styles */
        .true-false-options {
            display: flex;
            gap: 1.5rem;
            margin-left: 2.5rem;
            margin-top: 1rem;
        }

        .true-false-option {
            flex: 1;
            max-width: 200px;
        }

        .true-false-option input[type="radio"] {
            display: none;
        }

        .true-false-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            width: 100%;
            font-size: 1.1rem;
        }

        .true-false-option input[type="radio"]:checked + label {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .true-false-option label:hover {
            border-color: var(--primary-color);
            background: #F3F4F6;
        }

        /* Identification Styles */
        .identification-input {
            width: 100%;
            padding: 1rem;
            margin-left: 2.5rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .identification-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 2rem;
            transition: all 0.2s;
        }

        .submit-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .instructions-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .instructions-popup {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .instructions-content {
            margin-bottom: 1.5rem;
        }

        .quiz-content {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Instructions Popup -->
    <div class="instructions-overlay" id="instructionsOverlay">
        <div class="instructions-popup">
            <h2><?= htmlspecialchars($quiz['title']) ?> - Instructions</h2>
            <div class="instructions-content">
                <?= $quiz['instructions'] ?>
            </div>
        </div>
    </div>

    <!-- Quiz Content -->
    <div class="quiz-container quiz-content" id="quizContent">
        <div class="quiz-header">
            <h1><?= htmlspecialchars($quiz['title']) ?></h1>
            <div class="timer" id="timer"></div>
        </div>

        <form method="post" action="submit_quiz.php" id="quiz-form" class="quiz-form">
            <input type="hidden" name="quiz_id" value="<?=htmlspecialchars($quiz_id)?>" />
            <input type="hidden" name="attempt_id" value="<?=htmlspecialchars($attempt_id)?>" />
            
            <?php foreach ($questions as $index => $question): ?>
            <div class="question-block">
                <div class="question-text">
                    <span class="question-number"><?=($index + 1)?></span>
                    <span><?= htmlspecialchars($question['question_text']) ?></span>
                </div>

                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                    <div class="choices">
                        <?php foreach ($question['choices'] as $choice): ?>
                            <div class="choice-item">
                                <input type="radio" 
                                       id="choice_<?= $choice['id'] ?>" 
                                       name="answers[<?= $question['id'] ?>]" 
                                       value="<?= $choice['id'] ?>" 
                                       required>
                                <label for="choice_<?= $choice['id'] ?>"><?= htmlspecialchars($choice['choice_text']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($question['question_type'] === 'true_false'): ?>
                    <div class="true-false-options">
                        <?php 
                        $choices = [
                            ['value' => 'true', 'text' => 'True'],
                            ['value' => 'false', 'text' => 'False']
                        ];
                        foreach ($choices as $choice): 
                        ?>
                            <div class="true-false-option">
                                <input type="radio" 
                                       id="<?= $choice['value'] ?>_<?= $question['id'] ?>" 
                                       name="answers[<?= $question['id'] ?>]" 
                                       value="<?= $choice['value'] ?>" 
                                       required>
                                <label for="<?= $choice['value'] ?>_<?= $question['id'] ?>"><?= $choice['text'] ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: /* identification */ ?>
                    <input type="text" 
                           class="identification-input" 
                           name="answers[<?= $question['id'] ?>]" 
                           placeholder="Type your answer here..." 
                           required>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="submit-btn">Submit Quiz</button>
        </form>
    </div>

    <script>
        // Show instructions for 3 seconds then start quiz
        setTimeout(() => {
            document.getElementById('instructionsOverlay').style.display = 'none';
            document.getElementById('quizContent').style.display = 'block';
            startTimer(<?= $quiz['time_limit'] * 60 ?>);
        }, 3000);

        // Timer functionality
        const timeLimit = <?= $quiz['time_limit'] * 60 ?>;
        const startTime = <?= time() ?>;
        let timeLeft = timeLimit;

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (timeLeft <= 0) {
                document.getElementById('quiz-form').submit();
            } else {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }

        updateTimer();

        // Prevent form submission when timer is up
        document.getElementById('quiz-form').onsubmit = (e) => {
            if (timeLeft <= 0) {
                e.preventDefault();
                alert('Time is up! Your answers have been submitted.');
            }
        };
    </script>
</body>
</html> 