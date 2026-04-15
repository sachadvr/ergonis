<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProcessJobOfferFromExtensionMessage;
use App\Repository\UserRepository;
use App\Service\JobOfferFromExtensionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ProcessJobOfferFromExtensionHandler
{
    public function __construct(
        private JobOfferFromExtensionService $extensionService,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessJobOfferFromExtensionMessage $message): void
    {
        $user = $this->userRepository->find($message->userId);
        if (null === $user) {
            $this->logger->warning('Extension job offer user not found', ['userId' => $message->userId]);

            return;
        }

        $this->logger->info('Processing extension job offer', ['userId' => $message->userId]);
        $this->extensionService->createFromExtension($message->input, $user);
    }
}
