<?php
// -------------------------------------------------------------------------
// status.php — polling endpoint called by the desktop every 2 seconds
// -------------------------------------------------------------------------

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

// --- Validate session ID --------------------------------------------------
$sessionId = trim($_GET['session'] ?? '');

if (!preg_match('/^[a-zA-Z0-9_.]+$/', $sessionId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'invalid session']);
    exit;
}

// --- Supported extensions (must match upload.php) -------------------------
$extensions  = ['jpg', 'png', 'webp', 'heic', 'heif'];
$uploadsDir  = __DIR__ . '/uploads';
$foundUrl    = null;

foreach ($extensions as $ext) {
    $candidate = $uploadsDir . '/' . $sessionId . '.' . $ext;
    if (file_exists($candidate)) {
        // Build a relative URL the browser can load directly
        $foundUrl = 'uploads/' . $sessionId . '.' . $ext;
        break;
    }
}

if ($foundUrl !== null) {
    echo json_encode(['status' => 'ready', 'url' => $foundUrl]);
} else {
    echo json_encode(['status' => 'waiting']);
}
