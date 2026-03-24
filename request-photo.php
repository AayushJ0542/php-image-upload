<?php
/**
 * request-photo.php — Desktop calls this (POST) when user clicks "Upload Image".
 * Increments last_request_id and sets pending_request_id so mobile gets notified.
 * Returns the new request_id so the desktop can poll status.php for it.
 */
require_once __DIR__ . '/session-helper.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$sessionId = trim($_POST['session'] ?? '');
if (!$sessionId || !validateSessionId($sessionId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session']);
    exit;
}

$data = getSession($sessionId);

if (!$data['mobile_connected']) {
    http_response_code(409);
    echo json_encode(['error' => 'Phone not connected']);
    exit;
}

$newRequestId = (int)$data['last_request_id'] + 1;
$data['last_request_id']    = $newRequestId;
$data['pending_request_id'] = $newRequestId;
saveSession($sessionId, $data);

echo json_encode(['ok' => true, 'request_id' => $newRequestId]);
