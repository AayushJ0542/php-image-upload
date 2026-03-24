<?php
/**
 * end-session.php — Desktop calls this (POST) to end the current session.
 * Deletes the session JSON file, clears the PHP session, and returns a new session ID.
 */
session_start();
require_once __DIR__ . '/session-helper.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$oldSessionId = trim($_SESSION['scan_session'] ?? '');

// Delete old session JSON file
if ($oldSessionId && validateSessionId($oldSessionId)) {
    $path = SESSIONS_DIR . '/' . $oldSessionId . '.json';
    if (file_exists($path)) {
        @unlink($path);
    }
}

// Generate a fresh session ID
$newSessionId = uniqid('jw_', true);
$_SESSION['scan_session'] = $newSessionId;
initSession($newSessionId);

// Build new mobile URL
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$basePath  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$mobileUrl = $protocol . '://' . $host . $basePath . '/mobile.php?session=' . urlencode($newSessionId);
$qrApiUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($mobileUrl);

echo json_encode([
    'ok'         => true,
    'session_id' => $newSessionId,
    'qr_url'     => $qrApiUrl,
]);
