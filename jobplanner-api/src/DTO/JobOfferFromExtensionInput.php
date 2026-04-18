<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Callback('validateMinimumData')]
final readonly class JobOfferFromExtensionInput
{
    public function __construct(
        public string $url,
        public string $title,
        public string $content = '',
        public bool $createApplication = true,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRequestPayload(array $data): self
    {
        $url = trim((string) ($data['url'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));

        return new self(
            url: $url,
            title: $title,
            content: (string) ($data['content'] ?? ''),
            createApplication: (bool) ($data['createApplication'] ?? true),
        );
    }

    public function validateMinimumData(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if ('' === $this->url && '' === $this->title) {
            $context->buildViolation('The URL or title is required.')
                ->addViolation();
        }
    }

    public function getResolvedTitle(): string
    {
        return '' !== $this->title ? $this->title : 'Offer without title';
    }
}
