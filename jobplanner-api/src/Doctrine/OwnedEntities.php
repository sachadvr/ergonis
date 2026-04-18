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
 * List of entities filtered by ownership in OwnershipExtension.
 * Centralizes the config to avoid duplication.
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
        return 'owner';
    }
}
