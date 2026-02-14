<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function require_auth(): array {
  $token = get_bearer_token();
  if (!$token) json_out(['error' => 'missing token'], 401);

  $pdo = db();
  // توکن‌ها را یکی یکی چک می‌کنیم (برای ساده‌بودن).
  // اگر کاربر زیاد شد، می‌تونیم token_hash را با SHA256 ذخیره کنیم تا lookup سریع شود.
  $stmt = $pdo->query("SELECT lt.id, lt.user_id, lt.token_hash, lt.expires_at, u.username, u.is_active
                       FROM login_tokens lt
                       JOIN users u ON u.id = lt.user_id
                       WHERE lt.expires_at > NOW()");
  while ($row = $stmt->fetch()) {
    if ((int)$row['is_active'] !== 1) continue;
    if (password_verify($token, $row['token_hash'])) {
      return ['user_id' => (int)$row['user_id'], 'username' => $row['username']];
    }
  }
  json_out(['error' => 'invalid token'], 401);
}
