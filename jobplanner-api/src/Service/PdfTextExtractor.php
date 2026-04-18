<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PdfTextExtractor
{
    public function __construct(
        private readonly PdfCommandRunner $commandRunner,
    ) {
    }

    public function extract(UploadedFile $file): string
    {
        $inputPath = $file->getPathname();
        $tempBase = tempnam(sys_get_temp_dir(), 'jobplanner_pdf_');

        if (false === $tempBase) {
            throw new UnprocessableEntityHttpException('Unable to prepare the PDF extraction.');
        }

        unlink($tempBase);
        $outputPath = $tempBase.'.txt';

        $command = sprintf(
            'pdftotext -layout -enc UTF-8 %s %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        $output = [];
        $exitCode = 0;
        $this->commandRunner->run($command, $output, $exitCode);

        if (0 !== $exitCode || !is_file($outputPath)) {
            @unlink($outputPath);
            throw new UnprocessableEntityHttpException('Unable to extract the text from the PDF.');
        }

        $text = file_get_contents($outputPath);
        @unlink($outputPath);

        if (!is_string($text) || '' === trim($text)) {
            throw new UnprocessableEntityHttpException('The PDF does not contain any text to extract.');
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+$/m', '', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
