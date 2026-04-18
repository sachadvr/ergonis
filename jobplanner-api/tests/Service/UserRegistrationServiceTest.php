<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\RegisterInput;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Exception\ValidationFailedException;
use App\Repository\UserRepository;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserRegistrationServiceTest extends TestCase
{
    public function testRegisterThrowsWhenEmailAlreadyExists(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('existsByEmail')->willReturn(true);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $service = $this->createService($userRepository, $entityManager, $passwordHasher, $validator);

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage('existing@example.com');

        $input = new RegisterInput('existing@example.com', 'validPassword123');
        $service->register($input);
    }

    public function testRegisterThrowsWhenValidationFails(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('existsByEmail')->willReturn(false);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new ConstraintViolation('Invalid email', null, [], null, 'email', null),
        ]));

        $service = $this->createService($userRepository, $entityManager, $passwordHasher, $validator);

        $this->expectException(ValidationFailedException::class);

        $input = new RegisterInput('invalid', 'short');
        $service->register($input);
    }

    public function testRegisterCreatesUserAndReturnsPayload(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('existsByEmail')->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')
            ->willReturnCallback(static fn (User $user, string $password) => 'hashed_'.$password);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $service = $this->createService($userRepository, $entityManager, $passwordHasher, $validator);

        $persistedUser = null;
        $entityManager->expects($this->once())->method('persist')->willReturnCallback(function ($entity) use (&$persistedUser) {
            if ($entity instanceof User) {
                $persistedUser = $entity;
            }
        });
        $entityManager->expects($this->once())->method('flush');

        $input = new RegisterInput('new@example.com', 'validPassword123');
        $result = $service->register($input);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertSame('new@example.com', $result['email']);
        $this->assertInstanceOf(User::class, $persistedUser);
        $this->assertSame('new@example.com', $persistedUser->getEmail());
    }

    private function createService(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
    ): UserRegistrationService {
        return new UserRegistrationService($userRepository, $entityManager, $passwordHasher, $validator);
    }
}
