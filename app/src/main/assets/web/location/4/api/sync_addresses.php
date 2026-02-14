<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php'; // اینجا db(), json_out() داریم

function get_header(string $name): ?string {
  $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
  return $_SERVER[$key] ?? null;
}

function norm_phone(string $s): string {
  $s = trim($s);
  $s = preg_replace('/[^\d+]/u', '', $s);
  return $s ?: '';
}

$syncKey = trim((string)(get_header('X-Esmer-Sync-Key') ?? ''));
if ($syncKey === '') {
  json_out(['ok' => false, 'error' => 'Missing X-Esmer-Sync-Key'], 401);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
  json_out(['ok' => false, 'error' => 'Invalid JSON'], 400);
}

$mode = strtolower(trim((string)($payload['mode'] ?? 'upsert')));
if (!in_array($mode, ['upsert', 'replace'], true)) {
  json_out(['ok' => false, 'error' => 'mode must be upsert|replace'], 400);
}

$items = $payload['items'] ?? null;
if (!is_array($items)) {
  json_out(['ok' => false, 'error' => 'items must be array'], 400);
}

$pdo = db();

// 1) پیدا کردن یوزر از روی sync_key
$stmt = $pdo->prepare("SELECT id, username, is_active FROM users WHERE sync_key = ? LIMIT 1");
$stmt->execute([$syncKey]);
$user = $stmt->fetch();

if (!$user) {
  json_out(['ok' => false, 'error' => 'Invalid sync key'], 403);
}
if ((int)$user['is_active'] !== 1) {
  json_out(['ok' => false, 'error' => 'User is inactive'], 403);
}

$userId = (int)$user['id'];

$inserted = 0;
$updated  = 0;
$skipped  = 0;

try {
  $pdo->beginTransaction();

  if ($mode === 'replace') {
    // فقط موارد سینک‌شده پاک شوند (تا اگر دستی چیزی ثبت شده بود، حذف نشود)
    $pdo->prepare("DELETE FROM user_addresses WHERE user_id = ? AND external_id IS NOT NULL")
        ->execute([$userId]);
  }

  $sel = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id = ? AND external_id = ? LIMIT 1");

  $ins = $pdo->prepare("
    INSERT INTO user_addresses (user_id, external_id, address, tag, city, lat, lng, formatted_address)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $upd = $pdo->prepare("
    UPDATE user_addresses
    SET address = ?, tag = ?, city = ?, lat = ?, lng = ?, formatted_address = ?
    WHERE id = ? AND user_id = ?
  ");

  foreach ($items as $it) {
    if (!is_array($it)) { $skipped++; continue; }

    $externalId = trim((string)($it['customerId'] ?? $it['external_id'] ?? ''));
    $name       = trim((string)($it['name'] ?? ''));
    $phoneRaw   = trim((string)($it['phone'] ?? ''));
    $address    = trim((string)($it['address'] ?? ''));

    // حداقل‌های لازم
    if ($externalId === '' || $name === '' || $address === '') {
      $skipped++;
      continue;
    }

    $phone = $phoneRaw;              // همان چیزی که نمایش می‌دهید
    $phoneNorm = norm_phone($phone); // فعلاً ذخیره جدا نداریم، فقط normalize آماده است

    $lat = isset($it['lat']) && $it['lat'] !== '' ? (float)$it['lat'] : null;
    $lng = isset($it['lng']) && $it['lng'] !== '' ? (float)$it['lng'] : null;

    $formatted = trim((string)($it['formatted_address'] ?? ''));
    if ($formatted === '') {
      $formatted = mb_substr($address, 0, 255);
    } else {
      $formatted = mb_substr($formatted, 0, 255);
    }

    $sel->execute([$userId, $externalId]);
    $row = $sel->fetch();

    if ($row) {
      $upd->execute([
        $address,
        $name,     // tag = نام مشتری
        $phone,    // city = تلفن
        $lat,
        $lng,
        $formatted,
        (int)$row['id'],
        $userId
      ]);
      $updated++;
    } else {
      $ins->execute([
        $userId,
        $externalId,
        $address,
        $name,
        $phone,
        $lat,
        $lng,
        $formatted
      ]);
      $inserted++;
    }
  }

  $pdo->commit();

  json_out([
    'ok' => true,
    'user' => ['id' => $userId, 'username' => $user['username']],
    'mode' => $mode,
    'counts' => ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped]
  ], 200);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['ok' => false, 'error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
