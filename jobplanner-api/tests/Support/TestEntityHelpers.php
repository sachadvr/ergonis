<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\HttpFoundation\File\UploadedFile;

trait TestEntityHelpers
{
    protected function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id);
    }

    protected function createPdfUpload(
        string $name = 'cv.pdf',
        string $mimeType = 'application/pdf',
        string $content = '%PDF-1.4',
    ): UploadedFile {
        $path = tempnam(sys_get_temp_dir(), 'jobplanner_pdf_');
        if (false === $path) {
            self::fail('Unable to create temp file');
        }

        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $mimeType, null, true);
    }
}
