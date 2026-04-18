<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\MercureJwtFactory;
use PHPUnit\Framework\TestCase;

final class MercureJwtFactoryTest extends TestCase
{
    public function testCreateTokenEncodesMercureClaimsAndSignature(): void
    {
        $factory = new MercureJwtFactory('secret-key');

        $token = $factory->createToken(['topic-a', 'topic-b'], ['topic-c']);
        [$headerPart, $payloadPart, $signaturePart] = explode('.', $token);

        $header = $this->decodeBase64UrlJson($headerPart);
        $payload = $this->decodeBase64UrlJson($payloadPart);

        $this->assertSame('HS256', $header['alg']);
        $this->assertSame('JWT', $header['typ']);
        $this->assertSame(['topic-a', 'topic-b'], $payload['mercure']['publish']);
        $this->assertSame(['topic-c'], $payload['mercure']['subscribe']);
        $this->assertIsInt($payload['iat']);
        $this->assertSame($this->expectedSignature($headerPart, $payloadPart, 'secret-key'), $signaturePart);
    }

    public function testCreateTokenOmitsEmptyMercureArrays(): void
    {
        $factory = new MercureJwtFactory('secret-key');
        [, $payloadPart] = explode('.', $factory->createToken());

        $payload = $this->decodeBase64UrlJson($payloadPart);

        $this->assertArrayHasKey('mercure', $payload);
        $this->assertArrayNotHasKey('publish', $payload['mercure']);
        $this->assertArrayNotHasKey('subscribe', $payload['mercure']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBase64UrlJson(string $value): array
    {
        $decoded = json_decode(base64_decode(strtr($value, '-_', '+/'), true), true);

        self::assertIsArray($decoded);

        return $decoded;
    }

    private function expectedSignature(string $headerPart, string $payloadPart, string $secret): string
    {
        $signature = hash_hmac('sha256', $headerPart.'.'.$payloadPart, $secret, true);

        return rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }
}
