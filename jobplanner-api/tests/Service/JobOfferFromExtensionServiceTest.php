<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\JobOfferFromExtensionInput;
use App\Entity\User;
use App\Service\Ai\AiServiceInterface;
use App\Service\JobOfferFromExtensionService;
use App\Tests\Support\TestEntityHelpers;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class JobOfferFromExtensionServiceTest extends TestCase
{
    use TestEntityHelpers;

    public function testCreateFromExtensionPersistsJobOfferAndApplication(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $aiService = $this->createMock(AiServiceInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $aiService->expects($this->once())
            ->method('extractJobOfferFromContent')
            ->with('https://example.com/job', 'Backend Engineer', 'Full job content')
            ->willReturn([
                'title' => 'Backend Engineer',
                'company' => 'Acme',
                'location' => 'Paris',
                'full_data' => [
                    'job_summary' => 'Build APIs',
                    'recruiter_contact_email' => 'recruiter@example.com ',
                    'salary' => ['min' => '50000', 'max' => 70000, 'currency' => 'EUR'],
                    'contract' => ['type' => 'CDI'],
                    'location' => ['remote_policy' => 'hybrid'],
                ],
            ]);

        $persistedId = 100;
        $entityManager->method('persist')->willReturnCallback(function (object $entity) use (&$persistedId): void {
            if (property_exists($entity, 'id')) {
                $this->setEntityId($entity, $persistedId++);
            }
        });
        $entityManager->expects($this->once())->method('flush');

        $service = new JobOfferFromExtensionService($entityManager, $validator, $aiService, $logger);
        $input = new JobOfferFromExtensionInput('https://example.com/job', 'Backend Engineer', 'Full job content', true);
        $user = (new User())->setEmail('candidate@example.com')->setPassword('secret');

        $result = $service->createFromExtension($input, $user);

        $this->assertSame('Backend Engineer', $result['jobOffer']['title']);
        $this->assertSame('Acme', $result['jobOffer']['company']);
        $this->assertSame(100, $result['jobOffer']['id']);
        $this->assertSame(101, $result['application']['id']);
        $this->assertSame('wishlist', $result['application']['status']);
    }

    public function testCreateFromExtensionThrowsOnValidationErrors(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new \Symfony\Component\Validator\ConstraintViolation('invalid', null, [], null, 'field', null),
        ]));

        $service = new JobOfferFromExtensionService($entityManager, $validator, $this->createStub(AiServiceInterface::class), $this->createStub(LoggerInterface::class));

        $this->expectException(\App\Exception\ValidationFailedException::class);
        $service->createFromExtension(new JobOfferFromExtensionInput('https://example.com', 'Title'), (new User())->setEmail('candidate@example.com')->setPassword('secret'));
    }
}
