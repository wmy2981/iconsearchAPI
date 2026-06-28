<?php
/**
 * Core icon search engine
 */
class Searcher
{
    private string $sourcesDir;
    private string $cacheDir;
    private Config $config;
    private Logger $logger;
    private LinkBoost $linkBoost;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->sourcesDir = __DIR__ . '/../sources';
        $this->cacheDir = __DIR__ . '/../cache';
        $this->linkBoost = new LinkBoost($config->getLinkBoost());

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Search icons across all sources
     *
     * @param string $query Search term
     * @param array|null $type File extensions filter (overrides default, POST only)
     * @param array|null $sources Source names filter (overrides default, POST only)
     * @return array All matching search results
     */
    public function search(string $query, ?array $type = null, ?array $sources = null): array
    {
        $query = strtolower(trim($query));
        $results = [];
        $scanStart = microtime(true);

        // Scan sources directory
        $files = glob($this->sourcesDir . '/*.json');
        if ($files === false) {
            $this->logger->error("Failed to scan sources directory: {$this->sourcesDir}");
            return [];
        }

        $allowedSources = $sources ?? $this->config->getDefaultSources();
        $allowedType = $type ?? $this->config->getDefaultType();

        $this->logger->debug("Search config: query={$query} type=" . (is_array($allowedType) ? implode(',', $allowedType) : $allowedType) . " sources=" . (is_array($allowedSources) ? implode(',', $allowedSources) : $allowedSources));
        $this->logger->debug("Found " . count($files) . " source files: " . implode(', ', array_map(function ($f) { return basename($f); }, $files)));

        $totalIcons = 0;
        $matchedIcons = 0;
        $loadedSources = 0;
        $skippedSources = 0;

        foreach ($files as $file) {
            $sourceName = basename($file, '.json');

            // Filter by source name if specified
            if ($allowedSources !== '*' && !in_array($sourceName, $allowedSources, true)) {
                $this->logger->debug("Skip source [{$sourceName}]: not in allowed list");
                $skippedSources++;
                continue;
            }

            $icons = $this->loadSource($file);
            if ($icons === null) {
                $skippedSources++;
                continue;
            }

            $loadedSources++;
            $sourceCount = 0;

            foreach ($icons as $icon) {
                $name = $icon['name'] ?? '';
                $url = $icon['url'] ?? '';

                if (empty($name) || empty($url)) {
                    continue;
                }

                $totalIcons++;

                // Loose match: icon name contains query (case-insensitive)
                if (stripos($name, $query) === false) {
                    continue;
                }

                // Type filter
                if ($allowedType !== '*') {
                    $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                    $extWithDot = '.' . $ext;
                    if (!in_array($extWithDot, $allowedType, true)) {
                        continue;
                    }
                }

                // Apply link boost
                // First, build full URL if path is relative
                $fullUrl = $this->buildFullUrl($url);
                $boostedUrl = $this->linkBoost->transform($fullUrl);

                $results[] = [
                    'name' => $name,
                    'url' => $boostedUrl,
                    'source' => $sourceName,
                ];

                $sourceCount++;
                $matchedIcons++;
            }

            $this->logger->debug("Source [{$sourceName}]: " . count($icons) . " icons, {$sourceCount} matched");
        }

        $scanMs = round((microtime(true) - $scanStart) * 1000, 1);
        $this->logger->info("Search completed: query={$query} | sources={$loadedSources}/" . count($files) . " loaded, {$skippedSources} skipped | icons={$totalIcons} scanned, {$matchedIcons} matched | results=" . count($results) . " | scan={$scanMs}ms");

        return $results;
    }

    /**
     * Load and parse a source JSON file (with file cache)
     * @return array|null icons array or null on error
     */
    private function loadSource(string $file): ?array
    {
        $cacheKey = md5($file) . '.cache';
        $cacheFile = $this->cacheDir . '/' . $cacheKey;

        // Check if cache is valid (cache file exists and is newer than source)
        if (file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($file)) {
            $icons = $this->loadCache($cacheFile);
            if ($icons !== null) {
                $this->logger->debug("Loaded from cache: " . basename($file) . " (" . count($icons) . " icons)");
                return $icons;
            }
        }

        // Parse source file
        $icons = $this->parseSource($file);
        if ($icons === null) {
            return null;
        }

        // Write to cache
        $this->writeCache($cacheFile, $icons);
        $this->logger->debug("Cached: " . basename($file) . " (" . count($icons) . " icons)");

        return $icons;
    }

    /**
     * Parse a source JSON file
     */
    private function parseSource(string $file): ?array
    {
        $raw = file_get_contents($file);
        if ($raw === false) {
            $this->logger->warn("Failed to read source file: {$file}");
            return null;
        }

        // Strip UTF-8 BOM if present
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warn("Invalid JSON in source file: {$file} - " . json_last_error_msg());
            return null;
        }

        if (!isset($data['icons']) || !is_array($data['icons'])) {
            $this->logger->warn("Source file missing 'icons' array: {$file}");
            return null;
        }

        return $data['icons'];
    }

    /**
     * Load icons from cache file
     */
    private function loadCache(string $cacheFile): ?array
    {
        $raw = file_get_contents($cacheFile);
        if ($raw === false) {
            return null;
        }

        $icons = unserialize($raw);
        if (!is_array($icons)) {
            return null;
        }

        return $icons;
    }

    /**
     * Write icons to cache file
     */
    private function writeCache(string $cacheFile, array $icons): void
    {
        file_put_contents($cacheFile, serialize($icons), LOCK_EX);
    }

    /**
     * Build full URL from relative path
     * If URL is already absolute (starts with http/https), return as-is
     * Otherwise, prepend the request's base URL (scheme + host)
     */
    private function buildFullUrl(string $url): string
    {
        // If already a full URL, return as-is
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        // Get request base URL
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Build base URL: ensure trailing slash
        $baseUrl = $scheme . '://' . $host;
        if (!str_ends_with($baseUrl, '/')) {
            $baseUrl .= '/';
        }

        // Remove leading slash from path
        $path = ltrim($url, '/');

        return $baseUrl . $path;
    }
}