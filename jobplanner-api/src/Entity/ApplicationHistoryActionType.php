<?php

declare(strict_types=1);

namespace App\Entity;

enum ApplicationHistoryActionType: string
{
    case EMAIL_RECEIVED = 'email_received';
    case STATUS_CHANGED = 'status_changed';
    case CREATED = 'created';
    case IMPORTED_FROM_EXTENSION = 'imported_from_extension';
    case RELANCE_SENT = 'relance_sent';
    case INTERVIEW_SCHEDULED = 'interview_scheduled';
}
