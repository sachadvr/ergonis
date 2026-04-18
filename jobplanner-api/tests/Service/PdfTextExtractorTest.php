<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\PdfCommandRunner;
use App\Service\PdfTextExtractor;
use App\Tests\Support\TestEntityHelpers;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class PdfTextExtractorTest extends TestCase
{
    use TestEntityHelpers;

    public function testExtractNormalizesExtractedText(): void
    {
        $runner = $this->createStub(PdfCommandRunner::class);
        $runner->method('run')->willReturnCallback(function (string $command, &$output = null, &$exitCode = null): string {
            $output = [];
            $exitCode = 0;

            preg_match_all("/'([^']+)'/", $command, $matches);
            $paths = $matches[1] ?? [];
            $outputPath = $paths[count($paths) - 1] ?? null;

            if (null !== $outputPath) {
                file_put_contents($outputPath, "Line 1 \r\nLine 2\r\n\r\n\r\nLine 3\n");
            }

            return '';
        });

        $text = (new PdfTextExtractor($runner))->extract($this->createPdfUpload());

        $this->assertSame("Line 1\nLine 2\n\nLine 3", $text);
    }

    public function testExtractThrowsWhenPdfToolFails(): void
    {
        $runner = $this->createStub(PdfCommandRunner::class);
        $runner->method('run')->willReturnCallback(static function (string $command, &$output = null, &$exitCode = null): string {
            $output = [];
            $exitCode = 1;

            preg_match_all("/'([^']+)'/", $command, $matches);
            $paths = $matches[1] ?? [];
            $outputPath = $paths[count($paths) - 1] ?? null;

            if (null !== $outputPath) {
                file_put_contents($outputPath, '');
            }

            return '';
        });

        $this->expectException(UnprocessableEntityHttpException::class);
        (new PdfTextExtractor($runner))->extract($this->createPdfUpload());
    }
}
