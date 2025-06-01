<?php
header('Content-Type: application/json');

// Set your upload directory here - relative to this PHP file
$uploadDir = __DIR__ . '/../upload/images/';

// Make sure the upload directory exists
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'No image uploaded.']);
    exit;
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'File upload error code: ' . $file['error']]);
    exit;
}

// Validate file type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];

if (!array_key_exists($mime, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid image format.']);
    exit;
}

$extension = $allowedTypes[$mime];

// Generate unique file name to prevent overwrite
$baseName = bin2hex(random_bytes(8));
$targetFile = $uploadDir . $baseName . '.' . $extension;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
    exit;
}

// Compose URL to the uploaded file
// Here assuming your website root is parent directory and 'upload/images' is accessible URL path 
// Adjust according to your server setup
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$folder = dirname($_SERVER['SCRIPT_NAME']); // e.g., /admin
$folder = rtrim($folder, '/\\');
// URL path to upload/images folder
$urlPath = $folder . '/../upload/images/' . $baseName . '.' . $extension;

// Normalize URL path to remove ../
$realUrlPath = realpath($targetFile);
$docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
if ($realUrlPath && strpos($realUrlPath, $docRoot) === 0) {
    $url = str_replace('\\', '/', substr($realUrlPath, strlen($docRoot)));
    $url = $protocol . $host . $url;
} else {
    // Fallback assuming 'upload/images' is under webroot directly
    $url = $protocol . $host . '/upload/images/' . $baseName . '.' . $extension;
}

echo json_encode(['success' => true, 'url' => $url]);
exit;
?>
