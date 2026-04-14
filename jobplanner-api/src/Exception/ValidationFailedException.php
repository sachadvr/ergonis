<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationFailedException extends HttpException
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violations,
        int $statusCode = Response::HTTP_BAD_REQUEST,
    ) {
        parent::__construct($statusCode, 'Validation failed');
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    /**
     * @return array<string, string>
     */
    public function getErrorsAsArray(): array
    {
        $errors = [];
        foreach ($this->violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $errors;
    }
}
