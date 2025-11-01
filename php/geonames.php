<?php
/**
 * php/geonames.php
 * Proxy to GeoNames API (JSON endpoints)
 * Author: Ernest Eboagwu
 * 
 * Usage:
 *   /php/geonames.php?endpoint=searchJSON&name=dublin
 * 
 * Notes:
 *   - Requires GEONAMES_USERNAME set in environment variables
 *   - Returns JSON only (never HTML)
 */

require_once __DIR__ . '/load_env.php';


error_reporting(E_ALL);
ini_set('display_errors', 0);           // Don't print PHP errors into output
ini_set('log_errors', 1);               // Log errors instead
ini_set('error_log', __DIR__ . '/php_error.log'); // Log file inside /php/

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

//  Load environment variable
$username = getenv('GEONAMES_USERNAME');
if (!$username) {
    http_response_code(500);
    echo json_encode([
        "error" => "Missing GEONAMES_USERNAME server env var",
        "hint"  => "Set this in Render Dashboard → Settings → Environment"
    ]);
    exit;
}

//  Allow only safe GeoNames JSON endpoints
$allowed = [
    'searchJSON',
    'findNearbyPlaceNameJSON',
    'timezoneJSON',
    'findNearByWeatherJSON'
];

$endpoint = $_GET['endpoint'] ?? '';
if (!in_array($endpoint, $allowed)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid or missing endpoint",
        "allowed" => $allowed
    ]);
    exit;
}

//  Build URL with all query params + username
$base = "http://api.geonames.org/$endpoint";
$params = $_GET;
$params['username'] = $username;
unset($params['endpoint']);

$url = $base . '?' . http_build_query($params);

//  Execute cURL request
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

//  Validate response
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
        "error" => "GeoNames API error",
        "status" => $http_code,
        "url" => $url
    ]);
    exit;
}

//  Try decoding to ensure it's valid JSON
$decoded = json_decode($res, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode([
        "error" => "Invalid JSON returned by GeoNames",
        "url" => $url,
        "raw" => substr($res, 0, 300)
    ]);
    exit;
}

//  If everything’s good, return the clean JSON
echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
?>
