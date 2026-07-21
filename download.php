<?php
declare(strict_types=1);

/**
 * Subscriber download endpoint for Joomla extension updates.
 *
 * Files must use one of the allowed package filename patterns and exist in the
 * downloads directory. When this file is deployed inside the downloads
 * directory, package files may live alongside it. Configure valid key hashes with
 * either:
 *
 * 1. DOWNLOAD_KEY_HASHES environment variable, comma-separated.
 * 2. download-keys.local.php returning an array of sha256 hashes.
 *
 * Generate a hash with:
 * php -r "echo hash('sha256', 'subscriber-key-here') . PHP_EOL;"
 */

const PACKAGE_FILENAME_PATTERN = '/^(?:pkg_route_calculation_help_for_accounting|route_calculation_help_for_accounting|plg_installer_routecalculationupdatekey)_v\d+\.\d+\.\d+\.zip$/';

$validKeyHashes = [];
$envKeyHashes = getenv('DOWNLOAD_KEY_HASHES');

if (is_string($envKeyHashes) && trim($envKeyHashes) !== '') {
    $validKeyHashes = array_map('trim', explode(',', $envKeyHashes));
}

$localKeyConfig = __DIR__ . '/download-keys.local.php';

if (is_file($localKeyConfig)) {
    $localKeyHashes = require $localKeyConfig;

    if (is_array($localKeyHashes)) {
        $validKeyHashes = array_merge($validKeyHashes, $localKeyHashes);
    }
}

$validKeyHashes = array_values(array_filter(
    array_map('trim', $validKeyHashes),
    static fn ($hash): bool => is_string($hash) && preg_match('/^[a-f0-9]{64}$/i', $hash) === 1
));

$file = isset($_GET['file']) ? basename((string) $_GET['file']) : '';
$key = isset($_GET['key']) ? trim((string) $_GET['key']) : '';

if ($key === '' && isset($_GET['dlid'])) {
    $key = trim((string) $_GET['dlid']);
}

if ($file === '' || $key === '' || !preg_match(PACKAGE_FILENAME_PATTERN, $file)) {
    http_response_code(403);
    exit('Forbidden');
}

$keyHash = hash('sha256', $key);
$keyIsValid = false;

foreach ($validKeyHashes as $validKeyHash) {
    if (hash_equals($validKeyHash, $keyHash)) {
        $keyIsValid = true;
        break;
    }
}

if (!$keyIsValid) {
    http_response_code(403);
    exit('Forbidden');
}

$downloadsDir = realpath(__DIR__ . '/downloads') ?: realpath(__DIR__);
$path = $downloadsDir !== false ? realpath($downloadsDir . DIRECTORY_SEPARATOR . $file) : false;

if ($path === false || !is_file($path) || !is_readable($path) || dirname($path) !== $downloadsDir) {
    http_response_code(404);
    exit('File not found');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . (string) filesize($path));
header('X-Content-Type-Options: nosniff');

readfile($path);
exit;
