<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RegisterInput;
use App\Entity\FollowUpRule;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Exception\ValidationFailedException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserRegistrationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws UserAlreadyExistsException
     * @throws ValidationFailedException
     *
     * @return array{id: int, email: string}
     */
    public function register(RegisterInput $input): array
    {
        $this->validateInput($input);

        if ($this->userRepository->existsByEmail($input->email)) {
            throw new UserAlreadyExistsException($input->email);
        }

        $user = $this->createUser($input);
        $this->entityManager->persist($user);
        $this->createDefaultFollowUpRules($user);
        $this->entityManager->flush();

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ];
    }

    /**
     * @throws ValidationFailedException
     */
    private function validateInput(RegisterInput $input): void
    {
        $violations = $this->validator->validate($input);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($violations);
        }
    }

    private function createUser(RegisterInput $input): User
    {
        $user = new User();
        $user->setEmail($input->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $input->password));

        return $user;
    }

    private function createDefaultFollowUpRules(User $user): void
    {
        $defaults = [
            [7, FollowUpRule::TEMPLATE_FOLLOW_UP],
            [14, FollowUpRule::TEMPLATE_FOLLOW_UP],
        ];

        foreach ($defaults as [$days, $template]) {
            $rule = new FollowUpRule();
            $rule->setOwner($user);
            $rule->setDaysWithoutReply($days);
            $rule->setTemplateType($template);
            $rule->setEnabled(true);
            $this->entityManager->persist($rule);
        }
    }
}
