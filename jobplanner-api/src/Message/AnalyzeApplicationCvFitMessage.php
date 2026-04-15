<?php

declare(strict_types=1);

namespace App\Message;

final readonly class AnalyzeApplicationCvFitMessage
{
    public function __construct(
        public int $applicationId,
        public int $userId,
        public string $filePath,
        public string $originalFilename,
        public string $mimeType,
    ) {
    }
}
