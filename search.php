<?php
/**
 * IconSearchAPI - Main search endpoint
 * GET:  search.php?query=xxx&num=N
 * POST: search.php (query, num, type, sources in body)
 */

// CORS headers for cross-origin access (WebUI, etc.)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
require_once __DIR__ . '/includes/Searcher.php';
require_once __DIR__ . '/includes/LinkBoost.php';

/**
 * Send JSON error response and exit
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

// Log incoming request (sanitize token from URI)
$sanitizedUri = preg_replace('/[?&]token=[^&]*/', '', $_SERVER['REQUEST_URI']);
$sanitizedUri = preg_replace('/^\?/', '?', $sanitizedUri);
$logger->info(">>> Request: {$_SERVER['REQUEST_METHOD']} {$sanitizedUri} from {$_SERVER['REMOTE_ADDR']}");
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
$contentType = $_SERVER['CONTENT_TYPE'] ?? 'N/A';
$logger->debug("User-Agent: {$userAgent}");
$logger->debug("Content-Type: {$contentType}");

// Authenticate (skip if skipAuth is true)
$auth = new Auth($config, $logger);
if (!$config->getSkipAuth() && !$auth->authenticate()) {
    jsonResponse(401, 'Unauthorized: invalid or missing token');
}

// Parse request parameters
$method = $_SERVER['REQUEST_METHOD'];
$query = null;
$num = null;
$type = null;
$sources = null;
$page = null;
$pageSize = null;

if ($method === 'GET') {
    $query = $_GET['query'] ?? null;
    $num = $_GET['num'] ?? null;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : null;
    $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : null;
    // type and sources are silently ignored for GET
} elseif ($method === 'POST') {
    // Support both form-data and JSON body
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        $body = json_decode(file_get_contents('php://input'), true);
        if (is_array($body)) {
            $query = $body['query'] ?? null;
            $num = $body['num'] ?? null;
            $type = $body['type'] ?? null;
            $sources = $body['sources'] ?? null;
            $page = isset($body['page']) ? (int)$body['page'] : null;
            $pageSize = isset($body['pageSize']) ? (int)$body['pageSize'] : null;
        }
    } else {
        $query = $_POST['query'] ?? null;
        $num = $_POST['num'] ?? null;
        $type = $_POST['type'] ?? null;
        $sources = $_POST['sources'] ?? null;
        $page = isset($_POST['page']) ? (int)$_POST['page'] : null;
        $pageSize = isset($_POST['pageSize']) ? (int)$_POST['pageSize'] : null;
    }

    // Normalize type: string "png" -> array [".png"], string ".png" -> array [".png"]
    if (is_string($type)) {
        if ($type === '*') {
            $type = null; // no filter
        } else {
            $type = array_map(function ($t) {
                $t = trim($t);
                return str_starts_with($t, '.') ? $t : '.' . $t;
            }, explode(',', $type));
        }
    }

    // Normalize sources: string "HDiconsV2" -> array ["HDiconsV2"]
    if (is_string($sources)) {
        if ($sources === '*') {
            $sources = null; // no filter
        } else {
            $sources = array_map('trim', explode(',', $sources));
        }
    }
} else {
    jsonResponse(405, 'Method not allowed');
}

// Validate query
if (empty($query) || !is_string($query)) {
    jsonResponse(400, "Bad request: missing required parameter 'query'");
}

$query = trim($query);

// Validate num — must be a positive integer (1–1000)
if ($num !== null) {
    if (!is_int($num) && !ctype_digit((string)$num)) {
        jsonResponse(400, "Bad request: 'num' must be a positive integer");
    }
    $num = (int)$num;
    if ($num < 1 || $num > 1000) {
        jsonResponse(400, "Bad request: 'num' must be between 1 and 1000");
    }
}

// Validate page and pageSize (only when explicitly provided)
$usePagination = ($page !== null || $pageSize !== null);
if ($usePagination) {
    if ($page === null) $page = 1;
    if ($pageSize === null) $pageSize = 18;
    if ($page < 1) $page = 1;
    if ($pageSize < 1) $pageSize = 18;
    if ($pageSize > 100) $pageSize = 100;
}

// Log parsed parameters
$logger->debug("Params: query={$query} num=" . ($num ?? 'null') . " page=" . ($page ?? 'null') . " pageSize=" . ($pageSize ?? 'null') . " type=" . (is_array($type) ? implode(',', $type) : ($type ?? 'null')) . " sources=" . (is_array($sources) ? implode(',', $sources) : ($sources ?? 'null')));

// Execute search (returns all matching results)
$searcher = new Searcher($config, $logger);
$allResults = $searcher->search($query, $type, $sources);

// Apply num limit (caps total before pagination)
if ($num !== null && count($allResults) > $num) {
    $allResults = array_slice($allResults, 0, $num);
}

$total = count($allResults);

if ($usePagination) {
    // Paginated response
    $totalPages = max(1, (int)ceil($total / $pageSize));
    if ($page > $totalPages) $page = $totalPages;
    $results = array_slice($allResults, ($page - 1) * $pageSize, $pageSize);
    $responseData = [
        'serverName' => $config->getServerName(),
        'query' => $query,
        'total' => $total,
        'page' => $page,
        'pageSize' => $pageSize,
        'totalPages' => $totalPages,
        'results' => $results,
    ];
    $logger->info("<<< Response: 200 OK, total={$total} page={$page}/{$totalPages} results=" . count($results) . ", elapsed={$logger->getElapsed()}ms");
} else {
    // Return all results (default)
    $responseData = [
        'serverName' => $config->getServerName(),
        'query' => $query,
        'total' => $total,
        'results' => $allResults,
    ];
    $logger->info("<<< Response: 200 OK, total={$total} (all), elapsed={$logger->getElapsed()}ms");
}

jsonResponse(200, 'success', $responseData);