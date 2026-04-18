<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testDefaultsAndIdentity(): void
    {
        $user = (new User())
            ->setEmail('candidate@example.com')
            ->setPassword('secret');

        $this->assertSame('candidate@example.com', $user->getEmail());
        $this->assertSame('candidate@example.com', $user->getUserIdentifier());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
    }

    public function testRolesAndTimestampsUpdate(): void
    {
        $user = new User();
        $user->setEmail('candidate@example.com');
        $user->setPassword('secret');
        $user->setRoles(['ROLE_ADMIN']);

        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertNotNull($user->getUpdatedAt());
    }
}
