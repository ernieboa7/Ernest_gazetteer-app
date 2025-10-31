<?php
// php/opencage.php - Proxy to OpenCage Geocoding API
// Requires OPEN_CAGE_KEY in environment or set $apiKey below.


require_once __DIR__ . '/load_env.php';  // Load environment variables

header('Content-Type: application/json; charset=utf-8');
...
$apiKey = getenv('OPEN_CAGE_KEY');


header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

$apiKey = getenv('OPEN_CAGE_KEY');
if (!$apiKey) {
  http_response_code(500);
  echo json_encode(["error" => "Missing OPEN_CAGE_KEY server env var."]);
  exit;
}

$q = $_GET['q'] ?? null;
$limit = $_GET['limit'] ?? 1;
$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;

$base = 'https://api.opencagedata.com/geocode/v1/json';
$params = [
  'key' => $apiKey,
  'limit' => $limit,
  'no_annotations' => 1,
  'language' => 'en'
];
if ($q) $params['q'] = $q;
if ($lat && $lng) $params['q'] = $lat . ',' . $lng;

$url = $base . '?' . http_build_query($params);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($res === false || $code >= 400) {
  http_response_code($code ?: 500);
  echo json_encode(["error" => "Proxy request failed", "status" => $code, "detail" => curl_error($ch)]);
} else {
  echo $res;
}
curl_close($ch);
