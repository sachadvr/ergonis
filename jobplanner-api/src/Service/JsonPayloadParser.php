<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

final readonly class JsonPayloadParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(Request $request): array
    {
        $content = $request->getContent();
        if (!is_string($content)) {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }
}
