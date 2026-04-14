<?php

declare(strict_types=1);

namespace App\Entity;

enum ApplicationStatus: string
{
    case WISHLIST = 'wishlist';
    case APPLIED = 'applied';
    case INTERVIEW = 'interview';
    case OFFER = 'offer';
    case REJECTED = 'rejected';
    case ACCEPTED = 'accepted';
}
