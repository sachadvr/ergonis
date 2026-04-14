<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Entity\AiGenerationLog;
use App\Entity\Application;
use App\Entity\FollowUpRule;
use App\Entity\JobOffer;
use App\Entity\RecruiterEmail;
use App\Entity\UserMailboxSettings;

/**
 * Liste des entités filtrées par ownership dans OwnershipExtension.
 * Centralise la config pour éviter la duplication.
 */
final class OwnedEntities
{
    /**
     * @var list<class-string>
     */
    private const ENTITY_CLASSES = [
        JobOffer::class,
        Application::class,
        RecruiterEmail::class,
        FollowUpRule::class,
        UserMailboxSettings::class,
        AiGenerationLog::class,
    ];

    /** Entités utilisant "user" au lieu de "owner" */
    private const USER_FIELD_ENTITIES = [
        UserMailboxSettings::class,
        AiGenerationLog::class,
    ];

    /**
     * @return list<class-string>
     */
    public static function all(): array
    {
        return self::ENTITY_CLASSES;
    }

    public static function isOwned(string $resourceClass): bool
    {
        return in_array($resourceClass, self::ENTITY_CLASSES, true);
    }

    public static function getOwnerField(string $resourceClass): string
    {
        return in_array($resourceClass, self::USER_FIELD_ENTITIES, true) ? 'user' : 'owner';
    }
}
