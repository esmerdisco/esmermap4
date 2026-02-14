<?php
header("Content-Type: application/json; charset=UTF-8");

$apiKey = "service.f8331b56f32946cebb464676fdbf124d";

$address = $_GET['address'] ?? '';
if ($address === '') {
    echo json_encode(["error" => "no address"]);
    exit;
}

$data = [
    "address"  => $address,
    "city"     => "تهران",
    "province" => "تهران"
];

$url = "https://api.neshan.org/geocoding/v1/plus?json=" . urlencode(json_encode($data));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [
        "Api-Key: $apiKey",
        "Content-Type: application/json"
    ]
);

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode([
        "error" => "curl failed",
        "detail" => curl_error($ch)
    ]);
} else {
    echo $response;
}

curl_close($ch);
