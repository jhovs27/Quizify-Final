<?php
require '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$username || !$email) {
        $error = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password && $password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check for duplicate username/email (excluding self)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Update user info
            if ($password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $email, $hashed_password, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$username, $email, $user_id]);
            }
            $_SESSION['username'] = $username;
            $success = 'Profile updated successfully!';
            // Refresh user data
            $user['username'] = $username;
            $user['email'] = $email;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Profile - Quiz App</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F9FAFB;
            color: #1F2937;
            margin: 0;
        }
        .profile-container {
            max-width: 480px;
            margin: 3rem auto;
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 4px 24px rgba(80,112,255,0.07), 0 1.5px 6px rgba(31,41,55,0.06);
            padding: 2.5rem 2rem 2rem 2rem;
        }
        .profile-title {
            font-size: 2rem;
            font-weight: 700;
            color: #4F46E5;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1.3rem;
        }
        .form-label {
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 0.4rem;
            display: block;
        }
        .form-input {
            width: 95%;
            padding: 0.7rem 1rem;
            border: 1.5px solid #E5E7EB;
            border-radius: 8px;
            font-size: 1rem;
            background: #F9FAFB;
            color: #1F2937;
            margin-bottom: 0.2rem;
            transition: border 0.2s;
        }
        .form-input:focus {
            border-color: #4F46E5;
            outline: none;
            background: #fff;
        }
        .btn-save {
            background: linear-gradient(90deg, #6366F1 0%, #4F46E5 100%);
            color: #fff;
            padding: 0.75rem 2.2rem;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(80,112,255,0.08);
            transition: background 0.2s, box-shadow 0.2s;
            margin-top: 0.5rem;
            cursor: pointer;
            order: 2;
        }
        .btn-save:hover {
            background: linear-gradient(90deg, #4338CA 0%, #6366F1 100%);
            box-shadow: 0 4px 16px rgba(80,112,255,0.13);
        }
        .success-message, .error-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .success-message {
            background: #F0FDF4;
            color: #059669;
            border: 1.5px solid #059669;
        }
        .error-message {
            background: #FEF2F2;
            color: #DC2626;
            border: 1.5px solid #DC2626;
        }
        .profile-icon {
            font-size: 2.2rem;
            color: #6366F1;
            display: block;
            margin: 0 auto 1rem auto;
        }
        .btn-back-profile {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(90deg, #6366F1 0%, #4F46E5 100%);
            color: #fff;
            font-weight: 600;
            padding: 0.7rem 1.7rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(80,112,255,0.08);
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
            border: none;
            justify-content: center;
            order: 1;
        }
        .btn-back-profile:hover {
            background: linear-gradient(90deg, #4338CA 0%, #6366F1 100%);
            box-shadow: 0 4px 16px rgba(80,112,255,0.13);
            transform: translateY(-2px) scale(1.03);
            color: #fff;
        }
        .profile-actions-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
            margin-top: 1.2rem;
        }
        @media (max-width: 600px) {
            .profile-container {
                padding: 1.2rem 0.5rem;
            }
            .profile-actions-row {
                flex-direction: column-reverse;
                gap: 0.7rem;
            }
            .btn-back-profile, .btn-save {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-icon">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="profile-title">My Profile</div>
        <?php if ($success): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($success)?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?=htmlspecialchars($error)?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input 
                    type="text" 
                    id="username"
                    name="username" 
                    class="form-input" 
                    value="<?=htmlspecialchars($user['username'])?>"
                    required
                >
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input 
                    type="email" 
                    id="email"
                    name="email" 
                    class="form-input" 
                    value="<?=htmlspecialchars($user['email'])?>"
                    required
                >
            </div>
            <div class="form-group">
                <label class="form-label" for="password">New Password <span style="font-weight:400;color:#6B7280;">(leave blank to keep current)</span></label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    class="form-input" 
                    placeholder="Enter new password"
                >
            </div>
            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <input 
                    type="password" 
                    id="confirm_password"
                    name="confirm_password" 
                    class="form-input" 
                    placeholder="Confirm new password"
                >
            </div>
            <div class="profile-actions-row">
                <a href="index.php" class="btn-back-profile">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</body>
</html>