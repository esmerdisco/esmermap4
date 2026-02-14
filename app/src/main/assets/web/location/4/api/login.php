<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$raw = file_get_contents('php://input');
$body = json_decode($raw ?: '[]', true);

$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($username === '' || $password === '') {
  json_out(['error' => 'username/password required'], 400);
}

$pdo = db();
$stmt = $pdo->prepare("SELECT id, password_hash, is_active FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || (int)$user['is_active'] !== 1) {
  json_out(['error' => 'invalid credentials'], 401);
}
if (!password_verify($password, $user['password_hash'])) {
  json_out(['error' => 'invalid credentials'], 401);
}

// تولید توکن
$token = bin2hex(random_bytes(32));
$tokenHash = password_hash($token, PASSWORD_DEFAULT);
$expires = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

$ins = $pdo->prepare("INSERT INTO login_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
$ins->execute([(int)$user['id'], $tokenHash, $expires]);

json_out([
  'token' => $token,
  'expires_at' => $expires
]);
