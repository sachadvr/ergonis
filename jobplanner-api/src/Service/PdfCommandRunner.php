<?php

declare(strict_types=1);

namespace App\Service;

class PdfCommandRunner
{
    public function run(string $command, &$output = null, &$exitCode = null): string
    {
        return exec($command, $output, $exitCode);
    }
}
