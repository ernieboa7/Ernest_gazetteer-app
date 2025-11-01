<?php
/**
 * php/weather.php
 * Proxy to the OpenWeather API (current weather by coordinates)
 * Author: Ernest Eboagwu
 * 
 * Example:
 *   /php/weather.php?lat=53.3331&lon=-6.2489&units=metric
 * 
 * Requirements:
 *   - Environment variable OPEN_WEATHER_KEY must be set
 */

require_once __DIR__ . '/load_env.php';


error_reporting(E_ALL);
ini_set('display_errors', 0);                 // Don’t print PHP errors into output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log'); // Log PHP warnings/errors here

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

// Load API key from environment
$apiKey = getenv('OPEN_WEATHER_KEY');
if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        "error" => "Missing OPEN_WEATHER_KEY server env var",
        "hint"  => "Set this in Render → Settings → Environment"
    ]);
    exit;
}

// Validate inputs
$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;
$units = $_GET['units'] ?? 'metric';

if (!$lat || !$lon) {
    http_response_code(400);
    echo json_encode([
        "error" => "Latitude (lat) and Longitude (lon) are required"
    ]);
    exit;
}

// Build the API request
$base = 'https://api.openweathermap.org/data/2.5/weather';
$params = [
    'lat' => $lat,
    'lon' => $lon,
    'appid' => $apiKey,
    'units' => $units
];
$url = $base . '?' . http_build_query($params);

// Execute the cURL request
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_FOLLOWLOCATION => true,
]);
$res = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Handle request failure
if ($res === false) {
    http_response_code(500);
    echo json_encode([
        "error" => "cURL request failed",
        "detail" => $curl_error
    ]);
    exit;
}

if ($http_code >= 400) {
    http_response_code($http_code);
    echo json_encode([
        "error" => "OpenWeather API returned error",
        "status" => $http_code,
        "url" => $url
    ]);
    exit;
}

// Validate JSON before returning
$decoded = json_decode($res, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode([
        "error" => "Invalid JSON returned by OpenWeather",
        "url" => $url,
        "raw" => substr($res, 0, 300)
    ]);
    exit;
}

// Everything OK — return formatted JSON
echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
?>
