<?php
/**
 * connect.php — Mobile calls this (POST) once after scanning the QR code.
 * Marks the session as mobile_connected = true.
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
$data['mobile_connected'] = true;
$data['mobile_last_seen'] = time();
saveSession($sessionId, $data);

echo json_encode(['ok' => true]);
