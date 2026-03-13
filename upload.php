<?php
// -------------------------------------------------------------------------
// upload.php — receives the photo from the mobile device, saves it to disk
// -------------------------------------------------------------------------

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// --- Validate session ID --------------------------------------------------
$sessionId = trim($_POST['session'] ?? '');

if (!preg_match('/^[a-zA-Z0-9_.]+$/', $sessionId)) {
    http_response_code(400);
    exit('error: invalid session');
}

// --- Validate uploaded file -----------------------------------------------
if (empty($_FILES['jewelry_image']) || $_FILES['jewelry_image']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['jewelry_image']['error'] ?? -1;
    http_response_code(400);
    exit('error: upload failed (code ' . $code . ')');
}

$tmpPath  = $_FILES['jewelry_image']['tmp_name'];
$mimeType = mime_content_type($tmpPath);

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];

if (!in_array($mimeType, $allowedMimes, true)) {
    http_response_code(415);
    exit('error: unsupported file type (' . htmlspecialchars($mimeType) . ')');
}

// --- Determine extension from MIME so the filename is always consistent ---
$extMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/heic' => 'heic',
    'image/heif' => 'heif',
];
$ext = $extMap[$mimeType] ?? 'jpg';

// --- Save to uploads/ directory -------------------------------------------
$uploadsDir = __DIR__ . '/uploads';

if (!is_dir($uploadsDir)) {
    // Attempt to create the directory with safe permissions
    if (!mkdir($uploadsDir, 0755, true)) {
        http_response_code(500);
        exit('error: could not create uploads directory');
    }
}

// Destination: uploads/<sessionId>.jpg  (always .jpg for JPEG-originated files,
// but we use the actual detected extension for correctness)
$destFilename = $sessionId . '.' . $ext;
$destPath     = $uploadsDir . '/' . $destFilename;

// move_uploaded_file preserves the original binary — no re-encoding, no quality loss
if (!move_uploaded_file($tmpPath, $destPath)) {
    http_response_code(500);
    exit('error: could not save file');
}

// --- Respond to the mobile browser ----------------------------------------
http_response_code(200);
echo 'success';
