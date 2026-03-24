<?php
/**
 * Session helper — read/write per-session JSON state files with flock.
 * All session files live in sessions/<sessionId>.json
 */

define('SESSIONS_DIR', __DIR__ . '/sessions');

function _sessionsDir(): void {
    if (!is_dir(SESSIONS_DIR)) {
        mkdir(SESSIONS_DIR, 0750, true);
    }
}

function _sessPath(string $sessionId): string {
    return SESSIONS_DIR . '/' . $sessionId . '.json';
}

function sessionExists(string $sessionId): bool {
    return file_exists(_sessPath($sessionId));
}

function initSession(string $sessionId): array {
    _sessionsDir();
    $path = _sessPath($sessionId);
    if (file_exists($path)) {
        return getSession($sessionId);
    }
    $data = [
        'session_id'         => $sessionId,
        'mobile_connected'   => false,
        'mobile_last_seen'   => 0,
        'pending_request_id' => 0,
        'last_request_id'    => 0,
        'photos'             => (object)[],
    ];
    saveSession($sessionId, $data);
    return $data;
}

function getSession(string $sessionId): array {
    _sessionsDir();
    $path = _sessPath($sessionId);
    if (!file_exists($path)) {
        return initSession($sessionId);
    }
    $fp = fopen($path, 'r');
    flock($fp, LOCK_SH);
    $raw = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return initSession($sessionId);
    }
    return $data;
}

function saveSession(string $sessionId, array $data): void {
    _sessionsDir();
    $path = _sessPath($sessionId);
    $fp = fopen($path, 'c');
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function validateSessionId(string $id): bool {
    return (bool) preg_match('/^[a-zA-Z0-9_.]+$/', $id);
}
