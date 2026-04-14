<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Point d'entrée pour le login JWT.
 * Le JsonLoginAuthenticator intercepte la requête avant d'atteindre ce contrôleur.
 * Ce contrôleur n'est jamais exécuté en pratique.
 */
#[Route('/api/login', name: 'api_login', methods: ['POST'])]
final class LoginController extends AbstractController
{
    public function __invoke(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
