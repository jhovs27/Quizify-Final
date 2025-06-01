<?php
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Convert HH:MM:SS to seconds
function timeToSeconds($time) {
    if (!$time) return 0;
    $parts = explode(':', $time);
    if (count($parts) === 3) {
        return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
    } elseif (count($parts) === 2) {
        return ($parts[0] * 60) + $parts[1];
    } elseif (is_numeric($time)) {
        return (int)$time;
    }
    return 0;
}

// Convert seconds to HH:MM:SS
function secondsToTime($seconds) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

// Get quiz ID from query string
$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id) {
    $_SESSION['error'] = 'Quiz ID is missing.';
    header('Location: manage_quizzes.php');
    exit;
}

// Fetch quiz details
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    $_SESSION['error'] = 'Quiz not found.';
    header('Location: manage_quizzes.php');
    exit;
}

// Fetch questions and choices
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmtQ->execute([$quiz_id]);
$questions = $stmtQ->fetchAll();

$choicesByQuestion = [];
if ($questions) {
    $questionIds = array_column($questions, 'id');
    if ($questionIds) {
        $in = str_repeat('?,', count($questionIds) - 1) . '?';
        $stmtC = $pdo->prepare("SELECT * FROM choices WHERE question_id IN ($in)");
        $stmtC->execute($questionIds);
        foreach ($stmtC->fetchAll() as $choice) {
            $choicesByQuestion[$choice['question_id']][] = $choice;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $time_limit = timeToSeconds($_POST['time_limit'] ?? '');

    if ($title === '') {
        $error = 'Quiz title cannot be empty.';
    } else {
        // Update quiz info
        $stmt = $pdo->prepare("UPDATE quizzes SET title = ?, description = ?, time_limit = ? WHERE id = ?");
        $stmt->execute([$title, $description, $time_limit, $quiz_id]);

        // Update existing questions and choices
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $qid => $qdata) {
                $qtext = trim($qdata['question_text'] ?? '');
                if (strpos($qid, 'new_') === 0) {
                    // Insert new question
                    $stmtQ = $pdo->prepare("INSERT INTO questions (quiz_id, question_text) VALUES (?, ?)");
                    $stmtQ->execute([$quiz_id, $qtext]);
                    $new_qid = $pdo->lastInsertId();

                    // Insert new choices
                    if (isset($qdata['choices']) && is_array($qdata['choices'])) {
                        $new_choice_ids = [];
                        foreach ($qdata['choices'] as $cidx => $ctext) {
                            if (trim($ctext) !== '') {
                                $stmtC = $pdo->prepare("INSERT INTO choices (question_id, choice_text) VALUES (?, ?)");
                                $stmtC->execute([$new_qid, trim($ctext)]);
                                $new_choice_ids[$cidx] = $pdo->lastInsertId();
                            }
                        }
                        // Set correct answer for new question
                        if (isset($qdata['correct_choice']) && isset($new_choice_ids[$qdata['correct_choice']])) {
                            $pdo->prepare("UPDATE choices SET is_correct = 1 WHERE id = ?")->execute([$new_choice_ids[$qdata['correct_choice']]]);
                        }
                    }
                } else {
                    // Update existing question
                    $pdo->prepare("UPDATE questions SET question_text = ? WHERE id = ? AND quiz_id = ?")
                        ->execute([$qtext, $qid, $quiz_id]);

                    // Update existing choices
                    if (isset($qdata['choices']) && is_array($qdata['choices'])) {
                        foreach ($qdata['choices'] as $cid => $ctext) {
                            if (strpos($cid, 'new_') === 0) {
                                // Insert new choice for existing question
                                if (trim($ctext) !== '') {
                                    $stmtC = $pdo->prepare("INSERT INTO choices (question_id, choice_text) VALUES (?, ?)");
                                    $stmtC->execute([$qid, trim($ctext)]);
                                }
                            } else {
                                $pdo->prepare("UPDATE choices SET choice_text = ? WHERE id = ? AND question_id = ?")
                                    ->execute([trim($ctext), $cid, $qid]);
                            }
                        }
                    }
                }

                // After updating/inserting choices:
                if (isset($qdata['correct_choice'])) {
                    // Reset all to 0
                    $pdo->prepare("UPDATE choices SET is_correct = 0 WHERE question_id = ?")->execute([$qid]);
                    // Set selected to 1
                    $correct_cid = $qdata['correct_choice'];
                    if (strpos($correct_cid, 'new_') !== 0) {
                        $pdo->prepare("UPDATE choices SET is_correct = 1 WHERE id = ? AND question_id = ?")->execute([$correct_cid, $qid]);
                    }
                }
            }
        }

        $_SESSION['message'] = 'Quiz updated successfully.';
        header('Location: manage_quizzes.php');
        exit;
    }
}

// Handle question deletion for existing questions
if (isset($_POST['delete_question_id'])) {
    $delete_qid = (int)$_POST['delete_question_id'];
    // Delete choices first (to maintain referential integrity)
    $pdo->prepare("DELETE FROM choices WHERE question_id = ?")->execute([$delete_qid]);
    $pdo->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?")->execute([$delete_qid, $quiz_id]);
    $_SESSION['message'] = 'Question deleted successfully.';
    header("Location: edit_quiz.php?id=" . $quiz_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Quiz - Admin - Quizify</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #ec4899;
            --accent: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --dark-light: #1e293b;
            --gray: #64748b;
            --gray-light: #94a3b8;
            --gray-lighter: #cbd5e1;
            --white: #ffffff;
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
            color: white;
            box-shadow: var(--shadow-lg);
            animation: logoFloat 6s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-5px) rotate(2deg); }
        }

        .brand-text {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-subtitle {
            color: var(--gray-light);
            font-size: 0.875rem;
            font-weight: 400;
            margin-top: 0.25rem;
        }

        .user-profile {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-lg);
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .user-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent), var(--success));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 1.2rem;
            box-shadow: var(--shadow);
        }

        .user-info h4 {
            color: white;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .user-info span {
            color: var(--gray-light);
            font-size: 0.875rem;
            font-weight: 400;
        }

        .nav-menu {
            padding: 1rem 0;
            list-style: none;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: var(--gray-light);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 0 var(--radius-xl) var(--radius-xl) 0;
            margin-right: 1rem;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(99, 102, 241, 0.1);
            color: white;
            transform: translateX(8px);
        }

        .nav-link:hover::before,
        .nav-link.active::before {
            transform: scaleY(1);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.4s ease;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
            background-size: 200% 100%;
            animation: gradientShift 3s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .page-title i {
            font-size: 2rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1.25rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            position: relative;
            animation: slideInDown 0.4s ease-out;
            display: flex;
            align-items: center;
            gap: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #b91c1c;
            border-left: 4px solid var(--danger);
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Enhanced Quiz Form */
        .quiz-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInScale 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .quiz-form::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            background-size: 200% 100%;
            animation: gradientShift 3s ease-in-out infinite;
        }

        .form-group {
            margin-bottom: 2.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--dark);
            font-size: 1.1rem;
            position: relative;
            padding-left: 1.5rem;
        }

        .form-group label::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--gray-lighter);
            border-radius: var(--radius);
            font-size: 1rem;
            color: var(--dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.8);
            box-shadow: var(--shadow-sm);
            font-family: 'Poppins', sans-serif;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 
                0 0 0 4px rgba(99, 102, 241, 0.1),
                var(--shadow);
            transform: translateY(-1px);
            background: white;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Enhanced Question Blocks */
        .question-block {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 0;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            animation: slideInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .question-block:hover {
            box-shadow: var(--shadow-xl);
            transform: translateY(-4px);
        }

        .question-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, var(--dark) 0%, var(--dark-light) 100%);
            color: white;
            position: relative;
        }

        .question-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        }

        .question-header label {
            font-size: 1.25rem;
            color: white;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            padding-left: 0;
        }

        .question-header label::before {
            display: none;
        }

        .question-header label i {
            color: #a5b4fc;
            font-size: 1.1rem;
        }

        .delete-question-btn {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: none;
            border-radius: var(--radius);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .delete-question-btn:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* Question Content Area */
        .question-content {
            padding: 2rem;
        }

        /* Enhanced Choices */
        .choices-container {
            margin: 1.5rem 0;
        }

        .choice-row {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            border-radius: var(--radius);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            animation: fadeInScale 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .choice-row::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .choice-row:hover {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            border-color: #c7d2fe;
            transform: translateX(4px);
        }

        .choice-radio {
            margin-top: 1rem;
            width: 1.5rem;
            height: 1.5rem;
            accent-color: var(--primary);
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .choice-radio:hover {
            transform: scale(1.1);
        }

        .choice-row input[type="text"] {
            flex-grow: 1;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--gray-lighter);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            margin-bottom: 0;
        }

        .choice-row input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 
                0 0 0 4px rgba(99, 102, 241, 0.1),
                var(--shadow);
            background: white;
        }

        .remove-btn {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger);
            border: none;
            border-radius: var(--radius);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 0.5rem;
            box-shadow: var(--shadow-sm);
        }

        .remove-btn:hover {
            background: linear-gradient(135deg, var(--danger), #b91c1c);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }

        /* Enhanced Buttons */
        .add-btn {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: var(--primary);
            border: 2px dashed #a5b4fc;
            border-radius: var(--radius);
            padding: 1rem 2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            width: 100%;
            justify-content: center;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }

        .add-btn:hover {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--success), #047857);
            color: white;
            border: none;
            border-radius: var(--radius);
            padding: 1.25rem 2.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #047857, #065f46);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: var(--radius);
            padding: 1.25rem 2.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            backdrop-filter: blur(10px);
        }

        .btn-cancel:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1.5rem;
            margin-top: 3rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .form-actions button,
        .form-actions a {
            min-width: 200px;
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: var(--radius);
            width: 48px;
            height: 48px;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            background: white;
            transform: scale(1.05);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .quiz-form {
                padding: 1.5rem;
                border-radius: var(--radius-lg);
            }

            .page-title {
                font-size: 1.75rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .question-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                padding: 1.25rem;
            }

            .delete-question-btn {
                align-self: flex-end;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions button,
            .form-actions a {
                width: 100%;
                min-width: auto;
            }

            .choice-row {
                flex-direction: column;
                gap: 1rem;
            }

            .choice-radio {
                margin-top: 0;
                align-self: flex-start;
            }
        }

        /* Animation Enhancements */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Scrollbar Styling */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
    <script>
        let newQuestionCount = 1;

        function addQuestion() {
            const questionsList = document.getElementById('questions-list');
            const qid = 'new_' + (newQuestionCount++);
            const questionBlock = document.createElement('div');
            questionBlock.className = 'question-block';
            questionBlock.setAttribute('data-question', '');
            questionBlock.innerHTML = `
                <div class="question-header">
                    <label><i class="fas fa-question-circle"></i> New Question</label>
                    <button type="button" class="delete-question-btn" onclick="removeQuestion(this)" title="Delete Question">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                <div class="question-content">
                    <div class="form-group">
                        <label>Question Text</label>
                        <input type="text" name="questions[${qid}][question_text]" class="form-control" required placeholder="Enter your question here...">
                    </div>
                    <div class="form-group">
                        <label>Answer Choices</label>
                        <div class="choices-container"></div>
                        <button type="button" class="add-btn" onclick="addChoice(this, '${qid}')">
                            <i class="fas fa-plus"></i> Add Choice
                        </button>
                    </div>
                </div>
            `;
            questionsList.appendChild(questionBlock);
        }

        function addChoice(btn, qid) {
            const choicesContainer = btn.parentNode.querySelector('.choices-container');
            const cid = 'new_' + Math.floor(Math.random() * 1000000);
            const choiceRow = document.createElement('div');
            choiceRow.className = 'choice-row';
            choiceRow.innerHTML = `
                <input type="radio" class="choice-radio" name="questions[${qid}][correct_choice]" value="${cid}" required>
                <input type="text" name="questions[${qid}][choices][${cid}]" class="form-control" required placeholder="Enter choice text...">
                <button type="button" class="remove-btn" onclick="removeChoice(this)" title="Remove Choice">
                    <i class="fas fa-times"></i>
                </button>
            `;
            choicesContainer.appendChild(choiceRow);
        }

        function removeChoice(btn) {
            btn.parentNode.remove();
        }

        function removeQuestion(btn) {
            btn.closest('.question-block').remove();
        }

        function deleteExistingQuestion(qid) {
            if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
                document.getElementById('delete_question_id').value = qid;
                document.getElementById('quiz-edit-form').submit();
            }
        }

        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', () => {
            const menuBtn = document.querySelector('.mobile-menu-btn');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuBtn) {
                menuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 1024 && 
                    !sidebar.contains(e.target) && 
                    !menuBtn.contains(e.target) && 
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</head>
<body>
    <button class="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </button>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="brand-text">Quizify</div>
            <div class="brand-subtitle">Admin Portal</div>
        </div>

        <div class="user-profile">
            <div class="user-card">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <h4><?=htmlspecialchars($_SESSION['username'])?></h4>
                    <span>Administrator</span>
                </div>
            </div>
        </div>

        <nav>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="create_quiz.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        Create Quiz
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_quizzes.php" class="nav-link active">
                        <i class="fas fa-tasks"></i>
                        Manage Quizzes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_users.php" class="nav-link">
                        <i class="fas fa-users-cog"></i>
                        Manage Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="rank.php" class="nav-link">
                        <i class="fas fa-trophy"></i>
                        Rankings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile_admin.php" class="nav-link">
                        <i class="fas fa-user-shield"></i>
                        Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-edit"></i>
                Edit Quiz
            </h1>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="quiz-form" id="quiz-edit-form">
            <input type="hidden" id="delete_question_id" name="delete_question_id" value="">
            
            <div class="form-group">
                <label for="title">Quiz Title</label>
                <input type="text" id="title" name="title" required placeholder="Enter quiz title..."
                       value="<?= htmlspecialchars($quiz['title']) ?>">
            </div>

            <div class="form-group">
                <label for="description">Quiz Description</label>
                <textarea id="description" name="description" placeholder="Enter quiz description..."><?= htmlspecialchars($quiz['description'] ?? '') ?></textarea>
                <small style="color: var(--gray); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                    Provide a brief description of what this quiz covers.
                </small>
            </div>

            <div class="form-group">
                <label for="time_limit">Time Limit</label>
                <input type="text" id="time_limit" name="time_limit" placeholder="e.g., 00:30:00 (HH:MM:SS)"
                       value="<?= htmlspecialchars(secondsToTime($quiz['time_limit'])) ?>">
                <small style="color: var(--gray); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                    Set the time limit in HH:MM:SS format (optional)
                </small>
            </div>

            <div style="margin: 3rem 0 2rem;">
                <h3 style="color: var(--primary); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-list"></i> Questions
                </h3>
            </div>

            <div id="questions-list">
                <?php foreach ($questions as $q): ?>
                    <div class="question-block" data-question>
                        <div class="question-header">
                            <label><i class="fas fa-question-circle"></i> Question</label>
                            <button type="button" class="delete-question-btn" title="Delete Question"
                                onclick="deleteExistingQuestion(<?= $q['id'] ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <div class="question-content">
                            <div class="form-group">
                                <label>Question Text</label>
                                <input type="text" name="questions[<?= $q['id'] ?>][question_text]" 
                                       value="<?= htmlspecialchars($q['question_text']) ?>" required
                                       placeholder="Enter your question here...">
                            </div>
                            <div class="form-group">
                                <label>Answer Choices</label>
                                <div class="choices-container">
                                    <?php if (!empty($choicesByQuestion[$q['id']])): ?>
                                        <?php foreach ($choicesByQuestion[$q['id']] as $choice): ?>
                                            <div class="choice-row">
                                                <input type="radio" class="choice-radio"
                                                    name="questions[<?= $q['id'] ?>][correct_choice]"
                                                    value="<?= $choice['id'] ?>"
                                                    <?= !empty($choice['is_correct']) ? 'checked' : '' ?>
                                                    required>
                                                <input type="text"
                                                    name="questions[<?= $q['id'] ?>][choices][<?= $choice['id'] ?>]"
                                                    value="<?= htmlspecialchars($choice['choice_text']) ?>"
                                                    required placeholder="Enter choice text...">
                                                <button type="button" class="remove-btn" onclick="removeChoice(this)" title="Remove Choice">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="add-btn" onclick="addChoice(this, '<?= $q['id'] ?>')">
                                    <i class="fas fa-plus"></i> Add Choice
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="add-btn" onclick="addQuestion()" style="margin-bottom: 2rem;">
                <i class="fas fa-plus"></i> Add Question
            </button>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="manage_quizzes.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </main>
</body>
</html>