<?php

$envFile = __DIR__ . '/../.env'; // Locate .env file in project root

if (file_exists($envFile)) {
    // Read each non-empty line
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue; // Skip comments and blanks

        // Split only on first '=' to allow values with '=' characters
        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        // Remove optional surrounding quotes (e.g. "value" or 'value')
        $value = trim($value, "\"'");

        // Save to environment for getenv(), $_ENV, etc.
        putenv("$key=$value");
        $_ENV[$key] = $value;  // also store in $_ENV for compatibility
    }
}
?>
