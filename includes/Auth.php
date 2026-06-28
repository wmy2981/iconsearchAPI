<?php
/**
 * Token authentication - Bearer token via Authorization header
 */
class Auth
{
    private Config $config;
    private Logger $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Validate the request's Bearer token
     * @return bool true if authenticated
     */
    public function authenticate(): bool
    {
        $token = null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';

        // 1. Check Authorization header (Bearer token)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // 2. Fallback: check ?token= query parameter (GET only)
        if ($token === null && !empty($_GET['token'])) {
            $token = $_GET['token'];
        }

        if ($token === null) {
            $this->logger->warn("Auth failed: No token provided from {$ip} | UA: {$ua}");
            return false;
        }

        $hash = hash('sha256', $token);
        $tokenPreview = substr($token, 0, 4) . '...' . substr($token, -4);

        if (!hash_equals($this->config->getAuthHash(), $hash)) {
            $this->logger->warn("Auth failed: Invalid token [{$tokenPreview}] from {$ip} | UA: {$ua}");
            return false;
        }

        $this->logger->info("Auth success from {$ip} | UA: {$ua} | token=[{$tokenPreview}]");
        return true;
    }
}