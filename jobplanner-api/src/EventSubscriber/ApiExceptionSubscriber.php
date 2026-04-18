<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\UserAlreadyExistsException;
use App\Exception\ValidationFailedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Centralizes the format of the error responses for the API.
 * The controllers can still handle locally, this subscriber ensures consistency.
 */
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    private const API_PATH_PREFIX = '/api';

    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), self::API_PATH_PREFIX)) {
            return;
        }

        $throwable = $event->getThrowable();

        if ($throwable instanceof ValidationFailedException) {
            $event->setResponse(new JsonResponse(
                ['errors' => $throwable->getErrorsAsArray()],
                Response::HTTP_BAD_REQUEST
            ));

            return;
        }

        if ($throwable instanceof UserAlreadyExistsException) {
            $event->setResponse(new JsonResponse(
                ['error' => $throwable->getMessage()],
                Response::HTTP_CONFLICT
            ));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['error' => $throwable->getMessage()],
                $throwable->getStatusCode()
            ));
        }
    }
}
