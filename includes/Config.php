<?php
/**
 * Configuration loader - Singleton pattern
 */
class Config
{
    private static ?Config $instance = null;
    private array $config;

    private function __construct()
    {
        $this->load();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load(): void
    {
        $configPath = __DIR__ . '/../config.json';

        if (!file_exists($configPath)) {
            throw new RuntimeException("Config file not found: config.json");
        }

        $raw = file_get_contents($configPath);
        $this->config = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in config.json: " . json_last_error_msg());
        }

        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->config['severName'])) {
            throw new RuntimeException("config.json: 'severName' is required");
        }

        // skipAuth 为 true 时，auth 字段可选
        $skipAuth = $this->config['skipAuth'] ?? false;
        if (!$skipAuth && empty($this->config['auth'])) {
            throw new RuntimeException("config.json: 'auth' (SHA256 hash) is required");
        }

        if (!isset($this->config['linkBoost']) || !is_array($this->config['linkBoost'])) {
            $this->config['linkBoost'] = [];
        }

        if (!isset($this->config['default'])) {
            $this->config['default'] = ['num' => null, 'type' => '*', 'sources' => '*'];
        }

        // Validate logLevel
        $validLevels = ['DEBUG', 'INFO', 'WARN', 'ERROR'];
        if (!isset($this->config['logLevel']) || !in_array($this->config['logLevel'], $validLevels, true)) {
            $this->config['logLevel'] = 'DEBUG';
        }

        $default = &$this->config['default'];

        // num must be a positive integer or null
        if (!isset($default['num']) || $default['num'] === null) {
            $default['num'] = null;
        } elseif (is_int($default['num']) && $default['num'] >= 1 && $default['num'] <= 1000) {
            // valid integer, keep as-is
        } else {
            $default['num'] = null;
        }

        if (!isset($default['type']) || $default['type'] === '*') {
            $default['type'] = '*';
        } elseif (!is_array($default['type'])) {
            $default['type'] = '*';
        }

        if (!isset($default['sources']) || $default['sources'] === '*') {
            $default['sources'] = '*';
        } elseif (!is_array($default['sources'])) {
            $default['sources'] = '*';
        }
    }

    public function getServerName(): string
    {
        return $this->config['severName'];
    }

    public function getAuthHash(): string
    {
        return $this->config['auth'];
    }

    public function getLinkBoost(): array
    {
        return $this->config['linkBoost'];
    }

    public function getDefaults(): array
    {
        return $this->config['default'];
    }

    public function getDefaultNum(): ?int
    {
        return $this->config['default']['num'];
    }

    public function getDefaultType(): array|string
    {
        return $this->config['default']['type'];
    }

    public function getDefaultSources(): array|string
    {
        return $this->config['default']['sources'];
    }

    public function getLogLevel(): string
    {
        return $this->config['logLevel'];
    }

    public function getSkipAuth(): bool
    {
        return $this->config['skipAuth'] ?? false;
    }
}