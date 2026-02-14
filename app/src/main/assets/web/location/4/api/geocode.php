<?php
declare(strict_types=1);

/**
 * /location/4/api/geocode.php
 * GET: ?q=...  یا  ?address=...
 * Header: Authorization: Bearer <token>
 */

ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

try {
  // توکن را چک کن
  $user = require_auth();

  // ورودی: هم q هم address
  $q = trim((string)($_GET['q'] ?? ''));
  if ($q === '') {
    $q = trim((string)($_GET['address'] ?? ''));
  }
  if ($q === '') {
    json_out(['status' => 'ERROR', 'message' => 'EMPTY_QUERY'], 400);
  }

  // کلید وب‌سرویس ژئوکدینگ نشان
  $API_KEY = 'service.30f644894b78498d937b06557808dd58';

  $url = 'https://api.neshan.org/v4/geocoding?address=' . rawurlencode($q);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
      'Api-Key: ' . $API_KEY,
      'Accept: application/json'
    ],
  ]);

  $body = curl_exec($ch);
  $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $errno = curl_errno($ch);
  $err = curl_error($ch);
  curl_close($ch);

  if ($errno) {
    json_out(['status' => 'ERROR', 'message' => 'CURL_ERROR', 'detail' => $err], 502);
  }

  if ($http < 200 || $http >= 300) {
    // اگر نشان body داده، همون رو بده
    http_response_code($http ?: 502);
    echo $body ?: json_encode(['status'=>'ERROR','message'=>'UPSTREAM_ERROR','code'=>$http], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if (!$body) {
    json_out(['status' => 'ERROR', 'message' => 'EMPTY_RESPONSE'], 502);
  }

  echo $body;
  exit;

} catch (Throwable $e) {
  // خروجی JSON برای اینکه HTML برنگرده
  json_out([
    'status' => 'ERROR',
    'message' => 'SERVER_ERROR',
    'detail' => $e->getMessage()
  ], 500);
}
