<?php
require 'config.php';

header('Content-Type: application/json');

// Get passkey settings from database
$passkey_required = true;

try {
    $stmt = $pdo->prepare("SELECT is_enabled FROM admin_settings WHERE setting_key = 'admin_passkey'");
    $stmt->execute();
    $passkey_setting = $stmt->fetch();
    
    if ($passkey_setting) {
        $passkey_required = (bool)$passkey_setting['is_enabled'];
    }
} catch (PDOException $e) {
    // If table doesn't exist or error occurs, use default
    $passkey_required = true;
}

echo json_encode(['passkey_required' => $passkey_required]);
?>
