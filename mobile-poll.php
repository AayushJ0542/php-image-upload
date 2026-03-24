<?php
/**
 * mobile-poll.php — Mobile polls this (GET) every 2 s to check for pending requests.
 * Also refreshes mobile_last_seen so desktop knows the phone is still alive.
 * Returns: { pending_request_id: N }   (0 = standby, >0 = take photo now)
 */
require_once __DIR__ . '/session-helper.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$sessionId = trim($_GET['session'] ?? '');
if (!$sessionId || !validateSessionId($sessionId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session']);
    exit;
}

$data = getSession($sessionId);

// Refresh last-seen timestamp
$data['mobile_last_seen'] = time();
$data['mobile_connected'] = true;
saveSession($sessionId, $data);

echo json_encode([
    'pending_request_id' => (int)$data['pending_request_id'],
]);
