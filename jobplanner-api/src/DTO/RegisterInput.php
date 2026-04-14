<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'L\'email est requis')]
        #[Assert\Email(message: 'Format d\'email invalide')]
        public string $email,
        #[Assert\NotBlank(message: 'Le mot de passe est requis')]
        #[Assert\Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins 8 caractères')]
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
