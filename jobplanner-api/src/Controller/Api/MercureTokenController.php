<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\MercureJwtFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class MercureTokenController
{
    public function __construct(
        private Security $security,
        private MercureJwtFactory $mercureJwtFactory,
        #[Autowire(param: 'mercure_cookie_domain')]
        private ?string $cookieDomain = null,
    ) {
    }

    #[Route('/api/mercure/token', name: 'api_mercure_token', methods: ['POST'])]
    public function __invoke(): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof User || null === $user->getId()) {
            return new Response(null, Response::HTTP_UNAUTHORIZED);
        }

        $topic = 'urn:jobplanner:user:'.$user->getId().':notifications';
        $token = $this->mercureJwtFactory->createToken(subscribeTopics: [$topic]);

        $response = new Response(null, Response::HTTP_NO_CONTENT);
        $response->headers->setCookie(Cookie::create(
            'mercureAuthorization',
            $token,
            path: '/.well-known/mercure',
            domain: '' !== ($this->cookieDomain ?? '') ? $this->cookieDomain : null,
            httpOnly: true,
            sameSite: Cookie::SAMESITE_LAX,
        ));

        return $response;
    }
}
