<?php
require_once __DIR__ . '/load_env.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$geonames = getenv('GEONAMES_USERNAME');
$openweather = getenv('OPEN_WEATHER_KEY');
$opencage = getenv('OPEN_CAGE_KEY');

echo "<h3>Env Test</h3>";
echo "GEONAMES_USERNAME = $geonames<br>";
echo "OPEN_WEATHER_KEY = $openweather<br>";
echo "OPEN_CAGE_KEY = $opencage<br>";
