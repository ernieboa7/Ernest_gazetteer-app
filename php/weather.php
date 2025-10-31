<?php
// php/weather.php - Proxy to OpenWeather API (current weather by coords)
// Requires OPEN_WEATHER_KEY in environment.

require_once __DIR__ . '/load_env.php';  // Load environment variables

header('Content-Type: application/json; charset=utf-8');
...
$apiKey = getenv('OPEN_WEATHER_KEY');




header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

$apiKey = getenv('OPEN_WEATHER_KEY');
if (!$apiKey) {
  http_response_code(500);
  echo json_encode(["error" => "Missing OPEN_WEATHER_KEY server env var."]);
  exit;
}

$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;
$units = $_GET['units'] ?? 'metric';

if (!$lat || !$lon) {
  http_response_code(400);
  echo json_encode(["error" => "lat and lon are required"]);
  exit;
}

// Use current weather endpoint
$base = 'https://api.openweathermap.org/data/2.5/weather';
$params = [
  'lat' => $lat,
  'lon' => $lon,
  'appid' => $apiKey,
  'units' => $units
];
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
