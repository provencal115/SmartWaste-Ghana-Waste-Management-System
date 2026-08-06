<?php
/**
 * Application configuration loader with automatic base URL detection.
 * Supports localhost, ngrok, and production deployments.
 */

/** Whether the current request uses HTTPS (including ngrok / reverse proxies). */
function isHttpsRequest(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    $forwarded = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    if ($forwarded === 'https') {
        return true;
    }
    $ssl = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')));
    return $ssl === 'on';
}

/**
 * Detect the public base URL from the current request (scheme + host + subdirectory).
 */
function detectAppBaseUrl(): string
{
    foreach (['APP_URL', 'SMARTWASTE_URL'] as $key) {
        $env = getenv($key);
        if (is_string($env) && trim($env) !== '') {
            return rtrim(trim($env), '/');
        }
    }

    $scheme = isHttpsRequest() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $basePath = dirname($script);
    if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
        $basePath = '';
    } else {
        $basePath = rtrim($basePath, '/');
    }

    return $scheme . '://' . $host . $basePath;
}

/** Application base path segment (e.g. /finalyearproject) or empty string at web root. */
function appBasePath(): string
{
    $path = parse_url(appConfig()['url'], PHP_URL_PATH);
    if (!$path || $path === '/') {
        return '';
    }
    return rtrim($path, '/');
}

/**
 * Load merged application config (cached per request).
 *
 * @return array<string, mixed>
 */
function appConfig(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $config = require __DIR__ . '/../config/app.php';
    $local = __DIR__ . '/../config/app.local.php';
    if (is_file($local)) {
        $config = array_replace_recursive($config, require $local);
    }

    $configuredUrl = trim((string) ($config['url'] ?? ''));
    $autoDetect = ($config['auto_detect_url'] ?? true) !== false;

    if ($autoDetect || $configuredUrl === '' || strtolower($configuredUrl) === 'auto') {
        $config['url'] = detectAppBaseUrl();
    } else {
        $config['url'] = rtrim($configuredUrl, '/');
    }

    return $config;
}
