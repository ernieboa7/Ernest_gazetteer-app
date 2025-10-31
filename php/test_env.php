<?php
require_once __DIR__ . '/load_env.php';
echo json_encode([
  'GEONAMES_USERNAME' => getenv('GEONAMES_USERNAME'),
  'OPEN_CAGE_KEY' => getenv('OPEN_CAGE_KEY'),
  'OPEN_WEATHER_KEY' => getenv('OPEN_WEATHER_KEY')
]);


// http://localhost:8080/php/test_env.php
