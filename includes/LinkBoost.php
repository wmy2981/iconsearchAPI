<?php
/**
 * GitHub link acceleration transformer
 */
class LinkBoost
{
    private array $rules;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Transform a URL using link boost rules
     * If origin domain matches, replace with fast URL pattern
     */
    public function transform(string $url): string
    {
        foreach ($this->rules as $rule) {
            $origin = $rule['originDomain'] ?? '';
            $fast = $rule['fast'] ?? '';

            if (empty($origin) || empty($fast)) {
                continue;
            }

            if (str_starts_with($url, $origin)) {
                return str_replace('<origin>', $origin, $fast) . substr($url, strlen($origin));
            }
        }

        return $url;
    }

    /**
     * Transform an array of URLs
     */
    public function transformBatch(array $urls): array
    {
        return array_map([$this, 'transform'], $urls);
    }
}