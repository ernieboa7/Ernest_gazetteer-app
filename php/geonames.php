<?php
// php/geonames.php - Proxy to GeoNames API (JSON endpoints)
// Requires GEONAMES_USERNAME in environment.


require_once __DIR__ . '/load_env.php';  // Load environment variables

header('Content-Type: application/json; charset=utf-8');
...
$username = getenv('GEONAMES_USERNAME');



header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

$username = getenv('GEONAMES_USERNAME');
if (!$username) {
  http_response_code(500);
  echo json_encode(["error" => "Missing GEONAMES_USERNAME server env var."]);
  exit;
}

$allowed = [
  'searchJSON',
  'findNearbyPlaceNameJSON',
  'timezoneJSON',
  'findNearByWeatherJSON'
];

$endpoint = $_GET['endpoint'] ?? '';
if (!in_array($endpoint, $allowed)) {
  http_response_code(400);
  echo json_encode(["error" => "Invalid endpoint. Allowed: " . implode(', ', $allowed)]);
  exit;
}

$base = "http://api.geonames.org/$endpoint";
$params = $_GET;
$params['username'] = $username;
unset($params['endpoint']);

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
