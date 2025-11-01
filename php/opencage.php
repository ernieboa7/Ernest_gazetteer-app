<?php
/**
 * php/opencage.php
 * Proxy to the OpenCage Geocoding API
 * Author: Ernest Eboagwu
 *
 * Example:
 *   /php/opencage.php?q=Dublin
 *   /php/opencage.php?lat=53.3331&lng=-6.2489
 *
 * Requires:
 *   - Environment variable OPEN_CAGE_KEY set in Render
 */

require_once __DIR__ . '/load_env.php';


error_reporting(E_ALL);
ini_set('display_errors', 0);                 // Hide PHP errors from output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log'); // Log warnings/errors

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

// Load API key from environment
$apiKey = getenv('OPEN_CAGE_KEY');
if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        "error" => "Missing OPEN_CAGE_KEY server env var",
        "hint"  => "Set this in Render → Settings → Environment"
    ]);
    exit;
}

// Collect query parameters
$q = $_GET['q'] ?? null;
$limit = $_GET['limit'] ?? 1;
$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;

if (!$q && (!$lat || !$lng)) {
    http_response_code(400);
    echo json_encode([
        "error" => "You must provide either 'q' (place name) or both 'lat' and 'lng'."
    ]);
    exit;
}

// Build API request
$base = 'https://api.opencagedata.com/geocode/v1/json';
$params = [
    'key' => $apiKey,
    'limit' => $limit,
    'no_annotations' => 1,
    'language' => 'en'
];
if ($q) {
    $params['q'] = $q;
} elseif ($lat && $lng) {
    $params['q'] = $lat . ',' . $lng;
}

$url = $base . '?' . http_build_query($params);

// Execute cURL request
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

// Handle failures gracefully
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
        "error" => "OpenCage API returned error",
        "status" => $http_code,
        "url" => $url
    ]);
    exit;
}

// Validate API response
$decoded = json_decode($res, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode([
        "error" => "Invalid JSON returned by OpenCage",
        "url" => $url,
        "raw" => substr($res, 0, 300)
    ]);
    exit;
}

// Return clean, formatted JSON
echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
?>
