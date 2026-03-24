<?php
/**
 * upload.php — receives the photo from the mobile device, saves it to disk,
 * updates session JSON, and clears the pending request.
 */
require_once __DIR__ . '/session-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// --- Validate session ID ---------------------------------------------------
$sessionId = trim($_POST['session'] ?? '');
if (!$sessionId || !validateSessionId($sessionId)) {
    http_response_code(400);
    exit('error: invalid session');
}

// --- Validate request_id ---------------------------------------------------
$requestId = (int)($_POST['request_id'] ?? 0);
if ($requestId <= 0) {
    http_response_code(400);
    exit('error: invalid request_id');
}

// --- Validate uploaded file ------------------------------------------------
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

$extMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/heic' => 'heic',
    'image/heif' => 'heif',
];
$ext = $extMap[$mimeType] ?? 'jpg';

// --- Save to uploads/ -------------------------------------------------------
$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        http_response_code(500);
        exit('error: could not create uploads directory');
    }
}

// File named: <sessionId>_<requestId>.<ext>
$destFilename = $sessionId . '_' . $requestId . '.' . $ext;
$destPath     = $uploadsDir . '/' . $destFilename;

if (!move_uploaded_file($tmpPath, $destPath)) {
    http_response_code(500);
    exit('error: could not save file');
}

// --- Update session JSON ---------------------------------------------------
$data = getSession($sessionId);
if (!isset($data['photos']) || !is_array($data['photos'])) {
    $data['photos'] = [];
}
$data['photos'][(string)$requestId] = 'uploads/' . $destFilename;
// Clear the pending request so mobile returns to standby
if ((int)$data['pending_request_id'] === $requestId) {
    $data['pending_request_id'] = 0;
}
saveSession($sessionId, $data);

http_response_code(200);
echo 'success';
