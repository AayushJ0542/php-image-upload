<?php
/**
 * status.php — Desktop polls this to check:
 *   1. Whether the phone is connected (mobile_connected)
 *   2. Whether a specific photo request has been fulfilled
 *
 * GET params:
 *   session    — session ID
 *   request_id — (optional) the request ID to check for a photo
 */
require_once __DIR__ . '/session-helper.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$sessionId = trim($_GET['session'] ?? '');
if (!$sessionId || !validateSessionId($sessionId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session']);
    exit;
}

// Initialise session file on first poll (desktop just loaded)
$data = getSession($sessionId);

$requestId       = (int)($_GET['request_id'] ?? 0);
$mobileConnected = (bool)($data['mobile_connected'] ?? false);

// Consider phone disconnected if last seen > 10 seconds ago
if ($mobileConnected && isset($data['mobile_last_seen'])) {
    if ((time() - (int)$data['mobile_last_seen']) > 10) {
        $mobileConnected          = false;
        $data['mobile_connected'] = false;
        saveSession($sessionId, $data);
    }
}

$response = [
    'mobile_connected' => $mobileConnected,
    'status'           => 'waiting',
    'url'              => null,
];

if ($requestId > 0) {
    $photos = $data['photos'] ?? [];
    $key    = (string)$requestId;
    if (isset($photos[$key])) {
        $response['status'] = 'ready';
        $response['url']    = $photos[$key];
    }
}

echo json_encode($response);
