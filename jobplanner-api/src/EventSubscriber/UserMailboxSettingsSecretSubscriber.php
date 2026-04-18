<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\UserMailboxSettings;
use App\Security\MailboxSecretEncryptor;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

final class UserMailboxSettingsSecretSubscriber implements EventSubscriber
{
    /**
     * @var array<string, \ReflectionProperty>
     */
    private array $properties = [];

    public function __construct(
        private MailboxSecretEncryptor $encryptor,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->encrypt($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof UserMailboxSettings) {
            return;
        }

        foreach ($this->secretFields() as $field) {
            if (!$args->hasChangedField($field)) {
                continue;
            }

            $encrypted = $this->encryptor->encrypt($args->getNewValue($field));
            $args->setNewValue($field, $encrypted);
            $this->setField($entity, $field, $encrypted);
        }
    }

    private function encrypt(object $entity): void
    {
        if (!$entity instanceof UserMailboxSettings) {
            return;
        }

        foreach ($this->secretFields() as $field) {
            $this->setField($entity, $field, $this->encryptor->encrypt($this->getField($entity, $field)));
        }
    }

    private function secretFields(): array
    {
        return ['imapPassword', 'smtpPassword', 'accessToken', 'refreshToken'];
    }

    private function getField(UserMailboxSettings $entity, string $field): ?string
    {
        return $this->property($field)->getValue($entity);
    }

    private function setField(UserMailboxSettings $entity, string $field, ?string $value): void
    {
        $this->property($field)->setValue($entity, $value);
    }

    private function property(string $field): \ReflectionProperty
    {
        if (!isset($this->properties[$field])) {
            $property = new \ReflectionProperty(UserMailboxSettings::class, $field);
            $property->setAccessible(true);
            $this->properties[$field] = $property;
        }

        return $this->properties[$field];
    }
}
