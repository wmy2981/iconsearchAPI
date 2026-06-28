<?php
/**
 * File-based logging system with daily rotation
 */
class Logger
{
    private string $logDir;
    private float $startTime;
    private int $minLevel;
    private static ?Logger $instance = null;

    private const LEVELS = [
        'DEBUG' => 0,
        'INFO'  => 1,
        'WARN'  => 2,
        'ERROR' => 3,
    ];

    private function __construct(string $minLevel = 'DEBUG')
    {
        $this->logDir = __DIR__ . '/../logs';
        $this->startTime = microtime(true);
        $this->minLevel = self::LEVELS[$minLevel] ?? 0;
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public static function getInstance(string $minLevel = 'DEBUG'): self
    {
        if (self::$instance === null) {
            self::$instance = new self($minLevel);
        }
        return self::$instance;
    }

    private function shouldLog(string $level): bool
    {
        return (self::LEVELS[$level] ?? 0) >= $this->minLevel;
    }

    private function write(string $level, string $message): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $elapsed = round((microtime(true) - $this->startTime) * 1000, 1);
        $logFile = $this->logDir . '/' . date('Y-m-d') . '.log';
        $line = "[{$timestamp}] [{$level}] [{$elapsed}ms] {$message}" . PHP_EOL;
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get elapsed time since request start
     */
    public function getElapsed(): float
    {
        return round((microtime(true) - $this->startTime) * 1000, 1);
    }

    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    public function warn(string $message): void
    {
        $this->write('WARN', $message);
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    public function debug(string $message): void
    {
        $this->write('DEBUG', $message);
    }
}