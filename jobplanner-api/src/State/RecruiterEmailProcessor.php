<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\RecruiterEmail;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class RecruiterEmailProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof RecruiterEmail) {
            $msgId = $data->getMessageId();
            if ('' === $msgId || null === $msgId) {
                $data->setMessageId('manual-'.uniqid('', true).'-'.bin2hex(random_bytes(8)));
            }

            if (null === $data->getOwner()) {
                $user = $this->security->getUser();
                if ($user instanceof User) {
                    $data->setOwner($user);
                }
            }
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $result;
    }
}
