<?php

declare(strict_types=1);

namespace App\Service\Mail\Client\Abstract;

use App\Entity\User;
use App\Entity\UserMailboxSettings;
use App\Repository\UserMailboxSettingsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AbstractOAuthClient
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UserMailboxSettingsRepository $mailboxSettingsRepository,
        protected readonly UserRepository $userRepository,
        protected readonly JWTTokenManagerInterface $jwtManager,
        protected readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @return array{user: User, token: ?string}
     */
    public function resolveUser(?User $currentUser, string $email): array
    {
        if ($currentUser instanceof User) {
            return ['user' => $currentUser, 'token' => null];
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(20))));

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return [
            'user' => $user,
            'token' => $this->jwtManager->create($user),
        ];
    }

    public function saveMailboxSettings(
        User $user,
        string $email,
        string $accessToken,
        ?string $refreshToken,
        int $expiresIn,
    ): UserMailboxSettings {
        $settings = $this->mailboxSettingsRepository->findByUserId($user->getId()) ?? new UserMailboxSettings();
        $settings->setOwner($user);
        $settings->setOauthProvider($this->getOauthProvider());
        $settings->setAccessToken($accessToken);

        if (null !== $refreshToken && '' !== $refreshToken) {
            $settings->setRefreshToken($refreshToken);
        }

        $settings->setTokenExpiresAt(new \DateTimeImmutable('+'.$expiresIn.' seconds'));
        $settings->setImapHost($this->getImapHost($email));
        $settings->setImapPort($this->getImapPort());
        $settings->setImapEncryption($this->getImapEncryption());
        $settings->setImapUser($email);
        $settings->setImapPassword('oauth2_token');
        $settings->setSmtpHost($this->getSmtpHost($email));
        $settings->setSmtpPort($this->getSmtpPort());
        $settings->setSmtpEncryption($this->getSmtpEncryption());
        $settings->setSmtpUser($email);

        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        return $settings;
    }

    abstract protected function getOauthProvider(): string;

    abstract protected function getImapHost(string $email): string;

    protected function getImapPort(): int
    {
        return 993;
    }

    protected function getImapEncryption(): string
    {
        return 'ssl';
    }

    abstract protected function getSmtpHost(string $email): string;

    protected function getSmtpPort(): int
    {
        return 587;
    }

    protected function getSmtpEncryption(): string
    {
        return 'tls';
    }
}
