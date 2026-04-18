<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class UserAlreadyExistsException extends ConflictHttpException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('A user with the email "%s" already exists.', $email));
    }
}
