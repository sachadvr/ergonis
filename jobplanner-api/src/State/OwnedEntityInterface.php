<?php

declare(strict_types=1);

namespace App\State;

use App\Entity\User;

interface OwnedEntityInterface
{
    public function getOwner(): ?User;

    public function setOwner(?User $owner): static;
}
