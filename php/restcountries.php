<?php
// php/restcountries.php - Proxy for REST Countries API
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

$code = $_GET['code'] ?? null;
$name = $_GET['name'] ?? null;
if (!$code && !$name) {
  http_response_code(400);
  echo json_encode(["error" => "Provide 'code' or 'name' parameter"]);
  exit;
}

$base = 'https://restcountries.com/v3.1/';
if ($code) $url = $base . 'alpha/' . urlencode($code);
else $url = $base . 'name/' . urlencode($name);

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
