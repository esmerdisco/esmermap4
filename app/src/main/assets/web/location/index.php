<?php
$NESHAAN_API_KEY = "service.f8331b56f32946cebb464676fdbf124d";

$address = $_GET['address'] ?? '';
$results = [];

if ($address) {
    $url = "https://api.neshan.org/v4/geocoding?address=" . urlencode($address);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Api-Key: $NESHAAN_API_KEY"
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (!empty($data['items'])) {
        $results = $data['items'];
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تبدیل آدرس به لوکیشن</title>

<style>
body {
  font-family: sans-serif;
  margin: 10px;
  background: #fafafa;
}

input, button {
  width: 100%;
  padding: 14px;
  font-size: 16px;
  margin-top: 6px;
}

.card {
  background: #fff;
  border-radius: 8px;
  padding: 12px;
  margin-top: 12px;
  box-shadow: 0 2px 6px rgba(0,0,0,.08);
}

.nav-btn {
  display: block;
  text-align: center;
  margin-top: 10px;
  padding: 12px;
  background: #1976d2;
  color: #fff;
  text-decoration: none;
  border-radius: 6px;
  font-size: 16px;
}
</style>
</head>

<body>

<form method="get">
  <input name="address" placeholder="آدرس را وارد کنید"
         value="<?= htmlspecialchars($address) ?>">
  <button>جستجو</button>
</form>

<?php if ($address && !$results): ?>
  <p>❌ نتیجه‌ای یافت نشد</p>
<?php endif; ?>

<?php foreach ($results as $i => $item): 
  $lat = $item['location']['latitude'];
  $lng = $item['location']['longitude'];
?>
<div class="card">
  <b>نتیجه <?= $i + 1 ?></b><br>
  <?= $item['neighbourhood'] ?? '' ?><br>

  <!-- 🔑 مسیریابی واقعی موبایل -->
  <a class="nav-btn"
     href="geo:<?= $lat ?>,<?= $lng ?>?q=<?= $lat ?>,<?= $lng ?>">
     🧭 مسیریابی با نقشه
  </a>
</div>
<?php endforeach; ?>

</body>
</html>
