<?php

function app_config(string $name): string
{
    static $fileConfig;

    if ($fileConfig === null) {
        $envFile = __DIR__ . '/.env';
        $fileConfig = is_file($envFile)
            ? (parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: [])
            : [];
    }

    $value = getenv($name);
    if ($value === false) {
        $value = $fileConfig[$name] ?? '';
    }

    return trim((string) $value);
}