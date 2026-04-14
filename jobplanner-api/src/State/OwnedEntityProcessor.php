<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Processor générique qui assigne l'utilisateur connecté comme owner des entités OwnedEntityInterface.
 * Évite la duplication de logique entre JobOffer, Application, FollowUpRule.
 */
final class OwnedEntityProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof OwnedEntityInterface) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        if (null !== $data->getOwner()) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $data->setOwner($user);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
