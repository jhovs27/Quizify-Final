<?php
// Database connection config

$host = 'localhost';
$db   = 'quiz_simple';
$user = 'root';
$pass = ''; // Set your MySQL password here
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
session_start();

// Add question_type and correct_answer columns if they don't exist
$sql = "ALTER TABLE questions 
        ADD COLUMN IF NOT EXISTS question_type ENUM('multiple_choice', 'true_false', 'identification') NOT NULL DEFAULT 'multiple_choice',
        ADD COLUMN IF NOT EXISTS correct_answer TEXT NULL";
$pdo->exec($sql);
?>
