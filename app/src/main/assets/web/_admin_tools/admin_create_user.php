<?php
declare(strict_types=1);

// مسیر واقعی کانفیگ شما:
require '/home/esmerd/public_html/location/4/api/config.php';

// رمز ادمین خودت (قوی بگذار)
$ADMIN_PASS = '45621363';

if (($_GET['p'] ?? '') !== $ADMIN_PASS) {
  http_response_code(403);
  echo "Forbidden";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  if ($username === '' || $password === '') {
    echo "username/password required";
    exit;
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);

  $pdo = db();
  $st = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
  $st->execute([$username, $hash]);

  echo "OK";
  exit;
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ساخت یوزر</title>
  <style>
    body{font-family:system-ui;background:#f6f7f9;margin:0;padding:20px}
    .card{max-width:360px;margin:0 auto;background:#fff;border:1px solid #e3e7ee;border-radius:14px;padding:14px}
    input,button{width:100%;padding:12px;border-radius:10px;font-size:15px;margin-top:10px}
    input{border:1px solid #d8dde5}
    button{border:0;background:#111;color:#fff}
  </style>
</head>
<body>
  <div class="card">
    <h3>ساخت یوزر جدید</h3>
    <form method="post">
      <input name="username" placeholder="username" />
      <input name="password" placeholder="password" />
      <button type="submit">Create</button>
    </form>
  </div>
</body>
</html>
