<?php
require '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$quiz_id = $_POST['quiz_id'] ?? null;
$user_answers = $_POST['answers'] ?? [];

if (!$quiz_id || empty($user_answers)) {
    // --- Styled and animated incomplete submission message ---
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Incomplete Submission</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #f87171 0%, #fbbf24 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            margin: 0;
        }
        .incomplete-message {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px rgba(239,68,68,0.13), 0 2px 8px rgba(251,191,36,0.10);
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 420px;
            width: 100%;
            text-align: center;
            animation: fadeInDown 0.7s cubic-bezier(.68,-0.55,.27,1.55);
            position: relative;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-60px) scale(0.95);}
            60% { opacity: 1; transform: translateY(10px) scale(1.05);}
            to { opacity: 1; transform: translateY(0) scale(1);}
        }
        .incomplete-icon {
            font-size: 3.5rem;
            color: #f87171;
            margin-bottom: 1rem;
            animation: shake 0.7s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translateX(-2px);}
            20%, 80% { transform: translateX(4px);}
            30%, 50%, 70% { transform: translateX(-8px);}
            40%, 60% { transform: translateX(8px);}
        }
        .incomplete-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 0.5rem;
            letter-spacing: 0.01em;
        }
        .incomplete-desc {
            color: #b91c1c;
            font-size: 1.05rem;
            margin-bottom: 1.5rem;
        }
        .btn-back {
            display: inline-block;
            background: linear-gradient(90deg, #fbbf24 0%, #f87171 100%);
            color: #fff;
            font-weight: 600;
            padding: 0.7rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
            transition: background 0.2s, transform 0.2s;
        }
        .btn-back:hover {
            background: linear-gradient(90deg, #f87171 0%, #fbbf24 100%);
            transform: translateY(-2px) scale(1.03);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    </head>
    <body>
        <div class="incomplete-message">
            <div class="incomplete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="incomplete-title">Incomplete Submission</div>
            <div class="incomplete-desc">
                You did not complete all questions or ran out of time.<br>
                Please try again to submit your answers.
            </div>
            <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Quizzes</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Fetch correct answers
$stmtCorrect = $pdo->prepare("
    SELECT q.id AS question_id, c.id AS choice_id
    FROM questions q 
    JOIN choices c ON q.id = c.question_id
    WHERE q.quiz_id = ? AND c.is_correct = 1
");
$stmtCorrect->execute([$quiz_id]);
$correctAnswers = $stmtCorrect->fetchAll(PDO::FETCH_KEY_PAIR); // question_id => choice_id

// Calculate score
$totalQuestions = count($correctAnswers);
$score = 0;
foreach ($correctAnswers as $question_id => $correct_choice_id) {
    if (isset($user_answers[$question_id]) && $user_answers[$question_id] == $correct_choice_id) {
        $score++;
    }
}

// Calculate percentage for progress bar
$percentage = ($score / $totalQuestions) * 100;

// Save user submission
try {
    $pdo->beginTransaction();
    $stmtInsertSub = $pdo->prepare("INSERT INTO user_submissions (user_id, quiz_id, score) VALUES (?, ?, ?)");
    $stmtInsertSub->execute([$_SESSION['user_id'], $quiz_id, $score]);
    $submission_id = $pdo->lastInsertId();

    $stmtInsertAnswer = $pdo->prepare("INSERT INTO user_answers (submission_id, question_id, choice_id) VALUES (?, ?, ?)");
    foreach ($user_answers as $question_id => $choice_id) {
        $stmtInsertAnswer->execute([$submission_id, $question_id, $choice_id]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("Failed to save quiz submission: " . $e->getMessage());
}

// Fetch quiz title
$stmtQuiz = $pdo->prepare("SELECT title FROM quizzes WHERE id = ?");
$stmtQuiz->execute([$quiz_id]);
$quiz = $stmtQuiz->fetch();

// Determine performance level
$performanceLevel = '';
$performanceColor = '';
if ($percentage >= 90) {
    $performanceLevel = 'Excellent!';
    $performanceColor = '#059669'; // Green
} elseif ($percentage >= 70) {
    $performanceLevel = 'Good Job!';
    $performanceColor = '#0284C7'; // Blue
} elseif ($percentage >= 50) {
    $performanceLevel = 'Keep Practicing!';
    $performanceColor = '#EAB308'; // Yellow
} else {
    $performanceLevel = 'Need Improvement';
    $performanceColor = '#DC2626'; // Red
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Quiz Result - <?=htmlspecialchars($quiz['title'])?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary-color: #4F46E5;
        --primary-hover: #4338CA;
        --success-color: #059669;
        --warning-color: #EAB308;
        --danger-color: #DC2626;
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
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .container {
        width: 100%;
        max-width: 600px;
    }

    .result-card {
        background: var(--bg-white);
        padding: 3rem;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        text-align: center;
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .quiz-title {
        color: var(--text-primary);
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 2rem;
    }

    .performance-level {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        animation: scaleIn 0.5s ease-out 0.3s both;
    }

    @keyframes scaleIn {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .score-container {
        margin: 2rem 0;
        position: relative;
    }

    .score-circle {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: var(--bg-primary);
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        position: relative;
        z-index: 1;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .score-circle:hover {
        transform: scale(1.02);
    }

    .score-circle::before {
        content: '';
        position: absolute;
        top: -3px;
        left: -3px;
        right: -3px;
        bottom: -3px;
        border-radius: 50%;
        background: conic-gradient(
            <?=htmlspecialchars($performanceColor)?> <?=htmlspecialchars($percentage)?>%,
            var(--border-color) <?=htmlspecialchars($percentage)?>% 100%
        );
        z-index: -1;
        animation: progressFill 1.2s ease-out;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    @keyframes progressFill {
        from { transform: rotate(-90deg); opacity: 0; }
        to { transform: rotate(0deg); opacity: 1; }
    }

    .score-number {
        font-size: 4rem;
        font-weight: 800;
        color: <?=htmlspecialchars($performanceColor)?>;
        line-height: 1;
        margin-bottom: 0.5rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        font-feature-settings: "tnum";
        letter-spacing: -0.05em;
        animation: scoreIn 0.5s ease-out 0.6s both;
    }

    @keyframes scoreIn {
        from { transform: scale(0.5); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .score-text {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-secondary);
        background: <?=htmlspecialchars($performanceColor)?>15;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        animation: fadeUp 0.5s ease-out 0.8s both;
    }

    @keyframes fadeUp {
        from { transform: translateY(10px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .percentage-indicator {
        position: absolute;
        top: -0.5rem;
        right: -0.5rem;
        background: <?=htmlspecialchars($performanceColor)?>;
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        animation: popIn 0.5s ease-out 1s both;
    }

    @keyframes popIn {
        from { transform: scale(0) rotate(-12deg); opacity: 0; }
        50% { transform: scale(1.2) rotate(5deg); }
        to { transform: scale(1) rotate(0); opacity: 1; }
    }

    .action-buttons {
        margin-top: 2rem;
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
    }

    .btn-outline {
        border: 2px solid var(--border-color);
        color: var(--text-primary);
    }

    .btn-outline:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        transform: translateY(-1px);
    }

    @media (max-width: 640px) {
        body {
            padding: 1rem;
        }

        .result-card {
            padding: 2rem 1.5rem;
        }

        .score-circle {
            width: 160px;
            height: 160px;
        }

        .score-number {
            font-size: 3rem;
        }

        .score-text {
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
        }

        .percentage-indicator {
            font-size: 0.75rem;
            padding: 0.2rem 0.4rem;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="result-card">
            <h1 class="quiz-title"><?=htmlspecialchars($quiz['title'])?></h1>
            
            <div class="performance-level" style="color: <?=htmlspecialchars($performanceColor)?>">
                <?=htmlspecialchars($performanceLevel)?>
            </div>
            
            <div class="score-container">
                <div class="score-circle">
                    <div class="score-number"><?=htmlspecialchars($score)?></div>
                    <div class="score-text">out of <?=htmlspecialchars($totalQuestions)?> points</div>
                    <div class="percentage-indicator"><?=round($percentage)?>%</div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="view_score.php?quiz_id=<?=htmlspecialchars($quiz_id)?>" class="btn btn-primary">View Detailed Results</a>
                <a href="index.php" class="btn btn-outline">Back to Quizzes</a>
            </div>
        </div>
    </div>
</body>
</html>
