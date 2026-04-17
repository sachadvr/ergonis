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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class UserRegistrationServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;
    private UserRegistrationService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->service = new UserRegistrationService(
            $this->userRepository,
            $this->entityManager,
            $this->passwordHasher,
            $this->validator,
        );
    }

    public function testRegisterThrowsWhenEmailAlreadyExists(): void
    {
        $this->userRepository->expects($this->once())
            ->method('existsByEmail')
            ->with('existing@example.com')
            ->willReturn(true);
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage('existing@example.com');

        $input = new RegisterInput('existing@example.com', 'validPassword123');
        $this->service->register($input);
    }

    public function testRegisterThrowsWhenValidationFails(): void
    {
        $this->userRepository->method('existsByEmail')->willReturn(false);
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Invalid email', null, [], null, 'email', null),
        ]);
        $this->validator->method('validate')->willReturn($violations);

        $this->expectException(ValidationFailedException::class);

        $input = new RegisterInput('invalid', 'short');
        $this->service->register($input);
    }

    public function testRegisterCreatesUserAndReturnsPayload(): void
    {
        $this->userRepository->method('existsByEmail')->willReturn(false);
        $this->passwordHasher->method('hashPassword')
            ->willReturnCallback(static fn (User $user, string $password) => 'hashed_'.$password);

        $persistedUser = null;
        $this->entityManager->method('persist')->willReturnCallback(function ($entity) use (&$persistedUser) {
            if ($entity instanceof User) {
                $persistedUser = $entity;
            }
        });
        $this->entityManager->expects($this->once())->method('flush');

        $input = new RegisterInput('new@example.com', 'validPassword123');
        $result = $this->service->register($input);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertSame('new@example.com', $result['email']);
        $this->assertInstanceOf(User::class, $persistedUser);
        $this->assertSame('new@example.com', $persistedUser->getEmail());
    }
}
