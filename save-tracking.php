<?php
header('Content-Type: application/json; charset=utf-8');

define('HASH_FILE',     __DIR__ . '/admin-hash.txt');
define('DEFAULT_HASH',  'a282168e1424e03313a24ae7ae61a9bb1933d4368a48449697c2d6b4119c6bdb');
define('TRACKING_FILE', __DIR__ . '/tracking.js');

function storedHash() {
    return file_exists(HASH_FILE) ? trim(file_get_contents(HASH_FILE)) : DEFAULT_HASH;
}

function ok($data = [])  { echo json_encode(['success' => true] + $data); exit; }
function fail($msg, $code = 400) { http_response_code($code); echo json_encode(['error' => $msg]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method not allowed', 405);

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) fail('Invalid JSON');

$pw     = $body['password'] ?? '';
$action = $body['action']   ?? '';

if (!$pw) fail('Password required', 401);
if (hash('sha256', $pw) !== storedHash()) fail('Unauthorized', 403);

if ($action === 'publish') {
    $content = $body['content'] ?? '';
    if (strlen($content) > 200000) fail('Content too large');
    if (file_put_contents(TRACKING_FILE, $content) === false) fail('Write failed', 500);
    ok();
}

if ($action === 'update_password') {
    $newPw = $body['new_password'] ?? '';
    if (strlen($newPw) < 6) fail('Minimum 6 characters');
    if (file_put_contents(HASH_FILE, hash('sha256', $newPw)) === false) fail('Write failed', 500);
    ok();
}

fail('Unknown action');
