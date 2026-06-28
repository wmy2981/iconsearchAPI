<?php
/**
 * IconSearchAPI - Status endpoint
 * Returns server configuration and resource information as JSON.
 */

// CORS headers for cross-origin access (WebUI, etc.)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Load dependencies
require_once __DIR__ . '/includes/Config.php';
require_once __DIR__ . '/includes/Logger.php';
require_once __DIR__ . '/includes/Auth.php';

/**
 * Send JSON response and exit
 */
function jsonResponse(int $code, string $message, mixed $data = null): void
{
    http_response_code($code >= 200 && $code < 600 ? $code : 500);
    $response = ['code' => $code, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $config = Config::getInstance();
    $logger = Logger::getInstance($config->getLogLevel());
} catch (RuntimeException $e) {
    jsonResponse(500, 'Server configuration error: ' . $e->getMessage());
}

$logger->info(">>> Status request from {$_SERVER['REMOTE_ADDR']}");

// Authenticate (skip if skipAuth is true)
$auth = new Auth($config, $logger);
if (!$config->getSkipAuth() && !$auth->authenticate()) {
    jsonResponse(401, 'Unauthorized: invalid or missing token');
}

// Scan sources directory for resource info
$sourcesDir = __DIR__ . '/sources';
$sources = [];

if (is_dir($sourcesDir)) {
    $files = glob($sourcesDir . '/*.json');
    if ($files !== false) {
        foreach ($files as $file) {
            $sourceName = basename($file, '.json');
            $iconCount = 0;

            $raw = file_get_contents($file);
            if ($raw !== false) {
                // Strip UTF-8 BOM if present
                if (str_starts_with($raw, "\xEF\xBB\xBF")) {
                    $raw = substr($raw, 3);
                }
                $data = json_decode($raw, true);
                if (is_array($data) && isset($data['icons']) && is_array($data['icons'])) {
                    $iconCount = count($data['icons']);
                }
            }

            $sources[] = [
                'name' => $sourceName,
                'iconCount' => $iconCount,
            ];
        }
    }
}

// Build status response
$responseData = [
    'serverName' => $config->getServerName(),
    'skipAuth' => $config->getSkipAuth(),
    'logLevel' => $config->getLogLevel(),
    'default' => $config->getDefaults(),
    'linkBoost' => count($config->getLinkBoost()),
    'sources' => $sources,
    'totalSources' => count($sources),
    'totalIcons' => array_sum(array_column($sources, 'iconCount')),
];

$logger->info("<<< Status response: {$config->getServerName()}, " . count($sources) . " sources, {$responseData['totalIcons']} icons");

jsonResponse(200, 'success', $responseData);
