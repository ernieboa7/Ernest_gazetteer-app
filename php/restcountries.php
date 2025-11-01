<?php
/**
 * php/restcountries.php
 * Proxy for REST Countries API
 * Author: Ernest Eboagwu
 *
 * Example:
 *   /php/restcountries.php?name=Ireland
 *   /php/restcountries.php?code=IE
 *
 * Notes:
 *   - No API key required
 *   - Returns structured JSON only
 */

require_once __DIR__ . '/load_env.php';


error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

// ✅ Validate query parameters
$code = $_GET['code'] ?? null;
$name = $_GET['name'] ?? null;

if (!$code && !$name) {
    http_response_code(400);
    echo json_encode([
        "error" => "Provide either 'code' or 'name' parameter.",
        "example" => [
            "by name" => "/php/restcountries.php?name=Ireland",
            "by code" => "/php/restcountries.php?code=IE"
        ]
    ]);
    exit;
}

// ✅ Build API URL
$base = 'https://restcountries.com/v3.1/';
if ($code) {
    $url = $base . 'alpha/' . urlencode($code);
} else {
    $url = $base . 'name/' . urlencode($name);
}

// ✅ Execute API request with cURL
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_FOLLOWLOCATION => true
]);
$res = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// ✅ Handle curl or network error
if ($res === false) {
    http_response_code(500);
    echo json_encode([
        "error" => "cURL request failed",
        "detail" => $curl_error
    ]);
    exit;
}

// ✅ Handle API-level error
if ($http_code >= 400) {
    http_response_code($http_code);
    echo json_encode([
        "error" => "REST Countries API returned error",
        "status" => $http_code,
        "url" => $url
    ]);
    exit;
}

// ✅ Verify JSON response
$decoded = json_decode($res, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode([
        "error" => "Invalid JSON returned by REST Countries API",
        "url" => $url,
        "raw" => substr($res, 0, 300)
    ]);
    exit;
}

// ✅ Output clean, formatted JSON
echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
?>
