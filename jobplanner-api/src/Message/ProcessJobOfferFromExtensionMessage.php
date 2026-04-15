<?php

declare(strict_types=1);

namespace App\Message;

use App\DTO\JobOfferFromExtensionInput;

final readonly class ProcessJobOfferFromExtensionMessage
{
    public function __construct(
        public int $userId,
        public JobOfferFromExtensionInput $input,
    ) {
    }
}
