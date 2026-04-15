<?php

declare(strict_types=1);

namespace App\Service;

final readonly class MercureJwtFactory
{
    public function __construct(
        private string $mercureJwtSecret,
    ) {
    }

    /**
     * @param string[] $publishTopics
     * @param string[] $subscribeTopics
     */
    public function createToken(array $publishTopics = [], array $subscribeTopics = []): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode([
            'mercure' => array_filter([
                'publish' => [] !== $publishTopics ? array_values($publishTopics) : null,
                'subscribe' => [] !== $subscribeTopics ? array_values($subscribeTopics) : null,
            ]),
            'iat' => time(),
        ], JSON_THROW_ON_ERROR));

        $signature = hash_hmac('sha256', $header.'.'.$payload, $this->mercureJwtSecret, true);

        return $header.'.'.$payload.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
