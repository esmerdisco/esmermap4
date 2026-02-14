<?php
declare(strict_types=1);

// این فایل تصویر نقشه استاتیک را از Neshan می‌گیرد و همان تصویر را برمی‌گرداند.
// طبق مستندات: https://api.neshan.org/v2/static?key=...&type=neshan&width=...&height=...&zoom=...&center=lat,lng  :contentReference[oaicite:2]{index=2}

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 0.0;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : 0.0;

if ($lat === 0.0 && $lng === 0.0) {
  http_response_code(400);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'Missing lat/lng'], JSON_UNESCAPED_UNICODE);
  exit;
}

$zoom = isset($_GET['zoom']) ? (int)$_GET['zoom'] : 15;
if ($zoom < 3) $zoom = 3;
if ($zoom > 20) $zoom = 20;

// سایز مناسب موبایل
$w = isset($_GET['w']) ? (int)$_GET['w'] : 640;
$h = isset($_GET['h']) ? (int)$_GET['h'] : 360;
if ($w < 200) $w = 200;
if ($w > 1024) $w = 1024;
if ($h < 200) $h = 200;
if ($h > 1024) $h = 1024;

$API_KEY = 'service.30f644894b78498d937b06557808dd58';

// طبق مستندات رسمی، کلید به صورت query param می‌آید :contentReference[oaicite:3]{index=3}
$query = http_build_query([
  'key'   => $API_KEY,
  'type'  => 'neshan',
  'width' => (string)$w,
  'height'=> (string)$h,
  'zoom'  => (string)$zoom,
  'center'=> $lat . ',' . $lng,
]);

$url = 'https://api.neshan.org/v2/static?' . $query;

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 15,
]);

$body = curl_exec($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$errno = curl_errno($ch);
$err = curl_error($ch);
curl_close($ch);

if ($errno) {
  http_response_code(502);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'cURL error', 'detail' => $err], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($http < 200 || $http >= 300) {
  http_response_code($http ?: 500);
  header('Content-Type: application/json; charset=utf-8');
  // خیلی وقت‌ها پاسخ متن/JSON است؛ برای دیباگ برمی‌گردونیم
  echo $body ?: json_encode(['error' => 'Upstream error', 'status' => $http, 'contentType' => $contentType], JSON_UNESCAPED_UNICODE);
  exit;
}

// اگر سرور content-type نداد، حدس می‌زنیم png
if (!$contentType) $contentType = 'image/png';

header('Content-Type: ' . $contentType);
header('Cache-Control: public, max-age=300');
echo $body;
