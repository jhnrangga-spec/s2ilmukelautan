<?php
declare(strict_types=1);

// Simple .env loader (loaded once)
function loadEnv(): void {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $envFile = __DIR__ . '/../.env';
    if (!is_file($envFile)) return;

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // strip surrounding quotes
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[-1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, ?string $default = null): ?string {
    loadEnv();
    $val = getenv($key);
    if ($val === false || $val === '') {
        return $default;
    }
    return $val;
}
