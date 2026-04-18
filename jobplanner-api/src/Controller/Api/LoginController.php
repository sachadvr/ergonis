<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Entry point for the JWT login.
 * The JsonLoginAuthenticator intercepts the request before reaching this controller.
 * This controller is never executed in practice.
 */
#[Route('/api/login', name: 'api_login', methods: ['POST'])]
final class LoginController extends AbstractController
{
    public function __invoke(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
