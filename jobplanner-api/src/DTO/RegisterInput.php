<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'The email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        public string $email,
        #[Assert\NotBlank(message: 'The password is required')]
        #[Assert\Length(min: 8, minMessage: 'The password must contain at least 8 characters')]
        public string $password,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRequestPayload(array $data): self
    {
        return new self(
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
        );
    }
}
