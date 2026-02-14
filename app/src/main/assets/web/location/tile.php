<?php
$apiKey = "service.40991fc2dfc449ccb390306caaf1a25a";

$z = intval($_GET['z']);
$x = intval($_GET['x']);
$y = intval($_GET['y']);

$url = "https://api.neshan.org/v4/static/tiles/$z/$x/$y.png";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Api-Key: $apiKey"
]);

$image = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$image) {
    http_response_code(404);
    exit;
}

header("Content-Type: image/png");
echo $image;
