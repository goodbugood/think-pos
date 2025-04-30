<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$config['pos'] = include __DIR__ . '/src/config/pos.php';

function config(string $key, $default = null)
{
    global $config;
    if (false !== strpos($key, '.')) {
        $keys = explode('.', $key);
        $value = $config;
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return $default;
            }
            $value = $value[$key];
        }
        return $value ?? $default;
    }
    return $config[$key] ?? $default;
}

function env(string $key, $default = null)
{
    return getenv($key) ?: $default;
}
