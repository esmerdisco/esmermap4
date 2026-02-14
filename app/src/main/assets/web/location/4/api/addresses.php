<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

try {
  $u = require_auth();
  $userId = (int)$u['user_id'];

  $pdo = db();
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

  // ---------- GET: list addresses for this user
  if ($method === 'GET') {
    $stmt = $pdo->prepare("
      SELECT id, address, tag, city, lat, lng, formatted_address, created_at, updated_at
      FROM user_addresses
      WHERE user_id = ?
      ORDER BY id DESC
    ");
    $stmt->execute([$userId]);
    json_out(['items' => $stmt->fetchAll()]);
  }

  $raw = file_get_contents('php://input') ?: '';
  $body = json_decode($raw, true);
  if (!is_array($body)) $body = [];

  // ---------- POST: add one or many
  if ($method === 'POST') {
    $items = isset($body['items']) && is_array($body['items'])
      ? $body['items']
      : [$body];

    $ins = $pdo->prepare("
      INSERT INTO user_addresses (user_id, address, tag, city)
      VALUES (?, ?, ?, ?)
    ");

    $inserted = 0;
    foreach ($items as $it) {
      $address = trim((string)($it['address'] ?? ''));
      if ($address === '') continue;

      $tag  = isset($it['tag']) ? trim((string)$it['tag']) : '';
      $city = isset($it['city']) ? trim((string)$it['city']) : '';

      $ins->execute([
        $userId,
        $address,
        $tag !== '' ? $tag : null,
        $city !== '' ? $city : null
      ]);

      $inserted++;
    }

    json_out(['ok' => true, 'inserted' => $inserted]);
  }

  // ---------- DELETE: delete one by id (only if belongs to user)
  if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) json_out(['error' => 'id required'], 400);

    $del = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $del->execute([$id, $userId]);

    json_out(['ok' => true]);
  }

  json_out(['error' => 'method not allowed'], 405);

} catch (Throwable $e) {
  json_out(['error' => 'server_error', 'detail' => $e->getMessage()], 500);
}
